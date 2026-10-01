<?php

namespace App\Services;

use App\Models\Kisi;

/**
 * Portalda kurs, etkinlik ve kreş başvurusunun son adımında istenen doğrulama kodu.
 */
class BasvuruDogrulamaServisi extends PortalDogrulamaKoduServisi
{
    protected bool $baslatmadaBeklemeUygula = true;

    protected function anahtarOnEki(): string
    {
        return 'basvuru_otp';
    }

    protected function gonderimKapsami(): string
    {
        return 'basvuru_dogrulama';
    }

    protected function smsMetni(string $kod, int $dakika): string
    {
        return "Başvuru doğrulama kodunuz: {$kod}. Kod {$dakika} dakika geçerlidir. Kodu kimseyle paylaşmayın.";
    }

    protected function epostaKonusu(): string
    {
        return 'Başvuru Doğrulama Kodu';
    }

    protected function epostaMetni(Kisi $kisi, string $kod, int $dakika): string
    {
        return "Merhaba {$kisi->tam_adi},\n\nBaşvurunuzu tamamlamak için doğrulama kodunuz: {$kod}\n\nBu kod {$dakika} dakika geçerlidir. Bu işlem size ait değilse bu e-postayı dikkate almayın ve şifrenizi değiştirin.";
    }

    protected function yenidenBaslatMesaji(): string
    {
        return 'Lütfen yeni bir doğrulama kodu isteyin.';
    }
}
