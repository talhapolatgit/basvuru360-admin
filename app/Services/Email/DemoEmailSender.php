<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Log;

/**
 * Demo e-posta sağlayıcısı — gerçek e-posta göndermez, loga yazar.
 */
class DemoEmailSender implements EmailSender
{
    public function send(string $email, string $konu, string $mesaj, array $context = []): array
    {
        Log::channel('single')->info('Demo e-posta entegrasyonu', [
            'email' => $email,
            'konu' => $konu,
            'mesaj' => $mesaj,
            'context' => $context,
        ]);

        return ['ok' => true, 'message' => 'demo'];
    }
}
