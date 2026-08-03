<?php

namespace App\Enums;

enum SmsGonderimSecenegi: string
{
    case Evet = 'evet';
    case Hayir = 'hayir';
    case IstegeBagli = 'istege_bagli';

    public function label(): string
    {
        return match ($this) {
            self::Evet => 'Evet',
            self::Hayir => 'Hayır',
            self::IstegeBagli => 'İsteğe bağlı',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
