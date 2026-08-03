<?php

namespace App\Http\Controllers;

use App\Enums\KursDurum;
use App\Models\Alan;
use App\Models\Brans;
use App\Services\LogKaydedici;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BransController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$branslar, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'branslar' => $branslar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('branslar._results', $viewData);
        }

        return view('branslar.index', $viewData + [
            'alanlar' => Alan::where('aktif', true)->orderBy('ad')->get(),
            'ozet' => $this->ozet(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validated($request);
        $brans = Brans::query()->create($validated);

        LogKaydedici::kaydet(
            islem: 'brans.olusturuldu',
            aciklama: '"'.$brans->ad.'" branşı oluşturuldu.',
            konu: $brans,
            yeni: ['ad' => $brans->ad, 'aktif' => (bool) $brans->aktif],
            konuAdi: $brans->ad,
        );

        $message = "\"{$brans->ad}\" branşı başarıyla oluşturuldu.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('branslar.index')->with('success', $message);
    }

    public function update(Request $request, Brans $brans): RedirectResponse|JsonResponse
    {
        $onceki = ['ad' => $brans->ad, 'aktif' => (bool) $brans->aktif];

        $validated = $this->validated($request, $brans);
        $brans->update($validated);

        $yeni = ['ad' => $brans->ad, 'aktif' => (bool) $brans->aktif];
        if ($onceki !== $yeni) {
            LogKaydedici::kaydet(
                islem: 'brans.guncellendi',
                aciklama: '"'.$brans->ad.'" branşı güncellendi.',
                konu: $brans,
                eski: $onceki,
                yeni: $yeni,
                konuAdi: $brans->ad,
            );
        }

        $message = "\"{$brans->ad}\" branşı başarıyla güncellendi.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('branslar.index')->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$branslar] = $this->search($request, paginate: false);

        $filename = 'branslar-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($branslar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Ad', 'Alan', 'Aktif Kurs Sayısı', 'Durum', 'Oluşturma Tarihi'], ';');

            $branslar->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $brans) {
                    fputcsv($handle, [
                        $brans->ad,
                        $brans->alan?->ad ?? '',
                        $brans->aktif_kurs_sayisi,
                        $brans->aktif ? 'Aktif' : 'Pasif',
                        $brans->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Brans $brans): View
    {
        $brans->load('alan');

        $kurslar = $brans->kurslar()
            ->with(['merkez', 'alan'])
            ->where('durum', KursDurum::Aktif)
            ->orderByDesc('id')
            ->get();

        return view('branslar.show', [
            'brans' => $brans,
            'kurslar' => $kurslar,
            'aktifKursSayisi' => $kurslar->count(),
            'toplamKursSayisi' => $brans->kurslar()->count(),
            'alanlar' => Alan::where('aktif', true)->orderBy('ad')->get(),
        ]);
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function search(Request $request, bool $paginate = true): array
    {
        $query = Brans::query()->with('alan')->withCount([
            'kurslar as aktif_kurs_sayisi' => fn (Builder $q) => $q->where('durum', KursDurum::Aktif),
        ]);

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            if ($q !== '') {
                $query->where('ad', 'like', "%{$q}%");
            }
        }

        if ($request->filled('alan_id')) {
            $query->where('alan_id', $request->integer('alan_id'));
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

        if ($sort === 'alan') {
            // Subquery kullanılıyor; withCount() ile eklenen sayım kolonunu
            // bozmamak için join + select('branslar.*') tercih edilmedi.
            $query->orderBy(
                Alan::query()->select('ad')->whereColumn('alanlar.id', 'branslar.alan_id'),
                $direction
            );
        } else {
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
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only(['q', 'alan_id', 'durum', 'per_page', 'sort', 'direction']);
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
            'toplam' => Brans::query()->count(),
            'aktif' => Brans::query()->where('aktif', true)->count(),
            'pasif' => Brans::query()->where('aktif', false)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Brans $brans = null): array
    {
        $validated = $request->validate([
            'ad' => ['required', 'string', 'max:150'],
            'alan_id' => ['required', 'integer', Rule::exists('alanlar', 'id')],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Branş adı zorunludur.',
            'alan_id.required' => 'Alan seçimi zorunludur.',
            'alan_id.exists' => 'Seçilen alan bulunamadı.',
        ]);

        $validated['aktif'] = $request->boolean('aktif');

        return $validated;
    }
}
