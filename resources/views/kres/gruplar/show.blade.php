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
                @if ($grup->cinsiyet_sarti)
                    · {{ $grup->cinsiyet_sarti->label() }}
                @endif
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

<div class="confirm-modal" id="kres-basvuru-sms-modal" hidden>
    <div class="confirm-modal-backdrop" data-kres-sms-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="kres-basvuru-sms-title">
        <div class="confirm-modal-header">
            <h3 id="kres-basvuru-sms-title" class="confirm-modal-title">SMS Gönder</h3>
            <button type="button" class="confirm-modal-x" data-kres-sms-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-kres-sms-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-kres-sms-no-telefon hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kişi için kayıtlı telefon numarası bulunamadı.
            </p>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="kres-basvuru-sms-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-kres-sms-insert="{ad_soyad}" title="İmleç konumuna ekler">{ad_soyad}</button>
                        <button type="button" class="sms-onizle-btn" data-kres-sms-onizle>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="kres-basvuru-sms-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="4"
                    maxlength="480"
                    data-kres-sms-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, bilgilendirme mesajınız."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-kres-sms-char-count>0</span>/480 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-kres-sms-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-kres-sms-send>Gönder</button>
        </div>
    </div>
</div>

<div class="confirm-modal" id="kres-basvuru-eposta-modal" hidden>
    <div class="confirm-modal-backdrop" data-kres-eposta-close></div>
    <div class="confirm-modal-dialog confirm-modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="kres-basvuru-eposta-title">
        <div class="confirm-modal-header">
            <h3 id="kres-basvuru-eposta-title" class="confirm-modal-title">E-posta Gönder</h3>
            <button type="button" class="confirm-modal-x" data-kres-eposta-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body">
            <p data-kres-eposta-alici style="margin-bottom:14px;"></p>
            <p class="form-hint" data-kres-eposta-no-email hidden style="margin-bottom:14px; color:#f64e60;">
                Bu kişi için kayıtlı e-posta adresi bulunamadı.
            </p>
            <div class="form-group">
                <div class="sms-mesaj-label-row">
                    <label for="kres-basvuru-eposta-konu">Konu <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-kres-eposta-insert="{ad_soyad}" data-kres-eposta-insert-target="konu" title="Konu alanına ekler">{ad_soyad}</button>
                    </div>
                </div>
                <input
                    type="text"
                    id="kres-basvuru-eposta-konu"
                    class="form-control"
                    maxlength="200"
                    data-kres-eposta-konu
                    placeholder="Örn: Merhaba {ad_soyad}"
                >
            </div>
            <div class="form-group sms-mesaj-group" style="margin-bottom:0;">
                <div class="sms-mesaj-label-row">
                    <label for="kres-basvuru-eposta-mesaj">Mesaj <span class="req">*</span></label>
                    <div class="sms-mesaj-actions">
                        <button type="button" class="sms-degisken-btn" data-kres-eposta-insert="{ad_soyad}" data-kres-eposta-insert-target="mesaj" title="Mesaj alanına ekler">{ad_soyad}</button>
                        <button type="button" class="sms-onizle-btn" data-kres-eposta-onizle>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Önizle
                        </button>
                    </div>
                </div>
                <textarea
                    id="kres-basvuru-eposta-mesaj"
                    class="form-control sms-mesaj-input"
                    rows="6"
                    maxlength="5000"
                    data-kres-eposta-mesaj
                    placeholder="Örn: Merhaba {ad_soyad}, bilgilendirme mesajınız."
                ></textarea>
                <p class="form-hint" style="margin-top:8px; font-size:12px; color:#7e8299;">
                    <span data-kres-eposta-char-count>0</span>/5000 karakter · Kişiye özel isim için <code>{ad_soyad}</code> kullanın
                </p>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-secondary btn-wide" data-kres-eposta-close>Vazgeç</button>
            <button type="button" class="btn btn-primary btn-wide" data-kres-eposta-send>Gönder</button>
        </div>
    </div>
</div>

<div class="confirm-modal" id="kres-sms-onizleme-modal" hidden>
    <div class="confirm-modal-backdrop" data-kres-sms-onizleme-close></div>
    <div class="confirm-modal-dialog sms-onizleme-dialog" role="dialog" aria-modal="true" aria-labelledby="kres-sms-onizleme-title">
        <div class="confirm-modal-header">
            <h3 id="kres-sms-onizleme-title" class="confirm-modal-title">SMS Önizleme</h3>
            <button type="button" class="confirm-modal-x" data-kres-sms-onizleme-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body sms-onizleme-body">
            <p class="sms-onizleme-alici" data-kres-sms-onizleme-alici></p>
            <div class="sms-phone" aria-hidden="true">
                <div class="sms-phone-frame">
                    <div class="sms-phone-notch"></div>
                    <div class="sms-phone-screen">
                        <div class="sms-phone-status">
                            <span>9:41</span>
                            <span>SMS</span>
                        </div>
                        <div class="sms-phone-thread">
                            <div class="sms-phone-bubble" data-kres-sms-onizleme-mesaj></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-kres-sms-onizleme-close>Kapat</button>
        </div>
    </div>
</div>

<div class="confirm-modal" id="kres-eposta-onizleme-modal" hidden>
    <div class="confirm-modal-backdrop" data-kres-eposta-onizleme-close></div>
    <div class="confirm-modal-dialog eposta-onizleme-dialog" role="dialog" aria-modal="true" aria-labelledby="kres-eposta-onizleme-title">
        <div class="confirm-modal-header">
            <h3 id="kres-eposta-onizleme-title" class="confirm-modal-title">E-Posta Önizleme</h3>
            <button type="button" class="confirm-modal-x" data-kres-eposta-onizleme-close aria-label="Kapat">&times;</button>
        </div>
        <div class="confirm-modal-body eposta-onizleme-body">
            <p class="sms-onizleme-alici" data-kres-eposta-onizleme-alici></p>
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
                        <strong data-kres-eposta-onizleme-kime>—</strong>
                    </div>
                    <div class="eposta-preview-row">
                        <span>Konu</span>
                        <strong data-kres-eposta-onizleme-konu></strong>
                    </div>
                </div>
                <div class="eposta-preview-body" data-kres-eposta-onizleme-mesaj></div>
            </div>
        </div>
        <div class="confirm-modal-footer">
            <button type="button" class="btn btn-primary btn-wide" data-kres-eposta-onizleme-close>Kapat</button>
        </div>
    </div>
</div>
@endsection
