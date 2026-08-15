<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Http\Controllers\Concerns\ResolvesKresDonem;
use App\Models\KresBasvuru;
use App\Models\KresBasvuruDurum;
use App\Models\KresDonem;
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

class KresGrupController extends Controller
{
    use ResolvesKresDonem;

    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        $varsayilanDonemId = $this->varsayilanDonemFiltresi($request);

        [$gruplar, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'gruplar' => $gruplar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('kres.gruplar._results', $viewData);
        }

        return view('kres.gruplar.index', $viewData + [
            'okullar' => KresOkul::query()->orderBy('ad')->get(),
            'donemler' => KresDonem::query()->orderByDesc('aktif')->orderByDesc('baslangic')->orderBy('ad')->get(),
            'varsayilanDonemId' => $varsayilanDonemId,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        [$okul, $donemId] = $this->okulVeDonem($request);
        $validated = $this->validated($request, $okul, $donemId);
        $validated['okul_id'] = $okul->id;
        $validated['donem_id'] = $donemId;

        $grup = KresGrup::query()->create($validated);
        $message = '"'.$grup->ad.'" grubu başarıyla oluşturuldu.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.gruplar.index')->with('success', $message);
    }

    public function update(Request $request, KresGrup $kresGrup): RedirectResponse|JsonResponse
    {
        [$okul, $donemId] = $this->okulVeDonem($request);
        $validated = $this->validated($request, $okul, $donemId, $kresGrup);
        $validated['okul_id'] = $okul->id;
        $validated['donem_id'] = $donemId;
        $kresGrup->update($validated);

        $message = '"'.$kresGrup->ad.'" grubu başarıyla güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.gruplar.index')->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        $this->varsayilanDonemFiltresi($request);

        [$gruplar] = $this->search($request, paginate: false);
        $filename = 'kres-gruplar-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($gruplar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Ad', 'Okul', 'Dönem', 'Yaş Aralığı', 'Kontenjan', 'Cinsiyet', 'Durum', 'Oluşturma Tarihi'], ';');

            $gruplar->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $grup) {
                    fputcsv($handle, [
                        $grup->ad,
                        $grup->okul?->ad ?? '',
                        $grup->donem?->ad ?? '',
                        $grup->yasAraligiLabel(),
                        $grup->kontenjan,
                        $grup->cinsiyetSartiLabel(),
                        $grup->aktif ? 'Aktif' : 'Pasif',
                        $grup->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Request $request, KresOkul $kresOkul, KresGrup $kresGrup): View
    {
        abort_unless($kresGrup->okul_id === $kresOkul->id, 404);

        $donemData = $this->donemViewData($request);
        $durumlar = KresBasvuruDurum::query()->where('aktif', true)->orderBy('sira')->get();
        $kesinId = KresBasvuruDurum::idByKod('kesin_kayit');
        $kesinSayisi = $kesinId
            ? KresBasvuru::query()->where('grup_id', $kresGrup->id)->where('durum_id', $kesinId)->count()
            : 0;

        $columns = $this->basvuruTableColumns();
        $basvuruDurum = (string) $request->input('basvuru_durum', 'tumu');
        if ($basvuruDurum !== 'tumu' && ! $durumlar->firstWhere('kod', $basvuruDurum)) {
            $basvuruDurum = 'tumu';
        }

        return view('kres.gruplar.show', $donemData + [
            'okul' => $kresOkul,
            'grup' => $kresGrup,
            'durumlar' => $durumlar,
            'kesinSayisi' => $kesinSayisi,
            'basvuruColumns' => $columns['all'],
            'basvuruDefaultVisible' => $columns['defaultVisible'],
            'basvuruDurum' => $basvuruDurum,
            'step' => 3,
        ]);
    }

    public function basvurular(Request $request, KresOkul $kresOkul, KresGrup $kresGrup): JsonResponse
    {
        abort_unless($kresGrup->okul_id === $kresOkul->id, 404);

        [$basvurular, $basvuruDurum, $sort, $direction] = $this->searchBasvurular($request, $kresGrup);
        $columns = $this->basvuruTableColumns();

        return response()->json([
            'html' => view('kres.gruplar._basvurular_list', [
                'okul' => $kresOkul,
                'grup' => $kresGrup,
                'basvurular' => $basvurular,
                'basvuruColumns' => $columns['all'],
                'basvuruDefaultVisible' => $columns['defaultVisible'],
                'basvuruSortable' => $columns['sortable'],
                'sort' => $sort,
                'direction' => $direction,
            ])->render(),
            'total' => $basvurular->total(),
            'durum' => $basvuruDurum,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function exportBasvurular(Request $request, KresOkul $kresOkul, KresGrup $kresGrup): StreamedResponse
    {
        abort_unless($kresGrup->okul_id === $kresOkul->id, 404);

        [$basvurular] = $this->searchBasvurular($request, $kresGrup, paginate: false);

        $filename = 'kres-'.$kresOkul->id.'-grup-'.$kresGrup->id.'-basvurular-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($basvurular) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Öğrenci', 'T.C. Kimlik No', 'Doğum T.', 'Telefon',
                'Başvuran', 'Veli', 'Durum', 'Yedek Sıra', 'Not', 'Kaydeden', 'Başvuru Tarihi',
            ], ';');

            $basvurular->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $basvuru) {
                    fputcsv($handle, [
                        $basvuru->kisi?->tam_adi ?? '',
                        $basvuru->kisi?->tc_kimlik_no ?? '',
                        $basvuru->kisi?->dogum_tarihi?->format('d.m.Y') ?? '',
                        $basvuru->kisi?->telefon ?? '',
                        $basvuru->basvuran?->tam_adi ?? '',
                        $basvuru->basvuran?->tam_adi ?? '',
                        $basvuru->durum?->ad ?? '',
                        $basvuru->durum?->kod === 'yedek' ? ($basvuru->yedek_sira ?? '') : '',
                        $basvuru->notlar ?? '',
                        $basvuru->olusturan?->tam_adi ?? '',
                        $basvuru->created_at?->format('d.m.Y H:i') ?? '',
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
        $query = KresGrup::query()->with(['okul', 'donem']);

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            if ($q !== '') {
                $mode = (string) $request->input('q_mode', 'contains');
                match ($mode) {
                    'starts' => $query->where('ad', 'like', $q.'%'),
                    'ends' => $query->where('ad', 'like', '%'.$q),
                    'exact' => $query->where('ad', $q),
                    default => $query->where('ad', 'like', '%'.$q.'%'),
                };
            }
        }

        if ($request->filled('okul_id')) {
            $query->where('okul_id', $request->integer('okul_id'));
        }

        if ($this->donemFiltresiSecili($request)) {
            $query->where('donem_id', $request->integer('donem_id'));
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

        if ($sort === 'okul') {
            $query->orderBy(
                KresOkul::query()->select('ad')->whereColumn('kres_okullar.id', 'kres_gruplar.okul_id'),
                $direction
            );
        } elseif ($sort === 'donem') {
            $query->orderBy(
                KresDonem::query()->select('ad')->whereColumn('kres_donemler.id', 'kres_gruplar.donem_id'),
                $direction
            );
        } else {
            $sortable = [
                'ad' => 'ad',
                'kontenjan' => 'kontenjan',
                'yedek' => 'yedek_kontenjan',
                'olusturma' => 'created_at',
            ];

            if (isset($sortable[$sort])) {
                $query->orderBy($sortable[$sort], $direction);
            } else {
                $query->orderBy('ad');
                $sort = '';
            }
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only(['q', 'q_mode', 'okul_id', 'donem_id', 'durum', 'per_page', 'sort', 'direction']);
        $filters['durum'] = $durum;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    private function varsayilanDonemFiltresi(Request $request): ?int
    {
        $aktifId = KresDonem::query()->where('aktif', true)->value('id');
        $aktifId = $aktifId !== null ? (int) $aktifId : null;

        if (! $request->has('donem_id') && $aktifId) {
            $request->merge(['donem_id' => $aktifId]);
        }

        return $aktifId;
    }

    private function donemFiltresiSecili(Request $request): bool
    {
        if (! $request->filled('donem_id')) {
            return false;
        }

        return (string) $request->input('donem_id') !== 'tumu';
    }

    /**
     * @return array{0: KresOkul, 1: int}
     */
    private function okulVeDonem(Request $request): array
    {
        $ids = $request->validate([
            'okul_id' => ['required', 'integer', Rule::exists('kres_okullar', 'id')],
            'donem_id' => ['required', 'integer', Rule::exists('kres_donemler', 'id')],
        ], [
            'okul_id.required' => 'Okul seçimi zorunludur.',
            'donem_id.required' => 'Dönem seçimi zorunludur.',
        ]);

        return [KresOkul::query()->findOrFail($ids['okul_id']), (int) $ids['donem_id']];
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: string}
     */
    private function searchBasvurular(Request $request, KresGrup $grup, bool $paginate = true): array
    {
        $durumlar = KresBasvuruDurum::query()->where('aktif', true)->orderBy('sira')->get();
        $basvuruDurum = (string) $request->input('basvuru_durum', 'tumu');

        $query = KresBasvuru::query()
            ->where('grup_id', $grup->id)
            ->with(['kisi', 'basvuran', 'durum', 'olusturan']);

        $secili = $durumlar->firstWhere('kod', $basvuruDurum);
        if ($secili) {
            $query->where('durum_id', $secili->id);
        } else {
            $basvuruDurum = 'tumu';
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'ogrenci' => 'kisi.ad',
            'kimlik' => 'kisi.tc_kimlik_no',
            'dogum' => 'kisi.dogum_tarihi',
            'telefon' => 'kisi.telefon',
            'basvuran' => 'basvuran.ad',
            'veli' => 'basvuran.ad',
            'durum' => 'kres_basvuru_durumlari.ad',
            'yedek_sira' => 'kres_basvurulari.yedek_sira',
            'kaydeden' => 'olusturan.ad',
            'basvuru_tarihi' => 'kres_basvurulari.created_at',
        ];

        if ($sort === '' && $basvuruDurum === 'yedek') {
            $sort = 'yedek_sira';
            $direction = 'asc';
        }

        if (isset($sortable[$sort])) {
            $query->reorder();

            if (in_array($sort, ['ogrenci', 'kimlik', 'dogum', 'telefon'], true)) {
                $query->leftJoin('kisiler as kisi', 'kisi.id', '=', 'kres_basvurulari.kisi_id')
                    ->orderBy($sortable[$sort], $direction)
                    ->select('kres_basvurulari.*');
            } elseif (in_array($sort, ['basvuran', 'veli'], true)) {
                $query->leftJoin('kisiler as basvuran', 'basvuran.id', '=', 'kres_basvurulari.basvuran_id')
                    ->orderBy('basvuran.ad', $direction)
                    ->select('kres_basvurulari.*');
            } elseif ($sort === 'durum') {
                $query->leftJoin('kres_basvuru_durumlari', 'kres_basvuru_durumlari.id', '=', 'kres_basvurulari.durum_id')
                    ->orderBy('kres_basvuru_durumlari.ad', $direction)
                    ->select('kres_basvurulari.*');
            } elseif ($sort === 'kaydeden') {
                $query->leftJoin('users as olusturan', 'olusturan.id', '=', 'kres_basvurulari.olusturan_id')
                    ->orderBy('olusturan.ad', $direction)
                    ->select('kres_basvurulari.*');
            } else {
                $query->orderBy($sortable[$sort], $direction);
            }
        } else {
            $query->latest('kres_basvurulari.created_at');
            $sort = '';
        }

        if ($paginate) {
            return [$query->paginate(20)->withQueryString(), $basvuruDurum, $sort, $direction];
        }

        return [$query, $basvuruDurum, $sort, $direction];
    }

    /**
     * @return array{all: array<string, string>, defaultVisible: list<string>, sortable: list<string>}
     */
    private function basvuruTableColumns(): array
    {
        return [
            'all' => [
                'ogrenci' => 'Öğrenci',
                'kimlik' => 'Kimlik No',
                'dogum' => 'Doğum T.',
                'telefon' => 'Telefon',
                'basvuran' => 'Başvuran',
                'veli' => 'Veli',
                'durum' => 'Durum',
                'yedek_sira' => 'Yedek Sıra',
                'notlar' => 'Not',
                'kaydeden' => 'Kaydeden',
                'basvuru_tarihi' => 'Başvuru Tarihi',
                'islemler' => 'İşlemler',
            ],
            'defaultVisible' => [
                'ogrenci', 'kimlik', 'basvuran', 'durum', 'yedek_sira', 'basvuru_tarihi', 'islemler',
            ],
            'sortable' => [
                'ogrenci', 'kimlik', 'dogum', 'telefon', 'basvuran', 'veli', 'durum', 'yedek_sira', 'kaydeden', 'basvuru_tarihi',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(
        Request $request,
        KresOkul $okul,
        int $donemId,
        ?KresGrup $grup = null,
    ): array {
        if ($request->input('cinsiyet_sarti') === '') {
            $request->merge(['cinsiyet_sarti' => null]);
        }

        $validated = $request->validate([
            'ad' => [
                'required',
                'string',
                'max:120',
                Rule::unique('kres_gruplar', 'ad')
                    ->where(fn ($q) => $q->where('okul_id', $okul->id)->where('donem_id', $donemId))
                    ->ignore($grup?->id),
            ],
            'min_yas' => ['nullable', 'integer', 'min:0', 'max:18'],
            'max_yas' => ['nullable', 'integer', 'min:0', 'max:18', 'gte:min_yas'],
            'kontenjan' => ['required', 'integer', 'min:0', 'max:500'],
            'yedek_kontenjan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cinsiyet_sarti' => ['nullable', Rule::enum(Cinsiyet::class)],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Grup adı zorunludur.',
            'ad.unique' => 'Bu okul ve dönemde aynı grup adı var.',
            'max_yas.gte' => 'Maksimum yaş minimumdan küçük olamaz.',
            'kontenjan.required' => 'Kontenjan zorunludur.',
        ]);

        $validated['aktif'] = $request->boolean('aktif');
        $validated['cinsiyet_sarti'] = $validated['cinsiyet_sarti'] ?? null;
        $validated['yedek_kontenjan'] = $validated['yedek_kontenjan'] ?? 0;

        return $validated;
    }
}
