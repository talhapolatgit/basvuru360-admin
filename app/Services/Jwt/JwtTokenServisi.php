<?php

namespace App\Services\Jwt;

use App\Models\Kisi;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;
use UnexpectedValueException;

class JwtTokenServisi
{
    public const TIP_ACCESS = 'access';

    public const TIP_REFRESH = 'refresh';

    public const SUBJECT_KISI = 'kisi';

    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int}
     */
    public function tokenCiftiOlustur(Kisi $kisi): array
    {
        $accessTtl = $this->accessTtlSaniye();
        $refreshTtl = $this->refreshTtlSaniye();

        return [
            'access_token' => $this->olustur($kisi, self::TIP_ACCESS, $accessTtl),
            'refresh_token' => $this->olustur($kisi, self::TIP_REFRESH, $refreshTtl),
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
        ];
    }

    public function olustur(Kisi $kisi, string $tip, ?int $ttlSaniye = null): string
    {
        $ttlSaniye ??= $tip === self::TIP_REFRESH
            ? $this->refreshTtlSaniye()
            : $this->accessTtlSaniye();

        $now = time();
        $jti = (string) Str::uuid();

        $payload = [
            'iss' => (string) config('jwt.issuer'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSaniye,
            'jti' => $jti,
            'sub' => (string) $kisi->getAuthIdentifier(),
            'prv' => self::SUBJECT_KISI,
            'typ' => $tip,
        ];

        return JWT::encode($payload, $this->secret(), (string) config('jwt.algo', 'HS256'));
    }

    /**
     * @throws UnexpectedValueException
     */
    public function coz(string $token): stdClass
    {
        $decoded = JWT::decode($token, new Key($this->secret(), (string) config('jwt.algo', 'HS256')));

        if ($this->karaListedeMi((string) ($decoded->jti ?? ''))) {
            throw new UnexpectedValueException('Token geçersiz kılınmış.');
        }

        $issuer = (string) config('jwt.issuer');
        if ($issuer !== '' && (string) ($decoded->iss ?? '') !== $issuer) {
            throw new UnexpectedValueException('Token issuer geçersiz.');
        }

        if ((string) ($decoded->prv ?? '') !== self::SUBJECT_KISI) {
            throw new UnexpectedValueException('Token konusu geçersiz.');
        }

        return $decoded;
    }

    public function kisi(string $token, string $beklenenTip = self::TIP_ACCESS): Kisi
    {
        $payload = $this->coz($token);

        if ((string) ($payload->typ ?? '') !== $beklenenTip) {
            throw new UnexpectedValueException('Token tipi geçersiz.');
        }

        $kisiId = (int) ($payload->sub ?? 0);
        /** @var Kisi|null $kisi */
        $kisi = Kisi::query()->find($kisiId);

        if (! $kisi) {
            throw new UnexpectedValueException('Kişi bulunamadı.');
        }

        if (! $kisi->aktif) {
            throw new UnexpectedValueException('Hesap pasif veya bloke.');
        }

        return $kisi;
    }

    public function invalidate(string $token): void
    {
        if (! config('jwt.blacklist_enabled', true)) {
            return;
        }

        try {
            $payload = JWT::decode($token, new Key($this->secret(), (string) config('jwt.algo', 'HS256')));
        } catch (\Throwable) {
            return;
        }

        $jti = (string) ($payload->jti ?? '');
        $exp = (int) ($payload->exp ?? 0);
        if ($jti === '' || $exp <= time()) {
            return;
        }

        Cache::put($this->blacklistKey($jti), true, $exp - time());
    }

    public function accessTtlSaniye(): int
    {
        return max(60, (int) config('jwt.access_ttl', 60) * 60);
    }

    public function refreshTtlSaniye(): int
    {
        return max(60, (int) config('jwt.refresh_ttl', 60 * 24 * 14) * 60);
    }

    private function karaListedeMi(string $jti): bool
    {
        if ($jti === '' || ! config('jwt.blacklist_enabled', true)) {
            return false;
        }

        return Cache::has($this->blacklistKey($jti));
    }

    private function blacklistKey(string $jti): string
    {
        return 'jwt:blacklist:'.$jti;
    }

    private function secret(): string
    {
        $secret = (string) (config('jwt.secret') ?: config('app.key'));

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);
            if ($decoded === false || $decoded === '') {
                throw new RuntimeException('JWT secret çözülemedi.');
            }

            return $decoded;
        }

        if ($secret === '') {
            throw new RuntimeException('JWT secret tanımlı değil. JWT_SECRET veya APP_KEY ayarlayın.');
        }

        return $secret;
    }
}
