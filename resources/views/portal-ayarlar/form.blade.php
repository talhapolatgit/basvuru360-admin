@extends('layouts.admin')

@section('title', $sayfa ? 'Sayfa Düzenle' : 'Yeni Portal Sayfası')

@section('content')
@php
    $guncelleyebilir = auth()->user()?->hasYetki('portal_ayar.guncelle');
    $isEdit = (bool) $sayfa;
    $sistem = (bool) ($sayfa?->sistem);
    $kurallariDestekler = app(\App\Services\PortalSayfaServisi::class)->icerikKurallariDestekler($sayfa);
@endphp

<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Sistem / Portal Ayarları</p>
        <h1 class="page-title">{{ $isEdit ? $sayfa->baslik : 'Yeni Sayfa' }}</h1>
        <p class="page-subtitle">
            @if ($kurallariDestekler)
                Sayfa başlığını, menü görünürlüğünü ve hangi kurs/etkinliklerin listeleneceğini belirleyin.
            @else
                Sayfa başlığını, açıklamalarını, menü görünürlüğünü ve görsellerini düzenleyin.
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button :href="route('portal-ayarlar.index')">Listeye Dön</x-back-button>
    </div>
</div>

<div class="card" data-portal-sayfa-form data-hedefler='@json($hedefler)'>
    <form
        method="POST"
        action="{{ $isEdit ? route('portal-ayarlar.update', $sayfa) : route('portal-ayarlar.store') }}"
        class="genel-ayarlar-form"
        enctype="multipart/form-data"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <fieldset @disabled(! $guncelleyebilir) style="border:0;padding:0;margin:0;min-inline-size:0">
        <div class="sertifika-ayarlar-block">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Sayfa Bilgileri</h3>
                <p class="sertifika-ayarlar-desc">Menüde görünecek ad ve URL.</p>
            </div>

            <div class="form-grid form-grid-2">
                <div class="form-group">
                    <label for="baslik">Başlık</label>
                    <input
                        type="text"
                        id="baslik"
                        name="baslik"
                        class="form-control @error('baslik') is-invalid @enderror"
                        value="{{ old('baslik', $sayfa->baslik ?? '') }}"
                        maxlength="200"
                        required
                        @disabled(! $guncelleyebilir)
                    >
                    @error('baslik')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="slug">URL (slug)</label>
                    <div class="flex items-center gap-2">
                        <span class="text-muted text-sm">{{ $sistem ? '/' : '/sayfa/' }}</span>
                        <input
                            type="text"
                            id="slug"
                            name="slug"
                            class="form-control @error('slug') is-invalid @enderror"
                            value="{{ old('slug', $sayfa->slug ?? '') }}"
                            maxlength="120"
                            @disabled(! $guncelleyebilir || $sistem)
                            @readonly($sistem)
                        >
                    </div>
                    @if ($sistem)
                        <p class="form-hint">Sistem sayfalarının URL’si değiştirilemez.</p>
                    @endif
                    @error('slug')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-group" style="margin-top: 1rem">
                <label for="aciklama">Açıklama</label>
                <textarea
                    id="aciklama"
                    name="aciklama"
                    class="form-control @error('aciklama') is-invalid @enderror"
                    rows="3"
                    maxlength="500"
                    placeholder="Portalda sayfa başlığının altında görünecek kısa açıklama"
                    @disabled(! $guncelleyebilir)
                >{{ old('aciklama', $sayfa->aciklama ?? '') }}</textarea>
                <p class="form-hint">Sayfa içeriğinde başlığın altında görünür. Boş bırakılırsa gösterilmez.</p>
                @error('aciklama')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group" style="margin-top: 1rem">
                <label for="menu_aciklama">Menü açıklaması</label>
                <textarea
                    id="menu_aciklama"
                    name="menu_aciklama"
                    class="form-control @error('menu_aciklama') is-invalid @enderror"
                    rows="2"
                    maxlength="500"
                    placeholder="Ana sayfa menü kartında başlığın altında görünecek kısa açıklama"
                    @disabled(! $guncelleyebilir)
                >{{ old('menu_aciklama', $sayfa->menu_aciklama ?? '') }}</textarea>
                <p class="form-hint">Ana sayfadaki menü kartında kullanılır.</p>
                @error('menu_aciklama')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group" style="margin-top: 1rem">
                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="menude_goster"
                        value="1"
                        @checked(old('menude_goster', $sayfa->menude_goster ?? true))
                        @disabled(! $guncelleyebilir)
                    >
                    <span>Menüde göster</span>
                </label>
            </div>
        </div>

        <div class="sertifika-ayarlar-block" style="margin-top: 1.5rem">
            <div class="sertifika-ayarlar-block-head">
                <h3 class="sertifika-ayarlar-title">Görseller</h3>
                <p class="sertifika-ayarlar-desc">Ana sayfa kartı, menü arkaplanı ve sol menü ikonu.</p>
            </div>

            <div class="form-grid form-grid-2 portal-gorseller-grid">
                <div class="form-group">
                    <label for="anasayfa_logo">Ana sayfa logosu</label>
                    <input
                        type="file"
                        id="anasayfa_logo"
                        name="anasayfa_logo"
                        class="form-control @error('anasayfa_logo') is-invalid @enderror"
                        accept=".png,.jpg,.jpeg,.svg,.webp,image/png,image/jpeg,image/svg+xml,image/webp"
                        @disabled(! $guncelleyebilir)
                    >
                    <p class="form-hint">PNG, JPG, SVG veya WEBP. En fazla 5 MB. Önerilen ölçü: 128×128 px.</p>
                    @error('anasayfa_logo')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    @if (! empty($anasayfaLogoUrl))
                        <label class="sertifika-reset-check">
                            <input
                                type="checkbox"
                                name="anasayfa_logo_kaldir"
                                value="1"
                                @checked(old('anasayfa_logo_kaldir'))
                                @disabled(! $guncelleyebilir)
                            >
                            Yüklenen ana sayfa logosunu kaldır
                        </label>
                        <div style="margin-top:.75rem">
                            <img src="{{ $anasayfaLogoUrl }}" alt="Ana sayfa logosu" style="max-height:64px;max-width:100%;object-fit:contain">
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="anasayfa_menu_arkaplan">Ana sayfa menü arkaplanı</label>
                    <input
                        type="file"
                        id="anasayfa_menu_arkaplan"
                        name="anasayfa_menu_arkaplan"
                        class="form-control @error('anasayfa_menu_arkaplan') is-invalid @enderror"
                        accept=".png,.jpg,.jpeg,.svg,.webp,image/png,image/jpeg,image/svg+xml,image/webp"
                        @disabled(! $guncelleyebilir)
                    >
                    <p class="form-hint">PNG, JPG, SVG veya WEBP. En fazla 5 MB. Önerilen ölçü: 1200×360 px. Yoksa kart beyaz kalır.</p>
                    @error('anasayfa_menu_arkaplan')
                        <p class="form-error">{{ $message }}</p>
                    @enderror

                    @php
                        $arkaplanMod = old(
                            'anasayfa_menu_arkaplan_mod',
                            $sayfa->anasayfa_menu_arkaplan_mod ?? \App\Services\PortalSayfaServisi::ARKAPLAN_MOD_KAPLA
                        );
                    @endphp
                    <div class="form-group" style="margin-top:.75rem">
                        <label>Görünüm</label>
                        <div class="flex items-center gap-4" style="margin-top:.35rem">
                            <label class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="anasayfa_menu_arkaplan_mod"
                                    value="kapla"
                                    @checked($arkaplanMod === 'kapla')
                                    @disabled(! $guncelleyebilir)
                                >
                                <span>Kapla</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="anasayfa_menu_arkaplan_mod"
                                    value="sigdir"
                                    @checked($arkaplanMod === 'sigdir')
                                    @disabled(! $guncelleyebilir)
                                >
                                <span>Sığdır</span>
                            </label>
                        </div>
                        <p class="form-hint">Kapla: kartı doldurur (kırpabilir). Sığdır: görselin tamamını gösterir.</p>
                        @error('anasayfa_menu_arkaplan_mod')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    @if (! empty($anasayfaMenuArkaplanUrl))
                        <label class="sertifika-reset-check">
                            <input
                                type="checkbox"
                                name="anasayfa_menu_arkaplan_kaldir"
                                value="1"
                                @checked(old('anasayfa_menu_arkaplan_kaldir'))
                                @disabled(! $guncelleyebilir)
                            >
                            Yüklenen menü arkaplanını kaldır
                        </label>
                        <div style="margin-top:.75rem">
                            <img src="{{ $anasayfaMenuArkaplanUrl }}" alt="Menü arkaplanı" style="max-height:96px;max-width:100%;object-fit:cover;border-radius:8px">
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="sidebar_ikon">Sidebar ikonu</label>
                    <input
                        type="file"
                        id="sidebar_ikon"
                        name="sidebar_ikon"
                        class="form-control @error('sidebar_ikon') is-invalid @enderror"
                        accept=".png,.jpg,.jpeg,.svg,.webp,image/png,image/jpeg,image/svg+xml,image/webp"
                        @disabled(! $guncelleyebilir)
                    >
                    <p class="form-hint">PNG, JPG, SVG veya WEBP. En fazla 5 MB. Önerilen ölçü: 64×64 px. Tek renk / silüet ikon önerilir; menüde diğer ikonlar gibi renklendirilir.</p>
                    @error('sidebar_ikon')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    @if (! empty($sidebarIkonUrl))
                        <label class="sertifika-reset-check">
                            <input
                                type="checkbox"
                                name="sidebar_ikon_kaldir"
                                value="1"
                                @checked(old('sidebar_ikon_kaldir'))
                                @disabled(! $guncelleyebilir)
                            >
                            Yüklenen sidebar ikonunu kaldır
                        </label>
                        <div style="margin-top:.75rem">
                            <img src="{{ $sidebarIkonUrl }}" alt="Sidebar ikonu" style="max-height:40px;max-width:40px;object-fit:contain">
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($kurallariDestekler)
        <div class="sertifika-ayarlar-block" style="margin-top: 1.5rem">
            <div class="sertifika-ayarlar-block-head portal-kurallar-head">
                <div>
                    <h3 class="sertifika-ayarlar-title">İçerik Kuralları</h3>
                    <p class="sertifika-ayarlar-desc">Birden fazla kural OR ile birleşir. Aynı sayfaya hem kurs hem etkinlik ekleyebilirsiniz.</p>
                </div>
                @if ($guncelleyebilir)
                <x-back-button type="button" icon="plus" class="btn-back-sm" data-kural-ekle>
                    Kural Ekle
                </x-back-button>
                @endif
            </div>

            @error('kurallar')
                <p class="form-error">{{ $message }}</p>
            @enderror

            <div class="portal-kurallar" data-kural-list>
                @if ($guncelleyebilir)
                    <p class="evrak-empty" data-kural-empty>Henüz içerik kuralı eklenmedi. Sayfada hangi kurs veya etkinliklerin listeleneceğini belirlemek için kural ekleyin.</p>
                @else
                    <p class="evrak-empty" data-kural-empty>Bu sayfada henüz içerik kuralı yok.</p>
                @endif
            </div>

            <template data-kural-template>
                <div class="portal-kural-row" data-kural-row style="display:grid;grid-template-columns:1fr 1fr 1.4fr auto;gap:.75rem;align-items:end;margin-bottom:.75rem">
                    <div class="form-group" style="margin:0">
                        <label>Kaynak</label>
                        <select class="form-control" data-kural-kaynak>
                            <option value="kurs">Kurs</option>
                            <option value="etkinlik">Etkinlik</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label>Seçim</label>
                        <select class="form-control" data-kural-secim></select>
                    </div>
                    <div class="form-group" style="margin:0" data-hedef-wrap>
                        <label class="portal-kural-hedef-label">
                            <span>Hedef</span>
                            <span class="form-hint portal-kural-hedef-hint" data-hedef-hint></span>
                        </label>
                        <div
                            class="custom-select searchable-select"
                            data-kural-hedef-select
                            data-required="1"
                        >
                            <input type="hidden" value="" data-kural-hedef data-select-value data-required-field="1">
                            <div
                                class="select-display is-empty"
                                data-select-toggle
                                tabindex="0"
                                role="combobox"
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                aria-required="true"
                            >
                                <span data-select-label class="select-label-text">Seçin</span>
                            </div>
                            <div class="select-dropdown" data-select-dropdown>
                                <input type="text" class="select-search" placeholder="Ara..." data-select-search autocomplete="off">
                                <div data-select-options></div>
                            </div>
                        </div>
                        <input
                            type="text"
                            class="form-control"
                            data-kural-hedef-nos
                            placeholder="Numaraları virgülle ayırın"
                            autocomplete="off"
                            hidden
                        >
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" data-kural-sil title="Kaldır">Sil</button>
                </div>
            </template>
        </div>
        @endif

        @if ($guncelleyebilir)
        <div class="filter-actions" style="margin-top: 1.5rem">
            <div class="filter-actions-right">
                <x-cta-button type="submit" icon="save">Kaydet</x-cta-button>
            </div>
        </div>
        @endif
        </fieldset>
    </form>
</div>

<script type="application/json" data-initial-kurallar>@json($kurallar)</script>
@endsection
