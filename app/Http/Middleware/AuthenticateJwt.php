<?php

namespace App\Http\Middleware;

use App\Services\Jwt\JwtTokenServisi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class AuthenticateJwt
{
    public function __construct(private readonly JwtTokenServisi $jwt) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->bearerToken($request);

        if ($token === null) {
            return response()->json([
                'success' => false,
                'message' => 'Yetkilendirme gerekli.',
                'errors' => null,
            ], 401);
        }

        try {
            $kisi = $this->jwt->kisi($token, JwtTokenServisi::TIP_ACCESS);
        } catch (UnexpectedValueException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Geçersiz veya süresi dolmuş token.',
                'errors' => null,
            ], 401);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz veya süresi dolmuş token.',
                'errors' => null,
            ], 401);
        }

        auth('api')->setUser($kisi);
        $request->setUserResolver(static fn () => $kisi);
        $request->attributes->set('jwt_token', $token);

        return $next($request);
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
