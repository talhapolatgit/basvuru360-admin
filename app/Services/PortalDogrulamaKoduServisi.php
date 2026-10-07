<?php

namespace App\Services;

use App\Models\Kisi;
use App\Services\Email\EmailSender;
use App\Services\Sms\PhoneNormalizer;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Portal kişisine SMS ve/veya e-posta ile tek kullanımlık doğrulama kodu gönderir.
 * Kod yalnızca hash olarak cache'te tutulur; istemciye rastgele bir doğrulama token'ı verilir.
 */
abstract class PortalDogrulamaKoduServisi
{
    public const TTL_SECONDS = 600;

    public const RESEND_COOLDOWN_SECONDS = 120;

    public const MAX_OTP_ATTEMPTS = 5;

    /** Kişinin bekleme süresi dolmadan yeni oturum başlatmasını engeller. */
    protected bool $baslatmadaBeklemeUygula = false;

    public function __construct(
        private readonly SmsSender $smsSender,
        private readonly EmailSender $emailSender,
    ) {}

    abstract protected function anahtarOnEki(): string;

    abstract protected function gonderimKapsami(): string;

    abstract protected function smsMetni(string $kod, int $dakika): string;

    abstract protected function epostaKonusu(): string;

    abstract protected function epostaMetni(Kisi $kisi, string $kod, int $dakika): string;

    /**
     * E-postayı HTML olarak göndermek için EmailSender context'ine eklenecek html / gomulu_gorseller.
     *
     * @return array{html?: string, gomulu_gorseller?: array<string, string>}
     */
    protected function epostaHtmlBaglami(Kisi $kisi, string $kod, int $dakika): array
    {
        return [];
    }

    /** Oturum geçersiz olduğunda kullanıcıya ne yapması gerektiğini söyleyen cümle. */
    abstract protected function yenidenBaslatMesaji(): string;

    /** Aynı kişiye ait önceki oturumu bulmak için kullanılan anahtar. */
    protected function sahipAnahtari(Kisi $kisi): string
    {
        return (string) $kisi->id;
    }

    /**
     * Kodun gönderileceği / doğrulama sonunda dönülecek kişi.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function kisiCoz(array $payload): ?Kisi
    {
        return isset($payload['kisi_id']) ? Kisi::query()->find($payload['kisi_id']) : null;
    }

    /**
     * @param  list<'sms'|'eposta'>  $kanallar
     * @param  array<string, mixed>  $ek  Oturumla birlikte saklanacak ek veri.
     * @return array{dogrulama_token: string, hedefler: list<array{kanal: string, hedef: string}>, ttl_dakika: int, yeniden_gonderim_saniye: int}
     *
     * @throws RuntimeException
     */
    public function baslat(Kisi $kisi, array $kanallar, array $ek = []): array
    {
        $sahip = $this->sahipAnahtari($kisi);
        $oncekiAnahtar = Cache::get($this->kisiKey($sahip));
        if (is_string($oncekiAnahtar)) {
            $onceki = Cache::get($oncekiAnahtar);
            if ($this->baslatmadaBeklemeUygula && is_array($onceki)) {
                $this->beklemeKontrol((int) ($onceki['sent_at'] ?? 0));
            }
            Cache::forget($oncekiAnahtar);
        }

        $token = Str::random(64);
        $hedefler = $this->kodGonder($kisi, $kanallar, $kod = $this->kodUret());

        Cache::put($this->cacheKey($token), [
            'kisi_id' => $kisi->id,
            'sahip' => $sahip,
            'kanallar' => $kanallar,
            'ek' => $ek,
            'hash' => Hash::make($kod),
            'attempts' => 0,
            'sent_at' => now()->timestamp,
        ], self::TTL_SECONDS);
        Cache::put($this->kisiKey($sahip), $this->cacheKey($token), self::TTL_SECONDS);

        return [
            'dogrulama_token' => $token,
            'hedefler' => $hedefler,
            'ttl_dakika' => $this->ttlDakika(),
            'yeniden_gonderim_saniye' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * @return array{hedefler: list<array{kanal: string, hedef: string}>, ttl_dakika: int, yeniden_gonderim_saniye: int}
     *
     * @throws RuntimeException
     */
    public function yenidenGonder(string $token, ?Kisi $sahip = null): array
    {
        $payload = $this->payload($token, $sahip);
        $kisi = $sahip ?? $this->kisiCoz($payload);
        if (! $kisi) {
            throw new RuntimeException($this->gecersizMesaji());
        }

        $this->beklemeKontrol((int) $payload['sent_at']);

        $hedefler = $this->kodGonder($kisi, $payload['kanallar'], $kod = $this->kodUret());

        Cache::put($this->cacheKey($token), [
            ...$payload,
            'hash' => Hash::make($kod),
            'attempts' => 0,
            'sent_at' => now()->timestamp,
        ], self::TTL_SECONDS);
        Cache::put($this->kisiKey($payload['sahip'] ?? (string) $payload['kisi_id']), $this->cacheKey($token), self::TTL_SECONDS);

        return [
            'hedefler' => $hedefler,
            'ttl_dakika' => $this->ttlDakika(),
            'yeniden_gonderim_saniye' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * Kodu kontrol eder ama oturumu kapatmaz; işlem tamamlanınca {@see tamamla()} çağrılmalıdır.
     *
     * @throws RuntimeException
     */
    public function kontrol(string $token, string $kod, ?Kisi $sahip = null): Kisi
    {
        $payload = $this->kodKontrol($token, $kod, $sahip);

        $kisi = $sahip ?? $this->kisiCoz($payload);
        if (! $kisi) {
            $this->tamamla($token);
            throw new RuntimeException($this->gecersizMesaji());
        }

        return $kisi;
    }

    /**
     * Kodu kontrol eder ve doğruysa oturum verisini döner; oturumu kapatmaz.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    protected function kodKontrol(string $token, string $kod, ?Kisi $sahip = null): array
    {
        $payload = $this->payload($token, $sahip);
        $attempts = (int) $payload['attempts'];
        $cokDeneme = 'Çok fazla hatalı deneme yapıldı. '.$this->yenidenBaslatMesaji();

        if ($attempts >= self::MAX_OTP_ATTEMPTS) {
            $this->tamamla($token);
            throw new RuntimeException($cokDeneme);
        }

        $kod = preg_replace('/\D+/', '', $kod) ?? '';
        if ($kod === '' || ! Hash::check($kod, $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            $kalan = self::MAX_OTP_ATTEMPTS - $payload['attempts'];

            if ($kalan <= 0) {
                $this->tamamla($token);
                throw new RuntimeException($cokDeneme);
            }

            Cache::put($this->cacheKey($token), $payload, self::TTL_SECONDS);
            throw new RuntimeException("Doğrulama kodu hatalı. Kalan deneme: {$kalan}.");
        }

        return $payload;
    }

    /**
     * Kodu kontrol eder ve doğruysa oturumu kapatır.
     *
     * @throws RuntimeException
     */
    public function dogrula(string $token, string $kod, ?Kisi $sahip = null): Kisi
    {
        $kisi = $this->kontrol($token, $kod, $sahip);
        $this->tamamla($token);

        return $kisi;
    }

    public function tamamla(string $token): void
    {
        $payload = $token !== '' ? Cache::get($this->cacheKey($token)) : null;
        Cache::forget($this->cacheKey($token));

        $sahip = is_array($payload) ? ($payload['sahip'] ?? (string) ($payload['kisi_id'] ?? '')) : '';
        if ($sahip !== '' && Cache::get($this->kisiKey($sahip)) === $this->cacheKey($token)) {
            Cache::forget($this->kisiKey($sahip));
        }
    }

    /**
     * @return array{kisi_id: int|null, sahip?: string, kanallar: list<string>, ek?: array<string, mixed>, hash: string, attempts: int, sent_at: int}
     */
    private function payload(string $token, ?Kisi $sahip): array
    {
        $payload = $token !== '' ? Cache::get($this->cacheKey($token)) : null;
        if (! is_array($payload) || empty($payload['hash'])) {
            throw new RuntimeException('Doğrulama kodunun süresi doldu. '.$this->yenidenBaslatMesaji());
        }

        if ($sahip && (int) $payload['kisi_id'] !== (int) $sahip->id) {
            throw new RuntimeException($this->gecersizMesaji());
        }

        return $payload;
    }

    private function beklemeKontrol(int $sentAt): void
    {
        $kalan = self::RESEND_COOLDOWN_SECONDS - (now()->timestamp - $sentAt);
        if ($kalan > 0) {
            throw new RuntimeException("Yeni doğrulama kodu için {$kalan} saniye bekleyin.");
        }
    }

    /**
     * Kodu seçili kanallardan kişinin kayıtlı iletişim bilgisi olanlara gönderir.
     *
     * @param  list<string>  $kanallar
     * @return list<array{kanal: string, hedef: string}>
     */
    private function kodGonder(Kisi $kisi, array $kanallar, string $kod): array
    {
        $telefon = in_array('sms', $kanallar, true) ? PhoneNormalizer::normalize($kisi->telefon) : null;
        $email = in_array('eposta', $kanallar, true) ? trim((string) $kisi->email) : '';

        if (! $telefon && $email === '') {
            throw new RuntimeException($this->iletisimEksikMesaji($kanallar));
        }

        $hedefler = [];
        $hata = null;
        $dakika = $this->ttlDakika();
        $context = ['gonderen_id' => null, 'kapsam' => $this->gonderimKapsami()];

        if ($telefon) {
            $sonuc = $this->smsSender->send($telefon, $this->smsMetni($kod, $dakika), $context);
            ($sonuc['ok'] ?? false)
                ? $hedefler[] = ['kanal' => 'sms', 'hedef' => $this->telefonMaskele($telefon)]
                : $hata = $sonuc['message'] ?? null;
        }

        if ($email !== '') {
            $sonuc = $this->emailSender->send(
                $email,
                $this->epostaKonusu(),
                $this->epostaMetni($kisi, $kod, $dakika),
                [...$context, ...$this->epostaHtmlBaglami($kisi, $kod, $dakika)],
            );
            ($sonuc['ok'] ?? false)
                ? $hedefler[] = ['kanal' => 'eposta', 'hedef' => $this->epostaMaskele($email)]
                : $hata = $sonuc['message'] ?? $hata;
        }

        if ($hedefler === []) {
            throw new RuntimeException('Doğrulama kodu gönderilemedi. '.($hata ?: 'Lütfen daha sonra tekrar deneyin.'));
        }

        return $hedefler;
    }

    /**
     * @param  list<string>  $kanallar
     */
    private function iletisimEksikMesaji(array $kanallar): string
    {
        $bilgi = match (true) {
            in_array('sms', $kanallar, true) && in_array('eposta', $kanallar, true) => 'telefon numarası veya e-posta adresi',
            in_array('sms', $kanallar, true) => 'telefon numarası',
            default => 'e-posta adresi',
        };

        return "Doğrulama kodu gönderilebilecek kayıtlı bir {$bilgi} bulunamadı. Lütfen kurumla iletişime geçin.";
    }

    private function gecersizMesaji(): string
    {
        return 'Doğrulama oturumu geçersiz. '.$this->yenidenBaslatMesaji();
    }

    private function kodUret(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function cacheKey(string $token): string
    {
        return $this->anahtarOnEki().':'.hash('sha256', $token);
    }

    private function kisiKey(string $sahip): string
    {
        return $this->anahtarOnEki().'_kisi:'.$sahip;
    }

    private function ttlDakika(): int
    {
        return (int) ceil(self::TTL_SECONDS / 60);
    }

    private function telefonMaskele(string $telefon): string
    {
        $digits = preg_replace('/\D+/', '', $telefon) ?? '';

        return strlen($digits) < 4 ? '****' : str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }

    private function epostaMaskele(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return $domain === '' ? '***' : mb_substr($local, 0, 1).'***@'.$domain;
    }
}
