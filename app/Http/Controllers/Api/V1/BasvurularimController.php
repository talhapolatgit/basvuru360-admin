<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\EtkinlikBasvuruResource;
use App\Http\Resources\Api\V1\KresBasvuruResource;
use App\Http\Resources\Api\V1\KursBasvuruResource;
use App\Models\EtkinlikBasvuru;
use App\Models\Kisi;
use App\Models\KresBasvuru;
use App\Models\KursBasvuru;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BasvurularimController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Kisi $kisi */
        $kisi = $request->user();

        $validated = $request->validate([
            'tip' => ['nullable', 'string', 'in:kurs,etkinlik,kres'],
        ]);

        $tip = $validated['tip'] ?? null;
        $items = collect();

        if ($tip === null || $tip === 'kurs') {
            $kursBasvurulari = KursBasvuru::query()
                ->where(function ($q) use ($kisi) {
                    $q->where('kisi_id', $kisi->id)
                        ->orWhere('basvuran_id', $kisi->id)
                        ->orWhere('veli_id', $kisi->id);
                })
                ->with([
                    'durum',
                    'basariDurum',
                    'iptalGerekce',
                    'kisi',
                    'veli',
                    'kurs.merkez',
                    'kurs.brans',
                    'evraklar.evrakTipi',
                ])
                ->orderByDesc('created_at')
                ->get();

            foreach ($kursBasvurulari as $basvuru) {
                $items->push([
                    'sort_at' => $basvuru->created_at,
                    'resource' => (new KursBasvuruResource($basvuru))->resolve(),
                ]);
            }
        }

        if ($tip === null || $tip === 'etkinlik') {
            $etkinlikBasvurulari = EtkinlikBasvuru::query()
                ->where(function ($q) use ($kisi) {
                    $q->where('kisi_id', $kisi->id)
                        ->orWhere('basvuran_id', $kisi->id)
                        ->orWhere('veli_id', $kisi->id);
                })
                ->with([
                    'durum',
                    'iptalGerekce',
                    'kisi',
                    'veli',
                    'etkinlik.merkez',
                    'evraklar.evrakTipi',
                ])
                ->orderByDesc('created_at')
                ->get();

            foreach ($etkinlikBasvurulari as $basvuru) {
                $items->push([
                    'sort_at' => $basvuru->created_at,
                    'resource' => (new EtkinlikBasvuruResource($basvuru))->resolve(),
                ]);
            }
        }

        if ($tip === null || $tip === 'kres') {
            $kresBasvurulari = KresBasvuru::query()
                ->where(function ($q) use ($kisi) {
                    $q->where('kisi_id', $kisi->id)
                        ->orWhere('basvuran_id', $kisi->id);
                })
                ->with(['durum', 'kisi', 'grup.okul', 'grup.donem'])
                ->orderByDesc('created_at')
                ->get();

            foreach ($kresBasvurulari as $basvuru) {
                $items->push([
                    'sort_at' => $basvuru->created_at,
                    'resource' => (new KresBasvuruResource($basvuru))->resolve(),
                ]);
            }
        }

        $sorted = $items
            ->sortByDesc(fn ($row) => $row['sort_at']?->timestamp ?? 0)
            ->values()
            ->map(fn ($row) => $row['resource']);

        return $this->success(['items' => $sorted]);
    }
}
