<?php

namespace App\Services;

use App\Models\SertifikaAyar;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class SertifikaAyarServisi
{
    /**
     * Config varsayılanları + veritabanıOverrides birleşik ayarlar.
     *
     * @return array{
     *     kurum_adi: string,
     *     hak_eden_kodlar: list<string>,
     *     sablonlar: array<string, array<string, mixed>>
     * }
     */
    public function ayarlar(): array
    {
        $defaults = config('sertifika', []);
        $kayit = SertifikaAyar::current();
        $kayitSablonlar = is_array($kayit->sablonlar) ? $kayit->sablonlar : [];

        $sablonlar = [];
        foreach ((array) ($defaults['sablonlar'] ?? []) as $kod => $varsayilan) {
            $override = is_array($kayitSablonlar[$kod] ?? null) ? $kayitSablonlar[$kod] : [];
            $merged = array_merge($varsayilan, $this->filtreOverride($override));

            $merged['arka_plan'] = $this->resolveArkaPlanPath(
                isset($override['arka_plan']) ? (string) $override['arka_plan'] : null,
                (string) ($varsayilan['arka_plan'] ?? '')
            );

            $sablonlar[$kod] = $merged;
        }

        $kurumAdi = $kayit->kurum_adi;
        if ($kurumAdi === null) {
            // İlk kayıt öncesi varsayılan
            $kurumAdi = (string) ($defaults['kurum_adi'] ?? config('app.name'));
        } else {
            $kurumAdi = trim((string) $kurumAdi);
        }

        return [
            'kurum_adi' => $kurumAdi,
            'hak_eden_kodlar' => array_values((array) ($defaults['hak_eden_kodlar'] ?? [])),
            'sablonlar' => $sablonlar,
        ];
    }

    /**
     * Form için düzenlenebilir alanlar + önizleme URL'leri.
     *
     * @return array<string, mixed>
     */
    public function formVerisi(): array
    {
        $ayarlar = $this->ayarlar();
        $kayit = SertifikaAyar::current();
        $kayitSablonlar = is_array($kayit->sablonlar) ? $kayit->sablonlar : [];

        $sablonlar = [];
        foreach ($ayarlar['sablonlar'] as $kod => $sablon) {
            $overridePath = isset($kayitSablonlar[$kod]['arka_plan'])
                ? (string) $kayitSablonlar[$kod]['arka_plan']
                : '';

            $sablonlar[$kod] = [
                'etiket' => $kod === 'katilim_belgesi_hak_etti' ? 'Katılım Belgesi' : 'Sertifika',
                'kod' => (string) ($sablon['kod'] ?? ''),
                'baslik' => (string) ($sablon['baslik'] ?? ''),
                'alt_baslik' => (string) ($sablon['alt_baslik'] ?? ''),
                'metin' => (string) ($sablon['metin'] ?? ''),
                'alt_metin' => (string) ($sablon['alt_metin'] ?? ''),
                'kenarlik_olcusu' => (float) ($sablon['kenarlik_olcusu'] ?? 12),
                'egitmen_imzasi' => (bool) ($sablon['egitmen_imzasi'] ?? true),
                'diger_imzaci' => (bool) ($sablon['diger_imzaci'] ?? false),
                'diger_imzaci_unvan' => (string) ($sablon['diger_imzaci_unvan'] ?? ''),
                'diger_imzaci_ad_soyad' => (string) ($sablon['diger_imzaci_ad_soyad'] ?? ''),
                'belge_no_yazdir' => (bool) ($sablon['belge_no_yazdir'] ?? true),
                'tarih_yazdir' => (bool) ($sablon['tarih_yazdir'] ?? true),
                'renk' => (string) ($sablon['renk'] ?? '#1e3a5f'),
                'vurgu' => (string) ($sablon['vurgu'] ?? '#b8860b'),
                'arka_plan_ozel' => $overridePath !== '',
                'arka_plan_url' => $this->arkaPlanUrl($overridePath !== '' ? $overridePath : (string) ($sablon['arka_plan'] ?? '')),
                'arka_plan_adi' => $overridePath !== ''
                    ? basename($overridePath)
                    : basename((string) ($sablon['arka_plan'] ?? 'varsayılan')),
            ];
        }

        return [
            'kurum_adi' => $ayarlar['kurum_adi'],
            'kurum_adi_kayitli' => $kayit->kurum_adi !== null,
            'yer_tutucular' => (array) config('sertifika.yer_tutucular', []),
            'sablonlar' => $sablonlar,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, UploadedFile|null>  $dosyalar  basari_kod => file
     * @param  list<string>  $arkaPlanKaldir  basari kodları
     */
    public function kaydet(array $payload, array $dosyalar = [], array $arkaPlanKaldir = []): SertifikaAyar
    {
        $kayit = SertifikaAyar::current();
        $mevcut = is_array($kayit->sablonlar) ? $kayit->sablonlar : [];
        $defaults = (array) config('sertifika.sablonlar', []);

        $sablonlar = [];
        foreach (array_keys($defaults) as $kod) {
            $gelen = is_array($payload['sablonlar'][$kod] ?? null) ? $payload['sablonlar'][$kod] : [];
            $onceki = is_array($mevcut[$kod] ?? null) ? $mevcut[$kod] : [];

            $arkaPlan = isset($onceki['arka_plan']) ? (string) $onceki['arka_plan'] : null;

            if (in_array($kod, $arkaPlanKaldir, true)) {
                $this->silArkaPlanDosyasi($arkaPlan);
                $arkaPlan = null;
            }

            if (isset($dosyalar[$kod]) && $dosyalar[$kod] instanceof UploadedFile) {
                $this->silArkaPlanDosyasi($arkaPlan);
                $arkaPlan = $this->kaydetArkaPlan($dosyalar[$kod], $kod);
            }

            $sablon = array_filter([
                'kod' => trim((string) ($gelen['kod'] ?? $onceki['kod'] ?? '')),
                'metin' => trim((string) ($gelen['metin'] ?? $onceki['metin'] ?? '')),
                'renk' => trim((string) ($gelen['renk'] ?? $onceki['renk'] ?? '')),
                'vurgu' => trim((string) ($gelen['vurgu'] ?? $onceki['vurgu'] ?? '')),
                'arka_plan' => $arkaPlan,
            ], fn ($v) => $v !== null && $v !== '');

            // Bilinçli boş bırakılabilir alanlar (belgede gizlenir).
            foreach (['baslik', 'alt_baslik', 'alt_metin'] as $opsiyonelAlan) {
                $sablon[$opsiyonelAlan] = array_key_exists($opsiyonelAlan, $gelen)
                    ? trim((string) $gelen[$opsiyonelAlan])
                    : trim((string) ($onceki[$opsiyonelAlan] ?? ''));
            }

            $kenarlik = array_key_exists('kenarlik_olcusu', $gelen)
                ? (float) $gelen['kenarlik_olcusu']
                : (float) ($onceki['kenarlik_olcusu'] ?? ($defaults[$kod]['kenarlik_olcusu'] ?? 12));
            $sablon['kenarlik_olcusu'] = max(0, min(40, round($kenarlik, 1)));

            $sablon['egitmen_imzasi'] = array_key_exists('egitmen_imzasi', $gelen)
                ? (bool) $gelen['egitmen_imzasi']
                : (bool) ($onceki['egitmen_imzasi'] ?? ($defaults[$kod]['egitmen_imzasi'] ?? true));

            $sablon['diger_imzaci'] = array_key_exists('diger_imzaci', $gelen)
                ? (bool) $gelen['diger_imzaci']
                : (bool) ($onceki['diger_imzaci'] ?? false);

            $sablon['diger_imzaci_unvan'] = $sablon['diger_imzaci']
                ? trim((string) ($gelen['diger_imzaci_unvan'] ?? $onceki['diger_imzaci_unvan'] ?? ''))
                : '';
            $sablon['diger_imzaci_ad_soyad'] = $sablon['diger_imzaci']
                ? trim((string) ($gelen['diger_imzaci_ad_soyad'] ?? $onceki['diger_imzaci_ad_soyad'] ?? ''))
                : '';

            $sablon['belge_no_yazdir'] = array_key_exists('belge_no_yazdir', $gelen)
                ? (bool) $gelen['belge_no_yazdir']
                : (bool) ($onceki['belge_no_yazdir'] ?? ($defaults[$kod]['belge_no_yazdir'] ?? true));

            $sablon['tarih_yazdir'] = array_key_exists('tarih_yazdir', $gelen)
                ? (bool) $gelen['tarih_yazdir']
                : (bool) ($onceki['tarih_yazdir'] ?? ($defaults[$kod]['tarih_yazdir'] ?? true));

            $sablonlar[$kod] = $sablon;
        }

        // Boş string = kurum adı basılmasın; null = henüz kaydedilmedi (varsayılan kullanılır).
        $kurumAdi = array_key_exists('kurum_adi', $payload)
            ? trim((string) $payload['kurum_adi'])
            : null;

        $kayit->update([
            'kurum_adi' => $kurumAdi,
            'sablonlar' => $sablonlar,
        ]);

        return $kayit->fresh();
    }

    public function resolveArkaPlanPath(?string $override, string $varsayilan): string
    {
        if ($override !== null && $override !== '') {
            $mutlak = $this->mutlakYol($override);
            if ($mutlak !== null) {
                return $mutlak;
            }
        }

        return $varsayilan;
    }

    public function arkaPlanUrl(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'uploads/')) {
            return asset($path);
        }

        $mutlak = $this->mutlakYol($path) ?? (File::isFile($path) ? $path : null);
        if ($mutlak === null) {
            return null;
        }

        // resource_path SVG'leri için data URI gerekmez; admin önizlemede asset yok.
        // Dosyayı geçici olarak okunabilir tutmak için relative public değilse base64 küçük SVG için kullan.
        if (! str_starts_with($mutlak, public_path())) {
            $ext = strtolower(pathinfo($mutlak, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => null,
            };
            if ($mime && File::isFile($mutlak)) {
                return 'data:'.$mime.';base64,'.base64_encode((string) File::get($mutlak));
            }

            return null;
        }

        return asset(ltrim(str_replace(public_path(), '', $mutlak), DIRECTORY_SEPARATOR.'\\/'));
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function filtreOverride(array $override): array
    {
        $allowed = [
            'kod', 'baslik', 'alt_baslik', 'metin', 'alt_metin', 'kenarlik_olcusu',
            'egitmen_imzasi', 'diger_imzaci', 'diger_imzaci_unvan', 'diger_imzaci_ad_soyad',
            'belge_no_yazdir', 'tarih_yazdir',
            'renk', 'vurgu', 'alanlar',
        ];
        $out = [];
        foreach ($allowed as $key) {
            if (! array_key_exists($key, $override)) {
                continue;
            }

            // Boş bırakılabilir metin alanları (belgede gizlenir).
            if (in_array($key, ['alt_metin', 'baslik', 'alt_baslik', 'diger_imzaci_unvan', 'diger_imzaci_ad_soyad'], true)) {
                $out[$key] = (string) $override[$key];
                continue;
            }

            if ($key === 'kenarlik_olcusu') {
                $out[$key] = max(0, min(40, (float) $override[$key]));
                continue;
            }

            if (in_array($key, ['egitmen_imzasi', 'diger_imzaci', 'belge_no_yazdir', 'tarih_yazdir'], true)) {
                $out[$key] = (bool) $override[$key];
                continue;
            }

            if ($override[$key] === null || $override[$key] === '') {
                continue;
            }
            $out[$key] = $override[$key];
        }

        return $out;
    }

    private function kaydetArkaPlan(UploadedFile $file, string $kod): string
    {
        $klasor = public_path('uploads/sertifika');
        if (! is_dir($klasor)) {
            mkdir($klasor, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'], true) ? $ext : 'png';
        $ad = $kod.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$ext;
        $file->move($klasor, $ad);

        return 'uploads/sertifika/'.$ad;
    }

    private function silArkaPlanDosyasi(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'uploads/sertifika/')) {
            return;
        }

        $mutlak = public_path($path);
        if (is_file($mutlak)) {
            @unlink($mutlak);
        }
    }

    private function mutlakYol(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (File::isFile($path)) {
            return $path;
        }

        if (str_starts_with($path, 'uploads/')) {
            $public = public_path($path);

            return File::isFile($public) ? $public : null;
        }

        return null;
    }
}
