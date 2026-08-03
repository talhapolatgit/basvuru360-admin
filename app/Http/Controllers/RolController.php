<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Yetki;
use App\Services\LogKaydedici;
use App\Support\YetkiKatalogu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RolController extends Controller
{
    public function index(): View
    {
        $roller = Rol::query()
            ->withCount(['yetkiler', 'kullanicilar'])
            ->orderBy('sira')
            ->orderBy('ad')
            ->get();

        return view('roller.index', [
            'roller' => $roller,
        ]);
    }

    public function create(): View
    {
        return view('roller.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $rol = Rol::query()->create([
            'kod' => $validated['kod'],
            'ad' => $validated['ad'],
            'aciklama' => $validated['aciklama'] ?? null,
            'tum_yetkiler' => false,
            'sistem' => false,
            'sira' => (int) ($validated['sira'] ?? 99),
            'aktif' => $request->boolean('aktif', true),
        ]);

        $rol->yetkiler()->sync($validated['yetkiler'] ?? []);

        LogKaydedici::kaydet(
            islem: 'rol.olusturuldu',
            aciklama: '"'.$rol->ad.'" rolü oluşturuldu.',
            konu: $rol,
            konuAdi: $rol->ad,
        );

        return redirect()
            ->route('roller.show', $rol)
            ->with('success', '"'.$rol->ad.'" rolü oluşturuldu.');
    }

    public function show(Rol $rol): View
    {
        $rol->load(['yetkiler', 'kullanicilar']);

        $yetkilerByModul = $rol->yetkiler
            ->groupBy('modul')
            ->sortKeys();

        return view('roller.show', [
            'rol' => $rol,
            'yetkilerByModul' => $yetkilerByModul,
            'modulAdlari' => YetkiKatalogu::MODULLER,
        ]);
    }

    public function edit(Rol $rol): View
    {
        $rol->load('yetkiler');

        return view('roller.edit', $this->formData() + [
            'rol' => $rol,
            'seciliYetkiler' => $rol->yetkiler->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Rol $rol): RedirectResponse
    {
        $validated = $this->validated($request, $rol);

        $eski = [
            'ad' => $rol->ad,
            'aciklama' => $rol->aciklama,
            'sira' => $rol->sira,
            'aktif' => $rol->aktif,
            'yetkiler' => $rol->yetkiler()->pluck('yetkiler.id')->sort()->values()->all(),
        ];

        $rol->update([
            'ad' => $validated['ad'],
            'aciklama' => $validated['aciklama'] ?? null,
            'sira' => (int) ($validated['sira'] ?? $rol->sira),
            'aktif' => $request->boolean('aktif', true),
            // kod ve tum_yetkiler sistem rollerinde değiştirilmez
            ...( $rol->sistem ? [] : ['kod' => $validated['kod']] ),
        ]);

        if (! $rol->tum_yetkiler) {
            $rol->yetkiler()->sync($validated['yetkiler'] ?? []);
        }

        $yeni = [
            'ad' => $rol->ad,
            'aciklama' => $rol->aciklama,
            'sira' => $rol->sira,
            'aktif' => $rol->aktif,
            'yetkiler' => $rol->yetkiler()->pluck('yetkiler.id')->sort()->values()->all(),
        ];

        if ($eski !== $yeni) {
            LogKaydedici::kaydet(
                islem: 'rol.guncellendi',
                aciklama: '"'.$rol->ad.'" rolü güncellendi.',
                konu: $rol,
                konuAdi: $rol->ad,
                eski: $eski,
                yeni: $yeni,
            );
        }

        return redirect()
            ->route('roller.show', $rol)
            ->with('success', '"'.$rol->ad.'" rolü güncellendi.');
    }

    public function destroy(Rol $rol): RedirectResponse
    {
        if (! $rol->silinebilirMi()) {
            return redirect()
                ->route('roller.index')
                ->with('error', 'Sistem rolleri silinemez.');
        }

        if ($rol->kullanicilar()->exists()) {
            return redirect()
                ->route('roller.index')
                ->with('error', 'Bu role atanmış kullanıcılar var. Önce kullanıcıların rollerini güncelleyin.');
        }

        $ad = $rol->ad;
        $rol->yetkiler()->detach();
        $rol->delete();

        LogKaydedici::kaydet(
            islem: 'rol.silindi',
            aciklama: '"'.$ad.'" rolü silindi.',
            konuAdi: $ad,
        );

        return redirect()
            ->route('roller.index')
            ->with('success', '"'.$ad.'" rolü silindi.');
    }

    /**
     * @return array{yetkilerByModul: \Illuminate\Support\Collection, modulAdlari: array<string, string>}
     */
    private function formData(): array
    {
        $yetkiler = Yetki::query()->orderBy('sira')->orderBy('ad')->get();

        return [
            'yetkilerByModul' => $yetkiler->groupBy('modul'),
            'modulAdlari' => YetkiKatalogu::MODULLER,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Rol $rol = null): array
    {
        $kodRule = [
            'required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/',
            Rule::unique('roller', 'kod')->ignore($rol?->id),
        ];

        if ($rol?->sistem) {
            // Sistem rolünün kodu değiştirilemez; validasyonda mevcut değeri kullan.
            $kodRule = ['nullable', 'string'];
        }

        $rules = [
            'kod' => $kodRule,
            'ad' => ['required', 'string', 'max:100'],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
            'yetkiler' => ['nullable', 'array'],
            'yetkiler.*' => ['integer', 'exists:yetkiler,id'],
        ];

        $validated = $request->validate($rules, [
            'kod.required' => 'Rol kodu zorunludur.',
            'kod.unique' => 'Bu rol kodu zaten kullanılıyor.',
            'kod.regex' => 'Rol kodu yalnızca küçük harf, rakam ve alt çizgi içerebilir.',
            'ad.required' => 'Rol adı zorunludur.',
        ]);

        if ($rol?->sistem) {
            $validated['kod'] = $rol->kod;
        } else {
            $validated['kod'] = Str::lower($validated['kod']);
        }

        if ($rol?->tum_yetkiler) {
            $validated['yetkiler'] = [];
        }

        return $validated;
    }
}
