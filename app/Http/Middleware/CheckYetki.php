<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckYetki
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$yetkiler  Virgülle veya ayrı argümanlarla yetki kodları (OR)
     */
    public function handle(Request $request, Closure $next, string ...$yetkiler): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $kodlar = [];
        foreach ($yetkiler as $parca) {
            foreach (explode(',', $parca) as $kod) {
                $kod = trim($kod);
                if ($kod !== '') {
                    $kodlar[] = $kod;
                }
            }
        }

        if ($kodlar === []) {
            return $next($request);
        }

        if (! $user->hasAnyYetki($kodlar)) {
            abort(403, 'Bu işlem için yetkiniz bulunmuyor.');
        }

        return $next($request);
    }
}
