@php
    $rol = $rol ?? null;
    $seciliYetkiler = $seciliYetkiler ?? old('yetkiler', $seciliYetkiler ?? []);
    $tumYetkiler = (bool) ($rol?->tum_yetkiler);
@endphp

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Rol Bilgileri</h2>
            <p class="card-section-desc">Rol adı, kodu ve durumu.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="ad">Ad <span class="required">*</span></label>
            <input type="text" id="ad" name="ad" value="{{ old('ad', $rol?->ad) }}" class="form-control @error('ad') is-invalid @enderror" required maxlength="100">
            @error('ad') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="kod">Kod <span class="required">*</span></label>
            <input
                type="text"
                id="kod"
                name="kod"
                value="{{ old('kod', $rol?->kod) }}"
                class="form-control @error('kod') is-invalid @enderror"
                required
                maxlength="50"
                @if ($rol?->sistem) readonly @endif
                pattern="[a-z0-9_]+"
                placeholder="ornek_rol"
            >
            @if ($rol?->sistem)
                <p class="form-hint">Sistem rollerinin kodu değiştirilemez.</p>
            @else
                <p class="form-hint">Küçük harf, rakam ve alt çizgi.</p>
            @endif
            @error('kod') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
            <label for="aciklama">Açıklama</label>
            <textarea id="aciklama" name="aciklama" class="form-control @error('aciklama') is-invalid @enderror" rows="2" maxlength="500">{{ old('aciklama', $rol?->aciklama) }}</textarea>
            @error('aciklama') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label for="sira">Sıra</label>
            <input type="number" id="sira" name="sira" value="{{ old('sira', $rol?->sira ?? 99) }}" class="form-control" min="0" max="9999">
        </div>

        <div class="form-group">
            <label class="checkbox-label" style="margin-top:28px;">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $rol?->aktif ?? true))>
                Aktif
            </label>
        </div>
    </div>
</div>

<div class="card form-section-card">
    <div class="card-section-header">
        <div>
            <h2 class="card-section-title">Yetkiler</h2>
            <p class="card-section-desc">
                @if ($tumYetkiler)
                    Bu rol tüm yetkilere sahiptir; tek tek seçim yapılamaz.
                @else
                    Modül bazında yetkileri seçin. Kullanıcıya birden fazla rol atanırsa yetkiler birleşir.
                @endif
            </p>
        </div>
        @unless ($tumYetkiler)
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-yetki-hepsi>Tümünü Seç</button>
                <button type="button" class="btn btn-secondary btn-sm" data-yetki-hicbiri>Temizle</button>
            </div>
        @endunless
    </div>

    @if ($tumYetkiler)
        <div class="empty-state" style="padding:24px;">
            <div class="empty-state-title">Admin — sınırsız yetki</div>
            <p class="empty-state-text">Bu role sahip kullanıcılar tüm işlemlere erişebilir.</p>
        </div>
    @else
        <div class="yetki-matrix" data-yetki-matrix>
            @foreach ($yetkilerByModul as $modul => $yetkiler)
                <div class="yetki-modul">
                    <div class="yetki-modul-header">
                        <label class="checkbox-label">
                            <input type="checkbox" data-yetki-modul-toggle="{{ $modul }}">
                            <strong>{{ $modulAdlari[$modul] ?? $modul }}</strong>
                        </label>
                    </div>
                    <div class="yetki-modul-body">
                        @foreach ($yetkiler as $yetki)
                            <label class="checkbox-label yetki-item">
                                <input
                                    type="checkbox"
                                    name="yetkiler[]"
                                    value="{{ $yetki->id }}"
                                    data-yetki-modul="{{ $modul }}"
                                    @checked(in_array($yetki->id, $seciliYetkiler, true))
                                >
                                {{ $yetki->ad }}
                                <span class="mono-cell" style="font-size:11px; color:#a1a5b7; margin-left:4px;">{{ $yetki->kod }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @error('yetkiler') <div class="form-error">{{ $message }}</div> @enderror
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const matrix = document.querySelector('[data-yetki-matrix]');
    if (!matrix) return;

    matrix.querySelectorAll('[data-yetki-modul-toggle]').forEach((toggle) => {
        const modul = toggle.getAttribute('data-yetki-modul-toggle');
        const boxes = () => Array.from(matrix.querySelectorAll(`[data-yetki-modul="${modul}"]`));

        const sync = () => {
            const list = boxes();
            toggle.checked = list.length > 0 && list.every((b) => b.checked);
            toggle.indeterminate = list.some((b) => b.checked) && !toggle.checked;
        };
        boxes().forEach((b) => b.addEventListener('change', sync));
        toggle.addEventListener('change', () => {
            boxes().forEach((b) => { b.checked = toggle.checked; });
            sync();
        });
        sync();
    });

    document.querySelector('[data-yetki-hepsi]')?.addEventListener('click', () => {
        matrix.querySelectorAll('input[name="yetkiler[]"]').forEach((b) => { b.checked = true; });
        matrix.querySelectorAll('[data-yetki-modul-toggle]').forEach((t) => {
            t.checked = true;
            t.indeterminate = false;
        });
    });
    document.querySelector('[data-yetki-hicbiri]')?.addEventListener('click', () => {
        matrix.querySelectorAll('input[name="yetkiler[]"]').forEach((b) => { b.checked = false; });
        matrix.querySelectorAll('[data-yetki-modul-toggle]').forEach((t) => {
            t.checked = false;
            t.indeterminate = false;
        });
    });
});
</script>
@endpush
