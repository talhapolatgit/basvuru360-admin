@php
    /** @var \App\Models\Etkinlik|null $etkinlik */
    $etkinlik = $etkinlik ?? null;

    $val = function (string $key, mixed $default = null) use ($etkinlik) {
        if (old($key) !== null) {
            return old($key);
        }
        if ($etkinlik === null) {
            return $default;
        }
        return data_get($etkinlik, $key) ?? $default;
    };

    $dateVal = function (string $key) use ($etkinlik) {
        if (old($key) !== null) {
            return old($key);
        }
        return $etkinlik?->{$key}?->format('Y-m-d') ?? '';
    };

    $datetimeVal = function (string $key) use ($etkinlik) {
        if (old($key) !== null) {
            return old($key);
        }
        return $etkinlik?->{$key}?->format('Y-m-d\TH:i') ?? '';
    };

    $selectedEvraklar = collect(old('evrak_tipi_ids', $etkinlik?->evrakTipleri->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();

    $kurumlar = $kurumlar ?? collect();
    $seciliKurumIds = collect(old('kurumlar', $etkinlik?->kurumlar?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();

    $ikametCurrent = old('ikamet_sarti', $etkinlik?->ikamet_sarti?->value ?? 'hayir');
@endphp

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Temel Bilgiler</h2>
            <p class="card-section-desc">Etkinlik adı, tipi ve konum bilgileri.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="ad">Etkinlik Adı <span class="req">*</span></label>
            <input type="text" id="ad" name="ad" value="{{ $val('ad') }}" class="form-control @error('ad') is-invalid @enderror" required maxlength="255">
            @error('ad') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="aciklama">Açıklama</label>
            <textarea id="aciklama" name="aciklama" class="form-control" rows="3" maxlength="5000">{{ $val('aciklama') }}</textarea>
        </div>

        <div class="form-group">
            <label>Etkinlik Tipi <span class="req">*</span></label>
            <x-searchable-select
                name="etkinlik_tipi_id"
                placeholder="Tip seçin"
                :value="(string) $val('etkinlik_tipi_id', '')"
                :options="$etkinlikTipleri->map(fn ($t) => ['value' => $t->id, 'label' => $t->ad])->all()"
                :required="true"
            />
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
            <div class="chip-select @error('kurumlar') is-invalid @enderror" data-chip-select data-input-name="kurumlar[]" data-placeholder="Kurum seçin">
                <div class="chip-select-control form-control" data-chip-select-toggle tabindex="0" role="combobox" aria-haspopup="listbox" aria-expanded="false">
                    <div class="chip-select-chips" data-chip-select-chips>
                        @foreach ($kurumlar->whereIn('id', $seciliKurumIds) as $kurum)
                            <span class="chip-select-chip" data-chip-id="{{ $kurum->id }}">
                                <input type="hidden" name="kurumlar[]" value="{{ $kurum->id }}">
                                <span class="chip-select-chip-label">{{ $kurum->ad }}</span>
                                <button type="button" class="chip-select-chip-remove" data-chip-remove aria-label="Kaldır">&times;</button>
                            </span>
                        @endforeach
                    </div>
                    <span class="chip-select-placeholder" data-chip-select-placeholder @if (count($seciliKurumIds) > 0) hidden @endif>Kurum seçin</span>
                </div>
                <div class="select-dropdown" data-chip-select-dropdown role="listbox">
                    <input type="text" class="select-search" placeholder="Ara..." data-chip-select-search autocomplete="off">
                    <div data-chip-select-options>
                        @foreach ($kurumlar as $kurum)
                            <div class="select-option {{ in_array($kurum->id, $seciliKurumIds, true) ? 'is-selected' : '' }}" data-value="{{ $kurum->id }}" data-label="{{ $kurum->ad }}" @if (in_array($kurum->id, $seciliKurumIds, true)) hidden @endif>{{ $kurum->ad }}</div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="durum">Durum <span class="req">*</span></label>
            <select id="durum" name="durum" class="form-control" required>
                @foreach ($durumlar as $durum)
                    <option value="{{ $durum->value }}" @selected(old('durum', $etkinlik?->durum?->value ?? 'hazirlik') === $durum->value)>{{ $durum->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Tarihler</h2>
            <p class="card-section-desc">Etkinlik ve başvuru tarih aralıkları.</p>
        </div>
    </div>
    <div class="form-grid">
        <div class="form-group">
            <label for="baslangic_tarihi">Başlangıç <span class="req">*</span></label>
            <input type="date" id="baslangic_tarihi" name="baslangic_tarihi" value="{{ $dateVal('baslangic_tarihi') }}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="bitis_tarihi">Bitiş <span class="req">*</span></label>
            <input type="date" id="bitis_tarihi" name="bitis_tarihi" value="{{ $dateVal('bitis_tarihi') }}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="basvuru_baslama_tarihi">Başvuru Başlama <span class="req">*</span></label>
            <input type="datetime-local" id="basvuru_baslama_tarihi" name="basvuru_baslama_tarihi" value="{{ $datetimeVal('basvuru_baslama_tarihi') }}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="basvuru_bitis_tarihi">Başvuru Bitiş <span class="req">*</span></label>
            <input type="datetime-local" id="basvuru_bitis_tarihi" name="basvuru_bitis_tarihi" value="{{ $datetimeVal('basvuru_bitis_tarihi') }}" class="form-control" required>
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Kontenjan</h2>
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
        </div>
    </div>
    <div class="form-grid">
        <div class="form-group">
            <label for="minimum_yas">Minimum Yaş</label>
            <input type="number" id="minimum_yas" name="minimum_yas" value="{{ $val('minimum_yas') }}" class="form-control" min="0" max="120">
        </div>
        <div class="form-group">
            <label for="maksimum_yas">Maksimum Yaş</label>
            <input type="number" id="maksimum_yas" name="maksimum_yas" value="{{ $val('maksimum_yas') }}" class="form-control" min="0" max="120">
        </div>
        <div class="form-group">
            <label for="cinsiyet_sarti">Cinsiyet Koşulu</label>
            <select id="cinsiyet_sarti" name="cinsiyet_sarti" class="form-control">
                <option value="">Farketmez</option>
                @foreach ($cinsiyetler as $cinsiyet)
                    <option value="{{ $cinsiyet->value }}" @selected(old('cinsiyet_sarti', $etkinlik?->cinsiyet_sarti?->value) === $cinsiyet->value)>{{ $cinsiyet->label() }}</option>
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
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Evraklar</h2>
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
                    <button type="button" class="evrak-item-remove" data-evrak-remove title="Kaldır">&times;</button>
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
            <h2 class="card-section-title">Yayın</h2>
        </div>
    </div>
    <div class="form-group form-group-switch">
        <label class="switch-label" for="onlinede_yayinlansin">
            <input type="checkbox" id="onlinede_yayinlansin" name="onlinede_yayinlansin" value="1" @checked(old('onlinede_yayinlansin', $etkinlik?->onlinede_yayinlansin))>
            <span>Online’da yayınlansın</span>
        </label>
        <p class="form-hint" style="margin:8px 0 0;">Başvuru yalnızca yayın izni açıkken ve başvuru tarih aralığında alınır.</p>
    </div>
</div>
