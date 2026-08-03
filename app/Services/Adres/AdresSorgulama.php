<?php

namespace App\Services\Adres;

interface AdresSorgulama
{
    /**
     * @return array{
     *     ok: bool,
     *     il?: string|null,
     *     ilce?: string|null,
     *     mahalle?: string|null,
     *     adres?: string|null,
     *     message?: string
     * }
     */
    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array;
}
