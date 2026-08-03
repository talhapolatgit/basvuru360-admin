<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Etkinlik;
use App\Services\EtkinlikAyarServisi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Etkinlik
 */
class EtkinlikResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Etkinlik $etkinlik */
        $etkinlik = $this->resource;

        return [
            'id' => $etkinlik->id,
            'etkinlik_no' => $etkinlik->etkinlik_no,
            'ad' => $etkinlik->ad,
            'aciklama' => $etkinlik->aciklama,
            'merkez' => $etkinlik->relationLoaded('merkez') && $etkinlik->merkez
                ? [
                    'id' => $etkinlik->merkez->id,
                    'ad' => $etkinlik->merkez->ad,
                    'il' => $etkinlik->merkez->il,
                    'ilce' => $etkinlik->merkez->ilce,
                ]
                : null,
            'etkinlik_tipi' => $etkinlik->relationLoaded('etkinlikTipi') && $etkinlik->etkinlikTipi
                ? [
                    'id' => $etkinlik->etkinlikTipi->id,
                    'ad' => $etkinlik->etkinlikTipi->ad,
                ]
                : null,
            'kontenjan' => (int) $etkinlik->kontenjan,
            'yedek_kontenjan' => (int) ($etkinlik->yedek_kontenjan ?? 0),
            'kayit_sayisi' => (int) ($etkinlik->kayit_sayisi ?? 0),
            'basvuru_sayisi' => (int) ($etkinlik->basvuru_sayisi ?? 0),
            'baslangic_tarihi' => $etkinlik->baslangic_tarihi?->format('Y-m-d'),
            'bitis_tarihi' => $etkinlik->bitis_tarihi?->format('Y-m-d'),
            'basvuru_baslama_tarihi' => $etkinlik->basvuru_baslama_tarihi?->toIso8601String(),
            'basvuru_bitis_tarihi' => $etkinlik->basvuru_bitis_tarihi?->toIso8601String(),
            'cinsiyet_sarti' => $etkinlik->cinsiyet_sarti?->value,
            'cinsiyet_sarti_label' => $etkinlik->cinsiyet_sarti?->label(),
            'ikamet_sarti' => $etkinlik->ikamet_sarti?->value,
            'ikamet_sarti_label' => $etkinlik->ikamet_sarti?->label(),
            'ikamet_disi_kontenjan' => (int) ($etkinlik->ikamet_disi_kontenjan ?? 0),
            'minimum_yas' => $etkinlik->minimum_yas,
            'maksimum_yas' => $etkinlik->maksimum_yas,
            'durum' => [
                'kod' => $etkinlik->durum?->value,
                'label' => $etkinlik->durum?->label(),
            ],
            'basvuru_durumu' => [
                'kod' => $etkinlik->basvuruDurumuKod(),
                'label' => $etkinlik->basvuruDurumuLabel(),
            ],
            'evrak_zorunlu' => (bool) $etkinlik->evrak_zorunlu,
            'evrak_tipleri' => $etkinlik->relationLoaded('evrakTipleri')
                ? $etkinlik->evrakTipleri->map(fn ($tip) => [
                    'id' => $tip->id,
                    'ad' => $tip->ad,
                    'aciklama' => $tip->aciklama,
                ])->values()->all()
                : [],
            'basvuru_onaylari' => $this->when(
                $request->routeIs('api.v1.etkinlikler.show'),
                fn () => app(EtkinlikAyarServisi::class)->basvuruOnaylari(),
            ),
        ];
    }
}
