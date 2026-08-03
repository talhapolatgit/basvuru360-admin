<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Alan;
use App\Models\Brans;
use App\Models\EtkinlikTipi;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\IptalGerekce;
use App\Models\KursTipi;
use App\Models\Merkez;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends ApiController
{
    public function merkezler(): JsonResponse
    {
        $items = Merkez::query()
            ->where('aktif', true)
            ->orderBy('ad')
            ->get(['id', 'ad', 'il', 'ilce'])
            ->map(fn (Merkez $m) => [
                'id' => $m->id,
                'ad' => $m->ad,
                'il' => $m->il,
                'ilce' => $m->ilce,
            ]);

        return $this->success(['items' => $items]);
    }

    public function alanlar(Request $request): JsonResponse
    {
        $query = Alan::query()->where('aktif', true)->orderBy('ad');

        $items = $query->get(['id', 'ad'])->map(fn (Alan $a) => [
            'id' => $a->id,
            'ad' => $a->ad,
        ]);

        return $this->success(['items' => $items]);
    }

    public function branslar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alan_id' => ['nullable', 'integer', 'exists:alanlar,id'],
        ]);

        $query = Brans::query()->where('aktif', true)->orderBy('ad');

        if (! empty($validated['alan_id'])) {
            $query->where('alan_id', (int) $validated['alan_id']);
        }

        $items = $query->get(['id', 'ad', 'alan_id'])->map(fn (Brans $b) => [
            'id' => $b->id,
            'ad' => $b->ad,
            'alan_id' => $b->alan_id,
        ]);

        return $this->success(['items' => $items]);
    }

    public function kursTipleri(): JsonResponse
    {
        $items = KursTipi::query()
            ->where('aktif', true)
            ->orderBy('ad')
            ->get(['id', 'ad'])
            ->map(fn (KursTipi $t) => [
                'id' => $t->id,
                'ad' => $t->ad,
            ]);

        return $this->success(['items' => $items]);
    }

    public function etkinlikTipleri(): JsonResponse
    {
        $items = EtkinlikTipi::query()
            ->where('aktif', true)
            ->orderBy('ad')
            ->get(['id', 'ad'])
            ->map(fn (EtkinlikTipi $t) => [
                'id' => $t->id,
                'ad' => $t->ad,
            ]);

        return $this->success(['items' => $items]);
    }

    public function iptalGerekceleri(): JsonResponse
    {
        $items = IptalGerekce::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->orderBy('ad')
            ->get(['id', 'ad'])
            ->map(fn (IptalGerekce $g) => [
                'id' => $g->id,
                'ad' => $g->ad,
            ]);

        return $this->success(['items' => $items]);
    }

    public function iller(): JsonResponse
    {
        $items = Il::query()
            ->orderBy('ad')
            ->get(['id', 'ad'])
            ->map(fn (Il $il) => [
                'id' => $il->id,
                'ad' => $il->ad,
            ]);

        return $this->success(['items' => $items]);
    }

    public function ilceler(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'il_id' => ['required_without:il', 'nullable', 'integer', 'exists:iller,id'],
            'il' => ['required_without:il_id', 'nullable', 'string', 'max:100'],
        ]);

        $query = Ilce::query()->orderBy('ad');

        if (! empty($validated['il_id'])) {
            $query->where('il_id', (int) $validated['il_id']);
        } else {
            $ilAd = trim((string) $validated['il']);
            $ilId = Il::query()
                ->whereRaw('LOWER(ad) = ?', [mb_strtolower($ilAd, 'UTF-8')])
                ->value('id');

            if (! $ilId) {
                return $this->success(['items' => []]);
            }

            $query->where('il_id', (int) $ilId);
        }

        $items = $query->get(['id', 'ad', 'il_id'])->map(fn (Ilce $ilce) => [
            'id' => $ilce->id,
            'ad' => $ilce->ad,
            'il_id' => $ilce->il_id,
        ]);

        return $this->success(['items' => $items]);
    }
}
