<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Cinsiyet;
use App\Enums\KisiGirisYontemi;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\KisiResource;
use App\Models\Kisi;
use App\Services\GenelAyarServisi;
use App\Services\Jwt\JwtTokenServisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use UnexpectedValueException;

class AuthController extends ApiController
{
    public function register(Request $request, JwtTokenServisi $jwt, GenelAyarServisi $ayarlar): JsonResponse
    {
        $yontem = $ayarlar->kisiGirisYontemi();

        $rules = [
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'telefon' => ['nullable', 'string', 'max:20'],
            'il' => ['nullable', 'string', 'max:100'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'adres' => ['nullable', 'string', 'max:500'],
            'cinsiyet' => ['nullable', Rule::enum(Cinsiyet::class)],
        ];

        $messages = [
            'ad.required' => 'Ad zorunludur.',
            'soyad.required' => 'Soyad zorunludur.',
        ];

        if ($yontem === KisiGirisYontemi::EpostaSifre) {
            $rules['email'] = ['required', 'email', 'max:150', 'unique:kisiler,email'];
            $rules['tc_kimlik_no'] = ['nullable', 'digits:11', 'unique:kisiler,tc_kimlik_no'];
            $rules['dogum_tarihi'] = ['nullable', 'date_format:Y-m-d'];
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
            $messages['email.required'] = 'E-posta adresi zorunludur.';
            $messages['email.unique'] = 'Bu e-posta adresi ile kayıt zaten var.';
            $messages['password.required'] = 'Şifre zorunludur.';
            $messages['password.confirmed'] = 'Şifre onayı eşleşmiyor.';
        } else {
            $rules['tc_kimlik_no'] = ['required', 'digits:11', 'unique:kisiler,tc_kimlik_no'];
            $rules['dogum_tarihi'] = ['required', 'date_format:Y-m-d'];
            $rules['email'] = ['nullable', 'email', 'max:150', 'unique:kisiler,email'];
            $messages['tc_kimlik_no.required'] = 'T.C. kimlik numarası zorunludur.';
            $messages['tc_kimlik_no.digits'] = 'T.C. kimlik numarası 11 haneli olmalıdır.';
            $messages['tc_kimlik_no.unique'] = 'Bu T.C. kimlik numarası ile kayıt zaten var.';
            $messages['dogum_tarihi.required'] = 'Doğum tarihi zorunludur.';

            if ($yontem === KisiGirisYontemi::TcSifre) {
                $rules['password'] = ['required', 'confirmed', Password::defaults()];
                $messages['password.required'] = 'Şifre zorunludur.';
                $messages['password.confirmed'] = 'Şifre onayı eşleşmiyor.';
            }
        }

        $validated = $request->validate($rules, $messages);

        $kisi = Kisi::query()->create([
            'ad' => trim($validated['ad']),
            'soyad' => trim($validated['soyad']),
            'tc_kimlik_no' => $validated['tc_kimlik_no'] ?? null,
            'dogum_tarihi' => $validated['dogum_tarihi'] ?? null,
            'telefon' => $validated['telefon'] ?? null,
            'email' => isset($validated['email']) ? mb_strtolower(trim($validated['email'])) : null,
            'password' => $validated['password'] ?? null,
            'cinsiyet' => $validated['cinsiyet'] ?? null,
            'il' => $validated['il'] ?? null,
            'ilce' => $validated['ilce'] ?? null,
            'adres' => $validated['adres'] ?? null,
            'aktif' => true,
        ]);

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

    public function login(Request $request, JwtTokenServisi $jwt, GenelAyarServisi $ayarlar): JsonResponse
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

        if (! $kisi || ! $this->kimlikDogrula($kisi, $yontem, $credentials)) {
            return $this->error('Girdiğiniz bilgiler kayıtlarımızla eşleşmiyor.', 401);
        }

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

    public function updateProfil(Request $request): JsonResponse
    {
        /** @var Kisi $kisi */
        $kisi = $request->user();

        $validated = $request->validate([
            'telefon' => ['required', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('kisiler', 'email')->ignore($kisi->id),
            ],
            'diger_adres' => ['nullable', 'string', 'max:500'],
        ], [
            'telefon.required' => 'Telefon zorunludur.',
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
