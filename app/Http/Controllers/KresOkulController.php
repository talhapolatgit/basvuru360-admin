<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesKresDonem;
use App\Models\KresBasvuru;
use App\Models\KresBasvuruDurum;
use App\Models\KresGrup;
use App\Models\KresOkul;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KresOkulController extends Controller
{
    use ResolvesKresDonem;

    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$okullar, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'okullar' => $okullar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('kres.okullar._results', $viewData);
        }

        return view('kres.okullar.index', $viewData);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $okul = KresOkul::query()->create($this->validated($request));
        $message = '"'.$okul->ad.'" okulu başarıyla oluşturuldu.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.okullar.index')->with('success', $message);
    }

    public function update(Request $request, KresOkul $kresOkul): RedirectResponse|JsonResponse
    {
        $kresOkul->update($this->validated($request, $kresOkul));
        $message = '"'.$kresOkul->ad.'" okulu başarıyla güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.okullar.index')->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$okullar] = $this->search($request, paginate: false);
        $filename = 'kres-okullar-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($okullar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Ad', 'Adres', 'Telefon', 'Grup Sayısı', 'Durum', 'Oluşturma Tarihi'], ';');

            $okullar->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $okul) {
                    fputcsv($handle, [
                        $okul->ad,
                        $okul->adres ?? '',
                        $okul->telefon ?? '',
                        $okul->gruplar_count,
                        $okul->aktif ? 'Aktif' : 'Pasif',
                        $okul->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Request $request, KresOkul $kresOkul): View
    {
        $donemData = $this->donemViewData($request);
        $aktifDonem = $donemData['aktifDonem'];

        $kesinId = KresBasvuruDurum::idByKod('kesin_kayit');

        $gruplar = collect();
        if ($aktifDonem) {
            $gruplar = KresGrup::query()
                ->where('okul_id', $kresOkul->id)
                ->where('donem_id', $aktifDonem->id)
                ->orderBy('ad')
                ->get()
                ->map(function (KresGrup $grup) use ($kesinId) {
                    $kesin = $kesinId
                        ? KresBasvuru::query()
                            ->where('grup_id', $grup->id)
                            ->where('durum_id', $kesinId)
                            ->count()
                        : 0;
                    $toplam = KresBasvuru::query()->where('grup_id', $grup->id)->count();
                    $kontenjan = (int) $grup->kontenjan;

                    return [
                        'model' => $grup,
                        'kesin_kayit' => $kesin,
                        'basvuru_sayisi' => $toplam,
                        'doluluk' => $kontenjan > 0 ? min(100, (int) round(($kesin / $kontenjan) * 100)) : 0,
                    ];
                });
        }

        return view('kres.okullar.show', $donemData + [
            'okul' => $kresOkul,
            'gruplar' => $gruplar,
            'step' => 2,
        ]);
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function search(Request $request, bool $paginate = true): array
    {
        $query = KresOkul::query()->withCount('gruplar');

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            if ($q !== '') {
                $query->where(function (Builder $inner) use ($q) {
                    $inner->where('ad', 'like', "%{$q}%")
                        ->orWhere('adres', 'like', "%{$q}%")
                        ->orWhere('telefon', 'like', "%{$q}%");
                });
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
            'grup_sayisi' => 'gruplar_count',
            'olusturma' => 'created_at',
        ];

        if (isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderBy('ad');
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
    private function validated(Request $request, ?KresOkul $okul = null): array
    {
        $validated = $request->validate([
            'ad' => [
                'required',
                'string',
                'max:150',
                Rule::unique('kres_okullar', 'ad')->ignore($okul?->id),
            ],
            'adres' => ['nullable', 'string', 'max:500'],
            'telefon' => ['nullable', 'string', 'max:30'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Okul adı zorunludur.',
            'ad.unique' => 'Bu okul adı zaten kayıtlı.',
        ]);

        $validated['aktif'] = $request->boolean('aktif');

        return $validated;
    }
}
