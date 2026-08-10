<?php

namespace App\Http\Resources\Api\V1\Concerns;

use App\Models\EtkinlikBasvuru;
use App\Models\KursBasvuru;
use Illuminate\Http\Request;

trait ResolvesPortalBasvuruOzeti
{
    /**
     * Giriş yapan kişi çocuk adına başvurduysa "cocuk", aksi halde "kendisi".
     */
    protected function portalBasvuruIcin(KursBasvuru|EtkinlikBasvuru $basvuru, Request $request): string
    {
        $auth = $request->user();
        if (! $auth || ! $basvuru->kisi_id || ! $basvuru->basvuran_id) {
            return 'kendisi';
        }

        if ((int) $basvuru->basvuran_id === (int) $auth->id
            && (int) $basvuru->kisi_id !== (int) $auth->id) {
            return 'cocuk';
        }

        return 'kendisi';
    }

    /**
     * @return array{id: int, ad: string|null, soyad: string|null, tam_adi: string, tc_kimlik_no: string|null, dogum_tarihi: string|null}|null
     */
    protected function portalCocukOzeti(KursBasvuru|EtkinlikBasvuru $basvuru, Request $request): ?array
    {
        if ($this->portalBasvuruIcin($basvuru, $request) !== 'cocuk') {
            return null;
        }

        if (! $basvuru->relationLoaded('kisi') || ! $basvuru->kisi) {
            return null;
        }

        $kisi = $basvuru->kisi;

        return [
            'id' => $kisi->id,
            'ad' => $kisi->ad,
            'soyad' => $kisi->soyad,
            'tam_adi' => $kisi->tam_adi,
            'tc_kimlik_no' => $kisi->tc_kimlik_no,
            'dogum_tarihi' => $kisi->dogum_tarihi?->format('Y-m-d'),
        ];
    }
}
