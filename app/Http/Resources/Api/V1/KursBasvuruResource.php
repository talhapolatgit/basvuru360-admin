<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\ResolvesPortalBasvuruOzeti;
use App\Models\KursBasvuru;
use App\Services\KursAyarServisi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KursBasvuru
 */
class KursBasvuruResource extends JsonResource
{
    use ResolvesPortalBasvuruOzeti;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var KursBasvuru $basvuru */
        $basvuru = $this->resource;

        return [
            'id' => $basvuru->id,
            'tip' => 'kurs',
            'basvuru_icin' => $this->portalBasvuruIcin($basvuru, $request),
            'cocuk' => $this->portalCocukOzeti($basvuru, $request),
            'kurs' => $basvuru->relationLoaded('kurs') && $basvuru->kurs
                ? [
                    'id' => $basvuru->kurs->id,
                    'kurs_no' => $basvuru->kurs->kurs_no,
                    'brans' => $basvuru->kurs->relationLoaded('brans')
                        ? $basvuru->kurs->brans?->ad
                        : null,
                    'merkez' => $basvuru->kurs->relationLoaded('merkez')
                        ? $basvuru->kurs->merkez?->ad
                        : null,
                    'basvuru_durumu' => [
                        'kod' => $basvuru->kurs->basvuruDurumuKod(),
                        'label' => $basvuru->kurs->basvuruDurumuLabel(),
                    ],
                ]
                : null,
            'durum' => $basvuru->relationLoaded('durum') && $basvuru->durum
                ? [
                    'kod' => $basvuru->durum->kod,
                    'ad' => $basvuru->durum->ad,
                ]
                : null,
            'basari_durum' => $basvuru->relationLoaded('basariDurum') && $basvuru->basariDurum
                ? [
                    'kod' => $basvuru->basariDurum->kod,
                    'ad' => $basvuru->basariDurum->ad,
                    'status_sinifi' => $basvuru->basariDurum->status_sinifi,
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
            'belge_indirilebilir' => $this->belgeIndirilebilir($basvuru),
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

    private function belgeIndirilebilir(KursBasvuru $basvuru): bool
    {
        if (! $basvuru->relationLoaded('basariDurum') || ! $basvuru->basariDurum) {
            return false;
        }

        $kodlar = config('sertifika.hak_eden_kodlar', [
            'sertifika_hak_etti',
            'katilim_belgesi_hak_etti',
        ]);

        return in_array($basvuru->basariDurum->kod, $kodlar, true);
    }

    private function iptalEdilebilir(KursBasvuru $basvuru): bool
    {
        $kod = $basvuru->durum?->kod;

        if (! in_array($kod, ['onay_bekliyor', 'yedek', 'kesin_kayit'], true)) {
            return false;
        }

        if ($kod === 'kesin_kayit' && ! app(KursAyarServisi::class)->kisiOnaylanmisBasvuruIptalEdebilir()) {
            return false;
        }

        return true;
    }
}
