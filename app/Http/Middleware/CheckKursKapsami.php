<?php

namespace App\Http\Middleware;

use App\Models\Kurs;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * kurs.sadece_atanan / merkez / kurum kapsam yetkilerine göre
 * kurs kaydına erişimi kısıtlar.
 */
class CheckKursKapsami
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $kurs = $request->route('kurs');

        if ($user && $kurs instanceof Kurs && $user->sadeceAtananKurslariGorur()) {
            abort_unless(
                $user->atanmisKursMu($kurs),
                403,
                'Yalnızca size atanmış kursları görüntüleyebilirsiniz.'
            );
        }

        if ($user && $kurs instanceof Kurs && $user->sadeceYetkiliMerkezleriGorur()) {
            abort_unless(
                $user->atanmisMerkezMu((int) $kurs->merkez_id),
                403,
                'Yalnızca yetkilendirildiğiniz merkezlerin kurslarını görüntüleyebilirsiniz.'
            );
        }

        if ($user && $kurs instanceof Kurs && $user->sadeceKendiKurumlariniGorur()) {
            abort_unless(
                $user->kursKendiKurumKapsamindaMi($kurs),
                403,
                'Yalnızca kendi kurumlarınıza ait kursları görüntüleyebilirsiniz.'
            );
        }

        return $next($request);
    }
}
