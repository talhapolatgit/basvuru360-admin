<?php

namespace App\Enums;

enum IkiAsamaliGuvenlik: string
{
    case Hayir = 'hayir';
    case Sms = 'sms';
    case Eposta = 'eposta';

    public function label(): string
    {
        return match ($this) {
            self::Hayir => 'Hayır',
            self::Sms => 'SMS',
            self::Eposta => 'E-posta',
        };
    }

    public function aktifMi(): bool
    {
        return $this !== self::Hayir;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
