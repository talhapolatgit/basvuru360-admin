@extends('layouts.admin')

@section('title', 'Merkez Yetkilendirme')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Yetkilendirme</p>
        <h1 class="page-title">Merkez Yetkilendirme</h1>
        <p class="page-subtitle">{{ $kullanici->tam_adi }} — görüntüleyebileceği merkezleri seçin.</p>
    </div>
    <x-back-button :href="($returnToListe ?? false) ? route('merkez-yetkileri.index') : route('kullanicilar.show', $kullanici)" />
</div>

@if ($errors->any())
    <div class="alert alert-error mb-4">
        <strong>Formda hatalar var.</strong>
        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('kullanicilar.merkez-yetkileri.update', $kullanici) }}">
    @csrf
    @method('PUT')
    @if ($returnToListe ?? false)
        <input type="hidden" name="return" value="liste">
    @endif

    <div class="card form-section-card">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Merkezler</h2>
                <p class="card-section-desc">
                    Kullanıcının rolünde <strong>Yalnızca yetkilendirildiği merkezleri görüntüle</strong> yetkisi varsa
                    yalnızca burada seçilen merkezler listelenir. <strong>Tüm merkezleri görüntüle</strong> yetkisi varsa bu seçim kısıtlamaz.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-secondary" data-merkez-select-all>Tümünü Seç</button>
                <button type="button" class="btn btn-secondary" data-merkez-clear-all>Temizle</button>
            </div>
        </div>

        @if ($merkezler->isEmpty())
            <div class="empty-state" style="padding:24px;">
                <div class="empty-state-title">Merkez bulunamadı</div>
                <p class="empty-state-text">Önce merkez kaydı oluşturun.</p>
            </div>
        @else
            <div class="yetki-matrix" data-merkez-yetki-matrix>
                <div class="yetki-modul">
                    <div class="yetki-modul-body" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px 16px;">
                        @foreach ($merkezler as $merkez)
                            <label class="checkbox-label yetki-item">
                                <input
                                    type="checkbox"
                                    name="merkezler[]"
                                    value="{{ $merkez->id }}"
                                    data-merkez-checkbox
                                    @checked(collect($seciliMerkezIds)->map(fn ($id) => (int) $id)->contains((int) $merkez->id))
                                >
                                <span>{{ $merkez->ad }}</span>
                                @unless ($merkez->aktif)
                                    <span class="status status-hazirlik" style="margin-left:6px; font-size:11px;">Pasif</span>
                                @endunless
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        @error('merkezler') <div class="form-error">{{ $message }}</div> @enderror
        @error('merkezler.*') <div class="form-error">{{ $message }}</div> @enderror
    </div>

    <div class="form-footer">
        <x-back-button
            :href="($returnToListe ?? false) ? route('merkez-yetkileri.index') : route('kullanicilar.show', $kullanici)"
            icon="close"
        >İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Yetkilendirmeyi Kaydet</x-cta-button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-merkez-yetki-matrix]');
    if (!root) return;

    const boxes = () => Array.from(root.querySelectorAll('[data-merkez-checkbox]'));

    document.querySelector('[data-merkez-select-all]')?.addEventListener('click', () => {
        boxes().forEach((el) => { el.checked = true; });
    });

    document.querySelector('[data-merkez-clear-all]')?.addEventListener('click', () => {
        boxes().forEach((el) => { el.checked = false; });
    });
});
</script>
@endpush
