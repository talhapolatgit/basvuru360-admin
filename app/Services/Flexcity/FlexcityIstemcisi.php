<?php

namespace App\Services\Flexcity;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Flexcity REST servislerine istek atar.
 */
class FlexcityIstemcisi
{
    private const VARSAYILAN_TIMEOUT = 30;

    /**
     * @param  array<string, mixed>  $ayarlar  adres, authorization, timeout
     * @param  string  $servis  Hata mesajlarında kullanılan servis adı (ör. "Kimlik", "Adres", "SMS")
     */
    public function __construct(
        private readonly array $ayarlar,
        private readonly string $servis,
    ) {}

    /**
     * T.C. kimlik no + doğum tarihi ile sorgulayan servisler için.
     *
     * @return array<string, mixed>
     */
    public function sorgula(string $tcKimlikNo, string $dogumTarihi): array
    {
        return $this->post([
            'dogumTarihi' => $this->servisTarihi($dogumTarihi),
            'tcKimlikNo' => $tcKimlikNo,
        ]);
    }

    /**
     * Form-urlencoded gövde ile POST (Content-Type: application/json — Flexcity sözleşmesi).
     *
     * @param  array<string, scalar|null>  $govde
     * @return array<string, mixed>
     */
    public function post(array $govde): array
    {
        return $this->postHam(http_build_query($govde));
    }

    /**
     * Encode edilmemiş ham gövde ile POST (SMS hizliGonder gibi servisler).
     *
     * @return array<string, mixed>
     */
    public function postHam(string $govde): array
    {
        $adres = trim((string) ($this->ayarlar['adres'] ?? ''));
        $authorization = trim((string) ($this->ayarlar['authorization'] ?? ''));

        if ($adres === '' || $authorization === '') {
            throw new RuntimeException('Flexcity entegrasyon ayarları eksik. Servis adresi ve Authorization değeri zorunludur.');
        }

        $timeout = $this->timeout();

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min($timeout, 10))
                ->accept('*/*')
                ->withHeaders(['Authorization' => $authorization])
                ->withBody($govde, 'application/json')
                ->post($adres);
        } catch (ConnectionException $e) {
            if (preg_match('/cURL error (35|51|58|59|60|77|83)\b/', $e->getMessage())) {
                throw new RuntimeException("{$this->servis} servisinin SSL sertifikası doğrulanamadı. Sunucudaki PHP CA sertifika paketini (curl.cainfo) kontrol edin.");
            }

            if (str_contains($e->getMessage(), 'cURL error 28')) {
                throw new RuntimeException("{$this->servis} servisi {$timeout} saniye içinde yanıt vermedi (zaman aşımı). Lütfen tekrar deneyin.");
            }

            throw new RuntimeException("{$this->servis} servisine şu anda ulaşılamıyor. Lütfen daha sonra tekrar deneyin.");
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException("{$this->servis} servisi yetkilendirmesi başarısız. Flexcity Authorization değerini kontrol edin.");
        }

        if ($response->failed()) {
            $detay = self::servisMesaji($response->json());

            throw new RuntimeException("{$this->servis} servisi hata döndürdü (HTTP {$response->status()})".($detay ? ": {$detay}" : '. Lütfen daha sonra tekrar deneyin.'));
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException("{$this->servis} servisinden geçersiz yanıt alındı.");
        }

        return $json;
    }

    public static function servisMesaji(mixed $json): ?string
    {
        if (! is_array($json)) {
            return null;
        }

        foreach (['resultMessage', 'message', 'errorMessage', 'hata'] as $anahtar) {
            if (is_string($json[$anahtar] ?? null) && trim($json[$anahtar]) !== '') {
                return trim($json[$anahtar]);
            }
        }

        return null;
    }

    /** Servis tarihi gece yarısı + saat dilimi farkıyla bekler: 1983-04-16T00:00:00+03:00 */
    private function servisTarihi(string $dogumTarihi): string
    {
        return CarbonImmutable::parse($dogumTarihi)->format('Y-m-d').'T00:00:00+03:00';
    }

    private function timeout(): int
    {
        $timeout = (int) ($this->ayarlar['timeout'] ?? 0);

        return $timeout > 0 ? $timeout : self::VARSAYILAN_TIMEOUT;
    }
}
