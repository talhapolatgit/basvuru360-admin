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
use App\Services\Kimlik\FlexcityKimlikSorgulama;
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

        return $this->ornek('sms', $this->ayarlar->aktifSaglayiciKod('sms'));
    }

    public function emailSender(): EmailSender
    {
        $this->assertTurAktif('eposta', 'E-posta');

        return $this->ornek('eposta', $this->ayarlar->aktifSaglayiciKod('eposta'));
    }

    public function kimlikSorgulama(): KimlikSorgulama
    {
        $this->assertTurAktif('kimlik_sorgulama', 'Kimlik sorgulama');

        return $this->ornek('kimlik_sorgulama', $this->ayarlar->aktifSaglayiciKod('kimlik_sorgulama'));
    }

    public function adresSorgulama(): AdresSorgulama
    {
        $this->assertTurAktif('adres_sorgulama', 'Adres sorgulama');

        return $this->ornek('adres_sorgulama', $this->ayarlar->aktifSaglayiciKod('adres_sorgulama'));
    }

    /**
     * Sağlayıcı örneğini aktiflik kontrolü yapmadan üretir. Ayarlar verilmezse kayıtlı ayarlar kullanılır.
     *
     * @param  array<string, mixed>|null  $ayarlar
     */
    public function ornek(string $tur, string $kod, ?array $ayarlar = null): SmsSender|EmailSender|KimlikSorgulama|AdresSorgulama
    {
        $ayar = fn () => $ayarlar ?? $this->ayarlar->saglayiciAyarlari($tur, $kod);

        return match ($tur) {
            'sms' => match ($kod) {
                'demo_sms' => new DemoSmsSender,
                // İleride eklenecek sağlayıcı örnekleri:
                // 'http_sms' => new HttpSmsSender,
                default => $this->smsFallback(),
            },
            'eposta' => match ($kod) {
                'demo_eposta' => new DemoEmailSender,
                'smtp' => new SmtpEmailSender($ayar()),
                'gmail_api' => new GmailApiEmailSender($ayar()),
                default => $this->emailFallback(),
            },
            'kimlik_sorgulama' => match ($kod) {
                'demo_kimlik' => new DemoKimlikSorgulama,
                'flexcity_kimlik' => new FlexcityKimlikSorgulama($ayar()),
                default => throw new RuntimeException('Aktif kimlik sorgulama sağlayıcısı bulunamadı.'),
            },
            'adres_sorgulama' => match ($kod) {
                'demo_adres' => new DemoAdresSorgulama,
                default => throw new RuntimeException('Aktif adres sorgulama sağlayıcısı bulunamadı.'),
            },
            default => throw new RuntimeException('Bilinmeyen entegrasyon türü.'),
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
