<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Kisi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Kisi
 */
class KisiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Kisi $kisi */
        $kisi = $this->resource;

        return [
            'id' => $kisi->id,
            'ad' => $kisi->ad,
            'soyad' => $kisi->soyad,
            'tam_adi' => $kisi->tam_adi,
            'tc_kimlik_no' => $kisi->tc_kimlik_no,
            'dogum_tarihi' => $kisi->dogum_tarihi?->format('Y-m-d'),
            'telefon' => $kisi->telefon,
            'email' => $kisi->email,
            'cinsiyet' => $kisi->cinsiyet?->value,
            'il' => $kisi->il,
            'ilce' => $kisi->ilce,
            'adres' => $kisi->adres,
            'diger_adres' => $kisi->diger_adres,
            'aktif' => (bool) $kisi->aktif,
            'profil_foto_url' => $kisi->profil_foto_url,
        ];
    }
}
