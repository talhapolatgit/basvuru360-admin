<?php

namespace App\Services\Email;

/**
 * Gmail SMTP sunucusu (smtp.gmail.com) ve Google uygulama şifresi ile e-posta gönderir.
 */
class GmailEmailSender implements EmailSender
{
    /**
     * @param  array<string, mixed>  $ayarlar
     */
    public function __construct(
        private readonly array $ayarlar,
    ) {}

    public function send(string $email, string $konu, string $mesaj, array $context = []): array
    {
        $adres = trim((string) ($this->ayarlar['email'] ?? ''));
        // Google uygulama şifresini "abcd efgh ijkl mnop" gibi boşluklu gösterir.
        $sifre = preg_replace('/\s+/', '', (string) ($this->ayarlar['app_password'] ?? '')) ?? '';

        if ($adres === '' || $sifre === '') {
            return ['ok' => false, 'message' => 'Gmail entegrasyon ayarları eksik. Gmail adresi ve uygulama şifresi zorunludur.'];
        }

        $sonuc = (new SmtpEmailSender([
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => $adres,
            'password' => $sifre,
            'from_address' => $adres,
            'from_name' => $this->ayarlar['from_name'] ?? '',
        ]))->send($email, $konu, $mesaj, $context);

        if ($sonuc['ok'] ?? false) {
            return ['ok' => true, 'message' => 'gmail'];
        }

        $hata = (string) ($sonuc['message'] ?? '');
        if (str_contains($hata, '535') || str_contains($hata, 'BadCredentials') || str_contains($hata, 'Username and Password not accepted')) {
            return ['ok' => false, 'message' => 'Gmail giriş bilgileri kabul edilmedi. Gmail adresini ve uygulama şifresini kontrol edin (normal hesap şifresi çalışmaz).'];
        }

        return $sonuc;
    }
}
