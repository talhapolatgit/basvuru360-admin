{{-- Beklenen: $aktifDonem, $donemler --}}
<form method="POST" action="{{ route('kres.donem-sec') }}" class="kres-donem-switcher" title="Aktif eğitim öğretim dönemini değiştir">
    @csrf
    <input type="hidden" name="redirect" value="{{ request()->getRequestUri() }}">
    <label for="kres-donem-select" class="kres-donem-switcher__label">Dönem</label>
    <select
        id="kres-donem-select"
        name="donem_id"
        class="form-control kres-donem-switcher__select"
        onchange="this.form.submit()"
        @disabled(($donemler ?? collect())->isEmpty())
    >
        @forelse (($donemler ?? collect()) as $donem)
            <option value="{{ $donem->id }}" @selected(($aktifDonem ?? null) && $aktifDonem->id === $donem->id)>
                {{ $donem->ad }}{{ $donem->aktif ? '' : ' (pasif)' }}
            </option>
        @empty
            <option value="">Dönem yok</option>
        @endforelse
    </select>
</form>
