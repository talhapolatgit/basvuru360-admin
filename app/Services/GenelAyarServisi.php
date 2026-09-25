<?php

namespace App\Services;

use App\Enums\KisiGirisYontemi;
use App\Models\GenelAyar;
use Illuminate\Http\UploadedFile;

class GenelAyarServisi
{
    public const DEFAULT_SIDEBAR_LOGO_ARKAPLAN = '#ffffff';

    /** Portal sidebar mevcut varsayılan (ink) rengi */
    public const DEFAULT_SIDEBAR_ARKAPLAN = '#0c2138';

    public const DEFAULT_SIDEBAR_ARKAPLAN_TIP = 'gradient';

    /** @var list<string> */
    public const SIDEBAR_ARKAPLAN_TIPLERI = ['duz', 'gradient'];

    /**
     * @return array{
     *     kurum_adi: string,
     *     telefon: string,
     *     eposta: string,
     *     il: string,
     *     ilce: string,
     *     adres: string,
     *     logo: string|null,
     *     logo_url: string|null,
     *     logo_adi: string|null,
     *     web_sitesi: string,
     *     kisi_giris_yontemi: string,
     *     kisi_giris_yontemi_secenekler: list<array{value: string, label: string}>,
     *     yakin_icin_basvuru_aktif: bool,
     *     manuel_yakin_ekleme_aktif: bool,
     *     sidebar_logo: string|null,
     *     sidebar_logo_url: string|null,
     *     sidebar_logo_adi: string|null,
     *     sidebar_baslik: string,
     *     sidebar_alt_baslik: string,
     *     sidebar_logo_arkaplan: string,
     *     sidebar_arkaplan: string,
     *     sidebar_arkaplan_tip: string,
     *     header_logo: string|null,
     *     header_logo_url: string|null,
     *     header_logo_adi: string|null,
     *     favicon: string|null,
     *     favicon_url: string|null,
     *     favicon_adi: string|null,
     *     site_aciklama: string
     * }
     */
    public function formVerisi(): array
    {
        $ayarlar = GenelAyar::harita();
        $logo = $ayarlar['logo'] ?? null;
        $sidebarLogo = $ayarlar['sidebar_logo'] ?? null;
        $headerLogo = $ayarlar['header_logo'] ?? null;
        $favicon = $ayarlar['favicon'] ?? null;
        $girisYontemi = $this->kisiGirisYontemi();

        return [
            'kurum_adi' => (string) ($ayarlar['kurum_adi'] ?? ''),
            'telefon' => (string) ($ayarlar['telefon'] ?? ''),
            'eposta' => (string) ($ayarlar['eposta'] ?? ''),
            'il' => (string) ($ayarlar['il'] ?? ''),
            'ilce' => (string) ($ayarlar['ilce'] ?? ''),
            'adres' => (string) ($ayarlar['adres'] ?? ''),
            'logo' => $logo,
            'logo_url' => $this->logoUrl($logo),
            'logo_adi' => $logo ? basename($logo) : null,
            'web_sitesi' => (string) ($ayarlar['web_sitesi'] ?? ''),
            'site_aciklama' => (string) ($ayarlar['site_aciklama'] ?? ''),
            'kisi_giris_yontemi' => $girisYontemi->value,
            'kisi_giris_yontemi_secenekler' => KisiGirisYontemi::secenekler(),
            'yakin_icin_basvuru_aktif' => $this->yakinIcinBasvuruAktif(),
            'manuel_yakin_ekleme_aktif' => $this->manuelYakinEklemeAktif(),
            'sidebar_logo' => $sidebarLogo,
            'sidebar_logo_url' => $this->logoUrl($sidebarLogo),
            'sidebar_logo_adi' => $sidebarLogo ? basename($sidebarLogo) : null,
            'sidebar_baslik' => (string) ($ayarlar['sidebar_baslik'] ?? ''),
            'sidebar_alt_baslik' => (string) ($ayarlar['sidebar_alt_baslik'] ?? ''),
            'sidebar_logo_arkaplan' => $this->normalizeArkaplan($ayarlar['sidebar_logo_arkaplan'] ?? null),
            'sidebar_arkaplan' => $this->normalizeHexRenk(
                $ayarlar['sidebar_arkaplan'] ?? null,
                self::DEFAULT_SIDEBAR_ARKAPLAN,
            ),
            'sidebar_arkaplan_tip' => $this->normalizeArkaplanTip($ayarlar['sidebar_arkaplan_tip'] ?? null),
            'header_logo' => $headerLogo,
            'header_logo_url' => $this->logoUrl($headerLogo),
            'header_logo_adi' => $headerLogo ? basename($headerLogo) : null,
            'favicon' => $favicon,
            'favicon_url' => $this->logoUrl($favicon),
            'favicon_adi' => $favicon ? basename($favicon) : null,
        ];
    }

    public function get(string $anahtar, ?string $varsayilan = null): ?string
    {
        return GenelAyar::deger($anahtar, $varsayilan);
    }

    public function kisiGirisYontemi(): KisiGirisYontemi
    {
        return KisiGirisYontemi::tryFrom((string) (GenelAyar::deger('kisi_giris_yontemi') ?? ''))
            ?? KisiGirisYontemi::TcSifre;
    }

    public function yakinIcinBasvuruAktif(): bool
    {
        return $this->boolAyar('yakin_icin_basvuru_aktif', true);
    }

    public function manuelYakinEklemeAktif(): bool
    {
        return $this->boolAyar('manuel_yakin_ekleme_aktif', true);
    }

    private function boolAyar(string $anahtar, bool $varsayilan = false): bool
    {
        $deger = GenelAyar::deger($anahtar);
        if ($deger === null || $deger === '') {
            return $varsayilan;
        }

        return in_array(strtolower(trim($deger)), ['1', 'true', 'evet', 'aktif'], true);
    }

    /**
     * @return array<string, string|null>
     */
    public function tumu(): array
    {
        return GenelAyar::harita();
    }

    /**
     * @param  array{
     *     kurum_adi?: string|null,
     *     telefon?: string|null,
     *     eposta?: string|null,
     *     il?: string|null,
     *     ilce?: string|null,
     *     adres?: string|null,
     *     web_sitesi?: string|null,
     *     kisi_giris_yontemi?: string|null,
     *     sidebar_baslik?: string|null,
     *     sidebar_alt_baslik?: string|null,
     *     sidebar_logo_arkaplan?: string|null,
     *     sidebar_logo_arkaplan_seffaf?: bool|null,
     *     sidebar_arkaplan?: string|null,
     *     sidebar_arkaplan_tip?: string|null,
     *     site_aciklama?: string|null
     * }  $payload
     * @return array<string, string|null>
     */
    public function kaydet(
        array $payload,
        ?UploadedFile $logo = null,
        bool $logoKaldir = false,
        ?UploadedFile $sidebarLogo = null,
        bool $sidebarLogoKaldir = false,
        ?UploadedFile $headerLogo = null,
        bool $headerLogoKaldir = false,
        ?UploadedFile $favicon = null,
        bool $faviconKaldir = false,
    ): array {
        $logoPath = $this->guncelleYukleme(
            GenelAyar::deger('logo'),
            $logo,
            $logoKaldir,
            'logo',
        );

        $sidebarLogoPath = $this->guncelleYukleme(
            GenelAyar::deger('sidebar_logo'),
            $sidebarLogo,
            $sidebarLogoKaldir,
            'sidebar-logo',
        );

        $headerLogoPath = $this->guncelleYukleme(
            GenelAyar::deger('header_logo'),
            $headerLogo,
            $headerLogoKaldir,
            'header-logo',
        );

        $faviconPath = $this->guncelleYukleme(
            GenelAyar::deger('favicon'),
            $favicon,
            $faviconKaldir,
            'favicon',
        );

        $girisYontemi = KisiGirisYontemi::tryFrom((string) ($payload['kisi_giris_yontemi'] ?? ''))
            ?? KisiGirisYontemi::TcSifre;

        $arkaplan = ! empty($payload['sidebar_logo_arkaplan_seffaf'])
            ? 'transparent'
            : $this->normalizeArkaplan($payload['sidebar_logo_arkaplan'] ?? null);

        GenelAyar::kaydetCoklu([
            'kurum_adi' => $this->normalize($payload['kurum_adi'] ?? null),
            'telefon' => $this->normalize($payload['telefon'] ?? null),
            'eposta' => $this->normalize($payload['eposta'] ?? null),
            'il' => $this->normalize($payload['il'] ?? null),
            'ilce' => $this->normalize($payload['ilce'] ?? null),
            'adres' => $this->normalize($payload['adres'] ?? null),
            'web_sitesi' => $this->normalize($payload['web_sitesi'] ?? null),
            'site_aciklama' => $this->normalize($payload['site_aciklama'] ?? null),
            'kisi_giris_yontemi' => $girisYontemi->value,
            'yakin_icin_basvuru_aktif' => ! empty($payload['yakin_icin_basvuru_aktif']) ? '1' : '0',
            'manuel_yakin_ekleme_aktif' => ! empty($payload['manuel_yakin_ekleme_aktif']) ? '1' : '0',
            'logo' => $logoPath,
            'sidebar_logo' => $sidebarLogoPath,
            'header_logo' => $headerLogoPath,
            'favicon' => $faviconPath,
            'sidebar_baslik' => $this->normalize($payload['sidebar_baslik'] ?? null),
            'sidebar_alt_baslik' => $this->normalize($payload['sidebar_alt_baslik'] ?? null),
            'sidebar_logo_arkaplan' => $arkaplan,
            'sidebar_arkaplan' => $this->normalizeHexRenk(
                $payload['sidebar_arkaplan'] ?? null,
                self::DEFAULT_SIDEBAR_ARKAPLAN,
            ),
            'sidebar_arkaplan_tip' => $this->normalizeArkaplanTip($payload['sidebar_arkaplan_tip'] ?? null),
        ]);

        return GenelAyar::harita();
    }

    private function logoUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || ! str_starts_with($path, 'uploads/')) {
            return null;
        }

        if (! is_file(public_path($path))) {
            return null;
        }

        return asset($path);
    }

    /**
     * Vatandaş portalı / mobil için logo adresi (yönetim paneli statik dosyasına bağlı değil).
     */
    public function apiLogoUrl(?string $path = null): ?string
    {
        return $this->apiGorselUrl(
            $path ?? GenelAyar::deger('logo'),
            '/api/v1/logo',
        );
    }

    public function apiSidebarLogoUrl(?string $path = null): ?string
    {
        return $this->apiGorselUrl(
            $path ?? GenelAyar::deger('sidebar_logo'),
            '/api/v1/sidebar-logo',
        );
    }

    public function apiHeaderLogoUrl(?string $path = null): ?string
    {
        return $this->apiGorselUrl(
            $path ?? GenelAyar::deger('header_logo'),
            '/api/v1/header-logo',
        );
    }

    public function apiFaviconUrl(?string $path = null): ?string
    {
        return $this->apiGorselUrl(
            $path ?? GenelAyar::deger('favicon'),
            '/api/v1/favicon',
        );
    }

    public function logoDosyaYolu(): ?string
    {
        return $this->dosyaYolu(GenelAyar::deger('logo'));
    }

    public function sidebarLogoDosyaYolu(): ?string
    {
        return $this->dosyaYolu(GenelAyar::deger('sidebar_logo'));
    }

    public function headerLogoDosyaYolu(): ?string
    {
        return $this->dosyaYolu(GenelAyar::deger('header_logo'));
    }

    public function faviconDosyaYolu(): ?string
    {
        return $this->dosyaYolu(GenelAyar::deger('favicon'));
    }

    private function apiGorselUrl(?string $path, string $endpoint): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || ! str_starts_with($path, 'uploads/')) {
            return null;
        }

        if (! is_file(public_path($path))) {
            return null;
        }

        return url($endpoint).'?v='.substr(hash('sha256', $path), 0, 12);
    }

    private function dosyaYolu(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || ! str_starts_with($path, 'uploads/')) {
            return null;
        }

        $full = public_path($path);
        if (! is_file($full)) {
            return null;
        }

        return $full;
    }

    private function guncelleYukleme(
        ?string $mevcut,
        ?UploadedFile $file,
        bool $kaldir,
        string $prefix,
    ): ?string {
        $path = $mevcut;

        if ($kaldir && $path) {
            $this->silLogo($path);
            $path = null;
        }

        if ($file !== null) {
            if ($path) {
                $this->silLogo($path);
            }
            $path = $this->kaydetLogo($file, $prefix);
        }

        return $path;
    }

    private function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    public function normalizeArkaplan(mixed $value): string
    {
        $trimmed = strtolower(trim((string) $value));

        if ($trimmed === 'transparent') {
            return 'transparent';
        }

        return $this->normalizeHexRenk($value, self::DEFAULT_SIDEBAR_LOGO_ARKAPLAN);
    }

    public function normalizeHexRenk(mixed $value, string $varsayilan): string
    {
        $trimmed = strtolower(trim((string) $value));

        if ($trimmed === '' || $trimmed === 'transparent') {
            return $varsayilan;
        }

        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/', $trimmed) === 1) {
            if (strlen($trimmed) === 4) {
                return sprintf(
                    '#%s%s%s%s%s%s',
                    $trimmed[1],
                    $trimmed[1],
                    $trimmed[2],
                    $trimmed[2],
                    $trimmed[3],
                    $trimmed[3],
                );
            }

            return $trimmed;
        }

        return $varsayilan;
    }

    public function normalizeArkaplanTip(mixed $value): string
    {
        $tip = strtolower(trim((string) $value));

        return in_array($tip, self::SIDEBAR_ARKAPLAN_TIPLERI, true)
            ? $tip
            : self::DEFAULT_SIDEBAR_ARKAPLAN_TIP;
    }

    private function kaydetLogo(UploadedFile $file, string $prefix = 'logo'): string
    {
        $klasor = public_path('uploads/genel');
        if (! is_dir($klasor)) {
            mkdir($klasor, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $allowed = $prefix === 'favicon'
            ? ['ico', 'png', 'jpg', 'jpeg', 'svg', 'webp']
            : ['png', 'jpg', 'jpeg', 'svg', 'webp'];
        $ext = in_array($ext, $allowed, true) ? $ext : 'png';
        $ad = $prefix.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$ext;
        $file->move($klasor, $ad);

        return 'uploads/genel/'.$ad;
    }

    private function silLogo(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'uploads/genel/')) {
            return;
        }

        $mutlak = public_path($path);
        if (is_file($mutlak)) {
            @unlink($mutlak);
        }
    }
}
