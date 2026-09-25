<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Enums\KursDurum;
use App\Models\BasvuruDurum;
use App\Models\EpostaLog;
use App\Models\Kisi;
use App\Models\KisiYakin;
use App\Models\KursBasvuru;
use App\Models\KursYoklama;
use App\Models\SmsLog;
use App\Models\YakinlikDerecesi;
use App\Services\Adres\AdresSorgulama;
use App\Services\Email\EmailSender;
use App\Services\Kimlik\KimlikSorgulama;
use App\Services\LogKaydedici;
use App\Services\Sms\PhoneNormalizer;
use App\Services\Sms\SmsSender;
use RuntimeException;
use Throwable;
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

class KisiController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$kisiler, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'kisiler' => $kisiler,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('kisiler._results', $viewData);
        }

        return view('kisiler.index', $viewData);
    }

    public function create(): View
    {
        return view('kisiler.create');
    }

    public function kimlikSorgula(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tc_kimlik_no' => ['required', 'digits:11'],
            'dogum_tarihi' => ['required', 'date'],
        ], [
            'tc_kimlik_no.required' => 'TC Kimlik No zorunludur.',
            'tc_kimlik_no.digits' => 'TC Kimlik No 11 haneli olmalıdır.',
            'dogum_tarihi.required' => 'Doğum tarihi zorunludur.',
            'dogum_tarihi.date' => 'Geçerli bir doğum tarihi girin.',
        ]);

        try {
            $kimlikSorgulama = app(KimlikSorgulama::class);
            $sonuc = $kimlikSorgulama->sorgula(
                $validated['tc_kimlik_no'],
                $validated['dogum_tarihi'],
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Kimlik sorgulama sırasında bir hata oluştu.',
            ], 500);
        }

        if (! ($sonuc['ok'] ?? false)) {
            return response()->json([
                'message' => $sonuc['message'] ?? 'Kimlik bilgileri alınamadı.',
            ], 422);
        }

        $cinsiyet = $sonuc['cinsiyet'] ?? null;
        if (is_string($cinsiyet)) {
            $cinsiyet = Cinsiyet::tryFrom(mb_strtolower($cinsiyet))?->value
                ?? match (mb_strtolower($cinsiyet)) {
                    'e', 'erkek', 'male', 'm' => Cinsiyet::Erkek->value,
                    'k', 'kadin', 'kadın', 'female', 'f' => Cinsiyet::Kadin->value,
                    default => null,
                };
        }

        return response()->json([
            'message' => 'Kimlik bilgileri getirildi.',
            'kisi' => [
                'ad' => $sonuc['ad'] ?? null,
                'soyad' => $sonuc['soyad'] ?? null,
                'cinsiyet' => $cinsiyet,
                'dogum_yeri' => $sonuc['dogum_yeri'] ?? null,
                'medeni_durum' => $sonuc['medeni_durum'] ?? null,
                'uyruk' => $sonuc['uyruk'] ?? null,
                'anne_adi' => $sonuc['anne_adi'] ?? null,
                'baba_adi' => $sonuc['baba_adi'] ?? null,
                'dogum_tarihi' => $sonuc['dogum_tarihi'] ?? $validated['dogum_tarihi'],
            ],
        ]);
    }

    public function adresSorgula(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tc_kimlik_no' => ['required', 'digits:11'],
            'dogum_tarihi' => ['required', 'date'],
        ], [
            'tc_kimlik_no.required' => 'TC Kimlik No zorunludur.',
            'tc_kimlik_no.digits' => 'TC Kimlik No 11 haneli olmalıdır.',
            'dogum_tarihi.required' => 'Doğum tarihi zorunludur.',
            'dogum_tarihi.date' => 'Geçerli bir doğum tarihi girin.',
        ]);

        try {
            $adresSorgulama = app(AdresSorgulama::class);
            $sonuc = $adresSorgulama->sorgula(
                $validated['tc_kimlik_no'],
                $validated['dogum_tarihi'],
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Adres sorgulama sırasında bir hata oluştu.',
            ], 500);
        }

        if (! ($sonuc['ok'] ?? false)) {
            return response()->json([
                'message' => $sonuc['message'] ?? 'Adres bilgileri alınamadı.',
            ], 422);
        }

        return response()->json([
            'message' => 'Adres bilgileri getirildi.',
            'adres' => [
                'il' => $sonuc['il'] ?? null,
                'ilce' => $sonuc['ilce'] ?? null,
                'adres' => $sonuc['adres'] ?? null,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['aktif'] = $request->boolean('aktif');

        $kisi = Kisi::query()->create($validated);

        LogKaydedici::kaydet(
            islem: 'kisi.olusturuldu',
            aciklama: '"'.$kisi->tam_adi.'" kişisi oluşturuldu.',
            konu: $kisi,
            yeni: $this->kisiSnapshot($kisi),
            konuAdi: $kisi->tam_adi,
        );

        return redirect()->route('kisiler.show', $kisi)
            ->with('success', "\"{$kisi->tam_adi}\" kişisi başarıyla oluşturuldu.");
    }

    public function edit(Kisi $kisi): View
    {
        return view('kisiler.edit', ['kisi' => $kisi]);
    }

    public function update(Request $request, Kisi $kisi): RedirectResponse
    {
        $onceki = $this->kisiSnapshot($kisi);

        $validated = $this->validated($request, $kisi);
        $validated['aktif'] = $request->boolean('aktif');

        $kisi->update($validated);

        $yeni = $this->kisiSnapshot($kisi);
        if ($onceki !== $yeni) {
            LogKaydedici::kaydet(
                islem: 'kisi.guncellendi',
                aciklama: '"'.$kisi->tam_adi.'" kişisi güncellendi.',
                konu: $kisi,
                eski: $onceki,
                yeni: $yeni,
                konuAdi: $kisi->tam_adi,
            );
        }

        return redirect()->route('kisiler.show', $kisi)
            ->with('success', "\"{$kisi->tam_adi}\" kişisi başarıyla güncellendi.");
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$kisiler] = $this->search($request, paginate: false);

        $filename = 'kisiler-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($kisiler) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Ad Soyad', 'TC Kimlik No', 'Doğum Tarihi', 'Cinsiyet', 'Telefon', 'E-posta', 'İl', 'İlçe', 'Başvuru Sayısı', 'Durum', 'Kayıt Tarihi'], ';');

            $kisiler->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $kisi) {
                    fputcsv($handle, [
                        $kisi->tam_adi,
                        $kisi->tc_kimlik_no,
                        $kisi->dogum_tarihi?->format('d.m.Y') ?? '',
                        $kisi->cinsiyet?->label() ?? '',
                        $kisi->telefon,
                        $kisi->email,
                        $kisi->il,
                        $kisi->ilce,
                        $kisi->basvuru_sayisi,
                        $kisi->aktif ? 'Aktif' : 'Pasif',
                        $kisi->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Kisi $kisi): View
    {
        $user = request()->user();

        $basvurular = $kisi->kursBasvurulari()
            ->with(['kurs.brans', 'kurs.alan', 'kurs.merkez', 'durum', 'basariDurum'])
            ->when($user, fn ($q) => $q->whereHas('kurs', fn ($k) => $k->kullaniciKapsami($user)))
            ->orderByDesc('created_at')
            ->get();

        $etkinlikBasvurulari = $kisi->etkinlikBasvurulari()
            ->with(['etkinlik.etkinlikTipi', 'etkinlik.merkez', 'durum'])
            ->orderByDesc('created_at')
            ->get();

        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');

        $kesinKayitBasvurular = $basvurular->filter(
            fn (KursBasvuru $b) => (int) $b->durum_id === (int) $kesinKayitId
        );

        $aktifKursSayisi = $kesinKayitBasvurular
            ->filter(fn (KursBasvuru $b) => $b->kurs?->durum === KursDurum::Aktif)
            ->count();

        $yoklamalar = $kisi->yoklamalar()
            ->with(['ders.kurs.brans', 'ders.kurs.merkez'])
            ->get()
            ->sortByDesc(fn (KursYoklama $y) => $y->ders?->tarih?->toDateString())
            ->values();

        $mesajlar = $this->mesajlar($kisi, $basvurular->pluck('id'));

        $yakinlar = $kisi->yakinlar()
            ->with(['yakin', 'yakinlikDerecesi'])
            ->orderByDesc('id')
            ->get();

        $yakinlikDereceleri = YakinlikDerecesi::query()
            ->orderBy('sira')
            ->orderBy('id')
            ->get();

        return view('kisiler.show', [
            'kisi' => $kisi,
            'basvurular' => $basvurular,
            'etkinlikBasvurulari' => $etkinlikBasvurulari,
            'yoklamalar' => $yoklamalar,
            'mesajlar' => $mesajlar,
            'yakinlar' => $yakinlar,
            'yakinlikDereceleri' => $yakinlikDereceleri,
            'toplamBasvuru' => $basvurular->count() + $etkinlikBasvurulari->count(),
            'kesinKayitSayisi' => $kesinKayitBasvurular->count(),
            'aktifKursSayisi' => $aktifKursSayisi,
            'telefonVar' => PhoneNormalizer::normalize($kisi->telefon) !== null,
            'emailVar' => filled($kisi->email),
        ]);
    }

    public function kisiAra(Request $request, Kisi $kisi): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $items = Kisi::query()
            ->where('id', '!=', $kisi->id)
            ->when(
                preg_match('/^\d+$/', $q),
                fn ($query) => $query->where('tc_kimlik_no', 'like', $q.'%'),
                fn ($query) => $query->where(function ($inner) use ($q) {
                    $inner->where('ad', 'like', '%'.$q.'%')
                        ->orWhere('soyad', 'like', '%'.$q.'%')
                        ->orWhereRaw("CONCAT(ad, ' ', soyad) like ?", ['%'.$q.'%']);
                }),
            )
            ->orderBy('ad')
            ->orderBy('soyad')
            ->limit(15)
            ->get(['id', 'ad', 'soyad', 'tc_kimlik_no', 'dogum_tarihi']);

        return response()->json([
            'items' => $items->map(fn (Kisi $k) => [
                'id' => $k->id,
                'ad' => $k->ad,
                'soyad' => $k->soyad,
                'tam_adi' => $k->tam_adi,
                'tc_kimlik_no' => $k->tc_kimlik_no,
                'dogum_tarihi' => $k->dogum_tarihi?->format('Y-m-d'),
                'label' => $k->tam_adi.($k->tc_kimlik_no ? ' · '.$k->tc_kimlik_no : ''),
            ])->values(),
        ]);
    }

    public function storeYakin(Request $request, Kisi $kisi): RedirectResponse
    {
        $validator = validator($request->all(), [
            'yakin_kisi_id' => [
                'required',
                'integer',
                Rule::exists('kisiler', 'id')->where(fn ($q) => $q->where('id', '!=', $kisi->id)),
                Rule::unique('kisi_yakinlar', 'yakin_kisi_id')->where(fn ($q) => $q->where('kisi_id', $kisi->id)),
            ],
            'yakinlik_derecesi_id' => ['required', 'integer', Rule::exists('yakinlik_dereceleri', 'id')],
        ], [
            'yakin_kisi_id.required' => 'Yakın kişi seçmelisiniz.',
            'yakin_kisi_id.exists' => 'Seçilen kişi bulunamadı.',
            'yakin_kisi_id.unique' => 'Bu kişi zaten yakın olarak kayıtlı.',
            'yakinlik_derecesi_id.required' => 'Yakınlık derecesi seçmelisiniz.',
            'yakinlik_derecesi_id.exists' => 'Geçersiz yakınlık derecesi.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('kisiler.show', ['kisi' => $kisi, 'tab' => 'aile'])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        KisiYakin::query()->create([
            'kisi_id' => $kisi->id,
            'yakin_kisi_id' => (int) $validated['yakin_kisi_id'],
            'yakinlik_derecesi_id' => (int) $validated['yakinlik_derecesi_id'],
        ]);

        $yakin = Kisi::query()->find((int) $validated['yakin_kisi_id']);

        return redirect()
            ->route('kisiler.show', ['kisi' => $kisi, 'tab' => 'aile'])
            ->with('success', ($yakin?->tam_adi ?? 'Yakın').' aile listesine eklendi.');
    }

    public function destroyYakin(Kisi $kisi, KisiYakin $yakin): RedirectResponse
    {
        if ((int) $yakin->kisi_id !== (int) $kisi->id) {
            abort(404);
        }

        $ad = $yakin->yakin?->tam_adi ?? 'Yakın';
        $yakin->delete();

        return redirect()
            ->route('kisiler.show', ['kisi' => $kisi, 'tab' => 'aile'])
            ->with('success', "{$ad} aile listesinden kaldırıldı.");
    }

    public function basvurular(Request $request, Kisi $kisi): JsonResponse
    {
        $tur = (string) $request->input('tur', 'kurs');
        if (! in_array($tur, ['kurs', 'etkinlik'], true)) {
            $tur = 'kurs';
        }

        $user = $request->user();
        $kursSayisi = $kisi->kursBasvurulari()
            ->when($user, fn ($q) => $q->whereHas('kurs', fn ($k) => $k->kullaniciKapsami($user)))
            ->count();
        $etkinlikSayisi = $kisi->etkinlikBasvurulari()->count();

        if ($tur === 'etkinlik') {
            $etkinlikBasvurulari = $kisi->etkinlikBasvurulari()
                ->with(['etkinlik.etkinlikTipi', 'etkinlik.merkez', 'durum'])
                ->orderByDesc('created_at')
                ->get();

            $html = view('kisiler._etkinlik_basvurular', [
                'etkinlikBasvurulari' => $etkinlikBasvurulari,
            ])->render();
        } else {
            $basvurular = $kisi->kursBasvurulari()
                ->with(['kurs.brans', 'kurs.alan', 'kurs.merkez', 'durum', 'basariDurum'])
                ->when($user, fn ($q) => $q->whereHas('kurs', fn ($k) => $k->kullaniciKapsami($user)))
                ->orderByDesc('created_at')
                ->get();

            $html = view('kisiler._kurs_basvurular', [
                'basvurular' => $basvurular,
            ])->render();
        }

        return response()->json([
            'html' => $html,
            'tur' => $tur,
            'counts' => [
                'kurs' => $kursSayisi,
                'etkinlik' => $etkinlikSayisi,
            ],
        ]);
    }

    /**
     * Kişiye ait SMS ve e-posta gönderim kayıtlarını birleştirip döndürür.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $basvuruIds
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mesajlar(Kisi $kisi, $basvuruIds)
    {
        $telefon = PhoneNormalizer::normalize($kisi->telefon);
        $email = trim((string) $kisi->email);

        $smsler = collect();
        if ($basvuruIds->isNotEmpty() || $telefon !== null) {
            $smsler = SmsLog::query()
                ->with(['kurs.brans', 'gonderen'])
                ->where(function ($q) use ($basvuruIds, $telefon) {
                    $q->whereRaw('1 = 0');
                    if ($basvuruIds->isNotEmpty()) {
                        $q->orWhereIn('basvuru_id', $basvuruIds->all());
                    }
                    if ($telefon !== null) {
                        $q->orWhere('telefon', $telefon);
                    }
                })
                ->get()
                ->map(fn (SmsLog $g) => $this->mesajSatiri($g, 'sms', $g->telefon));
        }

        $epostalar = collect();
        if ($basvuruIds->isNotEmpty() || $email !== '') {
            $epostalar = EpostaLog::query()
                ->with(['kurs.brans', 'gonderen'])
                ->where(function ($q) use ($basvuruIds, $email) {
                    $q->whereRaw('1 = 0');
                    if ($basvuruIds->isNotEmpty()) {
                        $q->orWhereIn('basvuru_id', $basvuruIds->all());
                    }
                    if ($email !== '') {
                        $q->orWhere('email', $email);
                    }
                })
                ->get()
                ->map(fn (EpostaLog $g) => $this->mesajSatiri($g, 'eposta', $g->email));
        }

        return $smsler->concat($epostalar)
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * @param  SmsLog|EpostaLog  $log
     * @return array<string, mixed>
     */
    private function mesajSatiri($log, string $kanal, ?string $hedef): array
    {
        $kurs = $log->kurs;

        return [
            'kanal' => $kanal,
            'kurs_id' => $log->kurs_id,
            'kurs_no' => $kurs?->kurs_no,
            'kurs_adi' => $kurs?->brans?->ad ?? ($kurs ? 'Kurs #'.$kurs->kurs_no : null),
            'created_at' => $log->created_at,
            'gonderen' => $log->gonderen?->tam_adi,
            'konu' => $kanal === 'eposta' ? ($log->konu ?? null) : null,
            'mesaj' => $log->mesaj,
            'durum' => $log->durum,
            'hedef' => $hedef,
        ];
    }

    public function sendSms(Request $request, Kisi $kisi, SmsSender $smsSender): JsonResponse
    {
        $validated = $request->validate([
            'mesaj' => ['required', 'string', 'min:1', 'max:480'],
        ], [
            'mesaj.required' => 'SMS metni zorunludur.',
            'mesaj.max' => 'SMS metni en fazla 480 karakter olabilir.',
        ]);

        $telefon = PhoneNormalizer::normalize($kisi->telefon);
        if (! $telefon) {
            throw ValidationException::withMessages([
                'mesaj' => 'Bu kişi için kayıtlı telefon numarası bulunamadı.',
            ]);
        }

        $mesaj = $this->mesajKisisellestir($validated['mesaj'], $kisi->tam_adi);
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
            'message' => "SMS \"{$kisi->tam_adi}\" kişisine gönderildi.",
        ]);
    }

    public function sendEposta(Request $request, Kisi $kisi, EmailSender $emailSender): JsonResponse
    {
        $validated = $request->validate([
            'konu' => ['required', 'string', 'min:1', 'max:200'],
            'mesaj' => ['required', 'string', 'min:1', 'max:5000'],
        ], [
            'konu.required' => 'E-posta konusu zorunludur.',
            'mesaj.required' => 'E-posta metni zorunludur.',
        ]);

        $email = trim((string) $kisi->email);
        if ($email === '') {
            throw ValidationException::withMessages([
                'mesaj' => 'Bu kişi için kayıtlı e-posta adresi bulunamadı.',
            ]);
        }

        $konu = $this->mesajKisisellestir($validated['konu'], $kisi->tam_adi);
        $mesaj = $this->mesajKisisellestir($validated['mesaj'], $kisi->tam_adi);

        $sonuc = $emailSender->send($email, $konu, $mesaj, [
            'gonderen_id' => $request->user()?->id,
        ]);

        if (! ($sonuc['ok'] ?? false)) {
            return response()->json([
                'message' => $sonuc['message'] ?? 'E-posta gönderilemedi.',
            ], 422);
        }

        return response()->json([
            'message' => "E-posta \"{$kisi->tam_adi}\" kişisine gönderildi.",
        ]);
    }

    public function updateFoto(Request $request, Kisi $kisi): JsonResponse
    {
        $validated = $request->validate([
            'profil_foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'profil_foto.required' => 'Bir resim seçmelisiniz.',
            'profil_foto.image' => 'Yüklenen dosya bir resim olmalıdır.',
            'profil_foto.mimes' => 'Profil resmi JPG, PNG veya WEBP olmalıdır.',
            'profil_foto.max' => 'Profil resmi en fazla 2 MB olabilir.',
        ]);

        $dosya = $this->fotoKaydet($validated['profil_foto'], $kisi->profil_foto);
        $kisi->update(['profil_foto' => $dosya]);

        LogKaydedici::kaydet(
            islem: 'kisi.guncellendi',
            aciklama: '"'.$kisi->tam_adi.'" kişisinin profil fotoğrafı güncellendi.',
            konu: $kisi,
            konuAdi: $kisi->tam_adi,
        );

        return response()->json([
            'message' => 'Profil fotoğrafı güncellendi.',
            'url' => $kisi->profil_foto_url,
        ]);
    }

    public function deleteFoto(Kisi $kisi): JsonResponse
    {
        if (! $kisi->profil_foto) {
            return response()->json([
                'message' => 'Kaldırılacak bir profil fotoğrafı bulunamadı.',
            ], 422);
        }

        $this->fotoSil($kisi->profil_foto);
        $kisi->update(['profil_foto' => null]);

        LogKaydedici::kaydet(
            islem: 'kisi.guncellendi',
            aciklama: '"'.$kisi->tam_adi.'" kişisinin profil fotoğrafı kaldırıldı.',
            konu: $kisi,
            konuAdi: $kisi->tam_adi,
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

    private function mesajKisisellestir(string $sablon, string $adSoyad): string
    {
        return str_replace('{ad_soyad}', $adSoyad, $sablon);
    }

    /**
     * @return array<string, mixed>
     */
    private function kisiSnapshot(Kisi $kisi): array
    {
        return [
            'ad' => $kisi->ad,
            'soyad' => $kisi->soyad,
            'tc_kimlik_no' => $kisi->tc_kimlik_no,
            'dogum_tarihi' => $kisi->dogum_tarihi?->toDateString(),
            'cinsiyet' => $kisi->cinsiyet instanceof Cinsiyet ? $kisi->cinsiyet->value : $kisi->cinsiyet,
            'dogum_yeri' => $kisi->dogum_yeri,
            'medeni_durum' => $kisi->medeni_durum,
            'uyruk' => $kisi->uyruk,
            'anne_adi' => $kisi->anne_adi,
            'baba_adi' => $kisi->baba_adi,
            'telefon' => $kisi->telefon,
            'email' => $kisi->email,
            'il' => $kisi->il,
            'ilce' => $kisi->ilce,
            'adres' => $kisi->adres,
            'aktif' => (bool) $kisi->aktif,
        ];
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function search(Request $request, bool $paginate = true): array
    {
        $query = Kisi::query()->withCount('kursBasvurulari as basvuru_sayisi');

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

        $cinsiyet = (string) $request->input('cinsiyet', 'tumu');
        $cinsiyetEnum = Cinsiyet::tryFrom($cinsiyet);
        if ($cinsiyetEnum !== null) {
            $query->where('cinsiyet', $cinsiyetEnum);
        } else {
            $cinsiyet = 'tumu';
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
            'cinsiyet' => ['cinsiyet'],
            'basvuru_sayisi' => ['basvuru_sayisi'],
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

        $filters = $request->only(['ad_soyad', 'ad_soyad_mode', 'tc_kimlik_no', 'telefon', 'email', 'cinsiyet', 'durum', 'per_page', 'sort', 'direction']);
        $filters['durum'] = $durum;
        $filters['cinsiyet'] = $cinsiyet;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Kisi $kisi = null): array
    {
        $validated = $request->validate([
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'tc_kimlik_no' => [
                'nullable', 'digits:11',
                Rule::unique('kisiler', 'tc_kimlik_no')->ignore($kisi?->id)->whereNull('deleted_at'),
            ],
            'dogum_tarihi' => ['nullable', 'date'],
            'cinsiyet' => ['nullable', Rule::enum(Cinsiyet::class)],
            'dogum_yeri' => ['nullable', 'string', 'max:100'],
            'medeni_durum' => ['nullable', 'string', 'max:50'],
            'uyruk' => ['nullable', 'string', 'max:100'],
            'anne_adi' => ['nullable', 'string', 'max:100'],
            'baba_adi' => ['nullable', 'string', 'max:100'],
            'telefon' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'il' => ['nullable', 'string', 'max:100'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'adres' => ['nullable', 'string', 'max:500'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Ad alanı zorunludur.',
            'soyad.required' => 'Soyad alanı zorunludur.',
            'tc_kimlik_no.digits' => 'TC Kimlik No 11 haneli olmalıdır.',
            'tc_kimlik_no.unique' => 'Bu TC Kimlik No zaten kayıtlı.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
        ]);

        if (($validated['tc_kimlik_no'] ?? null) === '') {
            $validated['tc_kimlik_no'] = null;
        }

        return $validated;
    }
}
