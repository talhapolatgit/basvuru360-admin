<?php

namespace App\Services\Adres;

use Illuminate\Support\Facades\Log;

/**
 * Demo adres sorgulama — gerçek servise bağlanmaz.
 */
class DemoAdresSorgulama implements AdresSorgulama
{
    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array
    {
        Log::channel('single')->info('Demo adres sorgulama', [
            'tc_kimlik_no' => $tcKimlikNo,
            'dogum_tarihi' => $dogumTarihi,
        ]);

        return [
            'ok' => true,
            'il' => 'İstanbul',
            'ilce' => 'Kadıköy',
            'mahalle' => 'Caferağa',
            'sokak' => 'Moda Cad.',
            'kapi' => '12',
            'daire' => '3',
            'uavt_adres_no' => '1234567890',
            'adres' => 'Caferağa Mah. Moda Cad. No:12 D:3 Kadıköy/İstanbul',
            'message' => 'demo',
        ];
    }
}
