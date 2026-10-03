<?php

namespace App\Services\Flexcity;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Flexcity SBS kişi kayıtlarındaki (sbsKisiDto) alanları uygulama biçimine çevirir.
 */
trait FlexcityAlanDonusumleri
{
    private const MEDENI_HAL = [
        'BEKAR' => 'Bekar',
        'EVLI' => 'Evli',
        'BOSANMIS' => 'Boşanmış',
        'DUL' => 'Dul',
        'ESI_OLMUS' => 'Eşi ölmüş',
    ];

    private function tarih(mixed $deger): ?string
    {
        if (! is_string($deger) || $deger === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($deger)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function cinsiyet(mixed $deger): ?string
    {
        return match (mb_strtoupper(trim((string) $deger), 'UTF-8')) {
            'ERKEK', 'E' => 'erkek',
            'KADIN', 'K' => 'kadin',
            default => null,
        };
    }

    private function medeniHal(mixed $deger): ?string
    {
        $kod = mb_strtoupper(trim((string) $deger), 'UTF-8');

        return $kod === '' ? null : (self::MEDENI_HAL[$kod] ?? $kod);
    }

    private function metin(mixed $deger): ?string
    {
        if (! is_scalar($deger)) {
            return null;
        }

        $metin = trim((string) $deger);

        return $metin === '' ? null : $metin;
    }
}
