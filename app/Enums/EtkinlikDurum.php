<?php

namespace App\Enums;

enum EtkinlikDurum: string
{
    case Hazirlik = 'hazirlik';
    case Aktif = 'aktif';
    case Tamamlanan = 'tamamlanan';
    case Iptal = 'iptal';

    public function label(): string
    {
        return match ($this) {
            self::Hazirlik => 'Hazırlık',
            self::Aktif => 'Aktif',
            self::Tamamlanan => 'Tamamlanan',
            self::Iptal => 'İptal Edilen',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Hazirlik => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Aktif => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::Tamamlanan => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Iptal => 'bg-red-50 text-red-700 ring-red-200',
        };
    }
}
