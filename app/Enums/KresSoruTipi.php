<?php

namespace App\Enums;

enum KresSoruTipi: string
{
    case Metin = 'metin';
    case UzunMetin = 'uzun_metin';
    case Sayi = 'sayi';
    case Liste = 'liste';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case Tarih = 'tarih';
    case Dosya = 'dosya';
    case Resim = 'resim';
    case TcKimlik = 'tc_kimlik';
    case CepTelefonu = 'cep_telefonu';
    case Eposta = 'eposta';

    public function label(): string
    {
        return match ($this) {
            self::Metin => 'Metin',
            self::UzunMetin => 'Uzun metin',
            self::Sayi => 'Sayı',
            self::Liste => 'Listeden seçmeli',
            self::Checkbox => 'Seçim kutusu (checkbox)',
            self::Radio => 'Seçim (radio)',
            self::Tarih => 'Tarih',
            self::Dosya => 'Dosya',
            self::Resim => 'Resim',
            self::TcKimlik => 'T.C. kimlik no',
            self::CepTelefonu => 'Cep telefonu',
            self::Eposta => 'E-posta adresi',
        };
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::Metin, self::UzunMetin => 'Cevabınızı yazın',
            self::Sayi => 'Sayı girin',
            self::Tarih => '',
            self::TcKimlik => '11 haneli T.C. kimlik no',
            self::CepTelefonu => '05xx xxx xx xx',
            self::Eposta => 'ornek@eposta.com',
            default => '',
        };
    }

    public function secenekGerekli(): bool
    {
        return in_array($this, [self::Liste, self::Checkbox, self::Radio], true);
    }

    public function minSecenek(): int
    {
        return match ($this) {
            self::Liste, self::Radio => 2,
            self::Checkbox => 1,
            default => 0,
        };
    }
}
