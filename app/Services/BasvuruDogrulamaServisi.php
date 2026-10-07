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
        return view('emails.basvuru-dogrulama-metin', $this->sablonVerisi($kisi, $kod, $dakika))->render();
    }

    protected function epostaHtmlBaglami(Kisi $kisi, string $kod, int $dakika): array
    {
        return app(EpostaTasarimServisi::class)->htmlBaglami('emails.basvuru-dogrulama', $this->sablonVerisi($kisi, $kod, $dakika));
    }

    protected function yenidenBaslatMesaji(): string
    {
        return 'Lütfen yeni bir doğrulama kodu isteyin.';
    }

    /**
     * @return array<string, mixed>
     */
    private function sablonVerisi(Kisi $kisi, string $kod, int $dakika): array
    {
        $tasarim = app(EpostaTasarimServisi::class);

        return [
            ...$tasarim->ortakVeri(),
            'konu' => $this->epostaKonusu(),
            'tamAd' => $tasarim->ozelAd((string) $kisi->tam_adi),
            'kod' => $kod,
            'dakika' => $dakika,
        ];
    }
}
