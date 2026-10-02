<?php

namespace App\Services\Kimlik;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Flexcity SBS servisi (FindSbsKisiDtoByNvi) üzerinden NVİ kimlik sorgulama.
 */
class FlexcityKimlikSorgulama implements KimlikSorgulama
{
    private const VARSAYILAN_TIMEOUT = 30;

    private const MEDENI_HAL = [
        'BEKAR' => 'Bekar',
        'EVLI' => 'Evli',
        'BOSANMIS' => 'Boşanmış',
        'DUL' => 'Dul',
        'ESI_OLMUS' => 'Eşi ölmüş',
    ];

    /**
     * @param  array<string, mixed>  $ayarlar
     */
    public function __construct(
        private readonly array $ayarlar,
    ) {}

    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array
    {
        $adres = trim((string) ($this->ayarlar['adres'] ?? ''));
        $authorization = trim((string) ($this->ayarlar['authorization'] ?? ''));

        if ($adres === '' || $authorization === '') {
            throw new RuntimeException('Flexcity entegrasyon ayarları eksik. Servis adresi ve Authorization değeri zorunludur.');
        }

        if (! $dogumTarihi) {
            return ['ok' => false, 'message' => 'Kimlik sorgulama için doğum tarihi zorunludur.'];
        }

        $timeout = $this->timeout();

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min($timeout, 10))
                ->accept('*/*')
                ->withHeaders(['Authorization' => $authorization])
                ->withBody(http_build_query([
                    'dogumTarihi' => $this->servisTarihi($dogumTarihi),
                    'tcKimlikNo' => $tcKimlikNo,
                ]), 'application/json')
                ->post($adres);
        } catch (ConnectionException $e) {
            if (preg_match('/cURL error (35|51|58|59|60|77|83)\b/', $e->getMessage())) {
                throw new RuntimeException('Kimlik servisinin SSL sertifikası doğrulanamadı. Sunucudaki PHP CA sertifika paketini (curl.cainfo) kontrol edin.');
            }

            if (str_contains($e->getMessage(), 'cURL error 28')) {
                throw new RuntimeException("Kimlik servisi {$timeout} saniye içinde yanıt vermedi (zaman aşımı). Lütfen tekrar deneyin.");
            }

            throw new RuntimeException('Kimlik servisine şu anda ulaşılamıyor. Lütfen daha sonra tekrar deneyin.');
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException('Kimlik servisi yetkilendirmesi başarısız. Flexcity Authorization değerini kontrol edin.');
        }

        if ($response->failed()) {
            $detay = $this->servisMesaji($response->json());

            throw new RuntimeException("Kimlik servisi hata döndürdü (HTTP {$response->status()})".($detay ? ": {$detay}" : '. Lütfen daha sonra tekrar deneyin.'));
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Kimlik servisinden geçersiz yanıt alındı.');
        }

        $kisi = $json['sbsKisiDto'] ?? null;
        if (! ($json['success'] ?? false) || ! is_array($kisi) || blank($kisi['adi'] ?? null)) {
            return [
                'ok' => false,
                'message' => $this->servisMesaji($json)
                    ?? 'T.C. kimlik numarası ve doğum tarihi ile eşleşen kimlik kaydı bulunamadı.',
            ];
        }

        return [
            'ok' => true,
            'ad' => trim((string) $kisi['adi']),
            'soyad' => trim((string) ($kisi['soyadi'] ?? '')),
            'cinsiyet' => $this->cinsiyet($kisi['cinsiyet'] ?? null),
            'dogum_yeri' => $this->metin($kisi['dogumYeri'] ?? null),
            'medeni_durum' => $this->medeniHal($kisi['medeniHali'] ?? null),
            'uyruk' => $this->metin($kisi['absUlkeAdi'] ?? null)
                ?? ((int) ($kisi['absUlkeId'] ?? 0) === 1 ? 'T.C.' : null),
            'anne_adi' => $this->metin($kisi['anneAdi'] ?? null),
            'baba_adi' => $this->metin($kisi['babaAdi'] ?? null),
            'dogum_tarihi' => $this->tarih($kisi['dogumTarihi'] ?? null) ?? $dogumTarihi,
        ];
    }

    /** Servis tarihi gece yarısı + saat dilimi farkıyla bekler: 1983-04-16T00:00:00+03:00 */
    private function servisTarihi(string $dogumTarihi): string
    {
        return CarbonImmutable::parse($dogumTarihi)->format('Y-m-d').'T00:00:00+03:00';
    }

    private function tarih(mixed $deger): ?string
    {
        if (! is_string($deger) || $deger === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($deger)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function cinsiyet(mixed $deger): ?string
    {
        return match (mb_strtoupper(trim((string) $deger), 'UTF-8')) {
            'ERKEK', 'E' => 'erkek',
            'KADIN', 'K' => 'kadin',
            default => null,
        };
    }

    private function medeniHal(mixed $deger): ?string
    {
        $kod = mb_strtoupper(trim((string) $deger), 'UTF-8');

        return $kod === '' ? null : (self::MEDENI_HAL[$kod] ?? $kod);
    }

    private function metin(mixed $deger): ?string
    {
        $metin = trim((string) $deger);

        return $metin === '' ? null : $metin;
    }

    private function servisMesaji(mixed $json): ?string
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

    private function timeout(): int
    {
        $timeout = (int) ($this->ayarlar['timeout'] ?? 0);

        return $timeout > 0 ? $timeout : self::VARSAYILAN_TIMEOUT;
    }
}
