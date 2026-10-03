<?php

namespace App\Services\Adres;

use App\Services\Flexcity\FlexcityIstemcisi;

/**
 * Flexcity NVİ servisi (FindAllBaseAdresDto) üzerinden adres sorgulama.
 */
class FlexcityAdresSorgulama implements AdresSorgulama
{
    private readonly FlexcityIstemcisi $istemci;

    /**
     * @param  array<string, mixed>  $ayarlar
     */
    public function __construct(array $ayarlar)
    {
        $this->istemci = new FlexcityIstemcisi($ayarlar, 'Adres');
    }

    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array
    {
        if (! $dogumTarihi) {
            return ['ok' => false, 'message' => 'Adres sorgulama için doğum tarihi zorunludur.'];
        }

        $json = $this->istemci->sorgula($tcKimlikNo, $dogumTarihi);

        $liste = array_values(array_filter(
            is_array($json['baseAdresDtoList'] ?? null) ? $json['baseAdresDtoList'] : [],
            'is_array',
        ));

        if (! ($json['success'] ?? false) || $liste === []) {
            return [
                'ok' => false,
                'message' => FlexcityIstemcisi::servisMesaji($json)
                    ?? 'T.C. kimlik numarası ve doğum tarihi ile kayıtlı adres bulunamadı.',
            ];
        }

        $kayit = $this->adresSec($liste);
        $mahalle = $this->metin($kayit['mahalleAdi'] ?? null);
        $il = $this->metin($kayit['ilAdi'] ?? null);
        $ilce = $this->metin($kayit['ilceAdi'] ?? null);

        return [
            'ok' => true,
            'il' => $il,
            'ilce' => $ilce,
            'mahalle' => $mahalle,
            'sokak' => $this->metin($kayit['sokakAdi'] ?? null),
            'kapi' => $this->metin($kayit['kapi'] ?? null),
            'daire' => $this->metin($kayit['daire'] ?? null),
            'uavt_adres_no' => $this->metin($kayit['uavtAdresNo'] ?? null),
            'adres' => $this->ilIlceEkle(
                $this->metin($kayit['acikAdres'] ?? null) ?? $this->adresMetni($kayit, $mahalle),
                $ilce,
                $il,
            ),
        ];
    }

    /** Adresin sonuna "İLÇE/İL" ekler; açık adreste zaten varsa tekrarlamaz. */
    private function ilIlceEkle(?string $adres, ?string $ilce, ?string $il): ?string
    {
        $parcalar = array_filter([$ilce, $il]);
        $konum = implode('/', $parcalar);
        if ($konum === '') {
            return $adres;
        }
        if ($adres === null) {
            return $konum;
        }

        $buyuk = fn (string $s) => mb_strtoupper($s, 'UTF-8');
        $eksik = array_filter($parcalar, fn (string $parca) => ! str_contains($buyuk($adres), $buyuk($parca)));

        return $eksik === [] ? $adres : $adres.' '.$konum;
    }

    /**
     * Kullanımdaki yazışma adresi > kullanımdaki ikametgah > kullanımdaki herhangi biri > ilk kayıt.
     *
     * @param  list<array<string, mixed>>  $liste
     * @return array<string, mixed>
     */
    private function adresSec(array $liste): array
    {
        $kullanimda = array_values(array_filter($liste, fn (array $a) => ($a['adresDurumu'] ?? null) === 'KULLANIMDA'));

        foreach ([
            fn (array $a) => ($a['yazismaAdresi'] ?? null) === 'EVET',
            fn (array $a) => ($a['adresTuru'] ?? null) === 'IKAMETGAH',
        ] as $kosul) {
            foreach ($kullanimda as $adres) {
                if ($kosul($adres)) {
                    return $adres;
                }
            }
        }

        return $kullanimda[0] ?? $liste[0];
    }

    /**
     * @param  array<string, mixed>  $kayit
     */
    private function adresMetni(array $kayit, ?string $mahalle): ?string
    {
        $parcalar = array_filter([
            $mahalle,
            $this->metin($kayit['sokakAdi'] ?? null),
            $this->metin($kayit['binaAdi'] ?? null),
            $this->etiketli('Blok:', $kayit['blok'] ?? null),
            $this->etiketli('No:', $kayit['kapi'] ?? null),
            $this->etiketli('Kat:', $kayit['kat'] ?? null),
            $this->etiketli('D:', $kayit['daire'] ?? null),
        ]);

        return $parcalar === [] ? null : implode(' ', $parcalar);
    }

    private function etiketli(string $etiket, mixed $deger): ?string
    {
        $metin = $this->metin($deger);

        return $metin === null ? null : $etiket.$metin;
    }

    private function metin(mixed $deger): ?string
    {
        if (! is_scalar($deger)) {
            return null;
        }

        $metin = trim(preg_replace('/\s+/u', ' ', (string) $deger) ?? '');

        return $metin === '' ? null : $metin;
    }
}
