@php($mesajKanal = $mesajKanal ?? 'sms')
<div class="mesaj-kanal-tabs" role="tablist" aria-label="Mesaj kanalı">
    <button
        type="button"
        class="mesaj-kanal-tab {{ $mesajKanal === 'sms' ? 'is-active' : '' }}"
        data-mesaj-kanal="sms"
        role="tab"
        aria-selected="{{ $mesajKanal === 'sms' ? 'true' : 'false' }}"
    >SMS</button>
    <button
        type="button"
        class="mesaj-kanal-tab {{ $mesajKanal === 'eposta' ? 'is-active' : '' }}"
        data-mesaj-kanal="eposta"
        role="tab"
        aria-selected="{{ $mesajKanal === 'eposta' ? 'true' : 'false' }}"
    >E-Posta</button>
</div>

<div class="card form-section-card sms-history-card" data-mesaj-kanal-panel="sms" @if ($mesajKanal !== 'sms') hidden @endif>
    <div class="sms-history-head">
        <div>
            <h3 class="sms-history-title">SMS Geçmişi</h3>
            <p class="sms-history-subtitle">Bu etkinliğe gönderilen SMS kayıtları</p>
        </div>
        @yetki('etkinlik.sms')
        <button
            type="button"
            class="btn sms-history-send-btn"
            data-sms-modal-open
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            SMS Gönder
        </button>
        @endyetki
    </div>
    @if (($smsGonderimleri ?? collect())->isEmpty())
        <div class="empty-state" style="padding:32px 16px;">
            <div class="empty-state-title">Henüz SMS yok</div>
            <p class="empty-state-text">Başvuranlara toplu veya seçili SMS göndermek için butona tıklayın.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Kapsam</th>
                        <th>Mesaj</th>
                        <th>Gönderen</th>
                        <th>Sonuç</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($smsGonderimleri as $sms)
                        <tr>
                            <td>{{ $sms->created_at?->format('d.m.Y H:i') }}</td>
                            <td>
                                @if (($sms->kapsam ?? '') === 'basvuru_onay')
                                    Başvuru onayı
                                @elseif (($sms->kapsam ?? '') === 'basvuru_iptal')
                                    Başvuru iptali
                                @elseif (($sms->kapsam ?? '') === 'basvuru_yedek')
                                    Başvuru yedeği
                                @elseif ($sms->basvuru_durum_kod)
                                    Filtre: {{ $sms->basvuru_durum_kod === 'tumu' ? 'Tümü' : ($basvuruDurumlari->firstWhere('kod', $sms->basvuru_durum_kod)?->ad ?? $sms->basvuru_durum_kod) }}
                                @else
                                    Seçilen kişiler
                                @endif
                                <button
                                    type="button"
                                    class="sms-history-alicilar-btn"
                                    data-sms-alicilar-detay-ac
                                    data-sms-alicilar-detay='@json($sms->detay ?? [])'
                                >{{ $sms->toplam }} alıcı</button>
                            </td>
                            <td class="sms-history-mesaj">{{ \Illuminate\Support\Str::limit($sms->mesaj, 80) }}</td>
                            <td>{{ $sms->gonderen?->tam_adi ?? '—' }}</td>
                            <td>
                                <span class="status status-aktif">{{ $sms->gonderilen }} gönderildi</span>
                                @if ($sms->atlanan > 0)
                                    <span class="status status-hazirlik" style="margin-left:4px;">{{ $sms->atlanan }} atlandı</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card form-section-card sms-history-card" data-mesaj-kanal-panel="eposta" @if ($mesajKanal !== 'eposta') hidden @endif>
    <div class="sms-history-head">
        <div>
            <h3 class="sms-history-title">E-Posta Geçmişi</h3>
            <p class="sms-history-subtitle">Bu etkinliğe gönderilen e-posta kayıtları</p>
        </div>
        @yetki('etkinlik.eposta')
        <button
            type="button"
            class="btn sms-history-send-btn"
            data-eposta-modal-open
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect width="20" height="16" x="2" y="4" rx="2"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
            E-Posta Gönder
        </button>
        @endyetki
    </div>
    @if (($epostaGonderimleri ?? collect())->isEmpty())
        <div class="empty-state" style="padding:32px 16px;">
            <div class="empty-state-title">Henüz e-posta yok</div>
            <p class="empty-state-text">Başvuranlara toplu veya seçili e-posta göndermek için butona tıklayın.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Kapsam</th>
                        <th>Konu</th>
                        <th>Gönderen</th>
                        <th>Sonuç</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($epostaGonderimleri as $eposta)
                        <tr>
                            <td>{{ $eposta->created_at?->format('d.m.Y H:i') }}</td>
                            <td>
                                @if ($eposta->basvuru_durum_kod)
                                    Filtre: {{ $eposta->basvuru_durum_kod === 'tumu' ? 'Tümü' : ($basvuruDurumlari->firstWhere('kod', $eposta->basvuru_durum_kod)?->ad ?? $eposta->basvuru_durum_kod) }}
                                @else
                                    Seçilen kişiler
                                @endif
                                <button
                                    type="button"
                                    class="sms-history-alicilar-btn"
                                    data-eposta-alicilar-detay-ac
                                    data-eposta-alicilar-detay='@json($eposta->detay ?? [])'
                                >{{ $eposta->toplam }} alıcı</button>
                            </td>
                            <td class="sms-history-mesaj">{{ \Illuminate\Support\Str::limit($eposta->konu, 80) }}</td>
                            <td>{{ $eposta->gonderen?->tam_adi ?? '—' }}</td>
                            <td>
                                <span class="status status-aktif">{{ $eposta->gonderilen }} gönderildi</span>
                                @if ($eposta->atlanan > 0)
                                    <span class="status status-hazirlik" style="margin-left:4px;">{{ $eposta->atlanan }} atlandı</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
