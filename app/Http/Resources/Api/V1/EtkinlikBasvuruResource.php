<?php

namespace App\Http\Resources\Api\V1;

use App\Models\EtkinlikBasvuru;
use App\Services\EtkinlikAyarServisi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EtkinlikBasvuru
 */
class EtkinlikBasvuruResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EtkinlikBasvuru $basvuru */
        $basvuru = $this->resource;

        return [
            'id' => $basvuru->id,
            'tip' => 'etkinlik',
            'etkinlik' => $basvuru->relationLoaded('etkinlik') && $basvuru->etkinlik
                ? [
                    'id' => $basvuru->etkinlik->id,
                    'etkinlik_no' => $basvuru->etkinlik->etkinlik_no,
                    'ad' => $basvuru->etkinlik->ad,
                    'merkez' => $basvuru->etkinlik->relationLoaded('merkez')
                        ? $basvuru->etkinlik->merkez?->ad
                        : null,
                    'basvuru_durumu' => [
                        'kod' => $basvuru->etkinlik->basvuruDurumuKod(),
                        'label' => $basvuru->etkinlik->basvuruDurumuLabel(),
                    ],
                ]
                : null,
            'durum' => $basvuru->relationLoaded('durum') && $basvuru->durum
                ? [
                    'kod' => $basvuru->durum->kod,
                    'ad' => $basvuru->durum->ad,
                ]
                : null,
            'yedek_sira' => $basvuru->yedek_sira,
            'iptal_tarihi' => $basvuru->iptal_tarihi?->toIso8601String(),
            'iptal_gerekce' => $basvuru->relationLoaded('iptalGerekce') && $basvuru->iptalGerekce
                ? [
                    'id' => $basvuru->iptalGerekce->id,
                    'ad' => $basvuru->iptalGerekce->ad,
                ]
                : null,
            'onay_tarihi' => $basvuru->onay_tarihi?->toIso8601String(),
            'created_at' => $basvuru->created_at?->toIso8601String(),
            'iptal_edilebilir' => $this->iptalEdilebilir($basvuru),
            'evraklar' => $basvuru->relationLoaded('evraklar')
                ? $basvuru->evraklar->map(fn ($e) => [
                    'id' => $e->id,
                    'evrak_tipi' => $e->relationLoaded('evrakTipi') && $e->evrakTipi
                        ? ['id' => $e->evrakTipi->id, 'ad' => $e->evrakTipi->ad]
                        : null,
                    'orijinal_ad' => $e->orijinal_ad,
                    'mime' => $e->mime,
                    'boyut' => $e->boyut,
                ])->values()->all()
                : [],
            'veli' => $basvuru->relationLoaded('veli') && $basvuru->veli
                ? [
                    'id' => $basvuru->veli->id,
                    'ad' => $basvuru->veli->ad,
                    'soyad' => $basvuru->veli->soyad,
                    'tam_adi' => $basvuru->veli->tam_adi,
                ]
                : null,
        ];
    }

    private function iptalEdilebilir(EtkinlikBasvuru $basvuru): bool
    {
        $kod = $basvuru->durum?->kod;

        if (! in_array($kod, ['onay_bekliyor', 'yedek', 'kesin_kayit'], true)) {
            return false;
        }

        if ($kod === 'kesin_kayit' && ! app(EtkinlikAyarServisi::class)->kisiOnaylanmisBasvuruIptalEdebilir()) {
            return false;
        }

        return true;
    }
}
