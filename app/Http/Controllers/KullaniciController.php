<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Enums\IkiAsamaliGuvenlik;
use App\Enums\KursDurum;
use App\Models\BasvuruDurum;
use App\Models\KursBasvuru;
use App\Models\KursDers;
use App\Models\KursEpostaGonderim;
use App\Models\KursSmsGonderim;
use App\Models\KursYoklama;
use App\Models\Kurum;
use App\Models\Rol;
use App\Models\User;
use App\Models\Merkez;
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

class KullaniciController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$kullanicilar, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'kullanicilar' => $kullanicilar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
            'roller' => Rol::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('kullanicilar._results', $viewData);
        }

        return view('kullanicilar.index', $viewData);
    }

    public function create(): View
    {
        return view('kullanicilar.create', $this->formLookups());
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

        $kullanici = User::query()->create($validated);
        $kullanici->syncRoller($this->resolveRolIds($request));
        $kullanici->syncKurumlar($this->resolveKurumIds($request));

        LogKaydedici::kaydet(
            islem: 'kullanici.olusturuldu',
            aciklama: '"'.$kullanici->tam_adi.'" kullanıcısı oluşturuldu.',
            konu: $kullanici,
            konuAdi: $kullanici->tam_adi,
        );

        $message = "\"{$kullanici->tam_adi}\" kullanıcısı başarıyla oluşturuldu.";
        if ($geciciSifre) {
            $message .= " Geçici şifre: {$geciciSifre}";
        }

        return redirect()->route('kullanicilar.show', $kullanici)->with('success', $message);
    }

    public function edit(User $kullanici): View
    {
        $kullanici->load(['roller', 'kurumlar']);

        return view('kullanicilar.edit', $this->formLookups() + [
            'kullanici' => $kullanici,
        ]);
    }

    public function update(Request $request, User $kullanici): RedirectResponse
    {
        $validated = $this->validated($request, $kullanici);

        if (! isset($validated['password']) || $validated['password'] === null) {
            unset($validated['password']);
        }

        $validated['aktif'] = $request->boolean('aktif');

        $yeniRolIds = $this->resolveRolIds($request);
        $this->assertSonAdminKorunuyor($kullanici, $yeniRolIds);

        $kullanici->update($validated);
        $kullanici->syncRoller($yeniRolIds);
        $kullanici->syncKurumlar($this->resolveKurumIds($request));

        LogKaydedici::kaydet(
            islem: 'kullanici.guncellendi',
            aciklama: '"'.$kullanici->tam_adi.'" kullanıcısı güncellendi.',
            konu: $kullanici,
            konuAdi: $kullanici->tam_adi,
        );

        $message = "\"{$kullanici->tam_adi}\" kullanıcısı başarıyla güncellendi.";

        return redirect()->route('kullanicilar.show', $kullanici)->with('success', $message);
    }

    public function editMerkezYetkileri(Request $request, User $kullanici): View
    {
        $kullanici->load('atananMerkezler');

        return view('kullanicilar.merkez-yetkileri', [
            'kullanici' => $kullanici,
            'merkezler' => Merkez::query()->orderBy('ad')->get(),
            'seciliMerkezIds' => old('merkezler', $kullanici->atananMerkezler->pluck('id')->all()),
            'returnToListe' => $request->input('return') === 'liste',
        ]);
    }

    public function updateMerkezYetkileri(Request $request, User $kullanici): RedirectResponse
    {
        $validated = $request->validate([
            'merkezler' => ['nullable', 'array'],
            'merkezler.*' => ['integer', 'exists:merkezler,id'],
            'return' => ['nullable', 'in:liste'],
        ]);

        $oncekiIds = $kullanici->atananMerkezler()->pluck('merkezler.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $yeniIds = collect($validated['merkezler'] ?? [])->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        $kullanici->syncMerkezler($yeniIds);

        if ($oncekiIds !== $yeniIds) {
            $adlar = Merkez::query()->whereIn('id', $yeniIds)->orderBy('ad')->pluck('ad')->all();

            LogKaydedici::kaydet(
                islem: 'kullanici.merkez_yetkilendirildi',
                aciklama: '"'.$kullanici->tam_adi.'" kullanıcısının merkez yetkileri güncellendi.',
                konu: $kullanici,
                eski: ['merkez_ids' => $oncekiIds],
                yeni: ['merkez_ids' => $yeniIds, 'merkezler' => $adlar],
                konuAdi: $kullanici->tam_adi,
            );
        }

        $message = "\"{$kullanici->tam_adi}\" için merkez yetkilendirmesi kaydedildi.";

        if (($validated['return'] ?? null) === 'liste' || $request->input('return') === 'liste') {
            return redirect()->route('merkez-yetkileri.index')->with('success', $message);
        }

        return redirect()->route('kullanicilar.show', $kullanici)->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$kullanicilar] = $this->search($request, paginate: false);

        $filename = 'kullanicilar-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($kullanicilar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Ad Soyad', 'TC Kimlik No', 'Doğum Tarihi', 'Telefon', 'E-posta', 'Roller', 'Aktif Kurs Sayısı', 'Durum', 'Kayıt Tarihi'], ';');

            $kullanicilar->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $kullanici) {
                    fputcsv($handle, [
                        $kullanici->tam_adi,
                        $kullanici->tc_kimlik_no,
                        $kullanici->dogum_tarihi?->format('d.m.Y') ?? '',
                        $kullanici->telefon,
                        $kullanici->email,
                        $kullanici->roller->pluck('ad')->implode(', '),
                        $kullanici->aktif_kurs_sayisi,
                        $kullanici->aktif ? 'Aktif' : 'Pasif',
                        $kullanici->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(User $kullanici): View
    {
        $kurslar = $kullanici->atananKurslar()
            ->with(['alan', 'brans', 'merkez'])
            ->orderByDesc('kurs_baslama_tarihi')
            ->get();

        $etkinlikler = $kullanici->atananEtkinlikler()
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

        // Yoklamalar: atanan kursların yanı sıra, kullanıcının yoklama aldığı
        // (atanmamış olsa bile) tüm ders oturumlarını da kapsar.
        $yoklamaDersleri = KursDers::query()
            ->whereDate('tarih', '<=', $bugun)
            ->where(function ($q) use ($kursIds, $kullanici) {
                if ($kursIds->isNotEmpty()) {
                    $q->whereIn('kurs_id', $kursIds);
                }
                $q->orWhere('yoklama_alan_id', $kullanici->id);
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

        // Mesajlar: yalnızca bu kullanıcının kendisinin gönderdiği tüm SMS/e-postalar.
        $smsler = KursSmsGonderim::query()
            ->where('gonderen_id', $kullanici->id)
            ->with(['kurs.brans', 'gonderen'])
            ->get()
            ->map(fn (KursSmsGonderim $g) => $this->mesajSatiri($g, 'sms'));

        $epostalar = KursEpostaGonderim::query()
            ->where('gonderen_id', $kullanici->id)
            ->with(['kurs.brans', 'gonderen'])
            ->get()
            ->map(fn (KursEpostaGonderim $g) => $this->mesajSatiri($g, 'eposta'));

        $mesajlar = $smsler->concat($epostalar)
            ->sortByDesc('created_at')
            ->values();

        return view('kullanicilar.show', [
            'kullanici' => $kullanici->loadMissing(['roller', 'kurumlar']),
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
            'telefonVar' => PhoneNormalizer::normalize($kullanici->telefon) !== null,
            'emailVar' => filled($kullanici->email),
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

    public function sendSms(Request $request, User $kullanici, SmsSender $smsSender): JsonResponse
    {
        $validated = $request->validate([
            'mesaj' => ['required', 'string', 'min:1', 'max:480'],
        ], [
            'mesaj.required' => 'SMS metni zorunludur.',
            'mesaj.max' => 'SMS metni en fazla 480 karakter olabilir.',
        ]);

        $telefon = PhoneNormalizer::normalize($kullanici->telefon);
        if (! $telefon) {
            throw ValidationException::withMessages([
                'mesaj' => 'Bu kullanıcı için kayıtlı telefon numarası bulunamadı.',
            ]);
        }

        $mesaj = $this->mesajKisisellestir($validated['mesaj'], $kullanici->tam_adi);
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
            'message' => "SMS \"{$kullanici->tam_adi}\" kişisine gönderildi.",
        ]);
    }

    public function sendEposta(Request $request, User $kullanici, EmailSender $emailSender): JsonResponse
    {
        $validated = $request->validate([
            'konu' => ['required', 'string', 'min:1', 'max:200'],
            'mesaj' => ['required', 'string', 'min:1', 'max:5000'],
        ], [
            'konu.required' => 'E-posta konusu zorunludur.',
            'mesaj.required' => 'E-posta metni zorunludur.',
        ]);

        $email = trim((string) $kullanici->email);
        if ($email === '') {
            throw ValidationException::withMessages([
                'mesaj' => 'Bu kullanıcı için kayıtlı e-posta adresi bulunamadı.',
            ]);
        }

        $konu = $this->mesajKisisellestir($validated['konu'], $kullanici->tam_adi);
        $mesaj = $this->mesajKisisellestir($validated['mesaj'], $kullanici->tam_adi);

        $sonuc = $emailSender->send($email, $konu, $mesaj, [
            'gonderen_id' => $request->user()?->id,
        ]);

        if (! ($sonuc['ok'] ?? false)) {
            return response()->json([
                'message' => $sonuc['message'] ?? 'E-posta gönderilemedi.',
            ], 422);
        }

        return response()->json([
            'message' => "E-posta \"{$kullanici->tam_adi}\" kişisine gönderildi.",
        ]);
    }

    public function sendSifre(Request $request, User $kullanici, SmsSender $smsSender, EmailSender $emailSender): JsonResponse
    {
        $validated = $request->validate([
            'kanal' => ['required', 'string', Rule::in(['sms', 'eposta', 'ikisi'])],
        ], [
            'kanal.required' => 'Gönderim kanalı seçilmelidir.',
            'kanal.in' => 'Geçersiz gönderim kanalı.',
        ]);

        $kanal = $validated['kanal'];
        $telefon = PhoneNormalizer::normalize($kullanici->telefon);
        $email = trim((string) $kullanici->email);

        if (in_array($kanal, ['sms', 'ikisi'], true) && ! $telefon) {
            throw ValidationException::withMessages([
                'kanal' => 'Bu kullanıcı için kayıtlı telefon numarası bulunamadı.',
            ]);
        }

        if (in_array($kanal, ['eposta', 'ikisi'], true) && $email === '') {
            throw ValidationException::withMessages([
                'kanal' => 'Bu kullanıcı için kayıtlı e-posta adresi bulunamadı.',
            ]);
        }

        $sifre = Str::password(10, symbols: false);
        $kullanici->update(['password' => $sifre]);

        $smsMesaj = "Merhaba {$kullanici->tam_adi}, Başvuru 360 giriş şifreniz: {$sifre}";
        $epostaKonu = 'Başvuru 360 Giriş Şifreniz';
        $epostaMesaj = "Merhaba {$kullanici->tam_adi},\n\nBaşvuru 360 sistemine giriş için geçici şifreniz: {$sifre}\n\nGüvenliğiniz için giriş yaptıktan sonra şifrenizi değiştirmenizi öneririz.";

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

    public function updateFoto(Request $request, User $kullanici): JsonResponse
    {
        $validated = $request->validate([
            'profil_foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'profil_foto.required' => 'Bir resim seçmelisiniz.',
            'profil_foto.image' => 'Yüklenen dosya bir resim olmalıdır.',
            'profil_foto.mimes' => 'Profil resmi JPG, PNG veya WEBP olmalıdır.',
            'profil_foto.max' => 'Profil resmi en fazla 2 MB olabilir.',
        ]);

        $dosya = $this->fotoKaydet($validated['profil_foto'], $kullanici->profil_foto);
        $kullanici->update(['profil_foto' => $dosya]);

        LogKaydedici::kaydet(
            islem: 'kullanici.guncellendi',
            aciklama: '"'.$kullanici->tam_adi.'" kullanıcısının profil fotoğrafı güncellendi.',
            konu: $kullanici,
            konuAdi: $kullanici->tam_adi,
        );

        return response()->json([
            'message' => 'Profil fotoğrafı güncellendi.',
            'url' => $kullanici->profil_foto_url,
        ]);
    }

    public function deleteFoto(User $kullanici): JsonResponse
    {
        if (! $kullanici->profil_foto) {
            return response()->json([
                'message' => 'Kaldırılacak bir profil fotoğrafı bulunamadı.',
            ], 422);
        }

        $this->fotoSil($kullanici->profil_foto);
        $kullanici->update(['profil_foto' => null]);

        LogKaydedici::kaydet(
            islem: 'kullanici.guncellendi',
            aciklama: '"'.$kullanici->tam_adi.'" kullanıcısının profil fotoğrafı kaldırıldı.',
            konu: $kullanici,
            konuAdi: $kullanici->tam_adi,
        );

        return response()->json([
            'message' => 'Profil fotoğrafı kaldırıldı.',
        ]);
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
            ->with('roller')
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

        $rol = (string) $request->input('rol', 'tumu');
        if ($rol !== 'tumu' && $rol !== '') {
            $query->whereHas('roller', fn (Builder $q) => $q->where('roller.kod', $rol));
        } else {
            $rol = 'tumu';
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

        $filters = $request->only(['ad_soyad', 'ad_soyad_mode', 'tc_kimlik_no', 'telefon', 'email', 'rol', 'durum', 'per_page', 'sort', 'direction']);
        $filters['durum'] = $durum;
        $filters['rol'] = $rol;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $kullanici = null): array
    {
        $validated = $request->validate([
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'roller' => ['required', 'array', 'min:1'],
            'roller.*' => ['integer', 'exists:roller,id'],
            'kurumlar' => ['nullable', 'array'],
            'kurumlar.*' => ['integer', 'exists:kurumlar,id'],
            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($kullanici?->id),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'telefon' => ['nullable', 'string', 'max:20'],
            'tc_kimlik_no' => [
                'nullable', 'digits:11',
                Rule::unique('users', 'tc_kimlik_no')->ignore($kullanici?->id),
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
            'iki_asamali_guvenlik' => ['required', Rule::enum(IkiAsamaliGuvenlik::class)],
        ], [
            'ad.required' => 'Ad alanı zorunludur.',
            'soyad.required' => 'Soyad alanı zorunludur.',
            'roller.required' => 'En az bir rol seçilmelidir.',
            'roller.min' => 'En az bir rol seçilmelidir.',
            'email.required' => 'E-posta alanı zorunludur.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'tc_kimlik_no.digits' => 'TC Kimlik No 11 haneli olmalıdır.',
            'tc_kimlik_no.unique' => 'Bu TC Kimlik No zaten kayıtlı.',
            'password.min' => 'Şifre en az 6 karakter olmalıdır.',
            'iki_asamali_guvenlik.required' => 'İki aşamalı güvenlik seçilmelidir.',
            'iki_asamali_guvenlik.enum' => 'Geçersiz iki aşamalı güvenlik seçeneği.',
        ]);

        unset($validated['roller'], $validated['kurumlar']);

        if (($validated['tc_kimlik_no'] ?? null) === '') {
            $validated['tc_kimlik_no'] = null;
        }

        if (($validated['password'] ?? null) === '') {
            $validated['password'] = null;
        }

        $ikiAsamali = IkiAsamaliGuvenlik::tryFrom((string) ($validated['iki_asamali_guvenlik'] ?? 'hayir'))
            ?? IkiAsamaliGuvenlik::Hayir;
        $validated['iki_asamali_guvenlik'] = $ikiAsamali->value;

        if ($ikiAsamali === IkiAsamaliGuvenlik::Sms) {
            $telefon = trim((string) ($validated['telefon'] ?? ''));
            if ($telefon === '') {
                throw ValidationException::withMessages([
                    'telefon' => 'SMS ile iki aşamalı güvenlik için telefon numarası zorunludur.',
                ]);
            }
        }

        return $validated;
    }

    /**
     * @return list<int>
     */
    private function resolveRolIds(Request $request): array
    {
        return array_values(array_unique(array_map('intval', (array) $request->input('roller', []))));
    }

    /**
     * @return list<int>
     */
    private function resolveKurumIds(Request $request): array
    {
        return array_values(array_unique(array_map('intval', (array) $request->input('kurumlar', []))));
    }

    /**
     * @return array{roller: \Illuminate\Support\Collection, kurumlar: \Illuminate\Support\Collection}
     */
    private function formLookups(): array
    {
        return [
            'roller' => Rol::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
            'kurumlar' => Kurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
        ];
    }

    /**
     * Sistemde en az bir Admin kalmasını garanti eder.
     *
     * @param  list<int>  $yeniRolIds
     */
    private function assertSonAdminKorunuyor(User $kullanici, array $yeniRolIds): void
    {
        if (! $kullanici->isAdmin()) {
            return;
        }

        $adminRolId = Rol::query()->where('kod', 'admin')->value('id');
        if (! $adminRolId) {
            return;
        }

        if (in_array((int) $adminRolId, $yeniRolIds, true)) {
            return;
        }

        $digerAdminSayisi = User::query()
            ->whereKeyNot($kullanici->id)
            ->whereHas('roller', fn (Builder $q) => $q->where('roller.kod', 'admin')->where('aktif', true))
            ->count();

        if ($digerAdminSayisi === 0) {
            throw ValidationException::withMessages([
                'roller' => 'Sistemde en az bir Admin kullanıcı kalmalıdır. Bu kullanıcının Admin rolünü kaldıramazsınız.',
            ]);
        }
    }
}
