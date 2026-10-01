<?php

namespace App\Services;

use App\Models\Kisi;

/**
 * Portal (vatandaş) girişinde ikinci adım doğrulama kodu.
 * Şifre doğrulandıktan sonra kod üretilir; JWT yalnızca kod doğrulanınca verilir.
 */
class PortalIkiAsamaliDogrulamaServisi extends PortalDogrulamaKoduServisi
{
    protected function anahtarOnEki(): string
    {
        return 'portal_otp';
    }

    protected function gonderimKapsami(): string
    {
        return 'portal_giris_dogrulama';
    }

    protected function smsMetni(string $kod, int $dakika): string
    {
        return "Giriş doğrulama kodunuz: {$kod}. Kod {$dakika} dakika geçerlidir. Kodu kimseyle paylaşmayın.";
    }

    protected function epostaKonusu(): string
    {
        return 'Giriş Doğrulama Kodu';
    }

    protected function epostaMetni(Kisi $kisi, string $kod, int $dakika): string
    {
        return "Merhaba {$kisi->tam_adi},\n\nPortal giriş doğrulama kodunuz: {$kod}\n\nBu kod {$dakika} dakika geçerlidir. Giriş denemesi size ait değilse bu e-postayı dikkate almayın ve şifrenizi değiştirin.";
    }

    protected function yenidenBaslatMesaji(): string
    {
        return 'Lütfen tekrar giriş yapın.';
    }
}
