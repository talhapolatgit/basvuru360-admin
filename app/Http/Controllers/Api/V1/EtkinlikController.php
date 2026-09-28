<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\EtkinlikResource;
use App\Models\Etkinlik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EtkinlikController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'merkez_id' => ['nullable', 'integer', 'exists:merkezler,id'],
            'etkinlik_tipi_id' => ['nullable', 'integer', 'exists:etkinlik_tipleri,id'],
            'basvuru_durumu' => ['nullable', 'string', 'in:acik,yakinda,kapandi,kapali'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = Etkinlik::query()
            ->portaldeAktif()
            ->with([
                'merkez',
                'etkinlikTipi',
                'evrakTipleri' => fn ($q) => $q->where('aktif', true)->orderBy('ad'),
            ]);

        if (! empty($validated['merkez_id'])) {
            $query->where('merkez_id', (int) $validated['merkez_id']);
        }

        if (! empty($validated['etkinlik_tipi_id'])) {
            $query->where('etkinlik_tipi_id', (int) $validated['etkinlik_tipi_id']);
        }

        if (! empty($validated['basvuru_durumu'])) {
            $query->basvuruDurumu($validated['basvuru_durumu']);
        }

        if (! empty($validated['q'])) {
            $q = trim((string) $validated['q']);
            $query->where(function ($builder) use ($q) {
                $builder
                    ->where('etkinlik_no', 'like', "%{$q}%")
                    ->orWhere('ad', 'like', "%{$q}%")
                    ->orWhereHas('etkinlikTipi', fn ($b) => $b->where('ad', 'like', "%{$q}%"))
                    ->orWhereHas('merkez', fn ($b) => $b->where('ad', 'like', "%{$q}%"));
            });
        }

        $query
            ->orderByRaw("CASE
                WHEN etkinlikler.basvuru_baslama_tarihi IS NOT NULL
                     AND etkinlikler.basvuru_bitis_tarihi IS NOT NULL
                     AND etkinlikler.basvuru_baslama_tarihi <= ?
                     AND etkinlikler.basvuru_bitis_tarihi >= ? THEN 0
                WHEN etkinlikler.basvuru_baslama_tarihi IS NOT NULL
                     AND etkinlikler.basvuru_baslama_tarihi > ? THEN 1
                ELSE 2
            END", [now(), now(), now()])
            ->orderBy('basvuru_bitis_tarihi')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return $this->success([
            'items' => EtkinlikResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $kisi = $this->opsiyonelKisi($request);

        $etkinlik = Etkinlik::query()
            ->where(function (Builder $q) use ($kisi) {
                $q->where(fn (Builder $a) => $a->portaldeAktif());

                if ($kisi) {
                    $q->orWhereHas('basvurular', fn (Builder $b) => $b->where(function (Builder $w) use ($kisi) {
                        $w->where('kisi_id', $kisi->id)
                            ->orWhere('basvuran_id', $kisi->id)
                            ->orWhere('veli_id', $kisi->id);
                    }));
                }
            })
            ->with([
                'merkez',
                'etkinlikTipi',
                'evrakTipleri' => fn ($q) => $q->where('aktif', true)->orderBy('ad'),
            ])
            ->find($id);

        if (! $etkinlik) {
            return $this->error('Etkinlik bulunamadı.', 404);
        }

        return $this->success(new EtkinlikResource($etkinlik));
    }
}
