@php
    /** @var \App\Models\Kurs|null $kurs */
    $kurs = $kurs ?? null;
    $isEdit = $kurs !== null;

    $val = function (string $key, mixed $default = null) use ($kurs) {
        if (old($key) !== null) {
            return old($key);
        }

        if ($kurs === null) {
            return $default;
        }

        $value = data_get($kurs, $key);

        return $value ?? $default;
    };

    $dateVal = function (string $key) use ($kurs) {
        if (old($key) !== null) {
            return old($key);
        }
        $value = $kurs?->{$key};

        return $value?->format('Y-m-d') ?? '';
    };

    $datetimeVal = function (string $key) use ($kurs) {
        if (old($key) !== null) {
            return old($key);
        }
        $value = $kurs?->{$key};

        return $value?->format('Y-m-d\TH:i') ?? '';
    };

    $defaultGunRow = ['gun' => '', 'baslangic_saati' => '', 'bitis_saati' => '', 'ders_saati' => '', 'sinif' => ''];

    if (old('gunler') !== null) {
        $gunlerRows = old('gunler');
    } elseif ($kurs && $kurs->gunler->isNotEmpty()) {
        $gunlerRows = $kurs->gunler
            ->sortBy(fn ($g) => $g->gun?->sira() ?? 99)
            ->values()
            ->map(fn ($g) => [
                'gun' => $g->gun?->value ?? '',
                'baslangic_saati' => substr((string) $g->baslangic_saati, 0, 5),
                'bitis_saati' => substr((string) $g->bitis_saati, 0, 5),
                'ders_saati' => $g->ders_saati,
                'sinif' => $g->sinif ?? '',
            ])
            ->all();
    } else {
        $gunlerRows = [$defaultGunRow];
    }

    $selectedEvraklar = collect(old('evrak_tipi_ids', $kurs?->evrakTipleri->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();

    /** @var \Illuminate\Support\Collection<int, \App\Models\Kurum> $kurumlar */
    $kurumlar = $kurumlar ?? collect();
    $seciliKurumIds = collect(old('kurumlar', $kurs?->kurumlar?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();

    $ikametDefault = $kurs?->ikamet_sarti?->value ?? 'hayir';
    $ikametCurrent = old('ikamet_sarti', $ikametDefault);
@endphp

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Temel Bilgiler</h2>
            <p class="card-section-desc">Kurs kimliği, konum ve eğitmen bilgileri.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="meb_numarasi">MEB Numarası</label>
            <input type="text" id="meb_numarasi" name="meb_numarasi" value="{{ $val('meb_numarasi') }}" class="form-control" maxlength="100" placeholder="Opsiyonel">
        </div>

        <div class="form-group">
            <label>Merkez <span class="req">*</span></label>
            <x-searchable-select
                name="merkez_id"
                placeholder="Merkez seçin"
                :value="(string) $val('merkez_id', '')"
                :options="$merkezler->map(fn ($m) => ['value' => $m->id, 'label' => $m->ad])->all()"
                :required="true"
            />
        </div>

        <div class="form-group">
            <label>Kurum</label>
            <div
                class="chip-select @error('kurumlar') is-invalid @enderror"
                data-chip-select
                data-input-name="kurumlar[]"
                data-placeholder="Kurum seçin"
            >
                <div
                    class="chip-select-control form-control @error('kurumlar') is-invalid @enderror"
                    data-chip-select-toggle
                    tabindex="0"
                    role="combobox"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                >
                    <div class="chip-select-chips" data-chip-select-chips>
                        @foreach ($kurumlar->whereIn('id', $seciliKurumIds) as $kurum)
                            <span class="chip-select-chip" data-chip-id="{{ $kurum->id }}">
                                <input type="hidden" name="kurumlar[]" value="{{ $kurum->id }}">
                                <span class="chip-select-chip-label">{{ $kurum->ad }}</span>
                                <button type="button" class="chip-select-chip-remove" data-chip-remove aria-label="Kaldır">&times;</button>
                            </span>
                        @endforeach
                    </div>
                    <span
                        class="chip-select-placeholder"
                        data-chip-select-placeholder
                        @if (count($seciliKurumIds) > 0) hidden @endif
                    >Kurum seçin</span>
                </div>
                <div class="select-dropdown" data-chip-select-dropdown role="listbox">
                    <input type="text" class="select-search" placeholder="Ara..." data-chip-select-search autocomplete="off">
                    <div data-chip-select-options>
                        @foreach ($kurumlar as $kurum)
                            <div
                                class="select-option {{ in_array($kurum->id, $seciliKurumIds, true) ? 'is-selected' : '' }}"
                                data-value="{{ $kurum->id }}"
                                data-label="{{ $kurum->ad }}"
                                @if (in_array($kurum->id, $seciliKurumIds, true)) hidden @endif
                            >
                                {{ $kurum->ad }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @error('kurumlar') <div class="form-error">{{ $message }}</div> @enderror
            @error('kurumlar.*') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label>Alan <span class="req">*</span></label>
            <x-searchable-select
                name="alan_id"
                placeholder="Alan seçin"
                :value="(string) $val('alan_id', '')"
                :options="$alanlar->map(fn ($a) => ['value' => $a->id, 'label' => $a->ad])->all()"
                :required="true"
            />
        </div>

        <div class="form-group">
            <label>Branş <span class="req">*</span></label>
            <x-searchable-select
                name="brans_id"
                placeholder="Branş seçin"
                :value="(string) $val('brans_id', '')"
                :options="$branslar->map(fn ($b) => ['value' => $b->id, 'label' => $b->ad])->all()"
                :required="true"
            />
        </div>

        <div class="form-group">
            <label for="kurs_tipi_id">Belge Tipi <span class="req">*</span></label>
            <select id="kurs_tipi_id" name="kurs_tipi_id" class="form-control" required>
                <option value="">Seçin</option>
                @foreach ($kursTipleri as $tip)
                    <option value="{{ $tip->id }}" @selected((string) $val('kurs_tipi_id') === (string) $tip->id)>{{ $tip->ad }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="durum">Durum <span class="req">*</span></label>
            <select id="durum" name="durum" class="form-control" required>
                @foreach ($durumlar as $durum)
                    <option value="{{ $durum->value }}" @selected(old('durum', $kurs?->durum?->value ?? 'hazirlik') === $durum->value)>{{ $durum->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group form-group-switch">
            <label class="switch-label" for="onlinede_yayinlansin">
                <input type="checkbox" id="onlinede_yayinlansin" name="onlinede_yayinlansin" value="1" @checked((bool) old('onlinede_yayinlansin', $kurs?->onlinede_yayinlansin ?? false))>
                <span>Online’da yayınlansın</span>
            </label>
            <p class="form-hint" style="margin:8px 0 0;">Başvuru yalnızca yayın izni açıkken ve başvuru tarih aralığında alınır.</p>
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Tarihler ve Süre</h2>
            <p class="card-section-desc">Kurs ve başvuru tarih aralıkları.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="kurs_baslama_tarihi">Kurs Başlama <span class="req">*</span></label>
            <input type="date" id="kurs_baslama_tarihi" name="kurs_baslama_tarihi" value="{{ $dateVal('kurs_baslama_tarihi') }}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="kurs_bitis_tarihi">
                Kurs Bitiş <span class="req">*</span>
                <span class="field-hint-inline">Başlama tarihi, toplam kurs saati ve haftalık programa göre otomatik hesaplanabilir.</span>
            </label>
            <div class="input-with-action">
                <input type="date" id="kurs_bitis_tarihi" name="kurs_bitis_tarihi" value="{{ $dateVal('kurs_bitis_tarihi') }}" class="form-control" required>
                <button type="button" class="btn-back btn-back-sm" id="hesapla-kurs-bitis" title="Başlama tarihi, toplam saat ve haftalık programa göre hesapla">
                    <span class="btn-back-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="16" height="16" x="4" y="4" rx="2"/>
                            <path d="M8 2v4"/>
                            <path d="M16 2v4"/>
                            <path d="M4 10h16"/>
                            <path d="m9 16 2 2 4-4"/>
                        </svg>
                    </span>
                    <span class="btn-back-text">Hesapla</span>
                </button>
            </div>
        </div>
        <div class="form-group">
            <label for="basvuru_baslama_tarihi">Başvuru Başlama <span class="req">*</span></label>
            <input type="datetime-local" id="basvuru_baslama_tarihi" name="basvuru_baslama_tarihi" value="{{ $datetimeVal('basvuru_baslama_tarihi') }}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="basvuru_bitis_tarihi">Başvuru Bitiş <span class="req">*</span></label>
            <input type="datetime-local" id="basvuru_bitis_tarihi" name="basvuru_bitis_tarihi" value="{{ $datetimeVal('basvuru_bitis_tarihi') }}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="toplam_kurs_saati">Toplam Kurs Saati</label>
            <input type="number" id="toplam_kurs_saati" name="toplam_kurs_saati" value="{{ $val('toplam_kurs_saati', 0) }}" class="form-control" min="0" max="9999">
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Kontenjan</h2>
            <p class="card-section-desc">Asıl ve yedek kontenjan bilgileri.</p>
        </div>
    </div>

    <div class="form-grid form-grid-2">
        <div class="form-group">
            <label for="kontenjan">Kontenjan <span class="req">*</span></label>
            <input type="number" id="kontenjan" name="kontenjan" value="{{ $val('kontenjan', 20) }}" class="form-control" min="0" max="9999" required>
        </div>
        <div class="form-group">
            <label for="yedek_kontenjan">Yedek Kontenjan</label>
            <input type="number" id="yedek_kontenjan" name="yedek_kontenjan" value="{{ $val('yedek_kontenjan', 0) }}" class="form-control" min="0" max="9999">
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Koşullar</h2>
            <p class="card-section-desc">Yaş, cinsiyet ve ikamet şartlarını tanımlayın.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="minimum_yas">Minimum Yaş</label>
            <input type="number" id="minimum_yas" name="minimum_yas" value="{{ $val('minimum_yas') }}" class="form-control" min="0" max="120" placeholder="Örn. 18">
        </div>
        <div class="form-group">
            <label for="maksimum_yas">Maksimum Yaş</label>
            <input type="number" id="maksimum_yas" name="maksimum_yas" value="{{ $val('maksimum_yas') }}" class="form-control" min="0" max="120" placeholder="Örn. 65">
        </div>
        <div class="form-group">
            <label for="cinsiyet_sarti">Cinsiyet Koşulu</label>
            <select id="cinsiyet_sarti" name="cinsiyet_sarti" class="form-control">
                <option value="">Farketmez</option>
                @foreach ($cinsiyetler as $cinsiyet)
                    <option value="{{ $cinsiyet->value }}" @selected(old('cinsiyet_sarti', $kurs?->cinsiyet_sarti?->value) === $cinsiyet->value)>{{ $cinsiyet->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="ikamet_sarti">İkamet Koşulu <span class="req">*</span></label>
            <select id="ikamet_sarti" name="ikamet_sarti" class="form-control" required data-ikamet-sarti>
                @foreach ($ikametSartlari as $sart)
                    <option value="{{ $sart->value }}" @selected($ikametCurrent === $sart->value)>{{ $sart->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group {{ $ikametCurrent === 'kismen' ? '' : 'is-hidden' }}" data-ikamet-disi-field>
            <label for="ikamet_disi_kontenjan">İkamet Dışı Kontenjan <span class="req">*</span></label>
            <input type="number" id="ikamet_disi_kontenjan" name="ikamet_disi_kontenjan" value="{{ $val('ikamet_disi_kontenjan', 0) }}" class="form-control" min="0" max="9999" data-ikamet-disi-input>
            <p class="field-hint">Sınırlı ilçe dışı başvurular için ayrılacak kontenjan.</p>
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Evraklar</h2>
            <p class="card-section-desc">İsteğe bağlı evrak koşullarını ekleyin.</p>
        </div>
    </div>

    <div class="evrak-picker">
        <div class="evrak-add-row">
            <div class="form-group grow">
                <label for="evrak-tipi-select">Evrak Tipi</label>
                <select id="evrak-tipi-select" class="form-control" data-evrak-select>
                    <option value="">Evrak seçin...</option>
                    @foreach ($evrakTipleri as $evrak)
                        <option value="{{ $evrak->id }}" data-label="{{ $evrak->ad }}">{{ $evrak->ad }}</option>
                    @endforeach
                </select>
            </div>
            <x-back-button type="button" icon="plus" data-evrak-add>Ekle</x-back-button>
        </div>

        <div class="evrak-list" data-evrak-list>
            @forelse ($evrakTipleri->whereIn('id', $selectedEvraklar) as $evrak)
                <div class="evrak-item" data-evrak-item data-id="{{ $evrak->id }}">
                    <input type="hidden" name="evrak_tipi_ids[]" value="{{ $evrak->id }}">
                    <span class="evrak-item-label">{{ $evrak->ad }}</span>
                    <button type="button" class="evrak-item-remove" data-evrak-remove title="Kaldır" aria-label="Kaldır">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                    </button>
                </div>
            @empty
                <p class="evrak-empty" data-evrak-empty>Henüz evrak koşulu eklenmedi.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Haftalık Program</h2>
            <p class="card-section-desc">Ders günü, saat aralığı ve sınıf bilgisi ekleyin.</p>
        </div>
        <x-back-button type="button" icon="plus" class="btn-back-sm" id="add-gun-row">Gün Ekle</x-back-button>
    </div>

    <div class="gun-rows" id="gun-rows">
        @foreach ($gunlerRows as $i => $gunRow)
            <div class="gun-row" data-gun-row>
                <div class="form-group">
                    <label>Gün <span class="req">*</span></label>
                    <select name="gunler[{{ $i }}][gun]" class="form-control" required data-gun-required>
                        <option value="">Seçin</option>
                        @foreach ($haftaGunleri as $gun)
                            <option value="{{ $gun->value }}" @selected(($gunRow['gun'] ?? '') === $gun->value)>{{ $gun->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Başlangıç <span class="req">*</span></label>
                    <input type="time" name="gunler[{{ $i }}][baslangic_saati]" value="{{ $gunRow['baslangic_saati'] ?? '' }}" class="form-control" required data-gun-required>
                </div>
                <div class="form-group">
                    <label>Bitiş <span class="req">*</span></label>
                    <input type="time" name="gunler[{{ $i }}][bitis_saati]" value="{{ $gunRow['bitis_saati'] ?? '' }}" class="form-control" required data-gun-required>
                </div>
                <div class="form-group">
                    <label>Ders Saati <span class="req">*</span></label>
                    <input type="number" step="0.5" min="0.5" max="24" name="gunler[{{ $i }}][ders_saati]" value="{{ $gunRow['ders_saati'] ?? '' }}" class="form-control" placeholder="2" required data-gun-required>
                </div>
                <div class="form-group">
                    <label>Sınıf</label>
                    <input type="text" name="gunler[{{ $i }}][sinif]" value="{{ $gunRow['sinif'] ?? '' }}" class="form-control" maxlength="50" placeholder="Opsiyonel">
                </div>
                <button type="button" class="gun-row-remove" data-remove-gun title="Satırı sil" aria-label="Satırı sil">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
            </div>
        @endforeach
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Açıklama</h2>
            <p class="card-section-desc">İsteğe bağlı. Portalda kurs detayının altında gösterilir.</p>
        </div>
    </div>

    <div class="form-group">
        <label for="aciklama">Açıklama</label>
        @php
            $aciklamaDegeri = old('aciklama', $kurs?->aciklama ?? '');
        @endphp
        <div class="rich-editor @error('aciklama') is-invalid @enderror" data-rich-editor>
            @include('sabit-tanimlar.partials.rich-editor-toolbar')
            <div
                class="rich-editor__surface"
                data-rich-surface
                contenteditable="true"
                role="textbox"
                aria-multiline="true"
                aria-label="Kurs açıklaması"
            >{!! $aciklamaDegeri !!}</div>
            <textarea
                id="aciklama"
                name="aciklama"
                class="rich-editor__input"
                hidden
                data-rich-input
            >{{ $aciklamaDegeri }}</textarea>
        </div>
        @error('aciklama')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </div>
</div>

<template id="gun-row-template">
    <div class="gun-row" data-gun-row>
        <div class="form-group">
            <label>Gün <span class="req">*</span></label>
            <select name="gunler[__INDEX__][gun]" class="form-control" required data-gun-required>
                <option value="">Seçin</option>
                @foreach ($haftaGunleri as $gun)
                    <option value="{{ $gun->value }}">{{ $gun->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Başlangıç <span class="req">*</span></label>
            <input type="time" name="gunler[__INDEX__][baslangic_saati]" class="form-control" required data-gun-required>
        </div>
        <div class="form-group">
            <label>Bitiş <span class="req">*</span></label>
            <input type="time" name="gunler[__INDEX__][bitis_saati]" class="form-control" required data-gun-required>
        </div>
        <div class="form-group">
            <label>Ders Saati <span class="req">*</span></label>
            <input type="number" step="0.5" min="0.5" max="24" name="gunler[__INDEX__][ders_saati]" class="form-control" placeholder="2" required data-gun-required>
        </div>
        <div class="form-group">
            <label>Sınıf</label>
            <input type="text" name="gunler[__INDEX__][sinif]" class="form-control" maxlength="50" placeholder="Opsiyonel">
        </div>
        <button type="button" class="gun-row-remove" data-remove-gun title="Satırı sil" aria-label="Satırı sil">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
    </div>
</template>
