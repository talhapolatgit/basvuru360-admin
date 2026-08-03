<?php

namespace App\Services\Kimlik;

use Illuminate\Support\Facades\Log;

/**
 * Demo kimlik sorgulama — gerçek servise bağlanmaz.
 */
class DemoKimlikSorgulama implements KimlikSorgulama
{
    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array
    {
        Log::channel('single')->info('Demo kimlik sorgulama', [
            'tc_kimlik_no' => $tcKimlikNo,
            'dogum_tarihi' => $dogumTarihi,
        ]);

        // Son hane tekse kadın, çiftse erkek (demo tutarlılığı için).
        $sonHane = (int) substr(preg_replace('/\D/', '', $tcKimlikNo) ?: '0', -1);
        $cinsiyet = $sonHane % 2 === 1 ? 'kadin' : 'erkek';

        return [
            'ok' => true,
            'ad' => $cinsiyet === 'kadin' ? 'Ayşe' : 'Mehmet',
            'soyad' => 'Demo',
            'cinsiyet' => $cinsiyet,
            'dogum_yeri' => 'İstanbul',
            'medeni_durum' => 'Bekar',
            'uyruk' => 'T.C.',
            'anne_adi' => 'Fatma',
            'baba_adi' => 'Ali',
            'dogum_tarihi' => $dogumTarihi,
            'message' => 'demo',
        ];
    }
}
