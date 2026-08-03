<?php

namespace App\Enums;

enum KatilimDurumu: string
{
    case Katildi = 'katildi';
    case Katilmadi = 'katilmadi';

    public function label(): string
    {
        return match ($this) {
            self::Katildi => 'Katıldı',
            self::Katilmadi => 'Katılmadı',
        };
    }

    public function statusClass(): string
    {
        return match ($this) {
            self::Katildi => 'status-aktif',
            self::Katilmadi => 'status-iptal',
        };
    }
}
