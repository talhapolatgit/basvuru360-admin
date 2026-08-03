<?php

namespace App\Enums;

enum IkametSarti: string
{
    case Evet = 'evet';
    case Hayir = 'hayir';
    case Kismen = 'kismen';

    public function label(): string
    {
        return match ($this) {
            self::Evet => 'Evet',
            self::Hayir => 'Hayır',
            self::Kismen => 'Sınırlı ilçe dışı',
        };
    }
}
