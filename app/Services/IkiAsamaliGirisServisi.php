<?php

namespace App\Services;

use App\Enums\IkiAsamaliGuvenlik;
use App\Models\User;
use App\Services\Email\EmailSender;
use App\Services\Sms\PhoneNormalizer;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class IkiAsamaliGirisServisi
{
    private const CACHE_PREFIX = 'login_otp:';

    private const FAIL_PREFIX = 'login_fail:';

    private const TTL_SECONDS = 600;

    private const RESEND_COOLDOWN_SECONDS = 120;

    private const MAX_OTP_ATTEMPTS = 5;

    private const MAX_LOGIN_FAILURES = 4;

    private const FAIL_TTL_SECONDS = 86400;

    public function __construct(
        private readonly SmsSender $smsSender,
        private readonly EmailSender $emailSender,
    ) {}

    /**
     * @return array{kanal: string, hedef: string, ttl_dakika: int, yeniden_gonderim_saniye: int}
     */
    public function kodGonder(User $user, bool $yeniden = false): array
    {
        $kanal = $user->iki_asamali_guvenlik ?? IkiAsamaliGuvenlik::Hayir;
        if (! $kanal instanceof IkiAsamaliGuvenlik || ! $kanal->aktifMi()) {
            throw ValidationException::withMessages([
                'kod' => 'Bu hesap için iki aşamalı güvenlik tanımlı değil.',
            ]);
        }

        if ($yeniden) {
            $this->assertYenidenGonderimBekleme($user->id);
        }

        $kod = (string) random_int(100000, 999999);
        $payload = [
            'hash' => Hash::make($kod),
            'attempts' => 0,
            'kanal' => $kanal->value,
            'sent_at' => now()->timestamp,
        ];

        Cache::put($this->cacheKey($user->id), $payload, self::TTL_SECONDS);

        if ($kanal === IkiAsamaliGuvenlik::Sms) {
            $telefon = PhoneNormalizer::normalize($user->telefon);
            if (! $telefon) {
                throw ValidationException::withMessages([
                    'email' => 'Hesabınızda kayıtlı telefon numarası bulunamadı. Yöneticinize başvurun.',
                ]);
            }

            $sonuc = $this->smsSender->send(
                $telefon,
                "Başvuru 360 giriş doğrulama kodunuz: {$kod}. Kod {$this->ttlDakika()} dakika geçerlidir.",
                ['gonderen_id' => null, 'kapsam' => 'giris_dogrulama'],
            );

            if (! ($sonuc['ok'] ?? false)) {
                Cache::forget($this->cacheKey($user->id));
                throw ValidationException::withMessages([
                    'email' => 'Doğrulama kodu SMS ile gönderilemedi. '.($sonuc['message'] ?? 'Lütfen tekrar deneyin.'),
                ]);
            }

            return [
                'kanal' => 'sms',
                'hedef' => $this->telefonMaskele($telefon),
                'ttl_dakika' => $this->ttlDakika(),
                'yeniden_gonderim_saniye' => self::RESEND_COOLDOWN_SECONDS,
            ];
        }

        $email = trim((string) $user->email);
        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Hesabınızda kayıtlı e-posta adresi bulunamadı.',
            ]);
        }

        $sonuc = $this->emailSender->send(
            $email,
            'Başvuru 360 Giriş Doğrulama Kodu',
            "Merhaba {$user->tam_adi},\n\nGiriş doğrulama kodunuz: {$kod}\n\nBu kod {$this->ttlDakika()} dakika geçerlidir. Kod size ait değilse bu e-postayı yok sayabilirsiniz.",
            ['gonderen_id' => null, 'kapsam' => 'giris_dogrulama'],
        );

        if (! ($sonuc['ok'] ?? false)) {
            Cache::forget($this->cacheKey($user->id));
            throw ValidationException::withMessages([
                'email' => 'Doğrulama kodu e-posta ile gönderilemedi. '.($sonuc['message'] ?? 'Lütfen tekrar deneyin.'),
            ]);
        }

        return [
            'kanal' => 'eposta',
            'hedef' => $this->epostaMaskele($email),
            'ttl_dakika' => $this->ttlDakika(),
            'yeniden_gonderim_saniye' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    public function yenidenGonderimKalanSaniye(int $userId): int
    {
        $payload = Cache::get($this->cacheKey($userId));
        if (! is_array($payload) || empty($payload['sent_at'])) {
            return 0;
        }

        $elapsed = now()->timestamp - (int) $payload['sent_at'];
        $kalan = self::RESEND_COOLDOWN_SECONDS - $elapsed;

        return max(0, $kalan);
    }

    public function koduDogrula(User $user, string $kod): void
    {
        $payload = Cache::get($this->cacheKey($user->id));
        if (! is_array($payload) || empty($payload['hash'])) {
            throw ValidationException::withMessages([
                'kod' => 'Doğrulama kodunun süresi dolmuş. Lütfen yeni kod isteyin.',
            ]);
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= self::MAX_OTP_ATTEMPTS) {
            Cache::forget($this->cacheKey($user->id));
            throw ValidationException::withMessages([
                'kod' => 'Çok fazla hatalı deneme. Lütfen yeni kod isteyin.',
            ]);
        }

        $kod = preg_replace('/\D+/', '', $kod) ?? '';
        if ($kod === '' || ! Hash::check($kod, $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($this->cacheKey($user->id), $payload, self::TTL_SECONDS);
            $kalan = self::MAX_OTP_ATTEMPTS - $payload['attempts'];
            throw ValidationException::withMessages([
                'kod' => $kalan > 0
                    ? "Doğrulama kodu hatalı. Kalan deneme: {$kalan}."
                    : 'Çok fazla hatalı deneme. Lütfen yeni kod isteyin.',
            ]);
        }

        Cache::forget($this->cacheKey($user->id));
    }

    /**
     * Hatalı giriş kaydeder. Hesap bloke edildiyse true döner.
     */
    public function hataliGirisKaydet(User $user): bool
    {
        $key = $this->failKey($user->id);
        $sayac = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $sayac, self::FAIL_TTL_SECONDS);

        if ($sayac < self::MAX_LOGIN_FAILURES) {
            return false;
        }

        $user->update(['aktif' => false]);
        Cache::forget($key);
        $this->temizle($user->id);

        LogKaydedici::kaydet(
            islem: 'oturum.hesap_bloke',
            aciklama: ($user->tam_adi ?: $user->email).' hesabı ardışık '.self::MAX_LOGIN_FAILURES.' hatalı giriş nedeniyle bloke edildi.',
            konu: $user,
            konuAdi: $user->tam_adi,
            userId: $user->id,
            ekstra: ['email' => $user->email, 'esik' => self::MAX_LOGIN_FAILURES],
        );

        return true;
    }

    public function hataliGirisSifirla(User $user): void
    {
        Cache::forget($this->failKey($user->id));
    }

    public function temizle(int $userId): void
    {
        Cache::forget($this->cacheKey($userId));
    }

    private function assertYenidenGonderimBekleme(int $userId): void
    {
        $kalan = $this->yenidenGonderimKalanSaniye($userId);
        if ($kalan <= 0) {
            return;
        }

        $dakika = intdiv($kalan, 60);
        $saniye = $kalan % 60;
        $metin = $dakika > 0
            ? sprintf('%d dk %d sn', $dakika, $saniye)
            : sprintf('%d sn', $saniye);

        throw ValidationException::withMessages([
            'kod' => "Yeni doğrulama kodu için {$metin} bekleyin.",
        ]);
    }

    private function cacheKey(int $userId): string
    {
        return self::CACHE_PREFIX.$userId;
    }

    private function failKey(int $userId): string
    {
        return self::FAIL_PREFIX.$userId;
    }

    private function ttlDakika(): int
    {
        return (int) ceil(self::TTL_SECONDS / 60);
    }

    private function telefonMaskele(string $telefon): string
    {
        $digits = preg_replace('/\D+/', '', $telefon) ?? '';
        if (strlen($digits) < 4) {
            return '****';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }

    private function epostaMaskele(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($domain === '') {
            return '***';
        }

        $visible = mb_substr($local, 0, 1);

        return $visible.'***@'.$domain;
    }
}
