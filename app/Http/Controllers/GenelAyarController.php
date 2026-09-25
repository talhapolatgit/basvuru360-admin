<?php

namespace App\Http\Controllers;

use App\Enums\KisiGirisYontemi;
use App\Services\GenelAyarServisi;
use App\Services\LogKaydedici;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GenelAyarController extends Controller
{
    public function edit(GenelAyarServisi $servis): View
    {
        return view('genel-ayarlar.edit', [
            'form' => $servis->formVerisi(),
        ]);
    }

    public function update(Request $request, GenelAyarServisi $servis): RedirectResponse
    {
        $validated = $request->validate([
            'kurum_adi' => ['nullable', 'string', 'max:200'],
            'telefon' => ['nullable', 'string', 'max:20'],
            'eposta' => ['nullable', 'email', 'max:255'],
            'il' => ['nullable', 'string', 'max:100'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'adres' => ['nullable', 'string', 'max:500'],
            'web_sitesi' => ['nullable', 'string', 'max:255'],
            'site_aciklama' => ['nullable', 'string', 'max:320'],
            'kisi_giris_yontemi' => ['required', 'string', Rule::in(KisiGirisYontemi::values())],
            'yakin_icin_basvuru_aktif' => ['nullable', 'boolean'],
            'manuel_yakin_ekleme_aktif' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'max:5120', 'extensions:png,jpg,jpeg,svg,webp'],
            'logo_kaldir' => ['nullable', 'boolean'],
            'favicon' => ['nullable', 'file', 'max:2048', 'extensions:ico,png,jpg,jpeg,svg,webp'],
            'favicon_kaldir' => ['nullable', 'boolean'],
            'sidebar_baslik' => ['nullable', 'string', 'max:200'],
            'sidebar_alt_baslik' => ['nullable', 'string', 'max:200'],
            'sidebar_logo' => ['nullable', 'file', 'max:5120', 'extensions:png,jpg,jpeg,svg,webp'],
            'sidebar_logo_kaldir' => ['nullable', 'boolean'],
            'header_logo' => ['nullable', 'file', 'max:5120', 'extensions:png,jpg,jpeg,svg,webp'],
            'header_logo_kaldir' => ['nullable', 'boolean'],
            'sidebar_logo_arkaplan' => ['nullable', 'string', 'max:20'],
            'sidebar_logo_arkaplan_seffaf' => ['nullable', 'boolean'],
            'sidebar_arkaplan' => ['nullable', 'string', 'max:20'],
            'sidebar_arkaplan_tip' => ['required', Rule::in(\App\Services\GenelAyarServisi::SIDEBAR_ARKAPLAN_TIPLERI)],
        ], [
            'eposta.email' => 'Geçerli bir e-posta adresi girin.',
            'kisi_giris_yontemi.required' => 'Kişi giriş yöntemi zorunludur.',
            'kisi_giris_yontemi.in' => 'Geçersiz kişi giriş yöntemi.',
            'site_aciklama.max' => 'Site açıklaması en fazla 320 karakter olabilir.',
            'logo.max' => 'Logo en fazla 5 MB olabilir.',
            'logo.extensions' => 'Logo PNG, JPG, SVG veya WEBP olmalıdır.',
            'favicon.max' => 'Favicon en fazla 2 MB olabilir.',
            'favicon.extensions' => 'Favicon ICO, PNG, JPG, SVG veya WEBP olmalıdır.',
            'sidebar_logo.max' => 'Sidebar logosu en fazla 5 MB olabilir.',
            'sidebar_logo.extensions' => 'Sidebar logosu PNG, JPG, SVG veya WEBP olmalıdır.',
            'header_logo.max' => 'Header logosu en fazla 5 MB olabilir.',
            'header_logo.extensions' => 'Header logosu PNG, JPG, SVG veya WEBP olmalıdır.',
            'sidebar_arkaplan_tip.required' => 'Sidebar arka plan tipi zorunludur.',
            'sidebar_arkaplan_tip.in' => 'Sidebar arka plan tipi Düz renk veya Gradient olmalıdır.',
        ]);

        $validated['sidebar_logo_arkaplan_seffaf'] = $request->boolean('sidebar_logo_arkaplan_seffaf');
        $validated['yakin_icin_basvuru_aktif'] = $request->boolean('yakin_icin_basvuru_aktif');
        $validated['manuel_yakin_ekleme_aktif'] = $request->boolean('manuel_yakin_ekleme_aktif');
        $validated['sidebar_logo_arkaplan'] = $servis->normalizeArkaplan($validated['sidebar_logo_arkaplan'] ?? null);
        $validated['sidebar_arkaplan'] = $servis->normalizeHexRenk(
            $validated['sidebar_arkaplan'] ?? null,
            \App\Services\GenelAyarServisi::DEFAULT_SIDEBAR_ARKAPLAN,
        );
        $validated['sidebar_arkaplan_tip'] = $servis->normalizeArkaplanTip($validated['sidebar_arkaplan_tip'] ?? null);

        $onceki = $servis->formVerisi();

        $servis->kaydet(
            $validated,
            $request->file('logo'),
            $request->boolean('logo_kaldir'),
            $request->file('sidebar_logo'),
            $request->boolean('sidebar_logo_kaldir'),
            $request->file('header_logo'),
            $request->boolean('header_logo_kaldir'),
            $request->file('favicon'),
            $request->boolean('favicon_kaldir'),
        );

        $yeni = $servis->formVerisi();

        LogKaydedici::kaydet(
            islem: 'genel_ayar.guncellendi',
            aciklama: 'Genel ayarlar güncellendi.',
            eski: $this->logOzet($onceki),
            yeni: $this->logOzet($yeni),
            konuAdi: 'Genel Ayarlar',
        );

        return redirect()
            ->route('genel-ayarlar.edit')
            ->with('success', 'Genel ayarlar kaydedildi.');
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private function logOzet(array $form): array
    {
        return [
            'kurum_adi' => $form['kurum_adi'] ?? null,
            'telefon' => $form['telefon'] ?? null,
            'eposta' => $form['eposta'] ?? null,
            'il' => $form['il'] ?? null,
            'ilce' => $form['ilce'] ?? null,
            'adres' => $form['adres'] ?? null,
            'web_sitesi' => $form['web_sitesi'] ?? null,
            'site_aciklama' => $form['site_aciklama'] ?? null,
            'logo' => $form['logo'] ?? null,
            'favicon' => $form['favicon'] ?? null,
            'kisi_giris_yontemi' => $form['kisi_giris_yontemi'] ?? null,
            'yakin_icin_basvuru_aktif' => $form['yakin_icin_basvuru_aktif'] ?? null,
            'manuel_yakin_ekleme_aktif' => $form['manuel_yakin_ekleme_aktif'] ?? null,
            'sidebar_logo' => $form['sidebar_logo'] ?? null,
            'header_logo' => $form['header_logo'] ?? null,
            'sidebar_baslik' => $form['sidebar_baslik'] ?? null,
            'sidebar_alt_baslik' => $form['sidebar_alt_baslik'] ?? null,
            'sidebar_logo_arkaplan' => $form['sidebar_logo_arkaplan'] ?? null,
            'sidebar_arkaplan' => $form['sidebar_arkaplan'] ?? null,
            'sidebar_arkaplan_tip' => $form['sidebar_arkaplan_tip'] ?? null,
        ];
    }
}
