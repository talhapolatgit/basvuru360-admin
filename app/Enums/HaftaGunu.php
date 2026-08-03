<?php

namespace App\Enums;

enum HaftaGunu: string
{
    case Pazartesi = 'pazartesi';
    case Sali = 'sali';
    case Carsamba = 'carsamba';
    case Persembe = 'persembe';
    case Cuma = 'cuma';
    case Cumartesi = 'cumartesi';
    case Pazar = 'pazar';

    public function label(): string
    {
        return match ($this) {
            self::Pazartesi => 'Pazartesi',
            self::Sali => 'Salı',
            self::Carsamba => 'Çarşamba',
            self::Persembe => 'Perşembe',
            self::Cuma => 'Cuma',
            self::Cumartesi => 'Cumartesi',
            self::Pazar => 'Pazar',
        };
    }

    public function kisaLabel(): string
    {
        return match ($this) {
            self::Pazartesi => 'Pzt',
            self::Sali => 'Sal',
            self::Carsamba => 'Çar',
            self::Persembe => 'Per',
            self::Cuma => 'Cum',
            self::Cumartesi => 'Cmt',
            self::Pazar => 'Paz',
        };
    }

    public function sira(): int
    {
        return match ($this) {
            self::Pazartesi => 1,
            self::Sali => 2,
            self::Carsamba => 3,
            self::Persembe => 4,
            self::Cuma => 5,
            self::Cumartesi => 6,
            self::Pazar => 7,
        };
    }

    /**
     * Carbon dayOfWeekIso: 1 = Pazartesi … 7 = Pazar
     */
    public function carbonIso(): int
    {
        return $this->sira();
    }

    public static function fromCarbonIso(int $iso): ?self
    {
        return match ($iso) {
            1 => self::Pazartesi,
            2 => self::Sali,
            3 => self::Carsamba,
            4 => self::Persembe,
            5 => self::Cuma,
            6 => self::Cumartesi,
            7 => self::Pazar,
            default => null,
        };
    }
}
