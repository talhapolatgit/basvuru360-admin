@extends('layouts.admin')

@section('title', ($basvuru->kisi?->tam_adi ?? 'Başvuru').' — Başvuru Detayı')

@section('content')
@php
    $etkinlik = $basvuru->etkinlik ?? $etkinlik;
    $katilimci = $basvuru->kisi;
    $basvuran = $basvuru->basvuran;
    $veli = $basvuru->veli;
@endphp

<div class="lesson-detail basvuru-detail">
    <div class="lesson-detail-header">
        <div>
            <x-back-button :href="route('etkinlikler.show', ['etkinlik' => $etkinlik, 'tab' => 'basvurular'])" class="btn-back-sm">Başvurulara Dön</x-back-button>
            <h1 class="lesson-detail-title" style="margin-top:12px;">
                {{ $katilimci?->tam_adi ?? ($basvuran?->tam_adi ?? 'Başvuru Detayı') }}
            </h1>
            <p class="sms-history-subtitle" style="margin-top:4px;">
                {{ $etkinlik->ad ?? 'Etkinlik' }}
                @if ($etkinlik->etkinlik_no)
                    · Etkinlik No {{ $etkinlik->etkinlik_no }}
                @endif
            </p>
        </div>
        <div class="basvuru-detail-badges">
            @if ($basvuru->durum)
                <span class="status {{ $basvuru->durum->statusClass() }}">{{ $basvuru->durum->ad }}</span>
            @endif
            @if ($basvuru->katilim_durumu)
                <span class="status {{ $basvuru->katilim_durumu->statusClass() }}">{{ $basvuru->katilim_durumu->label() }}</span>
            @endif
        </div>
    </div>

    {{-- Etkinlik bilgileri --}}
    <section class="card form-section-card basvuru-detail-section">
        <div class="basvuru-detail-section-head">
            <h2 class="basvuru-detail-section-title">Etkinlik Bilgileri</h2>
            <a href="{{ route('etkinlikler.show', $etkinlik) }}" class="sms-history-alicilar-btn">Etkinlik detayına git</a>
        </div>
        <div class="lesson-info-grid">
            <div class="lesson-info-card">
                <div class="lesson-info-label">Etkinlik No</div>
                <div class="lesson-info-value">{{ $etkinlik->etkinlik_no }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Ad</div>
                <div class="lesson-info-value">{{ $etkinlik->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Tip</div>
                <div class="lesson-info-value">{{ $etkinlik->etkinlikTipi?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Merkez</div>
                <div class="lesson-info-value">{{ $etkinlik->merkez?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Kurum</div>
                <div class="lesson-info-value">
                    {{ $etkinlik->kurumlar->isNotEmpty() ? $etkinlik->kurumlar->pluck('ad')->implode(', ') : '—' }}
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Tarihler</div>
                <div class="lesson-info-value">
                    {{ $etkinlik->baslangic_tarihi?->format('d.m.Y') ?? '—' }}
                    -
                    {{ $etkinlik->bitis_tarihi?->format('d.m.Y') ?? '—' }}
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Sorumlular</div>
                <div class="lesson-info-value">{{ $etkinlik->sorumluAdlari() }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Durum</div>
                <div class="lesson-info-value">
                    @if ($etkinlik->durum)
                        <span class="status status-{{ $etkinlik->durum->value }}">{{ $etkinlik->durum->label() }}</span>
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Başvuru bilgileri --}}
    @php
        $katilimciAdi = $katilimci?->tam_adi ?? ($basvuran?->tam_adi ?? 'Bu başvuru');
        $durumKod = $basvuru->durum?->kod ?? '';
        $veliVar = $basvuru->veliBasvurusuMu();
        $katilimciYas = $katilimci?->dogum_tarihi?->age;
        $katilimciKucuk = $katilimciYas !== null && $katilimciYas < 18;
    @endphp
    <section class="card form-section-card basvuru-detail-section">
        <div class="basvuru-detail-section-head">
            <h2 class="basvuru-detail-section-title">Başvuru Bilgileri</h2>
        </div>
        <div class="lesson-info-grid">
            <div class="lesson-info-card">
                <div class="lesson-info-label">Başvuru Tarihi</div>
                <div class="lesson-info-value">{{ $basvuru->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">
                    <span>Durum</span>
                    @yetki('etkinlik_basvuru.guncelle')
                        <button
                            type="button"
                            class="lesson-info-edit-btn"
                            title="Durumu güncelle"
                            aria-label="Durumu güncelle"
                            data-etkinlik-durum-open
                            data-url="{{ route('etkinlikler.basvurular.durum', [$etkinlik, $basvuru]) }}"
                            data-ad="{{ $katilimciAdi }}"
                            data-durum="{{ $durumKod }}"
                            data-iptal-gerekce-id="{{ $basvuru->iptal_gerekce_id }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                    @endyetki
                </div>
                <div class="lesson-info-value">
                    @if ($basvuru->durum)
                        <span class="status {{ $basvuru->durum->statusClass() }}">{{ $basvuru->durum->ad }}</span>
                        @if ($basvuru->durum->kod === 'yedek' && $basvuru->yedek_sira)
                            <span class="takvim-kurs-no" style="display:inline; margin-left:6px;">Sıra {{ $basvuru->yedek_sira }}</span>
                        @endif
                    @else
                        —
                    @endif
                </div>
            </div>
            @if ($basvuru->durum?->kod === 'yedek')
            <div class="lesson-info-card">
                <div class="lesson-info-label">
                    <span>Yedek Sırası</span>
                    @yetki('etkinlik_basvuru.yedek_sira_guncelle')
                        <button
                            type="button"
                            class="lesson-info-edit-btn"
                            title="Yedek sırasını güncelle"
                            aria-label="Yedek sırasını güncelle"
                            data-yedek-sira-open
                            data-list-url="{{ route('etkinlikler.yedek-sirasi', $etkinlik) }}"
                            data-save-url="{{ route('etkinlikler.yedek-sirasi.update', $etkinlik) }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                    @endyetki
                </div>
                <div class="lesson-info-value">{{ $basvuru->yedek_sira ?? '—' }}</div>
            </div>
            @endif
            <div class="lesson-info-card">
                <div class="lesson-info-label">Katılım</div>
                <div class="lesson-info-value">
                    @if ($basvuru->katilim_durumu)
                        <span class="status {{ $basvuru->katilim_durumu->statusClass() }}">{{ $basvuru->katilim_durumu->label() }}</span>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Onay Tarihi</div>
                <div class="lesson-info-value">{{ $basvuru->onay_tarihi?->format('d.m.Y H:i') ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Onaylayan</div>
                <div class="lesson-info-value">{{ $basvuru->onaylayan?->tam_adi ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">İptal Tarihi</div>
                <div class="lesson-info-value">{{ $basvuru->iptal_tarihi?->format('d.m.Y H:i') ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">
                    <span>İptal Gerekçesi</span>
                    @yetki('etkinlik_basvuru.guncelle')
                        <button
                            type="button"
                            class="lesson-info-edit-btn"
                            title="İptal gerekçesini güncelle"
                            aria-label="İptal gerekçesini güncelle"
                            data-basvuru-iptal-gerekce-ac
                            data-update-url="{{ route('etkinlikler.basvurular.iptal-gerekce', [$etkinlik, $basvuru]) }}"
                            data-katilimci="{{ $katilimciAdi }}"
                            data-durum-kod="{{ $durumKod }}"
                            data-iptal-gerekce-id="{{ $basvuru->iptal_gerekce_id }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                    @endyetki
                </div>
                <div class="lesson-info-value">{{ $basvuru->iptalGerekce?->ad ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">İptal Eden</div>
                <div class="lesson-info-value">{{ $basvuru->iptalEden?->tam_adi ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">Kaydeden</div>
                <div class="lesson-info-value">{{ $basvuru->olusturan?->tam_adi ?? '—' }}</div>
            </div>
            <div class="lesson-info-card">
                <div class="lesson-info-label">
                    <span>Veli Başvurusu</span>
                    @yetki('etkinlik_basvuru.guncelle')
                        <button
                            type="button"
                            class="lesson-info-edit-btn"
                            title="Veli başvurusunu güncelle"
                            aria-label="Veli başvurusunu güncelle"
                            data-basvuru-veli-ac
                            data-update-url="{{ route('etkinlikler.basvurular.veli', [$etkinlik, $basvuru]) }}"
                            data-katilimci="{{ $katilimciAdi }}"
                            data-veli-basvurusu="{{ $veliVar ? '1' : '0' }}"
                            data-katilimci-kucuk="{{ $katilimciKucuk ? '1' : '0' }}"
                            data-veli-tc="{{ $veli?->tc_kimlik_no }}"
                            data-veli-dogum="{{ $veli?->dogum_tarihi?->format('Y-m-d') }}"
                            data-veli-ad="{{ $veli?->ad }}"
                            data-veli-soyad="{{ $veli?->soyad }}"
                            data-veli-telefon="{{ $veli?->telefon }}"
                            data-veli-email="{{ $veli?->email }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        </button>
                    @endyetki
                </div>
                <div class="lesson-info-value">{{ $veliVar ? 'Evet' : 'Hayır' }}</div>
            </div>
        </div>
    </section>

    {{-- Kişi bilgileri --}}
    @php
        $kisiDetay = $katilimci ?? $basvuran;

        $kisiBloklari = collect([
            'Katılımcı' => $katilimci,
        ]);

        if ($basvuran && (! $katilimci || (int) $basvuran->id !== (int) $katilimci->id)) {
            $kisiBloklari['Başvuran'] = $basvuran;
        }

        if ($veli) {
            $kisiBloklari['Veli'] = $veli;
        }
    @endphp

    <section class="card form-section-card basvuru-detail-section">
        <div class="basvuru-detail-section-head">
            <h2 class="basvuru-detail-section-title">Kişi Bilgileri</h2>
            @if ($kisiDetay)
                @yetki('kisi.goruntule')
                    <a href="{{ route('kisiler.show', $kisiDetay) }}" class="sms-history-alicilar-btn">Kişi detayına git</a>
                @endyetki
            @endif
        </div>

        @foreach ($kisiBloklari as $rol => $kisi)
            <div class="basvuru-kisi-block">
                <h3 class="basvuru-kisi-title">{{ $rol }}</h3>
                @if ($kisi)
                    <div class="lesson-info-grid">
                        <div class="lesson-info-card">
                            <div class="lesson-info-label">Ad Soyad</div>
                            <div class="lesson-info-value">{{ $kisi->tam_adi }}</div>
                        </div>
                        <div class="lesson-info-card">
                            <div class="lesson-info-label">T.C. Kimlik No</div>
                            <div class="lesson-info-value mono-cell">{{ $kisi->tc_kimlik_no ?: '—' }}</div>
                        </div>
                        <div class="lesson-info-card">
                            <div class="lesson-info-label">Doğum Tarihi</div>
                            <div class="lesson-info-value">{{ $kisi->dogum_tarihi?->format('d.m.Y') ?? '—' }}</div>
                        </div>
                        <div class="lesson-info-card">
                            <div class="lesson-info-label">Cinsiyet</div>
                            <div class="lesson-info-value">{{ $kisi->cinsiyet?->label() ?? '—' }}</div>
                        </div>
                        <div class="lesson-info-card">
                            <div class="lesson-info-label">Telefon</div>
                            <div class="lesson-info-value">{{ $kisi->telefon ?: '—' }}</div>
                        </div>
                        <div class="lesson-info-card">
                            <div class="lesson-info-label">E-posta</div>
                            <div class="lesson-info-value">{{ $kisi->email ?: '—' }}</div>
                        </div>
                        <div class="lesson-info-card">
                            <div class="lesson-info-label">İl / İlçe</div>
                            <div class="lesson-info-value">
                                {{ collect([$kisi->il, $kisi->ilce])->filter()->implode(' / ') ?: '—' }}
                            </div>
                        </div>
                        <div class="lesson-info-card">
                            <div class="lesson-info-label">Adres</div>
                            <div class="lesson-info-value">{{ $kisi->adres ?: '—' }}</div>
                        </div>
                    </div>
                @else
                    <p class="basvuru-kisi-empty">Bu rol için kayıt yok.</p>
                @endif
            </div>
        @endforeach
    </section>

    {{-- Evraklar --}}
    @yetki('etkinlik_basvuru.evrak_goruntule')
    <section
        class="card form-section-card basvuru-detail-section"
        id="basvuru-evraklar-panel"
        @yetki('etkinlik_basvuru.evrak_yukle')
        data-upload-url="{{ route('etkinlikler.basvurular.evrak.store', [$etkinlik, $basvuru]) }}"
        @endyetki
        data-katilimci="{{ $katilimci?->tam_adi ?? ($basvuran?->tam_adi ?? 'Bu başvuru') }}"
        data-can-upload="{{ auth()->user()?->hasYetki('etkinlik_basvuru.evrak_yukle') ? '1' : '0' }}"
        data-evraklar='@json($evraklarPayload ?? [], JSON_UNESCAPED_UNICODE)'
        data-evrak-tipleri='@json($evrakTipOptions ?? [], JSON_UNESCAPED_UNICODE)'
    >
        <div class="basvuru-detail-section-head">
            <h2 class="basvuru-detail-section-title">Evraklar</h2>
            @yetki('etkinlik_basvuru.evrak_yukle')
                <button
                    type="button"
                    class="basvuru-evraklar-upload-toggle"
                    data-basvuru-evrak-upload-toggle
                    title="Yeni evrak yükle"
                    aria-label="Yeni evrak yükle"
                    aria-expanded="false"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 5v14"/>
                        <path d="M5 12h14"/>
                    </svg>
                </button>
            @endyetki
        </div>

        <div class="basvuru-evrak-upload-panel" data-basvuru-evrak-upload-panel hidden>
            <div class="basvuru-evrak-upload-panel-head">
                <strong>Yeni Evrak Yükle</strong>
                <span>Evrak tipini seçip PDF veya JPG/PNG dosyası ekleyin (max 5 MB).</span>
            </div>
            <div class="basvuru-evrak-upload-grid">
                <div class="form-group" style="margin:0;">
                    <label for="etkinlik-detail-evrak-tip-select">Evrak tipi <span class="req">*</span></label>
                    <select id="etkinlik-detail-evrak-tip-select" class="form-control" data-basvuru-evrak-tip-select>
                        <option value="">Seçiniz</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="etkinlik-detail-evrak-dosya">Dosya <span class="req">*</span></label>
                    <input
                        type="file"
                        id="etkinlik-detail-evrak-dosya"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        data-basvuru-evrak-dosya
                    >
                </div>
            </div>
            <div class="basvuru-evrak-upload-actions">
                <button type="button" class="btn btn-secondary" data-basvuru-evrak-upload-cancel>Vazgeç</button>
                <button type="button" class="btn btn-primary" data-basvuru-evrak-upload-submit>Yükle</button>
            </div>
        </div>

        <div class="basvuru-evraklar-summary" data-basvuru-evraklar-summary></div>
        <div class="basvuru-evraklar-list" data-basvuru-evraklar-list></div>
    </section>
    @endyetki

    {{-- Yoklama / katılım --}}
    <section class="card form-section-card basvuru-detail-section">
        <div class="basvuru-detail-section-head">
            <h2 class="basvuru-detail-section-title">Yoklama Bilgileri</h2>
            @if ($basvuru->katilim_durumu)
                <div class="basvuru-detail-badges">
                    <span class="status {{ $basvuru->katilim_durumu->statusClass() }}">{{ $basvuru->katilim_durumu->label() }}</span>
                </div>
            @endif
        </div>

        @if (! $basvuru->katilim_durumu)
            <div class="empty-state" style="padding:24px 12px;">
                <div class="empty-state-title">Yoklama kaydı yok</div>
                <p class="empty-state-text">Bu başvuruya ait yoklama henüz girilmemiş.</p>
            </div>
        @else
            <div class="lesson-info-grid">
                <div class="lesson-info-card">
                    <div class="lesson-info-label">Katılım Durumu</div>
                    <div class="lesson-info-value">
                        <span class="status {{ $basvuru->katilim_durumu->statusClass() }}">{{ $basvuru->katilim_durumu->label() }}</span>
                    </div>
                </div>
            </div>
        @endif
    </section>

    {{-- Mesajlar --}}
    <section class="card form-section-card basvuru-detail-section">
        <div class="basvuru-detail-section-head">
            <h2 class="basvuru-detail-section-title">Gönderilen Mesajlar</h2>
        </div>

        <h3 class="basvuru-kisi-title">SMS</h3>
        @if ($smsLoglari->isEmpty())
            <p class="basvuru-kisi-empty">Bu başvuruya gönderilmiş SMS yok.</p>
        @else
            <div class="table-wrapper" style="margin-bottom:20px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Telefon</th>
                            <th>Mesaj</th>
                            <th>Durum</th>
                            <th>Gönderen</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($smsLoglari as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="mono-cell">{{ $log->telefon }}</td>
                                <td class="sms-history-mesaj">{{ \Illuminate\Support\Str::limit($log->mesaj, 80) }}</td>
                                <td>
                                    @if ($log->durum === 'gonderildi')
                                        <span class="status status-aktif">Gönderildi</span>
                                    @else
                                        <span class="status status-iptal">Başarısız</span>
                                    @endif
                                </td>
                                <td>{{ $log->gonderen?->tam_adi ?? '—' }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="sms-onizle-btn"
                                        data-mesaj-log-onizle="sms"
                                        data-alici="{{ $katilimci?->tam_adi ?? ($basvuran?->tam_adi ?? 'Alıcı') }}"
                                        data-telefon="{{ $log->telefon }}"
                                        data-mesaj='@json($log->mesaj)'
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        Önizle
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h3 class="basvuru-kisi-title">E-Posta</h3>
        @if ($epostaLoglari->isEmpty())
            <p class="basvuru-kisi-empty">Bu başvuruya gönderilmiş e-posta yok.</p>
        @else
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>E-posta</th>
                            <th>Konu</th>
                            <th>Durum</th>
                            <th>Gönderen</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($epostaLoglari as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                                <td>{{ $log->email }}</td>
                                <td class="sms-history-mesaj">{{ \Illuminate\Support\Str::limit($log->konu, 80) }}</td>
                                <td>
                                    @if ($log->durum === 'gonderildi')
                                        <span class="status status-aktif">Gönderildi</span>
                                    @else
                                        <span class="status status-iptal">Başarısız</span>
                                    @endif
                                </td>
                                <td>{{ $log->gonderen?->tam_adi ?? '—' }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="sms-onizle-btn"
                                        data-mesaj-log-onizle="eposta"
                                        data-alici="{{ $katilimci?->tam_adi ?? ($basvuran?->tam_adi ?? 'Alıcı') }}"
                                        data-email="{{ $log->email }}"
                                        data-konu='@json($log->konu)'
                                        data-mesaj='@json($log->mesaj)'
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        Önizle
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
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

@include('etkinlikler._basvuru_durum_modal')
@include('etkinlikler._yedek_sira_modal')
@include('etkinlikler._basvuru_detail_modals')
@endsection
