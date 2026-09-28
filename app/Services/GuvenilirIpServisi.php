<?php

namespace App\Services;

use App\Models\GuvenilirIpAdresi;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Güvenilir IP adresleri: burada tanımlı adres/aralıklar IP bazlı istek sınırlarına takılmaz.
 */
class GuvenilirIpServisi
{
    private const CACHE_KEY = 'guvenilir_ip_adresleri';

    /**
     * Çok geniş aralıklar tüm sınırları fiilen kapatacağından izin verilen en küçük önek uzunlukları.
     */
    public const MIN_IPV4_ONEK = 16;

    public const MIN_IPV6_ONEK = 48;

    public function guvenilirMi(?string $ip): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        $liste = $this->liste();

        return $liste !== [] && IpUtils::checkIp($ip, $liste);
    }

    /**
     * @return list<string>
     */
    public function liste(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => GuvenilirIpAdresi::query()
            ->pluck('ip_adresi')
            ->map(fn ($ip) => (string) $ip)
            ->values()
            ->all());
    }

    public static function onbellegiTemizle(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Tek IP (IPv4/IPv6) veya CIDR aralığı geçerliyse null, değilse hata mesajı döner.
     */
    public static function dogrula(string $deger): ?string
    {
        if (filter_var($deger, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (! str_contains($deger, '/')) {
            return 'Geçerli bir IP adresi veya CIDR aralığı girin (örn. 85.105.10.20 ya da 85.105.10.0/24).';
        }

        [$adres, $onek] = explode('/', $deger, 2);

        if (! ctype_digit($onek)) {
            return 'CIDR önek uzunluğu sayı olmalıdır.';
        }

        $onek = (int) $onek;

        if (filter_var($adres, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($onek > 32) {
                return 'IPv4 için önek uzunluğu en fazla 32 olabilir.';
            }
            if ($onek < self::MIN_IPV4_ONEK) {
                return 'Güvenlik nedeniyle IPv4 aralığı en fazla /'.self::MIN_IPV4_ONEK.' genişliğinde olabilir.';
            }

            return null;
        }

        if (filter_var($adres, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($onek > 128) {
                return 'IPv6 için önek uzunluğu en fazla 128 olabilir.';
            }
            if ($onek < self::MIN_IPV6_ONEK) {
                return 'Güvenlik nedeniyle IPv6 aralığı en fazla /'.self::MIN_IPV6_ONEK.' genişliğinde olabilir.';
            }

            return null;
        }

        return 'Geçerli bir IP adresi veya CIDR aralığı girin (örn. 85.105.10.20 ya da 85.105.10.0/24).';
    }
}
