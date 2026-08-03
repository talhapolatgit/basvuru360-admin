<?php

namespace App\Services\Entegrasyon;

use App\Services\Adres\AdresSorgulama;
use App\Services\Adres\DemoAdresSorgulama;
use App\Services\Email\DemoEmailSender;
use App\Services\Email\EmailSender;
use App\Services\Email\GmailApiEmailSender;
use App\Services\Email\LogEmailSender;
use App\Services\Email\SmtpEmailSender;
use App\Services\Kimlik\DemoKimlikSorgulama;
use App\Services\Kimlik\KimlikSorgulama;
use App\Services\Sms\DemoSmsSender;
use App\Services\Sms\HttpSmsSender;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use RuntimeException;

/**
 * Aktif entegrasyon sağlayıcısına göre servis örneği üretir.
 * Yeni sağlayıcı eklendiğinde buraya eşleme eklenir.
 */
class EntegrasyonCozumleyici
{
    public function __construct(
        private readonly EntegrasyonAyarServisi $ayarlar,
    ) {}

    public function smsSender(): SmsSender
    {
        $this->assertTurAktif('sms', 'SMS');

        return match ($this->ayarlar->aktifSaglayiciKod('sms')) {
            'demo_sms' => new DemoSmsSender,
            // İleride eklenecek sağlayıcı örnekleri:
            // 'http_sms' => new HttpSmsSender,
            default => $this->smsFallback(),
        };
    }

    public function emailSender(): EmailSender
    {
        $this->assertTurAktif('eposta', 'E-posta');

        return match ($this->ayarlar->aktifSaglayiciKod('eposta')) {
            'demo_eposta' => new DemoEmailSender,
            'smtp' => new SmtpEmailSender($this->ayarlar->saglayiciAyarlari('eposta', 'smtp')),
            'gmail_api' => new GmailApiEmailSender($this->ayarlar->saglayiciAyarlari('eposta', 'gmail_api')),
            default => $this->emailFallback(),
        };
    }

    public function kimlikSorgulama(): KimlikSorgulama
    {
        $this->assertTurAktif('kimlik_sorgulama', 'Kimlik sorgulama');

        return match ($this->ayarlar->aktifSaglayiciKod('kimlik_sorgulama')) {
            'demo_kimlik' => new DemoKimlikSorgulama,
            default => throw new RuntimeException('Aktif kimlik sorgulama sağlayıcısı bulunamadı.'),
        };
    }

    public function adresSorgulama(): AdresSorgulama
    {
        $this->assertTurAktif('adres_sorgulama', 'Adres sorgulama');

        return match ($this->ayarlar->aktifSaglayiciKod('adres_sorgulama')) {
            'demo_adres' => new DemoAdresSorgulama,
            default => throw new RuntimeException('Aktif adres sorgulama sağlayıcısı bulunamadı.'),
        };
    }

    private function assertTurAktif(string $tur, string $etiket): void
    {
        if (! $this->ayarlar->turAktifMi($tur)) {
            throw new RuntimeException("{$etiket} entegrasyonu pasif durumda.");
        }
    }

    private function smsFallback(): SmsSender
    {
        // Geçici uyumluluk: env driver hâlâ http ise onu kullan.
        return match (config('services.sms.driver', 'log')) {
            'http' => new HttpSmsSender,
            default => new LogSmsSender,
        };
    }

    private function emailFallback(): EmailSender
    {
        // Geçici uyumluluk: eski EMAIL_DRIVER env ayarı.
        return match (config('services.email.driver', 'log')) {
            'mail' => new SmtpEmailSender([
                'host' => (string) config('mail.mailers.smtp.host', ''),
                'port' => (int) config('mail.mailers.smtp.port', 587),
                'encryption' => (string) (config('mail.mailers.smtp.encryption') ?? 'tls'),
                'username' => (string) config('mail.mailers.smtp.username', ''),
                'password' => (string) config('mail.mailers.smtp.password', ''),
                'from_address' => (string) config('mail.from.address', ''),
                'from_name' => (string) config('mail.from.name', ''),
            ]),
            default => new LogEmailSender,
        };
    }
}
