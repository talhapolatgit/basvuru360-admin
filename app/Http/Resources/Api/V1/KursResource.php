<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Kurs;
use App\Services\KursAyarServisi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Kurs
 */
class KursResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Kurs $kurs */
        $kurs = $this->resource;

        return [
            'id' => $kurs->id,
            'kurs_no' => $kurs->kurs_no,
            'merkez' => $kurs->relationLoaded('merkez') && $kurs->merkez
                ? [
                    'id' => $kurs->merkez->id,
                    'ad' => $kurs->merkez->ad,
                    'il' => $kurs->merkez->il,
                    'ilce' => $kurs->merkez->ilce,
                ]
                : null,
            'alan' => $kurs->relationLoaded('alan') && $kurs->alan
                ? [
                    'id' => $kurs->alan->id,
                    'ad' => $kurs->alan->ad,
                ]
                : null,
            'brans' => $kurs->relationLoaded('brans') && $kurs->brans
                ? [
                    'id' => $kurs->brans->id,
                    'ad' => $kurs->brans->ad,
                ]
                : null,
            'kurs_tipi' => $kurs->relationLoaded('kursTipi') && $kurs->kursTipi
                ? [
                    'id' => $kurs->kursTipi->id,
                    'ad' => $kurs->kursTipi->ad,
                ]
                : null,
            'kontenjan' => (int) $kurs->kontenjan,
            'yedek_kontenjan' => (int) ($kurs->yedek_kontenjan ?? 0),
            'kayit_sayisi' => (int) ($kurs->kayit_sayisi ?? 0),
            'basvuru_sayisi' => (int) ($kurs->basvuru_sayisi ?? 0),
            'kurs_baslama_tarihi' => $kurs->kurs_baslama_tarihi?->format('Y-m-d'),
            'kurs_bitis_tarihi' => $kurs->kurs_bitis_tarihi?->format('Y-m-d'),
            'basvuru_baslama_tarihi' => $kurs->basvuru_baslama_tarihi?->toIso8601String(),
            'basvuru_bitis_tarihi' => $kurs->basvuru_bitis_tarihi?->toIso8601String(),
            'toplam_kurs_saati' => $kurs->toplam_kurs_saati,
            'cinsiyet_sarti' => $kurs->cinsiyet_sarti?->value,
            'cinsiyet_sarti_label' => $kurs->cinsiyet_sarti?->label(),
            'ikamet_sarti' => $kurs->ikamet_sarti?->value,
            'ikamet_sarti_label' => $kurs->ikamet_sarti?->label(),
            'ikamet_disi_kontenjan' => (int) ($kurs->ikamet_disi_kontenjan ?? 0),
            'minimum_yas' => $kurs->minimum_yas,
            'maksimum_yas' => $kurs->maksimum_yas,
            'durum' => [
                'kod' => $kurs->durum?->value,
                'label' => $kurs->durum?->label(),
            ],
            'basvuru_durumu' => [
                'kod' => $kurs->basvuruDurumuKod(),
                'label' => $kurs->basvuruDurumuLabel(),
            ],
            'evrak_zorunlu' => (bool) $kurs->evrak_zorunlu,
            'aciklama' => $kurs->aciklama,
            'evrak_tipleri' => $kurs->relationLoaded('evrakTipleri')
                ? $kurs->evrakTipleri->map(fn ($tip) => [
                    'id' => $tip->id,
                    'ad' => $tip->ad,
                    'aciklama' => $tip->aciklama,
                ])->values()->all()
                : [],
            'basvuru_onaylari' => $this->when(
                $request->routeIs('api.v1.kurslar.show'),
                fn () => app(KursAyarServisi::class)->basvuruOnaylari(),
            ),
            'haftalik_program' => $kurs->relationLoaded('gunler')
                ? $kurs->gunler
                    ->sortBy(fn ($gun) => $gun->gun?->sira() ?? 99)
                    ->map(fn ($gun) => [
                        'gun' => $gun->gun?->carbonIso(),
                        'gun_kod' => $gun->gun?->value,
                        'gun_label' => $gun->gun?->label(),
                        'baslangic' => substr((string) $gun->baslangic_saati, 0, 5),
                        'bitis' => substr((string) $gun->bitis_saati, 0, 5),
                        'ders_saati' => $gun->ders_saati,
                        'sinif' => $gun->sinif,
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }
}
