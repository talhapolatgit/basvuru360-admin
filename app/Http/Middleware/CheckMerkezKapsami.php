<?php

namespace App\Http\Middleware;

use App\Models\Merkez;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * kurs.sadece_yetkili_merkez yetkisi olan kullanıcıların yalnızca
 * kendilerine yetkilendirilmiş merkez kayıtlarına erişmesini sağlar.
 */
class CheckMerkezKapsami
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $merkez = $request->route('merkez');

        if ($user && $merkez instanceof Merkez && $user->sadeceYetkiliMerkezleriGorur()) {
            abort_unless(
                $user->atanmisMerkezMu($merkez),
                403,
                'Yalnızca yetkilendirildiğiniz merkezleri görüntüleyebilirsiniz.'
            );
        }

        return $next($request);
    }
}
