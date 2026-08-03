<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Demo SMS sağlayıcısı — gerçek SMS göndermez, loga yazar.
 */
class DemoSmsSender implements SmsSender
{
    public function send(string $telefon, string $mesaj, array $context = []): array
    {
        Log::channel('single')->info('Demo SMS entegrasyonu', [
            'telefon' => $telefon,
            'mesaj' => $mesaj,
            'context' => $context,
        ]);

        return ['ok' => true, 'message' => 'demo'];
    }
}
