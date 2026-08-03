<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Mail;
use Throwable;

class LaravelEmailSender implements EmailSender
{
    public function send(string $email, string $konu, string $mesaj, array $context = []): array
    {
        try {
            Mail::raw($mesaj, function ($message) use ($email, $konu) {
                $message->to($email)->subject($konu);
            });

            return ['ok' => true, 'message' => 'mail'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
