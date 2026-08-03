<?php

namespace App\Http\Controllers;

use App\Enums\KursDurum;
use App\Models\Alan;
use App\Services\LogKaydedici;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlanController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$alanlar, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'alanlar' => $alanlar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('alanlar._results', $viewData);
        }

        return view('alanlar.index', $viewData + [
            'ozet' => $this->ozet(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validated($request);
        $alan = Alan::query()->create($validated);

        LogKaydedici::kaydet(
            islem: 'alan.olusturuldu',
            aciklama: '"'.$alan->ad.'" alanı oluşturuldu.',
            konu: $alan,
            yeni: ['ad' => $alan->ad, 'aktif' => (bool) $alan->aktif],
            konuAdi: $alan->ad,
        );

        $message = "\"{$alan->ad}\" alanı başarıyla oluşturuldu.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('alanlar.index')->with('success', $message);
    }

    public function update(Request $request, Alan $alan): RedirectResponse|JsonResponse
    {
        $onceki = ['ad' => $alan->ad, 'aktif' => (bool) $alan->aktif];

        $validated = $this->validated($request, $alan);
        $alan->update($validated);

        $yeni = ['ad' => $alan->ad, 'aktif' => (bool) $alan->aktif];
        if ($onceki !== $yeni) {
            LogKaydedici::kaydet(
                islem: 'alan.guncellendi',
                aciklama: '"'.$alan->ad.'" alanı güncellendi.',
                konu: $alan,
                eski: $onceki,
                yeni: $yeni,
                konuAdi: $alan->ad,
            );
        }

        $message = "\"{$alan->ad}\" alanı başarıyla güncellendi.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('alanlar.index')->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$alanlar] = $this->search($request, paginate: false);

        $filename = 'alanlar-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($alanlar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Ad', 'Aktif Kurs Sayısı', 'Durum', 'Oluşturma Tarihi'], ';');

            $alanlar->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $alan) {
                    fputcsv($handle, [
                        $alan->ad,
                        $alan->aktif_kurs_sayisi,
                        $alan->aktif ? 'Aktif' : 'Pasif',
                        $alan->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Alan $alan): View
    {
        $kurslar = $alan->kurslar()
            ->with(['merkez', 'brans'])
            ->where('durum', KursDurum::Aktif)
            ->orderByDesc('id')
            ->get();

        return view('alanlar.show', [
            'alan' => $alan,
            'kurslar' => $kurslar,
            'aktifKursSayisi' => $kurslar->count(),
            'toplamKursSayisi' => $alan->kurslar()->count(),
            'bransSayisi' => $alan->branslar()->count(),
        ]);
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function search(Request $request, bool $paginate = true): array
    {
        $query = Alan::query()->withCount([
            'kurslar as aktif_kurs_sayisi' => fn (Builder $q) => $q->where('durum', KursDurum::Aktif),
        ]);

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            if ($q !== '') {
                $query->where('ad', 'like', "%{$q}%");
            }
        }

        $durum = (string) $request->input('durum', 'tumu');
        if ($durum === 'aktif') {
            $query->where('aktif', true);
        } elseif ($durum === 'pasif') {
            $query->where('aktif', false);
        } else {
            $durum = 'tumu';
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'ad' => 'ad',
            'aktif_kurs_sayisi' => 'aktif_kurs_sayisi',
            'olusturma' => 'created_at',
        ];

        if (isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderBy('ad', 'asc');
            $sort = '';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only(['q', 'durum', 'per_page', 'sort', 'direction']);
        $filters['durum'] = $durum;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    /**
     * @return array<string, int>
     */
    private function ozet(): array
    {
        return [
            'toplam' => Alan::query()->count(),
            'aktif' => Alan::query()->where('aktif', true)->count(),
            'pasif' => Alan::query()->where('aktif', false)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Alan $alan = null): array
    {
        $validated = $request->validate([
            'ad' => [
                'required', 'string', 'max:150',
                Rule::unique('alanlar', 'ad')->ignore($alan?->id),
            ],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Alan adı zorunludur.',
            'ad.unique' => 'Bu isimde bir alan zaten kayıtlı.',
        ]);

        $validated['aktif'] = $request->boolean('aktif');

        return $validated;
    }
}
