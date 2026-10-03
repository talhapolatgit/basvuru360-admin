<?php

namespace App\Services\Yakin;

use App\Services\Flexcity\FlexcityAlanDonusumleri;
use App\Services\Flexcity\FlexcityIstemcisi;

/**
 * Flexcity SBS servisi (FindAllSbsKisiAileBireyleriByNvi) üzerinden NVİ aile bireyleri sorgulama.
 */
class FlexcityYakinSorgulama implements YakinSorgulama
{
    use FlexcityAlanDonusumleri;

    private const YAKINLIK = [
        'ESI' => 'Eşi',
        'OGLU' => 'Oğlu',
        'KIZI' => 'Kızı',
        'ANNESI' => 'Annesi',
        'BABASI' => 'Babası',
    ];

    private readonly FlexcityIstemcisi $istemci;

    /**
     * @param  array<string, mixed>  $ayarlar
     */
    public function __construct(array $ayarlar)
    {
        $this->istemci = new FlexcityIstemcisi($ayarlar, 'Yakın');
    }

    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array
    {
        if (! $dogumTarihi) {
            return ['ok' => false, 'message' => 'Yakın sorgulama için doğum tarihi zorunludur.'];
        }

        $json = $this->istemci->sorgula($tcKimlikNo, $dogumTarihi);
        $liste = is_array($json['sbsKisiDtoList'] ?? null) ? $json['sbsKisiDtoList'] : null;

        if (! ($json['success'] ?? false) || $liste === null) {
            return [
                'ok' => false,
                'message' => FlexcityIstemcisi::servisMesaji($json)
                    ?? 'T.C. kimlik numarası ve doğum tarihi ile eşleşen kayıt bulunamadı.',
            ];
        }

        $yakinlar = [];
        foreach ($liste as $kisi) {
            if (! is_array($kisi)) {
                continue;
            }

            $kod = mb_strtoupper(trim((string) ($kisi['sbsYakinlikDerecesi'] ?? '')), 'UTF-8');
            if ($kod === 'KENDISI' || (string) ($kisi['tcKimlikNo'] ?? '') === $tcKimlikNo) {
                continue;
            }

            $yakinlar[] = [
                'yakinlik_kodu' => $kod !== '' ? $kod : null,
                'yakinlik' => self::YAKINLIK[$kod] ?? ($kod !== '' ? $kod : null),
                'tc_kimlik_no' => $this->metin($kisi['tcKimlikNo'] ?? null),
                'ad' => $this->metin($kisi['adi'] ?? null),
                'soyad' => $this->metin($kisi['soyadi'] ?? null),
                'cinsiyet' => $this->cinsiyet($kisi['cinsiyet'] ?? null),
                'dogum_tarihi' => $this->tarih($kisi['dogumTarihi'] ?? null),
                'dogum_yeri' => $this->metin($kisi['dogumYeri'] ?? null),
                'medeni_durum' => $this->medeniHal($kisi['medeniHali'] ?? null),
                'anne_adi' => $this->metin($kisi['anneAdi'] ?? null),
                'baba_adi' => $this->metin($kisi['babaAdi'] ?? null),
                'olum_tarihi' => $this->tarih($kisi['olumTarihi'] ?? null),
            ];
        }

        return ['ok' => true, 'yakinlar' => $yakinlar];
    }
}
