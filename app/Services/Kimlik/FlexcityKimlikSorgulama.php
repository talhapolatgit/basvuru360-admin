<?php

namespace App\Services\Kimlik;

use App\Services\Flexcity\FlexcityAlanDonusumleri;
use App\Services\Flexcity\FlexcityIstemcisi;

/**
 * Flexcity SBS servisi (FindSbsKisiDtoByNvi) üzerinden NVİ kimlik sorgulama.
 */
class FlexcityKimlikSorgulama implements KimlikSorgulama
{
    use FlexcityAlanDonusumleri;

    private readonly FlexcityIstemcisi $istemci;

    /**
     * @param  array<string, mixed>  $ayarlar
     */
    public function __construct(array $ayarlar)
    {
        $this->istemci = new FlexcityIstemcisi($ayarlar, 'Kimlik');
    }

    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array
    {
        if (! $dogumTarihi) {
            return ['ok' => false, 'message' => 'Kimlik sorgulama için doğum tarihi zorunludur.'];
        }

        $json = $this->istemci->sorgula($tcKimlikNo, $dogumTarihi);

        $kisi = $json['sbsKisiDto'] ?? null;
        if (! ($json['success'] ?? false) || ! is_array($kisi) || blank($kisi['adi'] ?? null)) {
            return [
                'ok' => false,
                'message' => FlexcityIstemcisi::servisMesaji($json)
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
}
