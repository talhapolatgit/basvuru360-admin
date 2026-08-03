<?php

namespace App\Http\Middleware;

use App\Models\Etkinlik;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * etkinlik.sadece_atanan / merkez / kurum kapsam yetkilerine göre
 * etkinlik kaydına erişimi kısıtlar.
 */
class CheckEtkinlikKapsami
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $etkinlik = $request->route('etkinlik');

        if ($user && $etkinlik instanceof Etkinlik && $user->sadeceAtananEtkinlikleriGorur()) {
            abort_unless(
                $user->atanmisEtkinlikMu($etkinlik),
                403,
                'Yalnızca sorumlu olduğunuz etkinlikleri görüntüleyebilirsiniz.'
            );
        }

        if ($user && $etkinlik instanceof Etkinlik && $user->sadeceYetkiliMerkezleriGorurEtkinlik()) {
            abort_unless(
                $user->atanmisMerkezMu((int) $etkinlik->merkez_id),
                403,
                'Yalnızca yetkilendirildiğiniz merkezlerin etkinliklerini görüntüleyebilirsiniz.'
            );
        }

        if ($user && $etkinlik instanceof Etkinlik && $user->sadeceKendiKurumlariniGorurEtkinlik()) {
            abort_unless(
                $user->etkinlikKendiKurumKapsamindaMi($etkinlik),
                403,
                'Yalnızca kendi kurumlarınıza ait etkinlikleri görüntüleyebilirsiniz.'
            );
        }

        return $next($request);
    }
}
