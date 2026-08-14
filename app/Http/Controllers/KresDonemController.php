<?php

namespace App\Http\Controllers;

use App\Models\KresDonem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KresDonemController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$donemler, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'donemler' => $donemler,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('kres.donemler._results', $viewData);
        }

        return view('kres.donemler.index', $viewData);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $donem = KresDonem::query()->create($this->validated($request));
        $message = '"'.$donem->ad.'" dönemi başarıyla oluşturuldu.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.donemler.index')->with('success', $message);
    }

    public function update(Request $request, KresDonem $kresDonem): RedirectResponse|JsonResponse
    {
        $kresDonem->update($this->validated($request, $kresDonem));
        $message = '"'.$kresDonem->ad.'" dönemi başarıyla güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.donemler.index')->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$donemler] = $this->search($request, paginate: false);
        $filename = 'kres-donemler-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($donemler) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Ad', 'Başlangıç', 'Bitiş', 'Grup Sayısı', 'Durum', 'Oluşturma Tarihi'], ';');

            $donemler->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $donem) {
                    fputcsv($handle, [
                        $donem->ad,
                        $donem->baslangic?->format('d.m.Y') ?? '',
                        $donem->bitis?->format('d.m.Y') ?? '',
                        $donem->gruplar_count,
                        $donem->aktif ? 'Aktif' : 'Pasif',
                        $donem->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function search(Request $request, bool $paginate = true): array
    {
        $query = KresDonem::query()->withCount('gruplar');

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
            'baslangic' => 'baslangic',
            'bitis' => 'bitis',
            'grup_sayisi' => 'gruplar_count',
            'olusturma' => 'created_at',
        ];

        if (isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderByDesc('aktif')->orderByDesc('baslangic')->orderByDesc('id');
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
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?KresDonem $donem = null): array
    {
        $validated = $request->validate([
            'ad' => [
                'required',
                'string',
                'max:120',
                Rule::unique('kres_donemler', 'ad')->ignore($donem?->id),
            ],
            'baslangic' => ['nullable', 'date'],
            'bitis' => ['nullable', 'date', 'after_or_equal:baslangic'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Dönem adı zorunludur.',
            'ad.unique' => 'Bu dönem adı zaten kayıtlı.',
            'bitis.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
        ]);

        $validated['aktif'] = $request->boolean('aktif');

        return $validated;
    }
}
