@php
    use App\Enums\Cinsiyet;
    /** @var \App\Models\User|null $egitmen */
    $egitmen = $egitmen ?? null;

    $val = function (string $key, mixed $default = null) use ($egitmen) {
        if (old($key) !== null) {
            return old($key);
        }

        if ($egitmen === null) {
            return $default;
        }

        $value = data_get($egitmen, $key);

        return $value ?? $default;
    };

    $dateVal = function (string $key) use ($egitmen) {
        if (old($key) !== null) {
            return old($key);
        }

        $value = $egitmen?->{$key};

        return $value?->format('Y-m-d') ?? '';
    };

    $currentCinsiyet = old('cinsiyet') ?? $egitmen?->cinsiyet?->value ?? '';
@endphp

<div
    data-kisi-form
    data-kimlik-sorgula-url="{{ route('kisiler.kimlik-sorgula') }}"
    data-adres-sorgula-url="{{ route('kisiler.adres-sorgula') }}"
>
    <div class="card form-section-card">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">Kimlik Bilgileri</h2>
                <p class="card-section-desc">TC ve doğum tarihi ile kimlik sorgulayıp formu doldurabilirsiniz.</p>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="tc_kimlik_no">TC Kimlik No</label>
                <input
                    type="text"
                    id="tc_kimlik_no"
                    name="tc_kimlik_no"
                    value="{{ $val('tc_kimlik_no') }}"
                    class="form-control @error('tc_kimlik_no') is-invalid @enderror"
                    maxlength="11"
                    inputmode="numeric"
                    autocomplete="off"
                    data-kimlik-tc
                >
            </div>

            <div class="form-group">
                <label for="dogum_tarihi">Doğum Tarihi</label>
                <div class="input-with-action">
                    <input
                        type="date"
                        id="dogum_tarihi"
                        name="dogum_tarihi"
                        value="{{ $dateVal('dogum_tarihi') }}"
                        class="form-control @error('dogum_tarihi') is-invalid @enderror"
                        data-kimlik-dogum
                    >
                    <button
                        type="button"
                        class="btn-kimlik-sorgula"
                        data-kimlik-sorgula
                        title="Kimlik Sorgula"
                        aria-label="Kimlik Sorgula"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="ad">Ad <span class="req">*</span></label>
                <input type="text" id="ad" name="ad" value="{{ $val('ad') }}" class="form-control @error('ad') is-invalid @enderror" required maxlength="100" autocomplete="off" data-kimlik-alan="ad">
            </div>

            <div class="form-group">
                <label for="soyad">Soyad <span class="req">*</span></label>
                <input type="text" id="soyad" name="soyad" value="{{ $val('soyad') }}" class="form-control @error('soyad') is-invalid @enderror" required maxlength="100" autocomplete="off" data-kimlik-alan="soyad">
            </div>

            <div class="form-group">
                <label for="cinsiyet">Cinsiyet</label>
                <select id="cinsiyet" name="cinsiyet" class="form-control @error('cinsiyet') is-invalid @enderror" data-kimlik-alan="cinsiyet">
                    <option value="">Seçiniz</option>
                    @foreach (Cinsiyet::cases() as $cinsiyet)
                        <option value="{{ $cinsiyet->value }}" @selected($currentCinsiyet === $cinsiyet->value)>{{ $cinsiyet->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="dogum_yeri">Doğum Yeri</label>
                <input type="text" id="dogum_yeri" name="dogum_yeri" value="{{ $val('dogum_yeri') }}" class="form-control @error('dogum_yeri') is-invalid @enderror" maxlength="100" autocomplete="off" data-kimlik-alan="dogum_yeri">
            </div>

            <div class="form-group">
                <label for="medeni_durum">Medeni Durum</label>
                <input type="text" id="medeni_durum" name="medeni_durum" value="{{ $val('medeni_durum') }}" class="form-control @error('medeni_durum') is-invalid @enderror" maxlength="50" autocomplete="off" data-kimlik-alan="medeni_durum">
            </div>

            <div class="form-group">
                <label for="uyruk">Uyruk</label>
                <input type="text" id="uyruk" name="uyruk" value="{{ $val('uyruk') }}" class="form-control @error('uyruk') is-invalid @enderror" maxlength="100" autocomplete="off" data-kimlik-alan="uyruk">
            </div>

            <div class="form-group">
                <label for="anne_adi">Anne Adı</label>
                <input type="text" id="anne_adi" name="anne_adi" value="{{ $val('anne_adi') }}" class="form-control @error('anne_adi') is-invalid @enderror" maxlength="100" autocomplete="off" data-kimlik-alan="anne_adi">
            </div>

            <div class="form-group">
                <label for="baba_adi">Baba Adı</label>
                <input type="text" id="baba_adi" name="baba_adi" value="{{ $val('baba_adi') }}" class="form-control @error('baba_adi') is-invalid @enderror" maxlength="100" autocomplete="off" data-kimlik-alan="baba_adi">
            </div>
        </div>
    </div>

    <div class="card form-section-card">
        <div class="card-section-header">
            <div>
                <h2 class="card-section-title">İletişim ve Hesap Bilgileri</h2>
                <p class="card-section-desc">Giriş bilgileri, telefon ve adres.</p>
            </div>
            <button
                type="button"
                class="btn-kimlik-sorgula"
                data-adres-sorgula
                title="Adres Sorgula"
                aria-label="Adres Sorgula"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="email">E-posta <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="{{ $val('email') }}" class="form-control @error('email') is-invalid @enderror" required maxlength="150" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" placeholder="{{ $egitmen ? 'Boş bırakılırsa değişmez' : 'Boş bırakılırsa otomatik oluşturulur' }}">
            </div>

            <div class="form-group">
                <label for="telefon">Telefon</label>
                <input type="text" id="telefon" name="telefon" value="{{ $val('telefon') }}" class="form-control @error('telefon') is-invalid @enderror" maxlength="20" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="il">İl</label>
                <input type="text" id="il" name="il" value="{{ $val('il') }}" class="form-control @error('il') is-invalid @enderror" maxlength="100" autocomplete="off" data-adres-alan="il">
            </div>

            <div class="form-group">
                <label for="ilce">İlçe</label>
                <input type="text" id="ilce" name="ilce" value="{{ $val('ilce') }}" class="form-control @error('ilce') is-invalid @enderror" maxlength="100" autocomplete="off" data-adres-alan="ilce">
            </div>

            <div class="form-group">
                <label for="adres">Adres</label>
                <textarea id="adres" name="adres" class="form-control @error('adres') is-invalid @enderror" rows="2" maxlength="500" data-adres-alan="adres">{{ $val('adres') }}</textarea>
            </div>

            <div class="form-group form-group-switch">
                <label class="switch-label" for="aktif">
                    <input type="checkbox" id="aktif" name="aktif" value="1" @checked((bool) $val('aktif', true))>
                    <span>Aktif</span>
                </label>
            </div>
        </div>
    </div>
</div>
