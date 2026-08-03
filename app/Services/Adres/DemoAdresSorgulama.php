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
            'adres' => 'Caferağa Mah. Moda Cad. No:12 D:3',
            'message' => 'demo',
        ];
    }
}
