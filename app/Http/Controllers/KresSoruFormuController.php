<?php

namespace App\Http\Controllers;

use App\Enums\KresSoruTipi;
use App\Models\KresDonem;
use App\Models\KresSoru;
use App\Models\KresSoruFormu;
use App\Models\KresSoruSecenek;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KresSoruFormuController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        $varsayilanDonemId = $this->varsayilanDonemFiltresi($request);

        [$formlar, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'formlar' => $formlar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('kres.soru-formlari._results', $viewData);
        }

        $donemler = KresDonem::query()->orderByDesc('aktif')->orderByDesc('baslangic')->orderBy('ad')->get();
        $formluDonemIds = KresSoruFormu::query()->pluck('donem_id')->map(fn ($id) => (int) $id)->values()->all();

        return view('kres.soru-formlari.index', $viewData + [
            'donemler' => $donemler,
            'formluDonemIds' => $formluDonemIds,
            'varsayilanDonemId' => $varsayilanDonemId,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $form = $this->formuKaydet($request);
        $message = '"'.$form->ad.'" soru formu başarıyla oluşturuldu.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('kres.soru-formlari.show', $form),
            ]);
        }

        return redirect()->route('kres.soru-formlari.show', $form)->with('success', $message);
    }

    public function update(Request $request, KresSoruFormu $kresSoruFormu): RedirectResponse|JsonResponse
    {
        $form = $this->formuKaydet($request, $kresSoruFormu);
        $message = '"'.$form->ad.'" soru formu başarıyla güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.soru-formlari.index')->with('success', $message);
    }

    public function destroy(Request $request, KresSoruFormu $kresSoruFormu): RedirectResponse|JsonResponse
    {
        $ad = $kresSoruFormu->ad;
        $kresSoruFormu->delete();
        $message = '"'.$ad.'" soru formu silindi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.soru-formlari.index')->with('success', $message);
    }

    public function show(KresSoruFormu $kresSoruFormu): View
    {
        $kresSoruFormu->load(['donem', 'sorular.secenekler']);

        return view('kres.soru-formlari.show', [
            'form' => $kresSoruFormu,
            'soruTipleri' => KresSoruTipi::cases(),
        ]);
    }

    public function onizleme(KresSoruFormu $kresSoruFormu): View
    {
        $kresSoruFormu->load(['donem', 'sorular.secenekler']);

        return view('kres.soru-formlari._preview', [
            'form' => $kresSoruFormu,
        ]);
    }

    public function storeSoru(Request $request, KresSoruFormu $kresSoruFormu): RedirectResponse|JsonResponse
    {
        $soru = $this->soruyuKaydet($request, $kresSoruFormu);
        $message = '"'.$soru->baslik.'" sorusu eklendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($this->soruJson($message, $kresSoruFormu, $soru));
        }

        return redirect()->route('kres.soru-formlari.show', $kresSoruFormu)->with('success', $message);
    }

    public function updateSoru(Request $request, KresSoruFormu $kresSoruFormu, KresSoru $kresSoru): RedirectResponse|JsonResponse
    {
        $this->soruFormaAit($kresSoruFormu, $kresSoru);
        $soru = $this->soruyuKaydet($request, $kresSoruFormu, $kresSoru);
        $message = '"'.$soru->baslik.'" sorusu güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($this->soruJson($message, $kresSoruFormu, $soru));
        }

        return redirect()->route('kres.soru-formlari.show', $kresSoruFormu)->with('success', $message);
    }

    public function destroySoru(Request $request, KresSoruFormu $kresSoruFormu, KresSoru $kresSoru): RedirectResponse|JsonResponse
    {
        $this->soruFormaAit($kresSoruFormu, $kresSoru);
        $baslik = $kresSoru->baslik;
        $kresSoru->delete();
        $this->sorulariYenidenSirala($kresSoruFormu);
        $message = '"'.$baslik.'" sorusu silindi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('kres.soru-formlari.show', $kresSoruFormu)->with('success', $message);
    }

    public function siralaSorular(Request $request, KresSoruFormu $kresSoruFormu): JsonResponse
    {
        $validated = $request->validate([
            'sira' => ['required', 'array', 'min:1'],
            'sira.*' => ['integer', Rule::exists('kres_sorular', 'id')->where('form_id', $kresSoruFormu->id)],
        ]);

        DB::transaction(function () use ($validated, $kresSoruFormu) {
            foreach (array_values($validated['sira']) as $index => $id) {
                KresSoru::query()
                    ->where('form_id', $kresSoruFormu->id)
                    ->whereKey($id)
                    ->update(['sira' => $index + 1]);
            }
        });

        return response()->json(['message' => 'Soru sırası güncellendi.']);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        $this->varsayilanDonemFiltresi($request);

        [$formlar] = $this->search($request, paginate: false);
        $filename = 'kres-soru-formlari-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($formlar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Ad', 'Dönem', 'Soru Sayısı', 'Durum', 'Oluşturma Tarihi'], ';');

            $formlar->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $form) {
                    fputcsv($handle, [
                        $form->ad,
                        $form->donem?->ad ?? '',
                        $form->sorular_count,
                        $form->aktif ? 'Aktif' : 'Pasif',
                        $form->created_at?->format('d.m.Y H:i') ?? '',
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
        $query = KresSoruFormu::query()->with('donem')->withCount('sorular');

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

        if ($request->filled('donem_id') && (string) $request->input('donem_id') !== 'tumu') {
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

        if ($sort === 'donem') {
            $query->orderBy(
                KresDonem::query()->select('ad')->whereColumn('kres_donemler.id', 'kres_soru_formlari.donem_id'),
                $direction
            );
        } else {
            $sortable = [
                'ad' => 'ad',
                'soru_sayisi' => 'sorular_count',
                'olusturma' => 'created_at',
            ];

            if (isset($sortable[$sort])) {
                $query->orderBy($sortable[$sort], $direction);
            } else {
                $query->orderByDesc('created_at');
                $sort = '';
            }
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only(['q', 'q_mode', 'donem_id', 'durum', 'per_page', 'sort', 'direction']);
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

    private function formuKaydet(Request $request, ?KresSoruFormu $form = null): KresSoruFormu
    {
        $validated = $request->validate([
            'donem_id' => [
                'required',
                'integer',
                Rule::exists('kres_donemler', 'id'),
                Rule::unique('kres_soru_formlari', 'donem_id')->ignore($form),
            ],
            'ad' => ['required', 'string', 'max:255'],
            'aciklama' => ['nullable', 'string', 'max:2000'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'donem_id.required' => 'Dönem seçimi zorunludur.',
            'donem_id.unique' => 'Bu dönem için zaten bir soru formu var.',
            'ad.required' => 'Form adı zorunludur.',
        ]);

        $validated['aktif'] = $request->boolean('aktif');

        if ($form) {
            $form->update($validated);

            return $form->refresh();
        }

        return KresSoruFormu::query()->create($validated);
    }

    private function soruyuKaydet(Request $request, KresSoruFormu $form, ?KresSoru $soru = null): KresSoru
    {
        $validated = $request->validate([
            'tip' => ['required', Rule::enum(KresSoruTipi::class)],
            'baslik' => ['required', 'string', 'max:500'],
            'aciklama' => ['nullable', 'string', 'max:2000'],
            'zorunlu' => ['nullable', 'boolean'],
            'tam_sayi' => ['nullable', 'boolean'],
            'min_deger' => ['nullable', 'numeric'],
            'max_deger' => ['nullable', 'numeric'],
            'secenekler' => ['nullable', 'array'],
            'secenekler.*' => ['nullable', 'string', 'max:255'],
        ], [
            'tip.required' => 'Soru tipi seçin.',
            'baslik.required' => 'Soru metni zorunludur.',
        ]);

        $tip = KresSoruTipi::from($validated['tip']);
        $secenekler = collect($validated['secenekler'] ?? [])
            ->map(fn ($etiket) => trim((string) $etiket))
            ->filter(fn ($etiket) => $etiket !== '')
            ->values();

        if ($tip->secenekGerekli() && $secenekler->count() < $tip->minSecenek()) {
            $min = $tip->minSecenek();
            throw ValidationException::withMessages([
                'secenekler' => $tip->label().' tipi için en az '.$min.' seçenek girin.',
            ]);
        }

        $minDeger = null;
        $maxDeger = null;
        if (in_array($tip, [KresSoruTipi::Sayi, KresSoruTipi::Checkbox], true) && $request->filled('min_deger')) {
            $minDeger = (float) $request->input('min_deger');
        }
        if (in_array($tip, [KresSoruTipi::Sayi, KresSoruTipi::Checkbox], true) && $request->filled('max_deger')) {
            $maxDeger = (float) $request->input('max_deger');
        }

        if ($tip === KresSoruTipi::Checkbox) {
            if ($minDeger !== null && ($minDeger < 0 || fmod($minDeger, 1.0) !== 0.0)) {
                throw ValidationException::withMessages([
                    'min_deger' => 'Minimum seçim adedi 0 veya daha büyük tam sayı olmalıdır.',
                ]);
            }
            if ($maxDeger !== null && ($maxDeger < 1 || fmod($maxDeger, 1.0) !== 0.0)) {
                throw ValidationException::withMessages([
                    'max_deger' => 'Maksimum seçim adedi 1 veya daha büyük tam sayı olmalıdır.',
                ]);
            }
            $secenekAdedi = $secenekler->count();
            if ($minDeger !== null && $minDeger > $secenekAdedi) {
                throw ValidationException::withMessages([
                    'min_deger' => 'Minimum seçim, seçenek sayısından fazla olamaz.',
                ]);
            }
            if ($maxDeger !== null && $maxDeger > $secenekAdedi) {
                throw ValidationException::withMessages([
                    'max_deger' => 'Maksimum seçim, seçenek sayısından fazla olamaz.',
                ]);
            }
        }

        if ($minDeger !== null && $maxDeger !== null && $minDeger > $maxDeger) {
            throw ValidationException::withMessages([
                'max_deger' => 'Maksimum değer minimumdan küçük olamaz.',
            ]);
        }

        return DB::transaction(function () use ($validated, $tip, $secenekler, $form, $soru, $request, $minDeger, $maxDeger) {
            $payload = [
                'tip' => $tip,
                'baslik' => $validated['baslik'],
                'aciklama' => $validated['aciklama'] ?? null,
                'zorunlu' => $request->boolean('zorunlu'),
                'tam_sayi' => $tip === KresSoruTipi::Sayi && $request->boolean('tam_sayi'),
                'min_deger' => $minDeger,
                'max_deger' => $maxDeger,
            ];

            if ($soru) {
                $soru->update($payload);
            } else {
                $payload['form_id'] = $form->id;
                $payload['sira'] = ((int) $form->sorular()->max('sira')) + 1;
                $soru = KresSoru::query()->create($payload);
            }

            $soru->secenekler()->delete();

            if ($tip->secenekGerekli()) {
                foreach ($secenekler as $index => $etiket) {
                    KresSoruSecenek::query()->create([
                        'soru_id' => $soru->id,
                        'etiket' => $etiket,
                        'sira' => $index + 1,
                    ]);
                }
            }

            return $soru->refresh()->load('secenekler');
        });
    }

    /**
     * @return array{message: string, id: int, html: string}
     */
    private function soruJson(string $message, KresSoruFormu $form, KresSoru $soru): array
    {
        $soru->loadMissing('secenekler');

        return [
            'message' => $message,
            'id' => $soru->id,
            'html' => view('kres.soru-formlari._card', [
                'form' => $form,
                'soru' => $soru,
                'index' => (int) $soru->sira,
            ])->render(),
        ];
    }

    private function soruFormaAit(KresSoruFormu $form, KresSoru $soru): void
    {
        abort_unless($soru->form_id === $form->id, 404);
    }

    private function sorulariYenidenSirala(KresSoruFormu $form): void
    {
        $form->sorular()->orderBy('sira')->orderBy('id')->get()->each(function (KresSoru $soru, int $index) {
            $soru->update(['sira' => $index + 1]);
        });
    }
}
