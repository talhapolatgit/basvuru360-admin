<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Kisi;
use App\Models\KisiYakin;
use App\Models\YakinlikDerecesi;
use App\Services\Entegrasyon\EntegrasyonAyarServisi;
use App\Services\GenelAyarServisi;
use App\Services\Kimlik\KimlikSorgulama;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

trait ResolvesPortalBasvuruKatilimci
{
    protected function basvuruIcinCocukMu(Request $request): bool
    {
        return (string) $request->input('basvuru_icin', 'kendisi') === 'cocuk';
    }

    /**
     * 18 yaşından küçük başvuranlar yakın adına başvuru yapamaz.
     */
    protected function cocukBasvurusuIcinBasvuranUygunMu(Kisi $basvuran): void
    {
        if (! app(GenelAyarServisi::class)->yakinIcinBasvuruAktif()) {
            throw ValidationException::withMessages([
                'basvuru_icin' => 'Yakın adına başvuru şu anda kapalıdır.',
            ]);
        }

        $yas = $this->yasHesapla($basvuran->dogum_tarihi?->format('Y-m-d'));

        if ($yas !== null && $yas < 18) {
            throw ValidationException::withMessages([
                'basvuru_icin' => '18 yaşından küçük kişiler yakın adına başvuru yapamaz.',
            ]);
        }
    }

    protected function manuelYakinEklemeIzinliMi(Kisi $basvuran, string $yakinTc): void
    {
        if (app(GenelAyarServisi::class)->manuelYakinEklemeAktif()) {
            return;
        }

        $kayitli = KisiYakin::query()
            ->where('kisi_id', $basvuran->id)
            ->whereHas('yakin', fn ($q) => $q->where('tc_kimlik_no', $yakinTc))
            ->exists();

        if (! $kayitli) {
            throw ValidationException::withMessages([
                'cocuk_tc_kimlik_no' => 'Manuel yakın ekleme kapalıdır. Yalnızca kayıtlı yakınlarınız için başvuru yapabilirsiniz.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function cocukBasvuruKurallari(Kisi $basvuran): array
    {
        return [
            'basvuru_icin' => ['nullable', Rule::in(['kendisi', 'cocuk'])],
            'cocuk_ad' => ['required', 'string', 'max:100'],
            'cocuk_soyad' => ['required', 'string', 'max:100'],
            'cocuk_tc_kimlik_no' => [
                'required',
                'digits:11',
                Rule::notIn([(string) $basvuran->tc_kimlik_no]),
            ],
            'cocuk_dogum_tarihi' => ['required', 'date', 'before:today'],
            'yakinlik_derecesi' => ['nullable', Rule::in(['ESI', 'OGLU', 'KIZI'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function cocukBasvuruMesajlari(): array
    {
        return [
            'cocuk_ad.required' => 'Yakın adı zorunludur.',
            'cocuk_soyad.required' => 'Yakın soyadı zorunludur.',
            'cocuk_tc_kimlik_no.required' => 'Yakın TC Kimlik No zorunludur.',
            'cocuk_tc_kimlik_no.digits' => 'Yakın TC Kimlik No 11 haneli olmalıdır.',
            'cocuk_tc_kimlik_no.not_in' => 'Yakın TC Kimlik No başvuran kişiden farklı olmalıdır.',
            'cocuk_dogum_tarihi.required' => 'Yakın doğum tarihi zorunludur.',
            'cocuk_dogum_tarihi.date' => 'Geçerli bir yakın doğum tarihi girin.',
            'cocuk_dogum_tarihi.before' => 'Yakın doğum tarihi bugünden önce olmalıdır.',
            'yakinlik_derecesi.in' => 'Geçersiz yakınlık derecesi.',
        ];
    }

    protected function kimlikSorgulamaAktifMi(): bool
    {
        return app(EntegrasyonAyarServisi::class)->turAktifMi('kimlik_sorgulama');
    }

    /**
     * Kimlik sorgulama entegrasyonu aktifse çocuğu doğrular.
     * Demo sağlayıcıda ad/soyad eşleşmesi atlanır; doğrulama başarılı kabul edilir.
     *
     * @param  array{cocuk_tc_kimlik_no: string, cocuk_dogum_tarihi: string, cocuk_ad: string, cocuk_soyad: string}  $cocuk
     * @return string|null Entegrasyondan gelen cinsiyet (erkek|kadin) veya null
     */
    protected function cocukKimlikDogrula(array $cocuk): ?string
    {
        if (! $this->kimlikSorgulamaAktifMi()) {
            return null;
        }

        $ayarlar = app(EntegrasyonAyarServisi::class);
        $saglayici = $ayarlar->aktifSaglayiciKod('kimlik_sorgulama');
        $sonuc = $this->kimlikSorgula(
            $cocuk['cocuk_tc_kimlik_no'],
            $cocuk['cocuk_dogum_tarihi'],
            'cocuk_tc_kimlik_no',
        );

        if ($saglayici === 'demo_kimlik') {
            Log::channel('single')->info('Portal çocuk kimlik doğrulama (demo) başarılı', [
                'tc_kimlik_no' => $cocuk['cocuk_tc_kimlik_no'],
            ]);

            return $this->normalizeCinsiyet($sonuc['cinsiyet'] ?? null);
        }

        if (! ($sonuc['ok'] ?? false)) {
            throw ValidationException::withMessages([
                'cocuk_tc_kimlik_no' => $sonuc['message'] ?? 'Yakın kimlik bilgileri doğrulanamadı.',
            ]);
        }

        $beklenenAd = $this->normalizeKimlikMetin($cocuk['cocuk_ad']);
        $beklenenSoyad = $this->normalizeKimlikMetin($cocuk['cocuk_soyad']);
        $gelenAd = $this->normalizeKimlikMetin((string) ($sonuc['ad'] ?? ''));
        $gelenSoyad = $this->normalizeKimlikMetin((string) ($sonuc['soyad'] ?? ''));

        if ($gelenAd !== '' && $beklenenAd !== '' && $gelenAd !== $beklenenAd) {
            throw ValidationException::withMessages([
                'cocuk_ad' => 'Girilen ad kimlik kaydı ile eşleşmiyor.',
            ]);
        }

        if ($gelenSoyad !== '' && $beklenenSoyad !== '' && $gelenSoyad !== $beklenenSoyad) {
            throw ValidationException::withMessages([
                'cocuk_soyad' => 'Girilen soyad kimlik kaydı ile eşleşmiyor.',
            ]);
        }

        return $this->normalizeCinsiyet($sonuc['cinsiyet'] ?? null);
    }

    /**
     * Kimlik entegrasyonundan cinsiyet çeker (kayıtta yoksa).
     */
    protected function kimliktenCinsiyetAl(string $tcKimlikNo, ?string $dogumTarihi): ?string
    {
        if (! $this->kimlikSorgulamaAktifMi() || $tcKimlikNo === '' || ! $dogumTarihi) {
            return null;
        }

        try {
            $sonuc = $this->kimlikSorgula($tcKimlikNo, $dogumTarihi, 'cinsiyet');
        } catch (ValidationException) {
            return null;
        }

        if (! ($sonuc['ok'] ?? false) && app(EntegrasyonAyarServisi::class)->aktifSaglayiciKod('kimlik_sorgulama') !== 'demo_kimlik') {
            return null;
        }

        return $this->normalizeCinsiyet($sonuc['cinsiyet'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function kimlikSorgula(string $tcKimlikNo, ?string $dogumTarihi, string $errorField): array
    {
        try {
            return app(KimlikSorgulama::class)->sorgula($tcKimlikNo, $dogumTarihi);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                $errorField => $e->getMessage() ?: 'Kimlik sorgulama yapılamadı.',
            ]);
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                $errorField => 'Kimlik sorgulama sırasında bir hata oluştu.',
            ]);
        }
    }

    protected function normalizeCinsiyet(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = is_object($value) && property_exists($value, 'value')
            ? (string) $value->value
            : mb_strtolower(trim((string) $value), 'UTF-8');

        return match ($raw) {
            'erkek', 'e', 'male', '1' => 'erkek',
            'kadin', 'kadın', 'k', 'female', '2' => 'kadin',
            default => null,
        };
    }

    protected function cozulmusCinsiyet(mixed $value): ?string
    {
        return $this->normalizeCinsiyet($value);
    }

    protected function normalizeKimlikMetin(string $value): string
    {
        $value = trim(mb_strtoupper($value, 'UTF-8'));
        $value = str_replace(['İ', 'I', 'Ş', 'Ğ', 'Ü', 'Ö', 'Ç'], ['I', 'I', 'S', 'G', 'U', 'O', 'C'], $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function kisiUpsert(array $data): Kisi
    {
        $tc = trim((string) ($data['tc_kimlik_no'] ?? ''));
        $kisi = Kisi::query()->where('tc_kimlik_no', $tc)->first();

        $payload = [
            'ad' => trim((string) $data['ad']),
            'soyad' => trim((string) $data['soyad']),
            'tc_kimlik_no' => $tc,
            'dogum_tarihi' => $data['dogum_tarihi'] ?? null,
            'telefon' => $data['telefon'] ?? null,
            'email' => $data['email'] ?? null,
            'adres' => $data['adres'] ?? null,
            'cinsiyet' => $data['cinsiyet'] ?? null,
            'aktif' => true,
        ];

        if ($kisi) {
            $kisi->fill(array_filter(
                $payload,
                fn ($value, $key) => $key === 'aktif' || ($value !== null && $value !== ''),
                ARRAY_FILTER_USE_BOTH
            ))->save();

            return $kisi->fresh();
        }

        return Kisi::query()->create($payload);
    }

    /**
     * Yakın başvurusunda başvuran–yakın ilişkisini (Eşi/Oğlu/Kızı) kaydeder.
     */
    protected function cocukYakinligiKaydet(
        Kisi $kisi,
        Kisi $cocuk,
        mixed $cinsiyet = null,
        ?string $dereceKod = null,
    ): void {
        $derece = null;
        $kod = $dereceKod ? strtoupper(trim($dereceKod)) : null;

        if ($kod && in_array($kod, ['ESI', 'OGLU', 'KIZI'], true)) {
            $derece = YakinlikDerecesi::query()->where('kod', $kod)->first();
        }

        if (! $derece) {
            $cinsiyetKod = $this->cozulmusCinsiyet($cinsiyet)
                ?? $cocuk->cinsiyet?->value;
            $derece = YakinlikDerecesi::cocuktan($cinsiyetKod);
        }

        if (! $derece) {
            return;
        }

        KisiYakin::query()->updateOrCreate(
            [
                'kisi_id' => $kisi->id,
                'yakin_kisi_id' => $cocuk->id,
            ],
            [
                'yakinlik_derecesi_id' => $derece->id,
            ],
        );
    }

    protected function yasHesapla(?string $dogumTarihi): ?int
    {
        if (! $dogumTarihi) {
            return null;
        }

        try {
            return Carbon::parse($dogumTarihi)->age;
        } catch (Throwable) {
            return null;
        }
    }
}
