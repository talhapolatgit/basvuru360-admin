<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Enums\KursDurum;
use App\Models\BasvuruDurum;
use App\Models\KursBasvuru;
use App\Models\KursDers;
use App\Models\KursEpostaGonderim;
use App\Models\KursSmsGonderim;
use App\Models\KursYoklama;
use App\Models\Rol;
use App\Models\User;
use App\Services\Email\EmailSender;
use App\Services\LogKaydedici;
use App\Services\Sms\PhoneNormalizer;
use App\Services\Sms\SmsSender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EgitmenController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$egitmenler, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'egitmenler' => $egitmenler,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('egitmenler._results', $viewData);
        }

        return view('egitmenler.index', $viewData);
    }

    public function create(): View
    {
        return view('egitmenler.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $geciciSifre = null;
        if (! isset($validated['password']) || $validated['password'] === null) {
            $geciciSifre = Str::random(10);
            $validated['password'] = $geciciSifre;
        }

        $validated['aktif'] = $request->boolean('aktif');
        unset($validated['rol']);

        $egitmen = User::query()->create($validated);

        $ogretmenRolId = Rol::query()->where('kod', 'ogretmen')->value('id');
        if ($ogretmenRolId) {
            $egitmen->syncRoller([(int) $ogretmenRolId]);
        }

        $message = "\"{$egitmen->tam_adi}\" eğitmeni başarıyla oluşturuldu.";
        if ($geciciSifre) {
            $message .= " Geçici şifre: {$geciciSifre}";
        }

        return redirect()->route('egitmenler.show', $egitmen)->with('success', $message);
    }

    public function edit(User $egitmen): View
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        return view('egitmenler.edit', ['egitmen' => $egitmen]);
    }

    public function update(Request $request, User $egitmen): RedirectResponse
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        $validated = $this->validated($request, $egitmen);

        if (! isset($validated['password']) || $validated['password'] === null) {
            unset($validated['password']);
        }

        $validated['aktif'] = $request->boolean('aktif');

        $egitmen->update($validated);

        $message = "\"{$egitmen->tam_adi}\" eğitmeni başarıyla güncellendi.";

        return redirect()->route('egitmenler.show', $egitmen)->with('success', $message);
    }

    public function updateFoto(Request $request, User $egitmen): JsonResponse
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        $validated = $request->validate([
            'profil_foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'profil_foto.required' => 'Bir resim seçmelisiniz.',
            'profil_foto.image' => 'Yüklenen dosya bir resim olmalıdır.',
            'profil_foto.mimes' => 'Profil resmi JPG, PNG veya WEBP olmalıdır.',
            'profil_foto.max' => 'Profil resmi en fazla 2 MB olabilir.',
        ]);

        $dosya = $this->fotoKaydet($validated['profil_foto'], $egitmen->profil_foto);
        $egitmen->update(['profil_foto' => $dosya]);

        LogKaydedici::kaydet(
            islem: 'kullanici.guncellendi',
            aciklama: '"'.$egitmen->tam_adi.'" eğitmeninin profil fotoğrafı güncellendi.',
            konu: $egitmen,
            konuAdi: $egitmen->tam_adi,
        );

        return response()->json([
            'message' => 'Profil fotoğrafı güncellendi.',
            'url' => $egitmen->profil_foto_url,
        ]);
    }

    public function deleteFoto(User $egitmen): JsonResponse
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        if (! $egitmen->profil_foto) {
            return response()->json([
                'message' => 'Kaldırılacak bir profil fotoğrafı bulunamadı.',
            ], 422);
        }

        $this->fotoSil($egitmen->profil_foto);
        $egitmen->update(['profil_foto' => null]);

        LogKaydedici::kaydet(
            islem: 'kullanici.guncellendi',
            aciklama: '"'.$egitmen->tam_adi.'" eğitmeninin profil fotoğrafı kaldırıldı.',
            konu: $egitmen,
            konuAdi: $egitmen->tam_adi,
        );

        return response()->json([
            'message' => 'Profil fotoğrafı kaldırıldı.',
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$egitmenler] = $this->search($request, paginate: false);

        $filename = 'egitmenler-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($egitmenler) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Ad Soyad', 'TC Kimlik No', 'Doğum Tarihi', 'Telefon', 'E-posta', 'Aktif Kurs Sayısı', 'Durum', 'Kayıt Tarihi'], ';');

            $egitmenler->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $egitmen) {
                    fputcsv($handle, [
                        $egitmen->tam_adi,
                        $egitmen->tc_kimlik_no,
                        $egitmen->dogum_tarihi?->format('d.m.Y') ?? '',
                        $egitmen->telefon,
                        $egitmen->email,
                        $egitmen->aktif_kurs_sayisi,
                        $egitmen->aktif ? 'Aktif' : 'Pasif',
                        $egitmen->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(User $egitmen): View
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        $kurslar = $egitmen->atananKurslar()
            ->with(['alan', 'brans', 'merkez'])
            ->orderByDesc('kurs_baslama_tarihi')
            ->get();

        $etkinlikler = $egitmen->atananEtkinlikler()
            ->with(['merkez', 'etkinlikTipi'])
            ->orderByDesc('baslangic_tarihi')
            ->get();

        $aktifKursIds = $kurslar
            ->where('durum', KursDurum::Aktif)
            ->pluck('id');

        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        $aktifOgrenciSayisi = ($kesinKayitId && $aktifKursIds->isNotEmpty())
            ? KursBasvuru::query()
                ->whereIn('kurs_id', $aktifKursIds)
                ->where('durum_id', $kesinKayitId)
                ->count()
            : 0;

        $kursIds = $kurslar->pluck('id');
        $bugun = now()->toDateString();

        $dersler = collect();

        if ($kursIds->isNotEmpty()) {
            $dersler = KursDers::query()
                ->whereIn('kurs_id', $kursIds)
                ->with(['kurs.brans'])
                ->orderByDesc('tarih')
                ->orderByDesc('baslangic_saati')
                ->get();
        }

        // Yoklamalar: atanan kursların yanı sıra, eğitmenin yoklama aldığı
        // (atanmamış olsa bile) tüm ders oturumlarını da kapsar.
        $yoklamaDersleri = KursDers::query()
            ->whereDate('tarih', '<=', $bugun)
            ->where(function ($q) use ($kursIds, $egitmen) {
                if ($kursIds->isNotEmpty()) {
                    $q->whereIn('kurs_id', $kursIds);
                }
                $q->orWhere('yoklama_alan_id', $egitmen->id);
            })
            ->with([
                'kurs.brans',
                'yoklamaAlan',
                'yoklamalar' => fn ($q) => $this->scopeYoklamaAktifBasvuru($q),
            ])
            ->orderByDesc('tarih')
            ->orderByDesc('baslangic_saati')
            ->get()
            ->each(function (KursDers $ders) {
                $ders->setAttribute('yoklamalar_count', $ders->yoklamalar->count());
                $ders->setAttribute('var_sayisi', $ders->yoklamalar
                    ->filter(fn (KursYoklama $y) => $y->herhangiBirSaatteVarMi())
                    ->count());
            });

        // Mesajlar: yalnızca bu eğitmenin kendisinin gönderdiği tüm SMS/e-postalar.
        $smsler = KursSmsGonderim::query()
            ->where('gonderen_id', $egitmen->id)
            ->with(['kurs.brans', 'gonderen'])
            ->get()
            ->map(fn (KursSmsGonderim $g) => $this->mesajSatiri($g, 'sms'));

        $epostalar = KursEpostaGonderim::query()
            ->where('gonderen_id', $egitmen->id)
            ->with(['kurs.brans', 'gonderen'])
            ->get()
            ->map(fn (KursEpostaGonderim $g) => $this->mesajSatiri($g, 'eposta'));

        $mesajlar = $smsler->concat($epostalar)
            ->sortByDesc('created_at')
            ->values();

        return view('egitmenler.show', [
            'egitmen' => $egitmen,
            'kurslar' => $kurslar,
            'etkinlikler' => $etkinlikler,
            'dersler' => $dersler,
            'yoklamaDersleri' => $yoklamaDersleri,
            'mesajlar' => $mesajlar,
            'bugun' => $bugun,
            'aktifKursSayisi' => $aktifKursIds->count(),
            'toplamKursSayisi' => $kurslar->count(),
            'tamamlananKursSayisi' => $kurslar->where('durum', KursDurum::Tamamlanan)->count(),
            'aktifOgrenciSayisi' => $aktifOgrenciSayisi,
            'telefonVar' => PhoneNormalizer::normalize($egitmen->telefon) !== null,
            'emailVar' => filled($egitmen->email),
        ]);
    }

    /**
     * Mesaj geçmişi satırını normalize eder.
     *
     * @param  KursSmsGonderim|KursEpostaGonderim  $gonderim
     * @return array<string, mixed>
     */
    private function mesajSatiri($gonderim, string $kanal): array
    {
        $kurs = $gonderim->kurs;

        return [
            'kanal' => $kanal,
            'kurs_id' => $gonderim->kurs_id,
            'kurs_no' => $kurs?->kurs_no,
            'kurs_adi' => $kurs?->brans?->ad ?? ('Kurs #'.$kurs?->kurs_no),
            'created_at' => $gonderim->created_at,
            'gonderen' => $gonderim->gonderen?->tam_adi,
            'konu' => $gonderim->konu ?? null,
            'mesaj' => $gonderim->mesaj,
            'toplam' => (int) $gonderim->toplam,
            'gonderilen' => (int) $gonderim->gonderilen,
            'atlanan' => (int) $gonderim->atlanan,
        ];
    }

    /**
     * Ders tarihinde kursa başlamış (aktif) başvurulara ait yoklamaları kapsar.
     */
    private function scopeYoklamaAktifBasvuru($query)
    {
        return $query->whereHas('basvuru', function ($basvuruQuery) {
            $basvuruQuery->where(function ($q) {
                $q->whereNull('kurs_basvurulari.kursa_baslama_tarihi')
                    ->orWhereRaw(
                        'kurs_basvurulari.kursa_baslama_tarihi <= (select kd.tarih from kurs_dersleri kd where kd.id = kurs_yoklamalari.kurs_ders_id)'
                    );
            });
        });
    }

    public function exportKurslar(User $egitmen): StreamedResponse
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        $kurslar = $egitmen->atananKurslar()
            ->with(['alan', 'brans', 'merkez'])
            ->orderByDesc('kurs_baslama_tarihi')
            ->get();

        $filename = 'egitmen-'.$egitmen->id.'-kurslar-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($kurslar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Kurs No', 'Alan', 'Branş', 'Merkez', 'Başlama', 'Bitiş', 'Kontenjan', 'Kayıt', 'Durum'], ';');

            foreach ($kurslar as $kurs) {
                fputcsv($handle, [
                    $kurs->kurs_no,
                    $kurs->alan?->ad ?? '',
                    $kurs->brans?->ad ?? '',
                    $kurs->merkez?->ad ?? '',
                    $kurs->kurs_baslama_tarihi?->format('d.m.Y') ?? '',
                    $kurs->kurs_bitis_tarihi?->format('d.m.Y') ?? '',
                    $kurs->kontenjan,
                    $kurs->kayit_sayisi,
                    $kurs->durum?->label() ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function sendSms(Request $request, User $egitmen, SmsSender $smsSender): JsonResponse
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        $validated = $request->validate([
            'mesaj' => ['required', 'string', 'min:1', 'max:480'],
        ], [
            'mesaj.required' => 'SMS metni zorunludur.',
            'mesaj.max' => 'SMS metni en fazla 480 karakter olabilir.',
        ]);

        $telefon = PhoneNormalizer::normalize($egitmen->telefon);
        if (! $telefon) {
            throw ValidationException::withMessages([
                'mesaj' => 'Bu eğitmen için kayıtlı telefon numarası bulunamadı.',
            ]);
        }

        $mesaj = $this->mesajKisisellestir($validated['mesaj'], $egitmen->tam_adi);
        if (mb_strlen($mesaj) > 480) {
            throw ValidationException::withMessages([
                'mesaj' => 'Kişiselleştirilmiş mesaj 480 karakteri aşıyor.',
            ]);
        }

        $sonuc = $smsSender->send($telefon, $mesaj, [
            'gonderen_id' => $request->user()?->id,
        ]);

        if (! ($sonuc['ok'] ?? false)) {
            return response()->json([
                'message' => $sonuc['message'] ?? 'SMS gönderilemedi.',
            ], 422);
        }

        return response()->json([
            'message' => "SMS \"{$egitmen->tam_adi}\" kişisine gönderildi.",
        ]);
    }

    public function sendEposta(Request $request, User $egitmen, EmailSender $emailSender): JsonResponse
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        $validated = $request->validate([
            'konu' => ['required', 'string', 'min:1', 'max:200'],
            'mesaj' => ['required', 'string', 'min:1', 'max:5000'],
        ], [
            'konu.required' => 'E-posta konusu zorunludur.',
            'mesaj.required' => 'E-posta metni zorunludur.',
        ]);

        $email = trim((string) $egitmen->email);
        if ($email === '') {
            throw ValidationException::withMessages([
                'mesaj' => 'Bu eğitmen için kayıtlı e-posta adresi bulunamadı.',
            ]);
        }

        $konu = $this->mesajKisisellestir($validated['konu'], $egitmen->tam_adi);
        $mesaj = $this->mesajKisisellestir($validated['mesaj'], $egitmen->tam_adi);

        $sonuc = $emailSender->send($email, $konu, $mesaj, [
            'gonderen_id' => $request->user()?->id,
        ]);

        if (! ($sonuc['ok'] ?? false)) {
            return response()->json([
                'message' => $sonuc['message'] ?? 'E-posta gönderilemedi.',
            ], 422);
        }

        return response()->json([
            'message' => "E-posta \"{$egitmen->tam_adi}\" kişisine gönderildi.",
        ]);
    }

    public function sendSifre(Request $request, User $egitmen, SmsSender $smsSender, EmailSender $emailSender): JsonResponse
    {
        abort_unless($this->isEgitmen($egitmen), 404);

        $validated = $request->validate([
            'kanal' => ['required', 'string', Rule::in(['sms', 'eposta', 'ikisi'])],
        ], [
            'kanal.required' => 'Gönderim kanalı seçilmelidir.',
            'kanal.in' => 'Geçersiz gönderim kanalı.',
        ]);

        $kanal = $validated['kanal'];
        $telefon = PhoneNormalizer::normalize($egitmen->telefon);
        $email = trim((string) $egitmen->email);

        if (in_array($kanal, ['sms', 'ikisi'], true) && ! $telefon) {
            throw ValidationException::withMessages([
                'kanal' => 'Bu eğitmen için kayıtlı telefon numarası bulunamadı.',
            ]);
        }

        if (in_array($kanal, ['eposta', 'ikisi'], true) && $email === '') {
            throw ValidationException::withMessages([
                'kanal' => 'Bu eğitmen için kayıtlı e-posta adresi bulunamadı.',
            ]);
        }

        $sifre = Str::password(10, symbols: false);
        $egitmen->update(['password' => $sifre]);

        $smsMesaj = "Merhaba {$egitmen->tam_adi}, Başvuru 360 giriş şifreniz: {$sifre}";
        $epostaKonu = 'Başvuru 360 Giriş Şifreniz';
        $epostaMesaj = "Merhaba {$egitmen->tam_adi},\n\nBaşvuru 360 sistemine giriş için geçici şifreniz: {$sifre}\n\nGüvenliğiniz için giriş yaptıktan sonra şifrenizi değiştirmenizi öneririz.";

        $hatalar = [];
        $gonderilen = [];

        if (in_array($kanal, ['sms', 'ikisi'], true)) {
            $sonuc = $smsSender->send($telefon, $smsMesaj, [
                'gonderen_id' => $request->user()?->id,
            ]);
            if ($sonuc['ok'] ?? false) {
                $gonderilen[] = 'SMS';
            } else {
                $hatalar[] = 'SMS: '.($sonuc['message'] ?? 'gönderilemedi');
            }
        }

        if (in_array($kanal, ['eposta', 'ikisi'], true)) {
            $sonuc = $emailSender->send($email, $epostaKonu, $epostaMesaj, [
                'gonderen_id' => $request->user()?->id,
            ]);
            if ($sonuc['ok'] ?? false) {
                $gonderilen[] = 'e-posta';
            } else {
                $hatalar[] = 'E-posta: '.($sonuc['message'] ?? 'gönderilemedi');
            }
        }

        if ($gonderilen === []) {
            return response()->json([
                'message' => 'Şifre güncellendi ancak gönderilemedi. '.implode(' · ', $hatalar),
            ], 422);
        }

        $message = 'Yeni şifre oluşturuldu ve '.implode(' ile ', $gonderilen).' olarak gönderildi.';
        if ($hatalar !== []) {
            $message .= ' Uyarı: '.implode(' · ', $hatalar);
        }

        return response()->json(['message' => $message]);
    }

    private function mesajKisisellestir(string $sablon, string $adSoyad): string
    {
        return str_replace('{ad_soyad}', $adSoyad, $sablon);
    }

    /**
     * Kullanıcı, rolü öğretmen olduğu veya en az bir kursa öğretmen olarak
     * atandığı için Eğitmenler sayfasında yer almalı mı?
     */
    private function isEgitmen(User $egitmen): bool
    {
        return $egitmen->isOgretmen() || $egitmen->atananKurslar()->exists();
    }

    private function fotoKaydet(UploadedFile $foto, ?string $eski): string
    {
        $klasor = public_path('uploads/avatars');
        if (! is_dir($klasor)) {
            @mkdir($klasor, 0755, true);
        }

        $ad = Str::uuid()->toString().'.'.strtolower($foto->getClientOriginalExtension() ?: 'jpg');
        $foto->move($klasor, $ad);

        $this->fotoSil($eski);

        return $ad;
    }

    private function fotoSil(?string $dosya): void
    {
        if (! $dosya) {
            return;
        }

        $yol = public_path('uploads/avatars/'.$dosya);
        if (is_file($yol)) {
            @unlink($yol);
        }
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function search(Request $request, bool $paginate = true): array
    {
        $query = User::query()
            ->egitmen()
            ->withCount([
                'atananKurslar as aktif_kurs_sayisi' => fn (Builder $q) => $q->where('durum', KursDurum::Aktif),
            ]);

        if ($request->filled('ad_soyad')) {
            $adSoyad = trim((string) $request->string('ad_soyad'));
            if ($adSoyad !== '') {
                $mode = (string) $request->input('ad_soyad_mode', 'contains');
                [$operator, $pattern] = match ($mode) {
                    'starts' => ['like', $adSoyad.'%'],
                    'ends' => ['like', '%'.$adSoyad],
                    'exact' => ['=', $adSoyad],
                    default => ['like', '%'.$adSoyad.'%'],
                };

                $query->where(function (Builder $query) use ($operator, $pattern) {
                    $query->where('ad', $operator, $pattern)
                        ->orWhere('soyad', $operator, $pattern);

                    if ($operator === '=') {
                        $query->orWhereRaw("CONCAT(ad, ' ', soyad) = ?", [$pattern]);
                    } else {
                        $query->orWhereRaw("CONCAT(ad, ' ', soyad) like ?", [$pattern]);
                    }
                });
            }
        }

        if ($request->filled('tc_kimlik_no')) {
            $tc = trim((string) $request->string('tc_kimlik_no'));
            if ($tc !== '') {
                $query->where('tc_kimlik_no', 'like', "%{$tc}%");
            }
        }

        if ($request->filled('telefon')) {
            $telefon = trim((string) $request->string('telefon'));
            if ($telefon !== '') {
                $query->where('telefon', 'like', "%{$telefon}%");
            }
        }

        if ($request->filled('email')) {
            $email = trim((string) $request->string('email'));
            if ($email !== '') {
                $query->where('email', 'like', "%{$email}%");
            }
        }

        $durum = (string) $request->input('durum', 'tumu');
        if ($durum === 'aktif') {
            $query->where('aktif', true);
        } elseif ($durum === 'pasif') {
            $query->where('aktif', false);
        } else {
            $durum = 'tumu';
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'ad' => ['ad', 'soyad'],
            'tc_kimlik_no' => ['tc_kimlik_no'],
            'dogum_tarihi' => ['dogum_tarihi'],
            'telefon' => ['telefon'],
            'aktif_kurs_sayisi' => ['aktif_kurs_sayisi'],
            'olusturma' => ['created_at'],
        ];

        if (isset($sortable[$sort])) {
            foreach ($sortable[$sort] as $column) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->orderBy('ad', 'asc')->orderBy('soyad', 'asc');
            $sort = '';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only(['ad_soyad', 'ad_soyad_mode', 'tc_kimlik_no', 'telefon', 'email', 'durum', 'per_page', 'sort', 'direction']);
        $filters['durum'] = $durum;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $egitmen = null): array
    {
        $validated = $request->validate([
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($egitmen?->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'telefon' => ['nullable', 'string', 'max:20'],
            'tc_kimlik_no' => [
                'nullable', 'digits:11',
                Rule::unique('users', 'tc_kimlik_no')->ignore($egitmen?->id),
            ],
            'dogum_tarihi' => ['nullable', 'date'],
            'cinsiyet' => ['nullable', Rule::enum(Cinsiyet::class)],
            'dogum_yeri' => ['nullable', 'string', 'max:100'],
            'medeni_durum' => ['nullable', 'string', 'max:50'],
            'uyruk' => ['nullable', 'string', 'max:100'],
            'anne_adi' => ['nullable', 'string', 'max:100'],
            'baba_adi' => ['nullable', 'string', 'max:100'],
            'il' => ['nullable', 'string', 'max:100'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'adres' => ['nullable', 'string', 'max:500'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Ad alanı zorunludur.',
            'soyad.required' => 'Soyad alanı zorunludur.',
            'email.required' => 'E-posta alanı zorunludur.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'tc_kimlik_no.digits' => 'TC Kimlik No 11 haneli olmalıdır.',
            'tc_kimlik_no.unique' => 'Bu TC Kimlik No zaten kayıtlı.',
            'password.min' => 'Şifre en az 6 karakter olmalıdır.',
        ]);

        if (($validated['tc_kimlik_no'] ?? null) === '') {
            $validated['tc_kimlik_no'] = null;
        }

        if (($validated['password'] ?? null) === '') {
            $validated['password'] = null;
        }

        return $validated;
    }
}
