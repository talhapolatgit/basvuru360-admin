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
     *     sokak?: string|null,
     *     kapi?: string|null,
     *     daire?: string|null,
     *     uavt_adres_no?: string|null,
     *     adres?: string|null,
     *     message?: string
     * }
     */
    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array;
}
