@php
    $saatAdedi = max(1, (int) ($saatAdedi ?? $seciliDers->saatAdedi()));
    $yoklamaDuzenleyebilir = $yoklamaDuzenleyebilir
        ?? (auth()->user()?->hasYetki('kurs.yoklama') ?? false);
@endphp

<div class="yoklama-toolbar">
    <div class="yoklama-toolbar-left">
        <x-back-button type="button" class="btn-back-sm" data-yoklama-back>Ders listesi</x-back-button>
        <div class="yoklama-toolbar-title">
            <strong>{{ $seciliDers->ozet() }}</strong>
            @if ($seciliDers->sinif)
                <span class="yoklama-toolbar-meta">Sınıf: {{ $seciliDers->sinif }}</span>
            @endif
            <span class="yoklama-toolbar-meta">{{ $saatAdedi }} ders saati</span>
            @unless ($yoklamaDuzenleyebilir)
                <span class="yoklama-toolbar-meta">Salt görüntüleme</span>
            @endunless
        </div>
    </div>
    <div class="yoklama-toolbar-meta">Kesin kayıt: {{ $yoklamaKayitlari->count() }}</div>
</div>

<div class="card table-card lesson-table-card">
    @if ($yoklamaKayitlari->isEmpty())
        <div class="empty-state">
            <div class="empty-state-title">Öğrenci yok</div>
            <p class="empty-state-text">Bu derse yoklama alınacak kesin kayıtlı başvuru bulunmuyor.</p>
        </div>
    @else
        <form
            method="POST"
            action="{{ route('kurslar.yoklama.save', [$kurs, $seciliDers]) }}"
            class="yoklama-form"
            data-yoklama-form
            @unless ($yoklamaDuzenleyebilir) data-yoklama-readonly="1" @endunless
        >
            @csrf
            @method('PUT')
            <div class="table-wrapper">
                <table class="data-table yoklama-table yoklama-table-hours">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Öğrenci</th>
                            <th>Kimlik No</th>
                            @for ($saat = 1; $saat <= $saatAdedi; $saat++)
                                <th class="yoklama-saat-col">Saat {{ $saat }}</th>
                            @endfor
                            <th>Açıklama</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($yoklamaKayitlari as $index => $yoklama)
                            @php
                                $saatlik = $yoklama->normalizeSaatlikDurumlar($saatAdedi);
                            @endphp
                            <tr>
                                <td class="mono-cell">{{ $index + 1 }}</td>
                                <td>
                                    <input type="hidden" name="yoklamalar[{{ $index }}][basvuru_id]" value="{{ $yoklama->kurs_basvuru_id }}">
                                    {{ $yoklama->kisi?->tam_adi ?? '—' }}
                                </td>
                                <td class="mono-cell">{{ $yoklama->kisi?->tc_kimlik_no ?? '—' }}</td>
                                @for ($saatIndex = 0; $saatIndex < $saatAdedi; $saatIndex++)
                                    <td class="yoklama-saat-col">
                                        <div class="yoklama-durum-group" role="group" aria-label="Saat {{ $saatIndex + 1 }} yoklama durumu">
                                            @foreach ($yoklamaDurumlari as $durum)
                                                <label class="yoklama-durum-option yoklama-durum-{{ $durum->value }} {{ ($saatlik[$saatIndex] ?? '') === $durum->value ? 'is-active' : '' }}">
                                                    <input
                                                        type="radio"
                                                        name="yoklamalar[{{ $index }}][saatlik_durumlar][{{ $saatIndex }}]"
                                                        value="{{ $durum->value }}"
                                                        @checked(($saatlik[$saatIndex] ?? \App\Enums\YoklamaDurum::Yok->value) === $durum->value)
                                                        @disabled(! $yoklamaDuzenleyebilir)
                                                    >
                                                    <span>{{ $durum->label() }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </td>
                                @endfor
                                <td>
                                    <input
                                        type="text"
                                        name="yoklamalar[{{ $index }}][aciklama]"
                                        class="form-control yoklama-aciklama"
                                        value="{{ $yoklama->aciklama }}"
                                        placeholder="Opsiyonel"
                                        maxlength="500"
                                        @disabled(! $yoklamaDuzenleyebilir)
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-footer lesson-table-footer">
                <div class="table-footer-right">
                    <a
                        href="{{ route('kurslar.yoklama.formu', [$kurs, $seciliDers]) }}"
                        class="btn-pdf btn-pdf-sm"
                        target="_blank"
                        rel="noopener"
                    >
                        <span class="btn-pdf-mark" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <path d="M14 2v6h6"/>
                                <path d="M10 13h4"/>
                                <path d="M10 17h4"/>
                                <path d="M10 9h1"/>
                            </svg>
                        </span>
                        <span class="btn-pdf-text">Yoklama Formu</span>
                    </a>
                    <x-excel-export
                        :href="route('kurslar.yoklamalar.export', [$kurs, 'ders' => $seciliDers->id])"
                        class="btn-excel-sm"
                    />
                </div>
            </div>
            @yetki('kurs.yoklama')
            <div class="yoklama-form-footer">
                <x-cta-button type="submit" icon="save">Yoklamayı Kaydet</x-cta-button>
            </div>
            @endyetki
        </form>
    @endif
</div>
