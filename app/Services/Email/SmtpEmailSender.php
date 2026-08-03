<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SmtpEmailSender implements EmailSender
{
    /**
     * @param  array<string, mixed>  $ayarlar
     */
    public function __construct(
        private readonly array $ayarlar,
    ) {}

    public function send(string $email, string $konu, string $mesaj, array $context = []): array
    {
        $host = trim((string) ($this->ayarlar['host'] ?? ''));
        $port = (int) ($this->ayarlar['port'] ?? 0);
        $fromAddress = trim((string) ($this->ayarlar['from_address'] ?? ''));

        if ($host === '' || $port <= 0 || $fromAddress === '') {
            return ['ok' => false, 'message' => 'SMTP entegrasyon ayarları eksik. Host, port ve gönderen e-posta zorunludur.'];
        }

        $encryption = $this->ayarlar['encryption'] ?? 'tls';
        if ($encryption === '') {
            $encryption = null;
        }

        $fromName = trim((string) ($this->ayarlar['from_name'] ?? ''));
        $username = trim((string) ($this->ayarlar['username'] ?? ''));
        $password = (string) ($this->ayarlar['password'] ?? '');

        Config::set('mail.mailers.entegrasyon_smtp', [
            'transport' => 'smtp',
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'username' => $username !== '' ? $username : null,
            'password' => $password !== '' ? $password : null,
            'timeout' => null,
        ]);

        try {
            Mail::mailer('entegrasyon_smtp')->raw($mesaj, function ($message) use ($email, $konu, $fromAddress, $fromName) {
                $message->to($email)->subject($konu)->from(
                    $fromAddress,
                    $fromName !== '' ? $fromName : null
                );
            });

            return ['ok' => true, 'message' => 'smtp'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
