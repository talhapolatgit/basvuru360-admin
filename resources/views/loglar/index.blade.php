@extends('layouts.admin')

@section('title', 'İşlem Kayıtları')

@php
    $islemOptions = collect($islemler)
        ->map(fn ($etiket, $kod) => ['value' => $kod, 'label' => $etiket['ad']])
        ->values()
        ->all();

    $kullaniciOptions = $kullanicilar
        ->map(fn ($k) => ['value' => (string) $k->id, 'label' => $k->tam_adi])
        ->all();
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Sistem</p>
        <h1 class="page-title">İşlem Kayıtları</h1>
        <p class="page-subtitle">Sistemde yapılan tüm işlemlerin kim, ne zaman ve hangi cihazdan yaptığına dair kayıtlar.</p>
    </div>
</div>

{{-- Filtre Kartı --}}
<div class="card filter-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Arama ve Filtreler</h2>
            <p class="card-section-desc">İşleme, kullanıcıya ve tarih aralığına göre daraltın.</p>
        </div>
    </div>

    <form id="loglar-filter-form" method="GET" action="{{ route('loglar.index') }}">
        <div class="filter-grid">
            <div class="form-group">
                <label for="q">Arama</label>
                <input
                    type="text"
                    id="q"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Konu veya açıklama..."
                    class="form-control"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="kurs_no">Kurs No</label>
                <input
                    type="text"
                    id="kurs_no"
                    name="kurs_no"
                    value="{{ $filters['kurs_no'] ?? '' }}"
                    placeholder="Kurs numarası..."
                    class="form-control"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label>İşlem Türü</label>
                <x-searchable-select
                    name="islem"
                    :options="$islemOptions"
                    :value="$filters['islem'] ?? ''"
                    placeholder="Tümü"
                    data-reset-value=""
                />
            </div>

            <div class="form-group">
                <label>Kullanıcı</label>
                <x-searchable-select
                    name="user_id"
                    :options="$kullaniciOptions"
                    :value="$filters['user_id'] ?? ''"
                    placeholder="Tümü"
                    data-reset-value=""
                />
            </div>

            <div class="form-group">
                <label for="baslangic">Başlangıç Tarihi</label>
                <input type="date" id="baslangic" name="baslangic" value="{{ $filters['baslangic'] ?? '' }}" class="form-control">
            </div>

            <div class="form-group">
                <label for="bitis">Bitiş Tarihi</label>
                <input type="date" id="bitis" name="bitis" value="{{ $filters['bitis'] ?? '' }}" class="form-control">
            </div>
        </div>

        <div class="filter-actions">
            <div class="filter-actions-right">
                <x-back-button id="loglar-filter-clear" type="button" icon="close" href="{{ route('loglar.index') }}">Temizle</x-back-button>
                <x-cta-button type="submit" icon="filter">Filtrele</x-cta-button>
            </div>
        </div>
    </form>
</div>

{{-- Tablo Kartı --}}
<div class="card table-card">
    <div class="table-toolbar">
        <div>
            <div class="table-title">Kayıtlar</div>
            <p class="table-subtitle">Ayrıntıları görmek için satırdaki oku kullanın.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <x-excel-export :href="route('loglar.export', request()->query())" />
        </div>
    </div>

    <div class="table-wrapper">
        <table class="data-table" id="loglar-table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>Tarih</th>
                    <th>İşlem</th>
                    <th>Konu</th>
                    <th>Kullanıcı</th>
                    <th>Açıklama</th>
                    <th>IP / Cihaz</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loglar as $log)
                    <tr>
                        <td>
                            <button type="button" class="log-toggle-btn" data-log-toggle="{{ $log->id }}" aria-expanded="false" title="Ayrıntılar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </td>
                        <td style="white-space:nowrap;">{{ $log->created_at?->format('d.m.Y H:i:s') }}</td>
                        <td><span class="log-badge log-badge-{{ $log->islem_rengi }}">{{ $log->islem_adi }}</span></td>
                        <td>
                            @if ($log->kurs)
                                <a href="{{ route('kurslar.show', $log->kurs) }}">Kurs #{{ $log->kurs->kurs_no }}</a>
                            @elseif ($log->konu_adi)
                                <span style="color:#b5b5c3; font-size:11px;">{{ $log->konu_turu }}</span>
                                <span>{{ $log->konu_adi }}</span>
                            @elseif ($log->konu_turu)
                                <span style="color:#b5b5c3;">{{ $log->konu_turu }}</span>
                            @else
                                <span style="color:#b5b5c3;">—</span>
                            @endif
                        </td>
                        <td>{{ $log->user?->tam_adi ?? 'Sistem' }}</td>
                        <td>{{ $log->aciklama }}</td>
                        <td>
                            <div class="log-meta">
                                <span class="log-meta-item">{{ $log->ip_adresi ?? '—' }}</span>
                                <span class="log-meta-item">{{ $log->cihaz_tipi ?? '—' }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr class="log-detail-row" data-log-detail="{{ $log->id }}" hidden>
                        <td colspan="7">
                            <div class="log-detail-grid">
                                <div>
                                    <div class="log-detail-block-title">İstek Bilgileri</div>
                                    <div class="log-meta" style="flex-direction:column; gap:4px;">
                                        <span class="log-meta-item"><strong>Tarayıcı:</strong> {{ $log->tarayici ?? '—' }}</span>
                                        <span class="log-meta-item"><strong>Platform:</strong> {{ $log->platform ?? '—' }}</span>
                                        <span class="log-meta-item"><strong>Cihaz:</strong> {{ $log->cihaz_tipi ?? '—' }}</span>
                                        <span class="log-meta-item"><strong>IP:</strong> {{ $log->ip_adresi ?? '—' }}</span>
                                        <span class="log-meta-item"><strong>Yöntem:</strong> {{ $log->http_metodu ?? '—' }}</span>
                                        <span class="log-meta-item"><strong>URL:</strong> {{ $log->url ?? '—' }}</span>
                                    </div>
                                </div>
                                @if ($log->eski_veriler)
                                    <div>
                                        <div class="log-detail-block-title">Önceki Değerler</div>
                                        <pre class="log-detail-pre">{{ json_encode($log->eski_veriler, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif
                                @if ($log->yeni_veriler)
                                    <div>
                                        <div class="log-detail-block-title">Yeni Değerler</div>
                                        <pre class="log-detail-pre">{{ json_encode($log->yeni_veriler, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif
                            </div>
                            <div class="log-meta" style="margin-top:10px;">
                                <span class="log-meta-item" style="font-size:11px; color:#b5b5c3; word-break:break-all;"><strong>User-Agent:</strong> {{ $log->user_agent ?? '—' }}</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                                </div>
                                <div class="empty-state-title">İşlem kaydı bulunamadı</div>
                                <p class="empty-state-text">Filtreleri temizleyerek yeniden deneyin.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div style="display:flex; align-items:center; gap:12px; font-size:13px; color:#7e8299;">
            <span>
                @if ($loglar->total())
                    {{ $loglar->firstItem() }}–{{ $loglar->lastItem() }} / {{ $loglar->total() }} kayıt
                @else
                    0 kayıt
                @endif
            </span>
            <select name="per_page" class="form-control" style="width:auto; height:32px;" data-per-page-select>
                @foreach ([20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected(($filters['per_page'] ?? 20) == $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>

        <div class="table-footer-right">
            @if ($loglar->hasPages())
                <div data-pagination>
                    {{ $loglar->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-log-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-log-toggle');
            var row = document.querySelector('[data-log-detail="' + id + '"]');
            if (!row) return;
            var acik = row.hasAttribute('hidden');
            if (acik) {
                row.removeAttribute('hidden');
                btn.setAttribute('aria-expanded', 'true');
                btn.style.transform = 'rotate(90deg)';
            } else {
                row.setAttribute('hidden', '');
                btn.setAttribute('aria-expanded', 'false');
                btn.style.transform = '';
            }
        });
    });

    document.querySelector('[data-per-page-select]')?.addEventListener('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    });
</script>
@endpush
