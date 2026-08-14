@extends('layouts.admin')

@section('title', $grup->ad.' · '.$okul->ad)

@section('content')
<div class="lesson-detail" data-kres-grup-page data-kisi-ara-url="{{ route('kres.kisiler.ara') }}">
    <div class="lesson-detail-header">
        <div>
            <p class="page-eyebrow" style="margin-bottom:6px;">Kreş Yönetimi · {{ $okul->ad }}</p>
            <h1 class="lesson-detail-title">{{ $grup->ad }}</h1>
            <p class="page-subtitle" style="margin-top:6px;">
                {{ $grup->yasAraligiLabel() }}
                · Kontenjan {{ $grup->kontenjan }}
                · Kesin kayıt {{ $kesinSayisi }}
            </p>
        </div>
        <div class="kres-page-actions">
            <div class="kres-page-actions__btns">
                <x-back-button href="{{ route('kres.okullar.show', $okul) }}">Gruplara Dön</x-back-button>
            </div>
            @include('kres.partials.donem-switcher')
        </div>
    </div>

    @include('kres.partials.chrome')

    @php
        $doluluk = $grup->kontenjan > 0 ? min(100, (int) round(($kesinSayisi / $grup->kontenjan) * 100)) : 0;
    @endphp
    <div class="lesson-stat-grid" style="margin-bottom:1.25rem;">
        <div class="lesson-stat-card">
            <h3 class="lesson-stat-label">Kontenjan</h3>
            <div class="lesson-stat-value">{{ number_format($grup->kontenjan) }}</div>
        </div>
        <div class="lesson-stat-card">
            <h3 class="lesson-stat-label">Kesin Kayıt</h3>
            <div class="lesson-stat-value">{{ number_format($kesinSayisi) }}</div>
        </div>
        <div class="lesson-stat-card">
            <h3 class="lesson-stat-label">Doluluk</h3>
            <div class="lesson-stat-value">%{{ $doluluk }}</div>
        </div>
        <div class="lesson-stat-card">
            <h3 class="lesson-stat-label">Yaş Grubu</h3>
            <div class="lesson-stat-value" style="font-size:18px;">{{ $grup->yasAraligiLabel() }}</div>
        </div>
    </div>

    <div
        data-basvuru-panel
        data-basvuru-url="{{ route('kres.gruplar.basvurular', [$okul, $grup]) }}"
        data-basvuru-durum="{{ $basvuruDurum }}"
    >
        <div class="basvuru-toolbar">
            <div class="basvuru-filters">
                <button
                    type="button"
                    class="basvuru-filter {{ $basvuruDurum === 'tumu' ? 'is-active' : '' }}"
                    data-basvuru-filter="tumu"
                >Tümü</button>
                @foreach ($durumlar as $filtreDurum)
                    <button
                        type="button"
                        class="basvuru-filter {{ $basvuruDurum === $filtreDurum->kod ? 'is-active' : '' }}"
                        data-basvuru-filter="{{ $filtreDurum->kod }}"
                    >{{ $filtreDurum->ad }}</button>
                @endforeach
            </div>
            <div class="basvuru-toolbar-actions">
                <div class="basvuru-toolbar-meta" data-basvuru-total>Toplam Kayıt: —</div>
                <div class="column-picker" data-basvuru-column-picker>
                    <button
                        type="button"
                        class="btn-columns btn-columns-sm"
                        id="basvuruColumnPickerToggle"
                        data-basvuru-column-toggle
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="basvuruColumnDropdown"
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
                    <div class="column-dropdown" id="basvuruColumnDropdown" role="menu" aria-labelledby="basvuruColumnPickerToggle">
                        <div class="column-dropdown-header">
                            <span>Görünür sütunlar</span>
                            <span class="column-dropdown-hint">Sürükleyerek sıralayın</span>
                        </div>
                        <div class="column-dropdown-list">
                            @foreach ($basvuruColumns as $key => $label)
                                @if ($key === 'islemler')
                                    @continue
                                @endif
                                <label class="column-option" data-column="{{ $key }}">
                                    <input
                                        type="checkbox"
                                        class="column-toggle"
                                        data-column="{{ $key }}"
                                        @checked(in_array($key, $basvuruDefaultVisible, true))
                                    >
                                    <span class="column-option-check" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"/>
                                        </svg>
                                    </span>
                                    <span class="column-option-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="column-dropdown-footer">
                            <button
                                type="button"
                                class="column-save-btn save-prefs-btn"
                                data-basvuru-column-save
                                title="Kolon düzenlemelerini kaydet"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                    <polyline points="17 21 17 13 7 13 7 21"/>
                                    <polyline points="7 3 7 8 15 8"/>
                                </svg>
                                Kaydet
                            </button>
                            <button type="button" class="column-reset-btn" data-basvuru-column-reset>
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
                    :href="route('kres.gruplar.basvurular.export', [$okul, $grup])"
                    class="btn-excel-sm"
                    data-basvuru-excel
                />
                @yetki('kres.basvuru_olustur')
                <x-cta-button type="button" class="btn-cta-sm" data-kres-modal-open="basvuru-create">
                    Yeni Kayıt
                </x-cta-button>
                @endyetki
            </div>
        </div>

        <div class="card table-card lesson-table-card" data-basvuru-content>
            <div class="empty-state basvuru-placeholder">
                <div class="empty-state-title">Başvurular</div>
                <p class="empty-state-text">Başvurular yükleniyor…</p>
            </div>
        </div>
    </div>
</div>

@yetki('kres.basvuru_olustur')
<div class="confirm-modal" id="kres-basvuru-create-modal" hidden>
    <div class="confirm-modal-backdrop" data-kres-modal-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog--wide" role="dialog" aria-modal="true">
        <div class="confirm-modal-header">
            <h3 class="confirm-modal-title">Yeni kayıt</h3>
            <button type="button" class="confirm-modal-x" data-kres-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="{{ route('kres.basvurular.store', [$okul, $grup]) }}" class="confirm-modal-body">
            @csrf
            <div class="form-group">
                <label for="kisi_ara">Öğrenci ara *</label>
                <input id="kisi_ara" type="search" class="form-control" placeholder="Ad, soyad veya T.C. kimlik no" autocomplete="off" data-kisi-search data-kisi-target="kisi_id" data-kisi-label="kisi_label">
                <input type="hidden" name="kisi_id" id="kisi_id" required>
                <p class="kres-kisi-picked" data-kisi-picked="kisi_label">Öğrenci seçilmedi</p>
                <div class="kres-kisi-results" data-kisi-results hidden></div>
            </div>
            <div class="form-group">
                <label for="basvuran_ara">Başvuran (veli) — isteğe bağlı</label>
                <input id="basvuran_ara" type="search" class="form-control" placeholder="Ad, soyad veya T.C. kimlik no" autocomplete="off" data-kisi-search data-kisi-target="basvuran_id" data-kisi-label="basvuran_label">
                <input type="hidden" name="basvuran_id" id="basvuran_id">
                <p class="kres-kisi-picked" data-kisi-picked="basvuran_label">Seçilmedi</p>
                <div class="kres-kisi-results" data-kisi-results hidden></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="basvuru_durum">Durum *</label>
                    <select id="basvuru_durum" name="durum_id" class="form-control" required data-yedek-toggle>
                        @foreach ($durumlar as $durum)
                            <option value="{{ $durum->id }}" data-kod="{{ $durum->kod }}" @selected($durum->kod === 'onay_bekliyor')>
                                {{ $durum->ad }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" data-yedek-field hidden>
                    <label for="basvuru_yedek">Yedek sıra</label>
                    <input id="basvuru_yedek" name="yedek_sira" type="number" class="form-control" min="1" max="9999">
                </div>
            </div>
            <div class="form-group">
                <label for="basvuru_not">Not</label>
                <textarea id="basvuru_not" name="notlar" class="form-control" rows="2" maxlength="2000"></textarea>
            </div>
            <p class="text-muted" style="font-size:13px;margin:0 0 12px;">Kişi yoksa önce <a href="{{ route('kisiler.create') }}" target="_blank">Kişiler</a> modülünden ekleyin.</p>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-secondary" data-kres-modal-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>
@endyetki

@yetki('kres.basvuru_durum_guncelle')
<div class="confirm-modal" id="kres-basvuru-durum-modal" hidden>
    <div class="confirm-modal-backdrop" data-kres-modal-close></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true">
        <div class="confirm-modal-header">
            <h3 class="confirm-modal-title">Durum güncelle</h3>
            <button type="button" class="confirm-modal-x" data-kres-modal-close aria-label="Kapat">&times;</button>
        </div>
        <form method="POST" action="#" class="confirm-modal-body" data-durum-form>
            @csrf
            @method('PUT')
            <p class="kres-kisi-picked" data-durum-kisi></p>
            <div class="form-group">
                <label for="upd_durum">Durum *</label>
                <select id="upd_durum" name="durum_id" class="form-control" required data-yedek-toggle>
                    @foreach ($durumlar as $durum)
                        <option value="{{ $durum->id }}" data-kod="{{ $durum->kod }}">{{ $durum->ad }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" data-yedek-field hidden>
                <label for="upd_yedek">Yedek sıra</label>
                <input id="upd_yedek" name="yedek_sira" type="number" class="form-control" min="1" max="9999">
            </div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-secondary" data-kres-modal-close>Vazgeç</button>
                <button type="submit" class="btn btn-primary">Güncelle</button>
            </div>
        </form>
    </div>
</div>
@endyetki
@endsection
