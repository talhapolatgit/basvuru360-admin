<?php

namespace App\Services\Kimlik;

interface KimlikSorgulama
{
    /**
     * @return array{
     *     ok: bool,
     *     ad?: string,
     *     soyad?: string,
     *     cinsiyet?: string|null,
     *     dogum_yeri?: string|null,
     *     medeni_durum?: string|null,
     *     uyruk?: string|null,
     *     anne_adi?: string|null,
     *     baba_adi?: string|null,
     *     dogum_tarihi?: string|null,
     *     message?: string
     * }
     */
    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array;
}
