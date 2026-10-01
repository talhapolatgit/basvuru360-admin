<?php

namespace App\Services;

use App\Models\Kisi;
use RuntimeException;

/**
 * Portal üyeliğinde doğrulama kodu. Hesap kod doğrulanana kadar oluşturulmaz;
 * kayıt bilgileri (şifre hash'lenmiş olarak) doğrulama oturumunda bekletilir.
 */
class PortalKayitDogrulamaServisi extends PortalDogrulamaKoduServisi
{
    /** Kayıt bilgilerinden, kodun gönderileceği kaydedilmemiş kişi örneğini üretir. */
    public static function geciciKisi(array $kayit): Kisi
    {
        return (new Kisi)->forceFill([
            'ad' => $kayit['ad'] ?? null,
            'soyad' => $kayit['soyad'] ?? null,
            'tc_kimlik_no' => $kayit['tc_kimlik_no'] ?? null,
            'telefon' => $kayit['telefon'] ?? null,
            'email' => $kayit['email'] ?? null,
        ]);
    }

    /**
     * Kodu kontrol eder ve doğruysa bekletilen kayıt bilgilerini döner. Oturum kapatılmaz;
     * hesap oluşturulduktan sonra {@see tamamla()} çağrılmalıdır.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function kayitBilgileri(string $token, string $kod): array
    {
        $ek = $this->kodKontrol($token, $kod)['ek'] ?? null;
        if (! is_array($ek) || $ek === []) {
            $this->tamamla($token);
            throw new RuntimeException('Doğrulama oturumu geçersiz. '.$this->yenidenBaslatMesaji());
        }

        return $ek;
    }

    protected function sahipAnahtari(Kisi $kisi): string
    {
        return 'tc:'.$kisi->tc_kimlik_no;
    }

    protected function kisiCoz(array $payload): ?Kisi
    {
        return is_array($payload['ek'] ?? null) ? self::geciciKisi($payload['ek']) : null;
    }

    protected function anahtarOnEki(): string
    {
        return 'portal_kayit_otp';
    }

    protected function gonderimKapsami(): string
    {
        return 'portal_kayit_dogrulama';
    }

    protected function smsMetni(string $kod, int $dakika): string
    {
        return "Üyelik doğrulama kodunuz: {$kod}. Kod {$dakika} dakika geçerlidir. Kodu kimseyle paylaşmayın.";
    }

    protected function epostaKonusu(): string
    {
        return 'Üyelik Doğrulama Kodu';
    }

    protected function epostaMetni(Kisi $kisi, string $kod, int $dakika): string
    {
        return "Merhaba {$kisi->tam_adi},\n\nÜyeliğinizi tamamlamak için doğrulama kodunuz: {$kod}\n\nBu kod {$dakika} dakika geçerlidir. Bu kayıt işlemi size ait değilse bu e-postayı dikkate almayın.";
    }

    protected function yenidenBaslatMesaji(): string
    {
        return 'Lütfen kayıt formunu tekrar gönderin.';
    }
}
