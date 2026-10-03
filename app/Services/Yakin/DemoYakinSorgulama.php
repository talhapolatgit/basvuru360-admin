<?php

namespace App\Services\Yakin;

use Illuminate\Support\Facades\Log;

/**
 * Demo yakın sorgulama — gerçek servise bağlanmaz.
 */
class DemoYakinSorgulama implements YakinSorgulama
{
    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array
    {
        Log::channel('single')->info('Demo yakın sorgulama', [
            'tc_kimlik_no' => $tcKimlikNo,
            'dogum_tarihi' => $dogumTarihi,
        ]);

        $kisi = fn (string $kod, string $yakinlik, string $tc, string $ad, string $cinsiyet, string $dogum, string $medeni) => [
            'yakinlik_kodu' => $kod,
            'yakinlik' => $yakinlik,
            'tc_kimlik_no' => $tc,
            'ad' => $ad,
            'soyad' => 'DEMO',
            'cinsiyet' => $cinsiyet,
            'dogum_tarihi' => $dogum,
            'dogum_yeri' => 'İSTANBUL',
            'medeni_durum' => $medeni,
            'anne_adi' => null,
            'baba_adi' => null,
            'olum_tarihi' => null,
        ];

        return [
            'ok' => true,
            'yakinlar' => [
                $kisi('ESI', 'Eşi', '10000000146', 'AYŞE', 'kadin', '1988-03-12', 'Evli'),
                $kisi('OGLU', 'Oğlu', '10000000210', 'MEHMET', 'erkek', '2015-06-01', 'Bekar'),
                $kisi('KIZI', 'Kızı', '10000000384', 'ZEYNEP', 'kadin', '2018-09-20', 'Bekar'),
            ],
            'message' => 'demo',
        ];
    }
}
