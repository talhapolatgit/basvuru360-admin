<?php

namespace App\Enums;

enum Cinsiyet: string
{
    case Erkek = 'erkek';
    case Kadin = 'kadin';

    public function label(): string
    {
        return match ($this) {
            self::Erkek => 'Erkek',
            self::Kadin => 'Kadın',
        };
    }
}
