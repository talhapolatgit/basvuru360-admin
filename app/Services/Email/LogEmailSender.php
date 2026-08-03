<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Log;

class LogEmailSender implements EmailSender
{
    public function send(string $email, string $konu, string $mesaj, array $context = []): array
    {
        Log::channel('single')->info('E-posta gönderildi', [
            'email' => $email,
            'konu' => $konu,
            'mesaj' => $mesaj,
            'context' => $context,
        ]);

        return ['ok' => true, 'message' => 'log'];
    }
}
