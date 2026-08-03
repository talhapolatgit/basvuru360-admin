<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GmailApiEmailSender implements EmailSender
{
    /**
     * @param  array<string, mixed>  $ayarlar
     */
    public function __construct(
        private readonly array $ayarlar,
    ) {}

    public function send(string $email, string $konu, string $mesaj, array $context = []): array
    {
        $clientId = trim((string) ($this->ayarlar['client_id'] ?? ''));
        $clientSecret = trim((string) ($this->ayarlar['client_secret'] ?? ''));
        $refreshToken = trim((string) ($this->ayarlar['refresh_token'] ?? ''));
        $fromAddress = trim((string) ($this->ayarlar['from_address'] ?? ''));
        $fromName = trim((string) ($this->ayarlar['from_name'] ?? ''));

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '' || $fromAddress === '') {
            return ['ok' => false, 'message' => 'Gmail API entegrasyon ayarları eksik.'];
        }

        try {
            $accessToken = $this->accessToken($clientId, $clientSecret, $refreshToken);
            $raw = $this->rawMessage($fromAddress, $fromName, $email, $konu, $mesaj);

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                    'raw' => $raw,
                ]);

            if (! $response->successful()) {
                $hata = $response->json('error.message')
                    ?? $response->body()
                    ?: 'Gmail API isteği başarısız.';

                return ['ok' => false, 'message' => (string) $hata];
            }

            return ['ok' => true, 'message' => 'gmail_api'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function accessToken(string $clientId, string $clientSecret, string $refreshToken): string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $hata = $response->json('error_description')
                ?? $response->json('error')
                ?? 'Gmail OAuth token alınamadı.';

            throw new RuntimeException((string) $hata);
        }

        $token = (string) ($response->json('access_token') ?? '');

        if ($token === '') {
            throw new RuntimeException('Gmail OAuth access token boş döndü.');
        }

        return $token;
    }

    private function rawMessage(
        string $fromAddress,
        string $fromName,
        string $to,
        string $konu,
        string $mesaj,
    ): string {
        $from = $fromName !== ''
            ? sprintf('%s <%s>', $this->encodeHeader($fromName), $fromAddress)
            : $fromAddress;

        $headers = [
            'From: '.$from,
            'To: '.$to,
            'Subject: '.$this->encodeHeader($konu),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];

        $mime = implode("\r\n", $headers)."\r\n\r\n".chunk_split(base64_encode($mesaj));

        return rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
    }

    private function encodeHeader(string $value): string
    {
        if ($value === '' || preg_match('/^[\x20-\x7E]*$/', $value) === 1) {
            return $value;
        }

        return '=?UTF-8?B?'.base64_encode($value).'?=';
    }
}
