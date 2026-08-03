<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\KursResource;
use App\Models\Kurs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KursController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'merkez_id' => ['nullable', 'integer', 'exists:merkezler,id'],
            'alan_id' => ['nullable', 'integer', 'exists:alanlar,id'],
            'brans_id' => ['nullable', 'integer', 'exists:branslar,id'],
            'kurs_tipi_id' => ['nullable', 'integer', 'exists:kurs_tipleri,id'],
            'basvuru_durumu' => ['nullable', 'string', 'in:acik,yakinda,kapandi,kapali'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = Kurs::query()
            ->portaldeAktif()
            ->with([
                'merkez',
                'alan',
                'brans',
                'kursTipi',
                'gunler',
                'evrakTipleri' => fn ($q) => $q->where('aktif', true)->orderBy('ad'),
            ]);

        if (! empty($validated['merkez_id'])) {
            $query->where('merkez_id', (int) $validated['merkez_id']);
        }

        if (! empty($validated['alan_id'])) {
            $query->where('alan_id', (int) $validated['alan_id']);
        }

        if (! empty($validated['brans_id'])) {
            $query->where('brans_id', (int) $validated['brans_id']);
        }

        if (! empty($validated['kurs_tipi_id'])) {
            $query->where('kurs_tipi_id', (int) $validated['kurs_tipi_id']);
        }

        if (! empty($validated['basvuru_durumu'])) {
            $query->basvuruDurumu($validated['basvuru_durumu']);
        }

        if (! empty($validated['q'])) {
            $q = trim((string) $validated['q']);
            $query->where(function ($builder) use ($q) {
                $builder
                    ->where('kurs_no', 'like', "%{$q}%")
                    ->orWhereHas('brans', fn ($b) => $b->where('ad', 'like', "%{$q}%"))
                    ->orWhereHas('alan', fn ($b) => $b->where('ad', 'like', "%{$q}%"))
                    ->orWhereHas('merkez', fn ($b) => $b->where('ad', 'like', "%{$q}%"));
            });
        }

        $query
            ->orderByRaw("CASE
                WHEN kurslar.basvuru_baslama_tarihi IS NOT NULL
                     AND kurslar.basvuru_bitis_tarihi IS NOT NULL
                     AND kurslar.basvuru_baslama_tarihi <= ?
                     AND kurslar.basvuru_bitis_tarihi >= ? THEN 0
                WHEN kurslar.basvuru_baslama_tarihi IS NOT NULL
                     AND kurslar.basvuru_baslama_tarihi > ? THEN 1
                ELSE 2
            END", [now(), now(), now()])
            ->orderBy('basvuru_bitis_tarihi')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return $this->success([
            'items' => KursResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $kurs = Kurs::query()
            ->portaldeAktif()
            ->with([
                'merkez',
                'alan',
                'brans',
                'kursTipi',
                'gunler',
                'evrakTipleri' => fn ($q) => $q->where('aktif', true)->orderBy('ad'),
            ])
            ->find($id);

        if (! $kurs) {
            return $this->error('Kurs bulunamadı.', 404);
        }

        return $this->success(new KursResource($kurs));
    }
}
