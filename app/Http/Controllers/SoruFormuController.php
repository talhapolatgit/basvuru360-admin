<?php

namespace App\Http\Controllers;

use App\Enums\SoruTipi;
use App\Models\Soru;
use App\Models\SoruFormu;
use App\Models\SoruSecenek;
use App\Services\LogKaydedici;
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

class SoruFormuController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$formlar, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'formlar' => $formlar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('soru-formlari._results', $viewData);
        }

        return view('soru-formlari.index', $viewData);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $form = $this->formuKaydet($request);
        $message = '"'.$form->ad.'" soru formu başarıyla oluşturuldu.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('soru-formlari.show', $form),
            ]);
        }

        return redirect()->route('soru-formlari.show', $form)->with('success', $message);
    }

    public function update(Request $request, SoruFormu $soruFormu): RedirectResponse|JsonResponse
    {
        $form = $this->formuKaydet($request, $soruFormu);
        $message = '"'.$form->ad.'" soru formu başarıyla güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('soru-formlari.index')->with('success', $message);
    }

    public function destroy(Request $request, SoruFormu $soruFormu): RedirectResponse|JsonResponse
    {
        $ad = $soruFormu->ad;
        $soruFormu->delete();
        LogKaydedici::kaydet(islem: 'soru_formu.silindi', aciklama: '"'.$ad.'" soru formu silindi.', konuAdi: $ad);
        $message = '"'.$ad.'" soru formu silindi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('soru-formlari.index')->with('success', $message);
    }

    public function kopyala(Request $request, SoruFormu $soruFormu): RedirectResponse|JsonResponse
    {
        $soruFormu->load('sorular.secenekler');

        $kopya = DB::transaction(function () use ($soruFormu) {
            $kopya = SoruFormu::query()->create([
                'ad' => mb_substr($soruFormu->ad.' (Kopya)', 0, 255),
                'aciklama' => $soruFormu->aciklama,
                'aktif' => false,
            ]);

            $soruMap = [];
            $secenekMap = [];
            foreach ($soruFormu->sorular as $soru) {
                $yeni = $kopya->sorular()->create($soru->only([
                    'tip', 'baslik', 'aciklama', 'zorunlu', 'min_deger', 'max_deger', 'tam_sayi', 'sira',
                ]));
                $soruMap[$soru->id] = $yeni->id;
                foreach ($soru->secenekler as $secenek) {
                    $secenekMap[$secenek->id] = $yeni->secenekler()->create($secenek->only(['etiket', 'sira']))->id;
                }
            }

            foreach ($soruFormu->sorular as $soru) {
                if (! $soru->kosulluMu() || ! isset($soruMap[$soru->kosul_soru_id])) {
                    continue;
                }
                Soru::query()->whereKey($soruMap[$soru->id])->update([
                    'kosul_soru_id' => $soruMap[$soru->kosul_soru_id],
                    'kosul_secenek_ids' => json_encode(array_values(array_filter(
                        array_map(fn ($id) => $secenekMap[$id] ?? null, $soru->kosulSecenekIdleri())
                    ))),
                ]);
            }

            return $kopya;
        });

        $message = '"'.$soruFormu->ad.'" formunun kopyası oluşturuldu. Kopya pasif olarak kaydedildi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message, 'redirect' => route('soru-formlari.show', $kopya)]);
        }

        return redirect()->route('soru-formlari.show', $kopya)->with('success', $message);
    }

    public function show(SoruFormu $soruFormu): View
    {
        $soruFormu->load([
            'sorular.secenekler',
            'kurslar' => fn ($q) => $q->select(['id', 'kurs_no', 'brans_id', 'soru_formu_id'])->with('brans:id,ad'),
            'etkinlikler:id,etkinlik_no,ad,soru_formu_id',
            'kresDonemleri:id,ad,soru_formu_id',
        ]);

        return view('soru-formlari.show', [
            'form' => $soruFormu,
            'soruTipleri' => SoruTipi::cases(),
        ]);
    }

    public function onizleme(SoruFormu $soruFormu): View
    {
        $soruFormu->load('sorular.secenekler');

        return view('soru-formlari._preview', [
            'form' => $soruFormu,
        ]);
    }

    public function storeSoru(Request $request, SoruFormu $soruFormu): RedirectResponse|JsonResponse
    {
        $soru = $this->soruyuKaydet($request, $soruFormu);
        $message = '"'.$soru->baslik.'" sorusu eklendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($this->listeJson($message, $soruFormu));
        }

        return redirect()->route('soru-formlari.show', $soruFormu)->with('success', $message);
    }

    public function updateSoru(Request $request, SoruFormu $soruFormu, Soru $soru): RedirectResponse|JsonResponse
    {
        $this->soruFormaAit($soruFormu, $soru);
        $soru = $this->soruyuKaydet($request, $soruFormu, $soru);
        $message = '"'.$soru->baslik.'" sorusu güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($this->listeJson($message, $soruFormu));
        }

        return redirect()->route('soru-formlari.show', $soruFormu)->with('success', $message);
    }

    public function destroySoru(Request $request, SoruFormu $soruFormu, Soru $soru): RedirectResponse|JsonResponse
    {
        $this->soruFormaAit($soruFormu, $soru);
        $baslik = $soru->baslik;

        DB::transaction(function () use ($soruFormu, $soru) {
            Soru::query()->where('kosul_soru_id', $soru->id)->update(['kosul_soru_id' => null, 'kosul_secenek_ids' => null]);
            $soru->delete();
            $this->sorulariYenidenSirala($soruFormu);
        });

        $message = '"'.$baslik.'" sorusu silindi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($this->listeJson($message, $soruFormu));
        }

        return redirect()->route('soru-formlari.show', $soruFormu)->with('success', $message);
    }

    public function siralaSorular(Request $request, SoruFormu $soruFormu): JsonResponse
    {
        $validated = $request->validate([
            'sira' => ['required', 'array', 'min:1'],
            'sira.*' => ['integer', Rule::exists('sorular', 'id')->where('form_id', $soruFormu->id)],
        ]);

        $sira = array_flip(array_map('intval', array_values($validated['sira'])));

        foreach ($soruFormu->sorular()->get() as $soru) {
            if (! $soru->kosulluMu() || ! isset($sira[$soru->id], $sira[$soru->kosul_soru_id])) {
                continue;
            }
            if ($sira[$soru->kosul_soru_id] > $sira[$soru->id]) {
                throw ValidationException::withMessages([
                    'sira' => '"'.$soru->baslik.'" sorusu koşul olarak bağlı olduğu sorudan önce gelemez.',
                ]);
            }
        }

        DB::transaction(function () use ($sira, $soruFormu) {
            foreach ($sira as $id => $index) {
                Soru::query()
                    ->where('form_id', $soruFormu->id)
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

        [$formlar] = $this->search($request, paginate: false);
        $filename = 'soru-formlari-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($formlar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Ad', 'Soru Sayısı', 'Kullanıldığı Yer Sayısı', 'Durum', 'Oluşturma Tarihi'], ';');

            $formlar->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $form) {
                    fputcsv($handle, [
                        $form->ad,
                        $form->sorular_count,
                        $form->kullanimSayisi(),
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
        $query = SoruFormu::query()->withCount(['sorular', 'kurslar', 'etkinlikler', 'kresDonemleri']);

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
            'soru_sayisi' => 'sorular_count',
            'olusturma' => 'created_at',
        ];

        if (isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderByDesc('created_at');
            $sort = '';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only(['q', 'q_mode', 'durum', 'per_page', 'sort', 'direction']);
        $filters['durum'] = $durum;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    private function formuKaydet(Request $request, ?SoruFormu $form = null): SoruFormu
    {
        $validated = $request->validate([
            'ad' => ['required', 'string', 'max:255'],
            'aciklama' => ['nullable', 'string', 'max:2000'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Form adı zorunludur.',
        ]);

        $validated['aktif'] = $request->boolean('aktif');

        if ($form) {
            $form->update($validated);

            return $form->refresh();
        }

        return SoruFormu::query()->create($validated);
    }

    private function soruyuKaydet(Request $request, SoruFormu $form, ?Soru $soru = null): Soru
    {
        $validated = $request->validate([
            'tip' => ['required', Rule::enum(SoruTipi::class)],
            'baslik' => ['required', 'string', 'max:500'],
            'aciklama' => ['nullable', 'string', 'max:2000'],
            'zorunlu' => ['nullable', 'boolean'],
            'tam_sayi' => ['nullable', 'boolean'],
            'min_deger' => ['nullable', 'numeric'],
            'max_deger' => ['nullable', 'numeric'],
            'secenekler' => ['nullable', 'array'],
            'secenekler.*' => ['nullable', 'string', 'max:255'],
            'secenek_idler' => ['nullable', 'array'],
            'secenek_idler.*' => ['nullable', 'integer'],
            'kosul_soru_id' => ['nullable', 'integer'],
            'kosul_secenek_ids' => ['nullable', 'array'],
            'kosul_secenek_ids.*' => ['integer'],
        ], [
            'tip.required' => 'Soru tipi seçin.',
            'baslik.required' => 'Soru metni zorunludur.',
        ]);

        $tip = SoruTipi::from($validated['tip']);

        $idler = array_values($validated['secenek_idler'] ?? []);
        $secenekler = collect(array_values($validated['secenekler'] ?? []))
            ->map(fn ($etiket, $index) => [
                'id' => isset($idler[$index]) && $idler[$index] ? (int) $idler[$index] : null,
                'etiket' => trim((string) $etiket),
            ])
            ->filter(fn ($satir) => $satir['etiket'] !== '')
            ->values();

        if ($tip->secenekGerekli() && $secenekler->count() < $tip->minSecenek()) {
            throw ValidationException::withMessages([
                'secenekler' => $tip->label().' tipi için en az '.$tip->minSecenek().' seçenek girin.',
            ]);
        }

        [$minDeger, $maxDeger] = $this->sinirlar($request, $tip, $secenekler->count());
        [$kosulSoruId, $kosulSecenekIds] = $this->kosulDogrula($request, $form, $soru);

        return DB::transaction(function () use ($validated, $tip, $secenekler, $form, $soru, $request, $minDeger, $maxDeger, $kosulSoruId, $kosulSecenekIds) {
            $payload = [
                'tip' => $tip,
                'baslik' => $validated['baslik'],
                'aciklama' => $validated['aciklama'] ?? null,
                'zorunlu' => $request->boolean('zorunlu'),
                'tam_sayi' => $tip === SoruTipi::Sayi && $request->boolean('tam_sayi'),
                'min_deger' => $minDeger,
                'max_deger' => $maxDeger,
                'kosul_soru_id' => $kosulSoruId,
                'kosul_secenek_ids' => $kosulSecenekIds,
            ];

            if ($soru) {
                $soru->update($payload);
            } else {
                $payload['form_id'] = $form->id;
                $payload['sira'] = ((int) $form->sorular()->max('sira')) + 1;
                $soru = Soru::query()->create($payload);
            }

            $this->secenekleriEsitle($soru, $tip->secenekGerekli() ? $secenekler->all() : []);
            $this->bagliKosullariTemizle($soru);

            return $soru->refresh()->load('secenekler');
        });
    }

    /**
     * Seçenekleri yerinde günceller; böylece seçenek id'leri (ve kayıtlı cevaplar) korunur.
     *
     * @param  list<array{id: ?int, etiket: string}>  $satirlar
     */
    private function secenekleriEsitle(Soru $soru, array $satirlar): void
    {
        $mevcut = $soru->secenekler()->get()->keyBy('id');
        $kalanIdler = [];

        foreach ($satirlar as $index => $satir) {
            $secenek = $satir['id'] !== null ? $mevcut->get($satir['id']) : null;
            if ($secenek) {
                $secenek->update(['etiket' => $satir['etiket'], 'sira' => $index + 1]);
            } else {
                $secenek = SoruSecenek::query()->create([
                    'soru_id' => $soru->id,
                    'etiket' => $satir['etiket'],
                    'sira' => $index + 1,
                ]);
            }
            $kalanIdler[] = $secenek->id;
        }

        $soru->secenekler()->whereNotIn('id', $kalanIdler ?: [0])->delete();
    }

    /**
     * Bu soruya koşulla bağlı soruların artık var olmayan seçenek id'lerini temizler.
     */
    private function bagliKosullariTemizle(Soru $soru): void
    {
        $gecerli = $soru->tip->secenekGerekli()
            ? $soru->secenekler()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        Soru::query()->where('kosul_soru_id', $soru->id)->get()->each(function (Soru $bagli) use ($gecerli) {
            $kalan = array_values(array_intersect($bagli->kosulSecenekIdleri(), $gecerli));
            $bagli->update($kalan === []
                ? ['kosul_soru_id' => null, 'kosul_secenek_ids' => null]
                : ['kosul_secenek_ids' => $kalan]);
        });
    }

    /**
     * @return array{0: ?int, 1: ?list<int>}
     */
    private function kosulDogrula(Request $request, SoruFormu $form, ?Soru $soru): array
    {
        $ustId = $request->integer('kosul_soru_id');
        if ($ustId <= 0) {
            return [null, null];
        }

        /** @var Soru|null $ust */
        $ust = $form->sorular()->with('secenekler')->whereKey($ustId)->first();
        if (! $ust || ($soru && $ust->id === $soru->id)) {
            throw ValidationException::withMessages(['kosul_soru_id' => 'Koşul için bu formdaki başka bir soruyu seçin.']);
        }
        if (! $ust->tip->secenekGerekli()) {
            throw ValidationException::withMessages(['kosul_soru_id' => 'Koşul yalnızca seçmeli (liste, radio, checkbox) sorulara bağlanabilir.']);
        }
        if ($soru && $ust->sira >= $soru->sira) {
            throw ValidationException::withMessages(['kosul_soru_id' => 'Koşul sorusu bu sorudan önce gelmelidir.']);
        }
        if ($ust->kosul_soru_id !== null && $soru && $ust->kosul_soru_id === $soru->id) {
            throw ValidationException::withMessages(['kosul_soru_id' => 'Sorular birbirine karşılıklı koşul olarak bağlanamaz.']);
        }

        $gecerli = $ust->secenekler->pluck('id')->map(fn ($id) => (int) $id)->all();
        $secilen = array_values(array_unique(array_intersect(
            array_map('intval', (array) $request->input('kosul_secenek_ids', [])),
            $gecerli
        )));

        if ($secilen === []) {
            throw ValidationException::withMessages(['kosul_secenek_ids' => 'Koşul için en az bir cevap seçin.']);
        }

        return [$ust->id, $secilen];
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function sinirlar(Request $request, SoruTipi $tip, int $secenekAdedi): array
    {
        $sinirli = in_array($tip, [SoruTipi::Sayi, SoruTipi::Checkbox], true);
        $minDeger = $sinirli && $request->filled('min_deger') ? (float) $request->input('min_deger') : null;
        $maxDeger = $sinirli && $request->filled('max_deger') ? (float) $request->input('max_deger') : null;

        if ($tip === SoruTipi::Checkbox) {
            if ($minDeger !== null && ($minDeger < 0 || fmod($minDeger, 1.0) !== 0.0)) {
                throw ValidationException::withMessages(['min_deger' => 'Minimum seçim adedi 0 veya daha büyük tam sayı olmalıdır.']);
            }
            if ($maxDeger !== null && ($maxDeger < 1 || fmod($maxDeger, 1.0) !== 0.0)) {
                throw ValidationException::withMessages(['max_deger' => 'Maksimum seçim adedi 1 veya daha büyük tam sayı olmalıdır.']);
            }
            if ($minDeger !== null && $minDeger > $secenekAdedi) {
                throw ValidationException::withMessages(['min_deger' => 'Minimum seçim, seçenek sayısından fazla olamaz.']);
            }
            if ($maxDeger !== null && $maxDeger > $secenekAdedi) {
                throw ValidationException::withMessages(['max_deger' => 'Maksimum seçim, seçenek sayısından fazla olamaz.']);
            }
        }

        if ($minDeger !== null && $maxDeger !== null && $minDeger > $maxDeger) {
            throw ValidationException::withMessages(['max_deger' => 'Maksimum değer minimumdan küçük olamaz.']);
        }

        return [$minDeger, $maxDeger];
    }

    /**
     * Koşul özetleri diğer kartları da etkileyebildiği için tüm kart listesi yeniden çizilir.
     *
     * @return array{message: string, html: string}
     */
    private function listeJson(string $message, SoruFormu $form): array
    {
        $form->unsetRelation('sorular');
        $form->load('sorular.secenekler');

        return [
            'message' => $message,
            'html' => view('soru-formlari._cards', ['form' => $form])->render(),
        ];
    }

    private function soruFormaAit(SoruFormu $form, Soru $soru): void
    {
        abort_unless($soru->form_id === $form->id, 404);
    }

    private function sorulariYenidenSirala(SoruFormu $form): void
    {
        $form->sorular()->get()->each(function (Soru $soru, int $index) {
            $soru->update(['sira' => $index + 1]);
        });
    }
}
