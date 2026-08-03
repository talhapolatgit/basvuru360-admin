<?php

namespace App\Enums;

enum YoklamaDurum: string
{
    case Var = 'var';
    case Yok = 'yok';
    case Izinli = 'izinli';

    public function label(): string
    {
        return match ($this) {
            self::Var => 'Var',
            self::Yok => 'Yok',
            self::Izinli => 'İzinli',
        };
    }

    public function statusClass(): string
    {
        return match ($this) {
            self::Var => 'status-tamamlanan',
            self::Yok => 'status-iptal',
            self::Izinli => 'status-hazirlik',
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
