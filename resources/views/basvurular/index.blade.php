@extends('layouts.admin')

@section('title', 'Kurs Başvuruları')

@php
    $defaultVisible = $defaultVisible ?? ['kurs_no', 'brans', 'merkez', 'katilimci', 'kimlik', 'telefon', 'durum', 'basari', 'basvuru_tarihi', 'islemler'];
    $allColumns = $allColumns ?? [
        'kurs_no' => 'Kurs No',
        'brans' => 'Branş',
        'merkez' => 'Merkez',
        'basvuran' => 'Başvuran',
        'katilimci' => 'Katılımcı',
        'veli' => 'Veli',
        'kimlik' => 'Kimlik No',
        'dogum' => 'Doğum T.',
        'telefon' => 'Telefon',
        'ikamet' => 'İkamet',
        'durum' => 'Durum',
        'basari' => 'Başarı',
        'kursa_baslama' => 'Kursa Başlama',
        'onay' => 'Onay Tarihi',
        'iptal' => 'İptal Tarihi',
        'iptal_gerekce' => 'İptal Gerekçesi',
        'kaydeden' => 'Kaydeden',
        'basvuru_tarihi' => 'Başvuru Tarihi',
        'islemler' => 'İşlemler',
    ];
    $ozet = $ozet ?? ['toplam' => 0, 'onay_bekliyor' => 0, 'kesin_kayit' => 0, 'iptal' => 0];
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kurs Yönetimi</p>
        <h1 class="page-title">Kurs Başvuruları</h1>
        <p class="page-subtitle">Tüm kurslardaki başvuruları filtreleyin, sütunları düzenleyin ve kayıtları yönetin.</p>
    </div>
    <div class="flex items-center gap-2">
        @yetki('basvuru.olustur')
            <x-cta-button :href="route('basvurular.create')">Yeni Başvuru</x-cta-button>
        @endyetki
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Toplam Başvuru</div>
        <div class="stat-value">{{ number_format($ozet['toplam']) }}</div>
    </div>
    <div class="stat-card stat-card-hazirlik">
        <div class="stat-label">Onay Bekleyen</div>
        <div class="stat-value">{{ number_format($ozet['onay_bekliyor']) }}</div>
    </div>
    <div class="stat-card stat-card-kesin">
        <div class="stat-label">Kesin Kayıt</div>
        <div class="stat-value">{{ number_format($ozet['kesin_kayit']) }}</div>
    </div>
    <div class="stat-card stat-card-iptal">
        <div class="stat-label">İptal</div>
        <div class="stat-value">{{ number_format($ozet['iptal']) }}</div>
    </div>
</div>

{{-- Filtre Kartı --}}
<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">Ad, kimlik, kurs no, merkez ve tarih aralıklarına göre daraltın.</p>
        </div>
    </div>

    <form id="basvurular-filter-form" method="GET" action="{{ route('basvurular.index') }}" data-ajax-filter>
        <div class="filter-grid" data-filter-grid>
            <div class="form-group">
                <label for="q">Ad, Kimlik No veya Telefon</label>
                <input
                    type="text"
                    id="q"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Ara..."
                    class="form-control"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="kurs_no">Kurs No</label>
                <div class="input-with-mode" data-kurs-no-mode>
                    <input
                        type="text"
                        id="kurs_no"
                        name="kurs_no"
                        value="{{ $filters['kurs_no'] ?? '' }}"
                        placeholder="No girin..."
                        class="form-control input-with-mode-control"
                        autocomplete="off"
                    >
                    <input type="hidden" name="kurs_no_mode" value="{{ $filters['kurs_no_mode'] ?? 'exact' }}" data-mode-value>
                    <button type="button" class="mode-toggle" data-mode-toggle title="Arama yöntemi" aria-haspopup="listbox" aria-expanded="false">
                        <span data-mode-label>Eşit</span>
                        <svg class="mode-toggle-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="mode-dropdown" data-mode-dropdown hidden role="listbox">
                        <button type="button" class="mode-option" data-mode="contains" role="option">İçinde</button>
                        <button type="button" class="mode-option" data-mode="starts" role="option">Başında</button>
                        <button type="button" class="mode-option" data-mode="ends" role="option">Sonunda</button>
                        <button type="button" class="mode-option" data-mode="exact" role="option">Eşit</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Alan</label>
                <x-searchable-select
                    name="alan_id"
                    placeholder="Tüm Alanlar"
                    :value="$filters['alan_id'] ?? ''"
                    :options="$alanlar->map(fn ($a) => ['value' => $a->id, 'label' => $a->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Branş</label>
                <x-searchable-select
                    name="brans_id"
                    placeholder="Tüm Branşlar"
                    :value="$filters['brans_id'] ?? ''"
                    :options="$branslar->map(fn ($b) => ['value' => $b->id, 'label' => $b->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Merkez</label>
                <x-searchable-select
                    name="merkez_id"
                    placeholder="Tüm Merkezler"
                    :value="$filters['merkez_id'] ?? ''"
                    :options="$merkezler->map(fn ($m) => ['value' => $m->id, 'label' => $m->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Kurum</label>
                <x-searchable-select
                    name="kurum_id"
                    placeholder="Tüm Kurumlar"
                    :value="$filters['kurum_id'] ?? ''"
                    :options="$kurumlar->map(fn ($k) => ['value' => $k->id, 'label' => $k->ad])->all()"
                />
            </div>

            <div class="form-group">
                <label>Öğretmen</label>
                <x-searchable-select
                    name="ogretmen_id"
                    placeholder="Tüm Öğretmenler"
                    :value="$filters['ogretmen_id'] ?? ''"
                    :options="$ogretmenler->map(fn ($o) => ['value' => $o->id, 'label' => $o->tam_adi])->all()"
                />
            </div>

            <div class="form-group">
                <label for="basvuru_durum">Başvuru Durumu</label>
                <select id="basvuru_durum" name="basvuru_durum" class="form-control" data-reset-value="tumu">
                    <option value="tumu" @selected(($filters['basvuru_durum'] ?? 'tumu') === 'tumu')>Tümü</option>
                    @foreach ($basvuruDurumlari as $durum)
                        <option value="{{ $durum->kod }}" @selected(($filters['basvuru_durum'] ?? 'tumu') === $durum->kod)>{{ $durum->ad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="basari_durumu_id">Başarı Durumu</label>
                <select id="basari_durumu_id" name="basari_durumu_id" class="form-control">
                    <option value="">Hepsi</option>
                    @foreach ($basariDurumlari as $basari)
                        <option value="{{ $basari->id }}" @selected(($filters['basari_durumu_id'] ?? '') == $basari->id)>{{ $basari->ad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="kurs_durum">Kurs Durumu</label>
                <select id="kurs_durum" name="kurs_durum" class="form-control" data-reset-value="tumu">
                    <option value="tumu" @selected(($filters['kurs_durum'] ?? 'tumu') === 'tumu')>Tüm Kurslar</option>
                    @foreach ($kursDurumlari as $durum)
                        <option value="{{ $durum->value }}" @selected(($filters['kurs_durum'] ?? 'tumu') === $durum->value)>{{ $durum->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="basvuru_ilk">Başvuru Tarihi (İlk)</label>
                <input type="date" id="basvuru_ilk" name="basvuru_ilk" value="{{ $filters['basvuru_ilk'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="basvuru_son">Başvuru Tarihi (Son)</label>
                <input type="date" id="basvuru_son" name="basvuru_son" value="{{ $filters['basvuru_son'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="kursa_baslama_ilk">Kursa Başlama Tarihi (İlk)</label>
                <input type="date" id="kursa_baslama_ilk" name="kursa_baslama_ilk" value="{{ $filters['kursa_baslama_ilk'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="kursa_baslama_son">Kursa Başlama Tarihi (Son)</label>
                <input type="date" id="kursa_baslama_son" name="kursa_baslama_son" value="{{ $filters['kursa_baslama_son'] ?? '' }}" class="form-control">
            </div>
        </div>

        <div class="filter-actions">
            <button type="button" class="more-filters-btn" data-more-filters aria-expanded="false">
                <span data-more-filters-label>Daha fazla filtre</span>
                <svg class="more-filters-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="filter-actions-right">
                <x-back-button id="basvurular-filter-clear" type="button" icon="close">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

{{-- Tablo Kartı --}}
<div class="card table-card" id="basvurular-table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Başvuru Kayıtları</div>
            <p class="table-subtitle">Sütunları sürükleyerek sıralayabilir, görünürlüğü değiştirebilirsiniz.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="column-picker" data-column-picker>
                <button
                    type="button"
                    class="btn-columns"
                    id="columnPickerToggle"
                    onclick="toggleColumnDropdown(event)"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="columnDropdown"
                >
                    <span class="btn-columns-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2"/>
                            <path d="M9 3v18"/>
                            <path d="M15 3v18"/>
                        </svg>
                    </span>
                    <span class="btn-columns-text">Sütunlar</span>
                    <svg class="btn-columns-caret" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
                <div class="column-dropdown" id="columnDropdown" role="menu" aria-labelledby="columnPickerToggle">
                    <div class="column-dropdown-header">
                        <span>Görünür sütunlar</span>
                        <span class="column-dropdown-hint">Sürükleyerek sıralayın</span>
                    </div>
                    <div class="column-dropdown-list">
                        @foreach ($allColumns as $key => $label)
                            @if ($key !== 'islemler')
                                <label class="column-option" data-column="{{ $key }}">
                                    <input
                                        type="checkbox"
                                        class="column-toggle"
                                        data-column="{{ $key }}"
                                        @checked(in_array($key, $defaultVisible, true))
                                    >
                                    <span class="column-option-check" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </span>
                                    <span class="column-option-label">{{ $label }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <div class="column-dropdown-footer">
                        <button
                            type="button"
                            class="column-save-btn save-prefs-btn"
                            id="dropdown-save-column-prefs"
                            data-column-save
                            title="Kolon düzenlemelerini kaydet"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            Kaydet
                        </button>
                        <button type="button" class="column-reset-btn" id="reset-column-prefs">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                <path d="M3 3v5h5"/>
                            </svg>
                            Sıfırla
                        </button>
                    </div>
                </div>
            </div>
            <x-excel-export
                :href="route('basvurular.export', request()->query())"
                id="basvurular-excel-link"
            />
        </div>
    </div>

    <div id="basvurular-results">
        @include('basvurular._results')
    </div>
</div>

@include('kurslar._basvuru_action_modals')
@include('kurslar._yedek_sira_modal')

@yetki('basvuru.evrak_goruntule')
@include('partials.basvuru-evraklar-modal')
@endyetki

{{-- Başvuruya tekli SMS gönder modalı --}}
<div class="confirm-modal" id="basvuru-sms-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-sms-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="basvuru-sms-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-sms-modal-title" class="confirm-modal-title">SMS Gönder</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-sms-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-sms-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-basvuru-sms-no-telefon hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kişi için kayıtlı telefon numarası bulunamadı.
            </p>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="basvuru-sms-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-sms-insert="{ad_soyad}"
                            title="İmleç konumuna ekler"
                        >{ad_soyad}</button>
                        <button
                            type="button"
                            class="sms-onizle-btn"
                            data-basvuru-sms-onizle
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="basvuru-sms-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="4"
                    maxlength="480"
                    data-basvuru-sms-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, kurs kaydınız onaylandı."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-basvuru-sms-char-count>0</span>/480 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-sms-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-sms-send>Gönder</button>
        </div>
    </div>
</div>

{{-- Başvuruya tekli e-posta gönder modalı --}}
<div class="confirm-modal" id="basvuru-eposta-modal" hidden>
    <div class="confirm-modal-backdrop" data-basvuru-eposta-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="basvuru-eposta-modal-title">
        <div class="confirm-modal-header">
            <h3 id="basvuru-eposta-modal-title" class="confirm-modal-title">E-Posta Gönder</h3>
            <button type="button" class="confirm-modal-x" data-basvuru-eposta-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-basvuru-eposta-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-basvuru-eposta-no-email hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kişi için kayıtlı e-posta adresi bulunamadı.
            </p>
            <div class="form-group">
                <div class="sms-mesaj-label-row">
                    <label for="basvuru-eposta-konu">Konu <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-eposta-insert="{ad_soyad}"
                            data-basvuru-eposta-insert-target="konu"
                            title="Konu alanına ekler"
                        >{ad_soyad}</button>
                    </div>
                </div>
                <input
                    type="text"
                    id="basvuru-eposta-konu"
                    class="form-control"
                    maxlength="200"
                    data-basvuru-eposta-konu
                    placeholder="Örn: Merhaba {ad_soyad}"
                >
            </div>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="basvuru-eposta-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button
                            type="button"
                            class="sms-degisken-btn"
                            data-basvuru-eposta-insert="{ad_soyad}"
                            data-basvuru-eposta-insert-target="mesaj"
                            title="Mesaj alanına ekler"
                        >{ad_soyad}</button>
                        <button
                            type="button"
                            class="sms-onizle-btn"
                            data-basvuru-eposta-onizle
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="basvuru-eposta-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="6"
                    maxlength="5000"
                    data-basvuru-eposta-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, başvuru durumunuz güncellendi."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-basvuru-eposta-char-count>0</span>/5000 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-basvuru-eposta-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-basvuru-eposta-send>Gönder</button>
        </div>
    </div>
</div>

{{-- SMS önizleme modalı --}}
<div class="confirm-modal" id="sms-onizleme-modal" hidden>
    <div class="confirm-modal-backdrop" data-sms-onizleme-close></div>
    <div class="confirm-modal-dialog sms-onizleme-dialog" role="dialog" aria-modal="true" aria-labelledby="sms-onizleme-title">
        <div class="confirm-modal-header">
            <h3 id="sms-onizleme-title" class="confirm-modal-title">SMS Önizleme</h3>
            <button type="button" class="confirm-modal-x" data-sms-onizleme-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body sms-onizleme-body">
            <p class="sms-onizleme-alici" data-sms-onizleme-alici></p>
            <div class="sms-phone" aria-hidden="true">
                <div class="sms-phone-frame">
                    <div class="sms-phone-notch"></div>
                    <div class="sms-phone-screen">
                        <div class="sms-phone-status">
                            <span>9:41</span>
                            <span>SMS</span>
                        </div>
                        <div class="sms-phone-thread">
                            <div class="sms-phone-bubble" data-sms-onizleme-mesaj></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-sms-onizleme-close>Kapat</button>
        </div>
    </div>
</div>

{{-- E-posta önizleme modalı --}}
<div class="confirm-modal" id="eposta-onizleme-modal" hidden>
    <div class="confirm-modal-backdrop" data-eposta-onizleme-close></div>
    <div class="confirm-modal-dialog eposta-onizleme-dialog" role="dialog" aria-modal="true" aria-labelledby="eposta-onizleme-title">
        <div class="confirm-modal-header">
            <h3 id="eposta-onizleme-title" class="confirm-modal-title">E-Posta Önizleme</h3>
            <button type="button" class="confirm-modal-x" data-eposta-onizleme-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body eposta-onizleme-body">
            <p class="sms-onizleme-alici" data-eposta-onizleme-alici></p>
            <div class="eposta-preview" aria-hidden="true">
                <div class="eposta-preview-chrome">
                    <span class="eposta-preview-dot"></span>
                    <span class="eposta-preview-dot"></span>
                    <span class="eposta-preview-dot"></span>
                    <span class="eposta-preview-chrome-title">E-posta</span>
                </div>
                <div class="eposta-preview-meta">
                    <div class="eposta-preview-row">
                        <span>Kime</span>
                        <strong data-eposta-onizleme-kime>—</strong>
                    </div>
                    <div class="eposta-preview-row">
                        <span>Konu</span>
                        <strong data-eposta-onizleme-konu></strong>
                    </div>
                </div>
                <div class="eposta-preview-body" data-eposta-onizleme-mesaj></div>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-eposta-onizleme-close>Kapat</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function closeOtherMenus() {
        document.querySelectorAll('[data-searchable-select]').forEach(function (select) {
            select.classList.remove('open');
            select.querySelector('[data-select-dropdown]')?.classList.remove('open');
        });
        document.querySelectorAll('[data-row-actions].is-open').forEach(function (wrap) {
            wrap.classList.remove('is-open');
            wrap.querySelector('[data-action-toggle]')?.setAttribute('aria-expanded', 'false');
            const dropdown = wrap.querySelector('[data-action-dropdown]');
            if (dropdown) {
                dropdown.hidden = true;
                dropdown.classList.remove('is-dropup');
                dropdown.style.top = '';
                dropdown.style.bottom = '';
                dropdown.style.left = '';
                dropdown.style.right = '';
                dropdown.style.position = '';
            }
        });
        document.querySelectorAll('.table-wrapper.has-open-action-menu').forEach(function (el) {
            el.classList.remove('has-open-action-menu');
        });
    }

    function toggleColumnDropdown(event) {
        event.stopPropagation();
        closeOtherMenus();
        const dropdown = document.getElementById('columnDropdown');
        const toggle = document.getElementById('columnPickerToggle');
        const picker = document.querySelector('[data-column-picker]');
        const willOpen = !dropdown?.classList.contains('open');
        dropdown?.classList.toggle('open', willOpen);
        picker?.classList.toggle('is-open', willOpen);
        toggle?.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    document.getElementById('columnDropdown')?.addEventListener('click', function (event) {
        event.stopPropagation();
    });

    document.addEventListener('click', function () {
        document.getElementById('columnDropdown')?.classList.remove('open');
        document.querySelector('[data-column-picker]')?.classList.remove('is-open');
        document.getElementById('columnPickerToggle')?.setAttribute('aria-expanded', 'false');
    });
</script>
@endpush
