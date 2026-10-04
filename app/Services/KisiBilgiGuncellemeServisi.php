<?php

namespace App\Services;

use App\Enums\Cinsiyet;
use App\Models\Kisi;
use App\Services\Adres\AdresSorgulama;
use App\Services\Entegrasyon\EntegrasyonAyarServisi;
use App\Services\Kimlik\KimlikSorgulama;
use RuntimeException;

/**
 * Kişinin kimlik ve adres bilgilerini aktif entegrasyonlardan sorgulayıp kişi kaydına yazar.
 */
class KisiBilgiGuncellemeServisi
{
    private const KIMLIK_ALANLARI = [
        'ad' => 255,
        'soyad' => 255,
        'dogum_yeri' => 100,
        'medeni_durum' => 50,
        'uyruk' => 100,
        'anne_adi' => 100,
        'baba_adi' => 100,
    ];

    private const ADRES_ALANLARI = [
        'il' => 255,
        'ilce' => 255,
        'mahalle' => 150,
        'sokak' => 150,
        'kapi' => 30,
        'daire' => 30,
        'uavt_adres_no' => 20,
        'adres' => 65535,
    ];

    public const ETIKETLER = [
        'ad' => 'Ad',
        'soyad' => 'Soyad',
        'cinsiyet' => 'Cinsiyet',
        'dogum_yeri' => 'Doğum yeri',
        'medeni_durum' => 'Medeni durum',
        'uyruk' => 'Uyruk',
        'anne_adi' => 'Anne adı',
        'baba_adi' => 'Baba adı',
        'il' => 'İl',
        'ilce' => 'İlçe',
        'mahalle' => 'Mahalle',
        'sokak' => 'Sokak',
        'kapi' => 'Kapı',
        'daire' => 'Daire',
        'uavt_adres_no' => 'UAVT adres no',
        'adres' => 'Adres',
    ];

    public function __construct(
        private readonly EntegrasyonAyarServisi $entegrasyon,
    ) {}

    public function kimlikAktifMi(): bool
    {
        return $this->entegrasyon->turAktifMi('kimlik_sorgulama');
    }

    public function adresAktifMi(): bool
    {
        return $this->entegrasyon->turAktifMi('adres_sorgulama');
    }

    /**
     * Demo sağlayıcı sahte ad/soyad döndürdüğü için demo'da ad ve soyad güncellenmez.
     *
     * @return array<string, array{eski: mixed, yeni: mixed}> Değişen alanlar
     */
    public function kimlikGuncelle(Kisi $kisi): array
    {
        if (! $this->kimlikAktifMi()) {
            throw new RuntimeException('Kimlik sorgulama entegrasyonu pasif durumda.');
        }

        $sonuc = app(KimlikSorgulama::class)->sorgula($this->tc($kisi), $this->dogumTarihi($kisi));
        if (! ($sonuc['ok'] ?? false)) {
            throw new RuntimeException($sonuc['message'] ?? 'T.C. kimlik numarası ve doğum tarihi ile eşleşen kimlik kaydı bulunamadı.');
        }

        $alanlar = self::KIMLIK_ALANLARI;
        if ($this->entegrasyon->aktifSaglayiciKod('kimlik_sorgulama') === 'demo_kimlik') {
            unset($alanlar['ad'], $alanlar['soyad']);
        }

        $degerler = $this->metinAlanlari($sonuc, $alanlar);
        foreach (['ad', 'soyad'] as $alan) {
            if (isset($degerler[$alan])) {
                $degerler[$alan] = $this->buyukHarf($degerler[$alan]);
            }
        }
        if ($cinsiyet = $this->cinsiyet($sonuc['cinsiyet'] ?? null)) {
            $degerler['cinsiyet'] = $cinsiyet;
        }

        return $this->uygula($kisi, $degerler);
    }

    /**
     * @return array<string, array{eski: mixed, yeni: mixed}> Değişen alanlar
     */
    public function adresGuncelle(Kisi $kisi): array
    {
        if (! $this->adresAktifMi()) {
            throw new RuntimeException('Adres sorgulama entegrasyonu pasif durumda.');
        }

        $sonuc = app(AdresSorgulama::class)->sorgula($this->tc($kisi), $this->dogumTarihi($kisi));
        if (! ($sonuc['ok'] ?? false)) {
            throw new RuntimeException($sonuc['message'] ?? 'Kişiye ait adres kaydı bulunamadı.');
        }

        return $this->uygula($kisi, $this->metinAlanlari($sonuc, self::ADRES_ALANLARI));
    }

    private function tc(Kisi $kisi): string
    {
        if (! preg_match('/^\d{11}$/', (string) $kisi->tc_kimlik_no)) {
            throw new RuntimeException('Sorgulama için kişinin T.C. kimlik numarası kayıtlı olmalıdır.');
        }

        return (string) $kisi->tc_kimlik_no;
    }

    private function dogumTarihi(Kisi $kisi): string
    {
        if ($kisi->dogum_tarihi === null) {
            throw new RuntimeException('Sorgulama için kişinin doğum tarihi kayıtlı olmalıdır.');
        }

        return $kisi->dogum_tarihi->format('Y-m-d');
    }

    /**
     * @param  array<string, mixed>  $sonuc
     * @param  array<string, int>  $alanlar
     * @return array<string, string>
     */
    private function metinAlanlari(array $sonuc, array $alanlar): array
    {
        $degerler = [];
        foreach ($alanlar as $alan => $uzunluk) {
            $deger = is_scalar($sonuc[$alan] ?? null) ? trim((string) $sonuc[$alan]) : '';
            if ($deger !== '') {
                $degerler[$alan] = mb_substr($deger, 0, $uzunluk);
            }
        }

        return $degerler;
    }

    /**
     * @param  array<string, mixed>  $degerler
     * @return array<string, array{eski: mixed, yeni: mixed}>
     */
    private function uygula(Kisi $kisi, array $degerler): array
    {
        $kisi->fill($degerler);

        $degisen = [];
        foreach (array_keys($kisi->getDirty()) as $alan) {
            $eski = $kisi->getOriginal($alan);
            $yeni = $kisi->getAttribute($alan);
            $degisen[$alan] = [
                'eski' => $eski instanceof Cinsiyet ? $eski->value : $eski,
                'yeni' => $yeni instanceof Cinsiyet ? $yeni->value : $yeni,
            ];
        }

        if ($degisen !== []) {
            $kisi->save();
        }

        return $degisen;
    }

    private function cinsiyet(mixed $deger): ?Cinsiyet
    {
        if (! is_string($deger) || trim($deger) === '') {
            return null;
        }

        $deger = mb_strtolower(trim($deger));

        return Cinsiyet::tryFrom($deger) ?? match ($deger) {
            'e', 'erkek', 'male', 'm' => Cinsiyet::Erkek,
            'k', 'kadin', 'kadın', 'female', 'f' => Cinsiyet::Kadin,
            default => null,
        };
    }

    private function buyukHarf(string $deger): string
    {
        $deger = mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], trim($deger)), 'UTF-8');

        return preg_replace('/\s+/u', ' ', $deger) ?? $deger;
    }
}
