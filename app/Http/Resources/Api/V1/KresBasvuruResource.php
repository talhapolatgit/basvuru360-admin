<?php

namespace App\Http\Resources\Api\V1;

use App\Models\KresBasvuru;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KresBasvuru
 */
class KresBasvuruResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KresBasvuru $basvuru */
        $basvuru = $this->resource;
        $grup = $basvuru->relationLoaded('grup') ? $basvuru->grup : null;
        $okul = $grup?->relationLoaded('okul') ? $grup->okul : null;
        $donem = $grup?->relationLoaded('donem') ? $grup->donem : null;
        $ogrenci = $basvuru->relationLoaded('kisi') ? $basvuru->kisi : null;
        $authId = $request->user()?->id;

        return [
            'id' => $basvuru->id,
            'tip' => 'kres',
            'basvuru_icin' => 'cocuk',
            'cocuk' => $ogrenci ? [
                'id' => $ogrenci->id,
                'ad' => $ogrenci->ad,
                'soyad' => $ogrenci->soyad,
                'tam_adi' => $ogrenci->tam_adi,
                'tc_kimlik_no' => $ogrenci->tc_kimlik_no,
                'dogum_tarihi' => $ogrenci->dogum_tarihi?->format('Y-m-d'),
            ] : null,
            'kres' => [
                'okul' => $okul?->ad,
                'grup' => $grup?->ad,
                'donem' => $donem?->ad,
                'yas_araligi' => $grup?->yasAraligiLabel(),
            ],
            'durum' => $basvuru->relationLoaded('durum') && $basvuru->durum
                ? [
                    'kod' => $basvuru->durum->kod,
                    'ad' => $basvuru->durum->ad,
                ]
                : null,
            'yedek_sira' => $basvuru->yedek_sira,
            'iptal_tarihi' => null,
            'iptal_gerekce' => null,
            'onay_tarihi' => null,
            'created_at' => $basvuru->created_at?->toIso8601String(),
            'iptal_edilebilir' => false,
            'evraklar' => [],
            'kurs' => null,
            'etkinlik' => null,
            'kendisi_mi' => $authId && ((int) $basvuru->kisi_id === (int) $authId),
        ];
    }
}
