<?php

namespace App\Enums;

enum KisiGirisYontemi: string
{
    case TcDogumTarihi = 'tc_dogum_tarihi';
    case TcSifre = 'tc_sifre';
    case EpostaSifre = 'eposta_sifre';

    public function label(): string
    {
        return match ($this) {
            self::TcDogumTarihi => 'T.C. Kimlik No + Doğum Tarihi',
            self::TcSifre => 'T.C. Kimlik No + Şifre',
            self::EpostaSifre => 'E-posta Adresi + Şifre',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function secenekler(): array
    {
        return array_map(
            fn (self $yontem) => [
                'value' => $yontem->value,
                'label' => $yontem->label(),
            ],
            self::cases()
        );
    }
}
