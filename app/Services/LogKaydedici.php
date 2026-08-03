<?php

namespace App\Services;

use App\Models\Kurs;
use App\Models\LogKayit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Sistemdeki işlemlerin kalıcı olarak loglanmasını sağlar.
 *
 * Log kaydı hiçbir zaman asıl işlemi kesintiye uğratmamalıdır; bu nedenle
 * kayıt sırasında oluşabilecek hatalar yakalanır ve raporlanır.
 */
class LogKaydedici
{
    /**
     * @param  array<string, mixed>|null  $eski
     * @param  array<string, mixed>|null  $yeni
     * @param  array<string, mixed>  $ekstra
     */
    public static function kaydet(
        string $islem,
        Kurs|int|null $kurs = null,
        ?string $aciklama = null,
        ?Model $konu = null,
        ?array $eski = null,
        ?array $yeni = null,
        array $ekstra = [],
        ?string $konuAdi = null,
        ?int $userId = null,
    ): ?LogKayit {
        try {
            $request = request();
            $userAgent = $request?->userAgent();
            [$tarayici, $platform, $cihazTipi] = self::cihazBilgisi($userAgent);

            return LogKayit::query()->create([
                'kurs_id' => $kurs instanceof Kurs ? $kurs->getKey() : $kurs,
                'user_id' => $userId ?? Auth::id(),
                'islem' => $islem,
                'aciklama' => $aciklama,
                'konu_tipi' => $konu ? class_basename($konu) : null,
                'konu_id' => $konu?->getKey(),
                'konu_adi' => $konuAdi,
                'eski_veriler' => $eski !== null && $eski !== [] ? $eski : null,
                'yeni_veriler' => $yeni !== null && $yeni !== [] ? $yeni : null,
                'ekstra' => $ekstra !== [] ? $ekstra : null,
                'ip_adresi' => $request?->ip(),
                'user_agent' => $userAgent,
                'tarayici' => $tarayici,
                'platform' => $platform,
                'cihaz_tipi' => $cihazTipi,
                'http_metodu' => $request?->method(),
                'url' => $request?->fullUrl(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * User-Agent bilgisinden tarayıcı, işletim sistemi ve cihaz tipini çözümler.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    public static function cihazBilgisi(?string $userAgent): array
    {
        if (! $userAgent) {
            return [null, null, null];
        }

        $ua = $userAgent;

        // Tarayıcı tespiti (sıralama önemli: Edge/Opera Chrome içerir)
        $tarayici = match (true) {
            str_contains($ua, 'Edg/') || str_contains($ua, 'Edge') => 'Microsoft Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox') => 'Mozilla Firefox',
            str_contains($ua, 'Chrome') => 'Google Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            str_contains($ua, 'MSIE') || str_contains($ua, 'Trident') => 'Internet Explorer',
            default => 'Bilinmeyen Tarayıcı',
        };

        // İşletim sistemi tespiti
        $platform = match (true) {
            str_contains($ua, 'Windows NT 10.0') => 'Windows 10/11',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'iPod') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Bilinmeyen',
        };

        // Cihaz tipi tespiti
        $cihazTipi = match (true) {
            str_contains($ua, 'iPad') || (str_contains($ua, 'Tablet') && ! str_contains($ua, 'Mobile')) => 'Tablet',
            str_contains($ua, 'Mobile') || str_contains($ua, 'iPhone') || str_contains($ua, 'Android') => 'Mobil',
            default => 'Masaüstü',
        };

        return [$tarayici, $platform, $cihazTipi];
    }
}
