<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Cinsiyet;
use App\Enums\KisiGirisYontemi;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\KisiResource;
use App\Services\Adres\AdresSorgulama;
use App\Models\Kisi;
use App\Models\KisiYakin;
use App\Services\Entegrasyon\EntegrasyonAyarServisi;
use App\Services\GenelAyarServisi;
use App\Services\HosgeldinEpostaServisi;
use App\Services\Jwt\JwtTokenServisi;
use App\Services\Kimlik\KimlikSorgulama;
use App\Services\KisiYakinServisi;
use App\Services\PortalGirisKilitServisi;
use App\Services\PortalIkiAsamaliDogrulamaServisi;
use App\Services\PortalKayitDogrulamaServisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class AuthController extends ApiController
{
    public function register(Request $request, JwtTokenServisi $jwt, GenelAyarServisi $ayarlar, PortalKayitDogrulamaServisi $otp): JsonResponse
    {
        $yontem = $ayarlar->kisiGirisYontemi();
        $kanallar = $ayarlar->portalIkiAsamaliKanallari();

        $request->merge([
            'telefon' => preg_replace('/\s+/', '', (string) $request->input('telefon', '')),
        ]);

        $sahipsizId = $this->sahipsizKisi((string) $request->input('tc_kimlik_no', ''))?->id;
        $emailBenzersiz = Rule::unique('kisiler', 'email')->ignore($sahipsizId);

        $rules = [
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'telefon' => ['required', 'string', 'regex:/^05\d{9}$/'],
            'tc_kimlik_no' => [
                'required',
                'digits:11',
                Rule::unique('kisiler', 'tc_kimlik_no')->where(
                    fn ($q) => $q->where(fn ($q) => $q->whereNotNull('portal_hesap_at')->orWhereNotNull('deleted_at'))
                ),
            ],
            'dogum_tarihi' => ['required', 'date_format:Y-m-d'],
            'il' => ['nullable', 'string', 'max:100'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'adres' => ['nullable', 'string', 'max:500'],
            'cinsiyet' => ['nullable', Rule::enum(Cinsiyet::class)],
        ];

        $messages = [
            'ad.required' => 'Ad zorunludur.',
            'soyad.required' => 'Soyad zorunludur.',
            'telefon.required' => 'Telefon zorunludur.',
            'telefon.regex' => 'Telefon 05XXXXXXXXX biçiminde, 11 haneli cep telefonu numarası olmalıdır.',
            'tc_kimlik_no.required' => 'T.C. kimlik numarası zorunludur.',
            'tc_kimlik_no.digits' => 'T.C. kimlik numarası 11 haneli olmalıdır.',
            'tc_kimlik_no.unique' => 'Bu T.C. kimlik numarası ile kayıt zaten var.',
            'dogum_tarihi.required' => 'Doğum tarihi zorunludur.',
        ];

        if ($yontem === KisiGirisYontemi::EpostaSifre) {
            $rules['email'] = ['required', 'email', 'max:150', $emailBenzersiz];
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
            $messages['email.required'] = 'E-posta adresi zorunludur.';
            $messages['email.unique'] = 'Bu e-posta adresi ile kayıt zaten var.';
            $messages['password.required'] = 'Şifre zorunludur.';
            $messages['password.confirmed'] = 'Şifre onayı eşleşmiyor.';
        } else {
            $rules['email'] = ['nullable', 'email', 'max:150', $emailBenzersiz];

            if ($yontem === KisiGirisYontemi::TcSifre) {
                $rules['password'] = ['required', 'confirmed', Password::defaults()];
                $messages['password.required'] = 'Şifre zorunludur.';
                $messages['password.confirmed'] = 'Şifre onayı eşleşmiyor.';
            }
        }

        if ($kanallar === ['eposta']) {
            $rules['email'] = ['required', 'email', 'max:150', $emailBenzersiz];
            $messages['email.required'] = 'Doğrulama kodu gönderilebilmesi için e-posta adresi zorunludur.';
            $messages['email.unique'] = 'Bu e-posta adresi ile kayıt zaten var.';
        }

        $validated = $request->validate($rules, $messages);

        $kayit = [
            'ad' => $this->buyukHarf($validated['ad']),
            'soyad' => $this->buyukHarf($validated['soyad']),
            'tc_kimlik_no' => $validated['tc_kimlik_no'],
            'dogum_tarihi' => $validated['dogum_tarihi'],
            'telefon' => trim($validated['telefon']),
            'email' => isset($validated['email']) ? mb_strtolower(trim($validated['email'])) : null,
            'password' => isset($validated['password']) ? Hash::make($validated['password']) : null,
            'cinsiyet' => $validated['cinsiyet'] ?? null,
            'il' => $validated['il'] ?? null,
            'ilce' => $validated['ilce'] ?? null,
            'adres' => $validated['adres'] ?? null,
        ];

        $kayit = [...$kayit, ...$this->kayitKimlikDogrula($kayit), ...$this->kayitAdresSorgula($kayit)];

        if ($kanallar !== []) {
            try {
                $dogrulama = $otp->baslat(PortalKayitDogrulamaServisi::geciciKisi($kayit), $kanallar, $kayit);
            } catch (RuntimeException $e) {
                return $this->error($e->getMessage(), 422);
            }

            return $this->success([
                'iki_asamali' => true,
                ...$dogrulama,
            ], 'Doğrulama kodu gönderildi.');
        }

        return $this->kayitOlustur($kayit, $jwt, $yontem);
    }

    public function registerDogrulama(Request $request, JwtTokenServisi $jwt, GenelAyarServisi $ayarlar, PortalKayitDogrulamaServisi $otp): JsonResponse
    {
        $validated = $request->validate([
            'dogrulama_token' => ['required', 'string'],
            'kod' => ['required', 'string', 'max:12'],
        ], [
            'dogrulama_token.required' => 'Doğrulama oturumu bulunamadı. Lütfen kayıt formunu tekrar gönderin.',
            'kod.required' => 'Doğrulama kodu zorunludur.',
        ]);

        try {
            $kayit = $otp->kayitBilgileri($validated['dogrulama_token'], $validated['kod']);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422, ['kod' => [$e->getMessage()]]);
        }

        $sahipsizId = $this->sahipsizKisi($kayit['tc_kimlik_no'])?->id;
        $mevcut = Kisi::withTrashed()
            ->when($sahipsizId, fn ($q, $id) => $q->whereKeyNot($id))
            ->where(fn ($q) => $q
                ->where('tc_kimlik_no', $kayit['tc_kimlik_no'])
                ->when($kayit['email'], fn ($q, $email) => $q->orWhereRaw('LOWER(email) = ?', [$email])))
            ->exists();
        if ($mevcut) {
            $otp->tamamla($validated['dogrulama_token']);

            return $this->error('Bu T.C. kimlik numarası veya e-posta adresi ile kayıt zaten var.', 422);
        }

        $yanit = $this->kayitOlustur($kayit, $jwt, $ayarlar->kisiGirisYontemi());
        $otp->tamamla($validated['dogrulama_token']);

        return $yanit;
    }

    public function registerKodYenile(Request $request, PortalKayitDogrulamaServisi $otp): JsonResponse
    {
        $validated = $request->validate([
            'dogrulama_token' => ['required', 'string'],
        ], [
            'dogrulama_token.required' => 'Doğrulama oturumu bulunamadı. Lütfen kayıt formunu tekrar gönderin.',
        ]);

        try {
            $sonuc = $otp->yenidenGonder($validated['dogrulama_token']);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($sonuc, 'Yeni doğrulama kodu gönderildi.');
    }

    /**
     * Kimlik sorgulama entegrasyonu aktifse kayıt bilgilerini aktif sağlayıcıyla doğrular.
     * Demo sağlayıcıda ad/soyad eşleşmesi atlanır.
     *
     * @param  array<string, mixed>  $kayit
     * @return array<string, string> Kimlik kaydından kişiye yazılacak alanlar
     */
    private function kayitKimlikDogrula(array $kayit): array
    {
        $entegrasyon = app(EntegrasyonAyarServisi::class);
        if (! $entegrasyon->turAktifMi('kimlik_sorgulama')) {
            return [];
        }

        try {
            $sonuc = app(KimlikSorgulama::class)->sorgula($kayit['tc_kimlik_no'], $kayit['dogum_tarihi']);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['tc_kimlik_no' => $e->getMessage() ?: 'Kimlik sorgulama yapılamadı.']);
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages(['tc_kimlik_no' => 'Kimlik sorgulama sırasında bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
        }

        if ($entegrasyon->aktifSaglayiciKod('kimlik_sorgulama') === 'demo_kimlik') {
            return $this->kimlikAlanlari($sonuc);
        }

        if (! ($sonuc['ok'] ?? false)) {
            throw ValidationException::withMessages([
                'tc_kimlik_no' => 'T.C. kimlik numarası ve doğum tarihi ile eşleşen kimlik kaydı bulunamadı.',
            ]);
        }

        $hatalar = [];
        if ($this->kimlikMetni($kayit['ad']) !== $this->kimlikMetni((string) ($sonuc['ad'] ?? ''))) {
            $hatalar['ad'] = 'Girilen ad kimlik kaydı ile eşleşmiyor.';
        }
        if ($this->kimlikMetni($kayit['soyad']) !== $this->kimlikMetni((string) ($sonuc['soyad'] ?? ''))) {
            $hatalar['soyad'] = 'Girilen soyad kimlik kaydı ile eşleşmiyor.';
        }
        if ($hatalar !== []) {
            throw ValidationException::withMessages($hatalar);
        }

        return $this->kimlikAlanlari($sonuc);
    }

    /**
     * @param  array<string, mixed>  $sonuc
     * @return array<string, string>
     */
    private function kimlikAlanlari(array $sonuc): array
    {
        $alanlar = [];

        if (in_array($sonuc['cinsiyet'] ?? null, ['erkek', 'kadin'], true)) {
            $alanlar['cinsiyet'] = $sonuc['cinsiyet'];
        }

        foreach (['dogum_yeri' => 100, 'medeni_durum' => 50, 'uyruk' => 100, 'anne_adi' => 100, 'baba_adi' => 100] as $alan => $uzunluk) {
            $deger = is_scalar($sonuc[$alan] ?? null) ? trim((string) $sonuc[$alan]) : '';
            if ($deger !== '') {
                $alanlar[$alan] = mb_substr($deger, 0, $uzunluk);
            }
        }

        return $alanlar;
    }

    /**
     * Adres sorgulama entegrasyonu aktifse aktif sağlayıcıdan adres bilgilerini alır.
     * Adres zorunlu olmadığından sorgu başarısız olursa kayıt engellenmez.
     *
     * @param  array<string, mixed>  $kayit
     * @return array<string, string>
     */
    private function kayitAdresSorgula(array $kayit): array
    {
        if (! app(EntegrasyonAyarServisi::class)->turAktifMi('adres_sorgulama')) {
            return [];
        }

        try {
            $sonuc = app(AdresSorgulama::class)->sorgula($kayit['tc_kimlik_no'], $kayit['dogum_tarihi']);
        } catch (Throwable $e) {
            Log::warning('Portal kayıt adres sorgulaması başarısız', ['tc_kimlik_no' => $kayit['tc_kimlik_no'], 'hata' => $e->getMessage()]);

            return [];
        }

        if (! ($sonuc['ok'] ?? false)) {
            return [];
        }

        $alanlar = ['il', 'ilce', 'mahalle', 'sokak', 'kapi', 'daire', 'uavt_adres_no', 'adres'];

        return array_filter(
            array_map(fn ($deger) => is_scalar($deger) ? trim((string) $deger) : '', array_intersect_key($sonuc, array_flip($alanlar))),
            fn (string $deger) => $deger !== '',
        );
    }

    /**
     * Başvuru sırasında yakın olarak oluşturulmuş, henüz portal hesabı açılmamış kişi kaydı.
     * Kayıt olan kişi bu kaydı sahiplenir; yeni kişi oluşturulmaz.
     */
    private function sahipsizKisi(string $tcKimlikNo): ?Kisi
    {
        if (! preg_match('/^\d{11}$/', $tcKimlikNo)) {
            return null;
        }

        return Kisi::query()
            ->where('tc_kimlik_no', $tcKimlikNo)
            ->whereNull('portal_hesap_at')
            ->first();
    }

    /**
     * Yakın sorgulama entegrasyonu aktifse yeni üyenin 1. derece yakınlarını kaydeder.
     * Demo sağlayıcı sahte yakın döndürdüğü için atlanır; sorgu başarısız olursa kayıt engellenmez.
     */
    private function kayitYakinlariniAktar(Kisi $kisi): void
    {
        $entegrasyon = app(EntegrasyonAyarServisi::class);
        if (! $entegrasyon->turAktifMi('yakin_sorgulama') || $entegrasyon->aktifSaglayiciKod('yakin_sorgulama') === 'demo_yakin') {
            return;
        }

        try {
            app(KisiYakinServisi::class)->entegrasyondanAktar($kisi, $kisi);
        } catch (Throwable $e) {
            Log::warning('Portal kayıt yakın sorgulaması başarısız', ['kisi_id' => $kisi->id, 'hata' => $e->getMessage()]);
        }
    }

    private function buyukHarf(string $deger): string
    {
        $deger = mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], trim($deger)), 'UTF-8');

        return preg_replace('/\s+/u', ' ', $deger) ?? $deger;
    }

    private function kimlikMetni(string $deger): string
    {
        return str_replace(['İ', 'Ş', 'Ğ', 'Ü', 'Ö', 'Ç'], ['I', 'S', 'G', 'U', 'O', 'C'], $this->buyukHarf($deger));
    }

    /**
     * @param  array<string, mixed>  $kayit
     */
    private function kayitOlustur(array $kayit, JwtTokenServisi $jwt, KisiGirisYontemi $yontem): JsonResponse
    {
        $sahipsiz = $this->sahipsizKisi($kayit['tc_kimlik_no']);

        if ($sahipsiz) {
            $sahipsiz->fill([
                ...array_filter($kayit, fn ($deger) => $deger !== null && $deger !== ''),
                'aktif' => true,
                'portal_hesap_at' => now(),
            ])->save();
            $kisi = $sahipsiz->fresh();
        } else {
            $kisi = Kisi::query()->create([...$kayit, 'aktif' => true, 'portal_hesap_at' => now()]);
        }

        $this->kayitYakinlariniAktar($kisi);

        app()->terminating(fn () => app(HosgeldinEpostaServisi::class)->kayitSonrasiGonder($kisi));

        $tokens = $jwt->tokenCiftiOlustur($kisi);

        return $this->success([
            ...$tokens,
            'kisi' => new KisiResource($kisi),
            'giris_yontemi' => [
                'kod' => $yontem->value,
                'label' => $yontem->label(),
            ],
        ], 'Kayıt başarılı.', 201);
    }

    public function login(Request $request, JwtTokenServisi $jwt, GenelAyarServisi $ayarlar, PortalGirisKilitServisi $kilit, PortalIkiAsamaliDogrulamaServisi $otp): JsonResponse
    {
        $yontem = $ayarlar->kisiGirisYontemi();
        $credentials = $this->dogrulaKimlikBilgileri($request, $yontem);

        $kisi = match ($yontem) {
            KisiGirisYontemi::TcDogumTarihi,
            KisiGirisYontemi::TcSifre => Kisi::query()
                ->where('tc_kimlik_no', $credentials['tc_kimlik_no'])
                ->first(),
            KisiGirisYontemi::EpostaSifre => Kisi::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($credentials['email'])])
                ->first(),
        };

        if ($kisi && ! $kisi->aktif) {
            return $this->error('Bu hesap bloke edilmiş veya pasif durumda.', 403);
        }

        if ($kisi?->girisKilitliMi()) {
            return $this->error($kilit->kilitMesaji($kisi), 423);
        }

        if (! $kisi || ! $this->kimlikDogrula($kisi, $yontem, $credentials)) {
            if ($kisi && $kilit->hataliDenemeKaydet($kisi)) {
                return $this->error($kilit->kilitMesaji($kisi), 423);
            }

            return $this->error('Girdiğiniz bilgiler kayıtlarımızla eşleşmiyor.', 401);
        }

        $kilit->sifirla($kisi);

        $kanallar = $ayarlar->portalIkiAsamaliKanallari();
        if ($kanallar !== []) {
            try {
                $dogrulama = $otp->baslat($kisi, $kanallar);
            } catch (RuntimeException $e) {
                return $this->error($e->getMessage(), 422);
            }

            return $this->success([
                'iki_asamali' => true,
                ...$dogrulama,
            ], 'Doğrulama kodu gönderildi.');
        }

        return $this->girisYaniti($kisi, $jwt, $yontem);
    }

    public function loginDogrulama(Request $request, JwtTokenServisi $jwt, GenelAyarServisi $ayarlar, PortalGirisKilitServisi $kilit, PortalIkiAsamaliDogrulamaServisi $otp): JsonResponse
    {
        $validated = $request->validate([
            'dogrulama_token' => ['required', 'string'],
            'kod' => ['required', 'string', 'max:12'],
        ], [
            'dogrulama_token.required' => 'Doğrulama oturumu bulunamadı. Lütfen tekrar giriş yapın.',
            'kod.required' => 'Doğrulama kodu zorunludur.',
        ]);

        try {
            $kisi = $otp->dogrula($validated['dogrulama_token'], $validated['kod']);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422, ['kod' => [$e->getMessage()]]);
        }

        if (! $kisi->aktif) {
            return $this->error('Bu hesap bloke edilmiş veya pasif durumda.', 403);
        }

        if ($kisi->girisKilitliMi()) {
            return $this->error($kilit->kilitMesaji($kisi), 423);
        }

        return $this->girisYaniti($kisi, $jwt, $ayarlar->kisiGirisYontemi());
    }

    public function loginKodYenile(Request $request, PortalIkiAsamaliDogrulamaServisi $otp): JsonResponse
    {
        $validated = $request->validate([
            'dogrulama_token' => ['required', 'string'],
        ], [
            'dogrulama_token.required' => 'Doğrulama oturumu bulunamadı. Lütfen tekrar giriş yapın.',
        ]);

        try {
            $sonuc = $otp->yenidenGonder($validated['dogrulama_token']);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($sonuc, 'Yeni doğrulama kodu gönderildi.');
    }

    private function girisYaniti(Kisi $kisi, JwtTokenServisi $jwt, KisiGirisYontemi $yontem): JsonResponse
    {
        $tokens = $jwt->tokenCiftiOlustur($kisi);

        return $this->success([
            ...$tokens,
            'kisi' => new KisiResource($kisi),
            'giris_yontemi' => [
                'kod' => $yontem->value,
                'label' => $yontem->label(),
            ],
        ], 'Giriş başarılı.');
    }

    public function refresh(Request $request, JwtTokenServisi $jwt): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
        ], [
            'refresh_token.required' => 'Refresh token zorunludur.',
        ]);

        try {
            $kisi = $jwt->kisi($validated['refresh_token'], JwtTokenServisi::TIP_REFRESH);
            $jwt->invalidate($validated['refresh_token']);
        } catch (UnexpectedValueException $e) {
            return $this->error($e->getMessage() ?: 'Geçersiz refresh token.', 401);
        } catch (\Throwable) {
            return $this->error('Geçersiz refresh token.', 401);
        }

        $tokens = $jwt->tokenCiftiOlustur($kisi);

        return $this->success([
            ...$tokens,
            'kisi' => new KisiResource($kisi),
        ], 'Token yenilendi.');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Kisi $kisi */
        $kisi = $request->user();

        return $this->success(new KisiResource($kisi));
    }

    public function yakinlar(Request $request): JsonResponse
    {
        /** @var Kisi $kisi */
        $kisi = $request->user();

        $items = KisiYakin::query()
            ->where('kisi_id', $kisi->id)
            ->whereHas('yakinlikDerecesi', fn ($q) => $q->whereIn('kod', ['ESI', 'OGLU', 'KIZI']))
            ->whereNotNull('tc_kimlik_no')
            ->with(['yakin', 'yakinlikDerecesi'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (KisiYakin $kayit) => [
                'id' => $kayit->id,
                'ad' => $kayit->ad,
                'soyad' => $kayit->soyad,
                'tam_adi' => $kayit->tam_adi,
                'tc_kimlik_no' => $kayit->tc_kimlik_no,
                'dogum_tarihi' => ($kayit->dogum_tarihi ?? $kayit->yakin?->dogum_tarihi)?->format('Y-m-d'),
                'cinsiyet' => ($kayit->cinsiyet ?? $kayit->yakin?->cinsiyet)?->value,
                'yakinlik_derecesi' => $kayit->yakinlikDerecesi?->kod,
                'yakinlik_label' => $kayit->yakinlikDerecesi?->ad,
            ])
            ->values();

        return $this->success(['items' => $items]);
    }

    public function updateProfil(Request $request): JsonResponse
    {
        /** @var Kisi $kisi */
        $kisi = $request->user();

        $request->merge([
            'telefon' => preg_replace('/\s+/', '', (string) $request->input('telefon')),
        ]);

        $validated = $request->validate([
            'telefon' => ['required', 'string', 'regex:/^05\d{9}$/'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('kisiler', 'email')->ignore($kisi->id),
            ],
            'diger_adres' => ['nullable', 'string', 'max:500'],
        ], [
            'telefon.required' => 'Telefon zorunludur.',
            'telefon.regex' => 'Telefon 05XXXXXXXXX biçiminde, 11 haneli cep telefonu numarası olmalıdır.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'email.unique' => 'Bu e-posta adresi başka bir hesapta kullanılıyor.',
        ]);

        $kisi->fill([
            'telefon' => trim((string) $validated['telefon']),
            'email' => array_key_exists('email', $validated)
                ? ($validated['email'] !== null ? mb_strtolower(trim($validated['email'])) : null)
                : $kisi->email,
            'diger_adres' => array_key_exists('diger_adres', $validated)
                ? (filled($validated['diger_adres'] ?? null) ? trim((string) $validated['diger_adres']) : null)
                : $kisi->diger_adres,
        ])->save();

        return $this->success(new KisiResource($kisi->fresh()), 'Profil güncellendi.');
    }

    public function updateSifre(Request $request, GenelAyarServisi $ayarlar): JsonResponse
    {
        $yontem = $ayarlar->kisiGirisYontemi();

        if ($yontem === KisiGirisYontemi::TcDogumTarihi) {
            return $this->error('Bu giriş yönteminde şifre kullanılmaz.', 422);
        }

        /** @var Kisi $kisi */
        $kisi = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Mevcut şifre zorunludur.',
            'password.required' => 'Yeni şifre zorunludur.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
        ]);

        if ($kisi->password === null || $kisi->password === '' || ! Hash::check($validated['current_password'], (string) $kisi->password)) {
            return $this->error('Mevcut şifre hatalı.', 422, [
                'current_password' => ['Mevcut şifre hatalı.'],
            ]);
        }

        $kisi->password = $validated['password'];
        $kisi->save();

        return $this->success(null, 'Şifre güncellendi.');
    }

    public function logout(Request $request, JwtTokenServisi $jwt): JsonResponse
    {
        $access = $request->attributes->get('jwt_token');
        if (is_string($access) && $access !== '') {
            $jwt->invalidate($access);
        }

        $refresh = $request->input('refresh_token');
        if (is_string($refresh) && $refresh !== '') {
            $jwt->invalidate($refresh);
        }

        Auth::guard('api')->forgetUser();

        return $this->success(null, 'Çıkış yapıldı.');
    }

    /**
     * @return array<string, string>
     */
    private function dogrulaKimlikBilgileri(Request $request, KisiGirisYontemi $yontem): array
    {
        return match ($yontem) {
            KisiGirisYontemi::TcDogumTarihi => $request->validate([
                'tc_kimlik_no' => ['required', 'string', 'size:11'],
                'dogum_tarihi' => ['required', 'date_format:Y-m-d'],
            ], [
                'tc_kimlik_no.required' => 'T.C. kimlik numarası zorunludur.',
                'tc_kimlik_no.size' => 'T.C. kimlik numarası 11 haneli olmalıdır.',
                'dogum_tarihi.required' => 'Doğum tarihi zorunludur.',
                'dogum_tarihi.date_format' => 'Doğum tarihi YYYY-AA-GG formatında olmalıdır.',
            ]),
            KisiGirisYontemi::TcSifre => $request->validate([
                'tc_kimlik_no' => ['required', 'string', 'size:11'],
                'password' => ['required', 'string'],
            ], [
                'tc_kimlik_no.required' => 'T.C. kimlik numarası zorunludur.',
                'tc_kimlik_no.size' => 'T.C. kimlik numarası 11 haneli olmalıdır.',
                'password.required' => 'Şifre zorunludur.',
            ]),
            KisiGirisYontemi::EpostaSifre => $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ], [
                'email.required' => 'E-posta adresi zorunludur.',
                'email.email' => 'Geçerli bir e-posta adresi girin.',
                'password.required' => 'Şifre zorunludur.',
            ]),
        };
    }

    /**
     * @param  array<string, string>  $credentials
     */
    private function kimlikDogrula(Kisi $kisi, KisiGirisYontemi $yontem, array $credentials): bool
    {
        return match ($yontem) {
            KisiGirisYontemi::TcDogumTarihi => $kisi->dogum_tarihi !== null
                && $kisi->dogum_tarihi->format('Y-m-d') === $credentials['dogum_tarihi'],
            KisiGirisYontemi::TcSifre,
            KisiGirisYontemi::EpostaSifre => $kisi->password !== null
                && $kisi->password !== ''
                && Hash::check($credentials['password'], (string) $kisi->password),
        };
    }
}
