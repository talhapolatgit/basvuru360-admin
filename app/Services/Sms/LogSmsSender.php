<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    public function send(string $telefon, string $mesaj, array $context = []): array
    {
        Log::channel('single')->info('SMS gönderildi', [
            'telefon' => $telefon,
            'mesaj' => $mesaj,
            'context' => $context,
        ]);

        return ['ok' => true, 'message' => 'log'];
    }
}
