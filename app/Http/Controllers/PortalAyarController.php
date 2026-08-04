<?php

namespace App\Http\Controllers;

use App\Models\PortalSayfa;
use App\Services\LogKaydedici;
use App\Services\PortalSayfaServisi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortalAyarController extends Controller
{
    public function index(PortalSayfaServisi $servis): View
    {
        return view('portal-ayarlar.index', [
            'sayfalar' => $servis->liste(),
        ]);
    }

    public function create(PortalSayfaServisi $servis): View
    {
        $kurallar = old('kurallar', []);
        if (! is_array($kurallar)) {
            $kurallar = [];
        }

        return view('portal-ayarlar.form', [
            'sayfa' => null,
            'hedefler' => $servis->hedefSecenekleri(),
            'kurallar' => $servis->formKurallari($kurallar),
            'anasayfaLogoUrl' => null,
            'anasayfaMenuArkaplanUrl' => null,
            'sidebarIkonUrl' => null,
        ]);
    }

    public function store(Request $request, PortalSayfaServisi $servis): RedirectResponse
    {
        $validated = $this->validateSayfa($request);
        $sayfa = $servis->olustur($validated);

        LogKaydedici::kaydet(
            islem: 'portal_sayfa.olusturuldu',
            aciklama: '"'.$sayfa->baslik.'" portal sayfası oluşturuldu.',
            konu: $sayfa,
            yeni: ['baslik' => $sayfa->baslik, 'slug' => $sayfa->slug],
            konuAdi: $sayfa->baslik,
        );

        return redirect()
            ->route('portal-ayarlar.index')
            ->with('success', "\"{$sayfa->baslik}\" sayfası oluşturuldu.");
    }

    public function edit(PortalSayfa $portalSayfa, PortalSayfaServisi $servis): View
    {
        $portalSayfa->load('kurallar');

        $kurallar = old('kurallar');
        if (! is_array($kurallar)) {
            $kurallar = $portalSayfa->kurallar;
        }

        return view('portal-ayarlar.form', [
            'sayfa' => $portalSayfa,
            'hedefler' => $servis->hedefSecenekleri(),
            'kurallar' => $servis->formKurallari($kurallar),
            'anasayfaLogoUrl' => $servis->anasayfaLogoUrl($portalSayfa),
            'anasayfaMenuArkaplanUrl' => $servis->anasayfaMenuArkaplanUrl($portalSayfa),
            'sidebarIkonUrl' => $servis->sidebarIkonUrl($portalSayfa),
        ]);
    }

    public function update(Request $request, PortalSayfa $portalSayfa, PortalSayfaServisi $servis): RedirectResponse
    {
        $validated = $this->validateSayfa($request);
        $onceki = [
            'baslik' => $portalSayfa->baslik,
            'slug' => $portalSayfa->slug,
            'menude_goster' => $portalSayfa->menude_goster,
            'sadece_giris' => $portalSayfa->sadece_giris,
        ];

        $sayfa = $servis->guncelle($portalSayfa, $validated);

        LogKaydedici::kaydet(
            islem: 'portal_sayfa.guncellendi',
            aciklama: '"'.$sayfa->baslik.'" portal sayfası güncellendi.',
            konu: $sayfa,
            eski: $onceki,
            yeni: [
                'baslik' => $sayfa->baslik,
                'slug' => $sayfa->slug,
                'menude_goster' => $sayfa->menude_goster,
                'sadece_giris' => $sayfa->sadece_giris,
            ],
            konuAdi: $sayfa->baslik,
        );

        return redirect()
            ->route('portal-ayarlar.index')
            ->with('success', "\"{$sayfa->baslik}\" sayfası güncellendi.");
    }

    public function destroy(PortalSayfa $portalSayfa, PortalSayfaServisi $servis): RedirectResponse
    {
        $baslik = $portalSayfa->baslik;
        $servis->sil($portalSayfa);

        LogKaydedici::kaydet(
            islem: 'portal_sayfa.silindi',
            aciklama: '"'.$baslik.'" portal sayfası silindi.',
            konuAdi: $baslik,
            eski: ['baslik' => $baslik],
        );

        return redirect()
            ->route('portal-ayarlar.index')
            ->with('success', "\"{$baslik}\" sayfası silindi.");
    }

    public function menudeToggle(Request $request, PortalSayfa $portalSayfa, PortalSayfaServisi $servis): RedirectResponse
    {
        $request->validate([
            'menude_goster' => ['required'],
        ]);

        $servis->menudeGosterToggle($portalSayfa, $request->boolean('menude_goster'));

        return redirect()->route('portal-ayarlar.index')->with('success', 'Menü görünürlüğü güncellendi.');
    }

    public function move(Request $request, PortalSayfa $portalSayfa, PortalSayfaServisi $servis): RedirectResponse
    {
        $validated = $request->validate([
            'yon' => ['required', 'string', Rule::in(['yukari', 'asagi'])],
        ]);

        $ids = $servis->liste()->pluck('id')->all();
        $index = array_search($portalSayfa->id, $ids, true);
        if ($index === false) {
            return redirect()->route('portal-ayarlar.index');
        }

        $swapWith = $validated['yon'] === 'yukari' ? $index - 1 : $index + 1;
        if ($swapWith < 0 || $swapWith >= count($ids)) {
            return redirect()->route('portal-ayarlar.index');
        }

        [$ids[$index], $ids[$swapWith]] = [$ids[$swapWith], $ids[$index]];
        $servis->sirala($ids);

        return redirect()->route('portal-ayarlar.index')->with('success', 'Sayfa sırası güncellendi.');
    }

    /**
     * @return array{baslik: string, slug?: string|null, menude_goster: bool, sadece_giris: bool, kurallar: list<array{kaynak: string, secim_tipi: string, hedef_id?: int|null}>}
     */
    private function validateSayfa(Request $request): array
    {
        $validated = $request->validate([
            'baslik' => ['required', 'string', 'max:200'],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'menu_aciklama' => ['nullable', 'string', 'max:500'],
            'slug' => ['nullable', 'string', 'max:120'],
            'menude_goster' => ['nullable', 'boolean'],
            'sadece_giris' => ['nullable', 'boolean'],
            'anasayfa_logo' => ['nullable', 'file', 'max:5120', 'extensions:png,jpg,jpeg,svg,webp'],
            'anasayfa_logo_kaldir' => ['nullable', 'boolean'],
            'anasayfa_menu_arkaplan' => ['nullable', 'file', 'max:5120', 'extensions:png,jpg,jpeg,svg,webp'],
            'anasayfa_menu_arkaplan_kaldir' => ['nullable', 'boolean'],
            'anasayfa_menu_arkaplan_mod' => ['nullable', 'string', Rule::in(PortalSayfaServisi::ARKAPLAN_MODLARI)],
            'sidebar_ikon' => ['nullable', 'file', 'max:5120', 'extensions:png,jpg,jpeg,svg,webp'],
            'sidebar_ikon_kaldir' => ['nullable', 'boolean'],
            'kurallar' => ['nullable', 'array'],
            'kurallar.*.kaynak' => ['required_with:kurallar', 'string', Rule::in(['kurs', 'etkinlik'])],
            'kurallar.*.secim_tipi' => ['required_with:kurallar', 'string', 'max:20'],
            'kurallar.*.hedef_id' => ['nullable', 'integer'],
            'kurallar.*.hedef_nos' => ['nullable', 'string', 'max:2000'],
        ], [
            'baslik.required' => 'Sayfa başlığı zorunludur.',
            'aciklama.max' => 'Açıklama en fazla 500 karakter olabilir.',
            'menu_aciklama.max' => 'Menü açıklaması en fazla 500 karakter olabilir.',
            'anasayfa_logo.max' => 'Ana sayfa logosu en fazla 5 MB olabilir.',
            'anasayfa_logo.extensions' => 'Ana sayfa logosu PNG, JPG, SVG veya WEBP olmalıdır.',
            'anasayfa_menu_arkaplan.max' => 'Ana sayfa menü arkaplanı en fazla 5 MB olabilir.',
            'anasayfa_menu_arkaplan.extensions' => 'Ana sayfa menü arkaplanı PNG, JPG, SVG veya WEBP olmalıdır.',
            'sidebar_ikon.max' => 'Sidebar ikonu en fazla 5 MB olabilir.',
            'sidebar_ikon.extensions' => 'Sidebar ikonu PNG, JPG, SVG veya WEBP olmalıdır.',
        ]);

        $validated['menude_goster'] = $request->boolean('menude_goster');
        $validated['sadece_giris'] = $request->boolean('sadece_giris');
        $validated['anasayfa_logo_kaldir'] = $request->boolean('anasayfa_logo_kaldir');
        $validated['anasayfa_menu_arkaplan_kaldir'] = $request->boolean('anasayfa_menu_arkaplan_kaldir');
        $validated['sidebar_ikon_kaldir'] = $request->boolean('sidebar_ikon_kaldir');
        $validated['anasayfa_logo'] = $request->file('anasayfa_logo');
        $validated['anasayfa_menu_arkaplan'] = $request->file('anasayfa_menu_arkaplan');
        $validated['sidebar_ikon'] = $request->file('sidebar_ikon');
        $validated['kurallar'] = array_values($validated['kurallar'] ?? []);

        return $validated;
    }
}
