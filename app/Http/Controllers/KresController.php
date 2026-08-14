<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesKresDonem;
use App\Models\KresBasvuru;
use App\Models\KresBasvuruDurum;
use App\Models\KresGrup;
use App\Models\KresOkul;
use App\Services\KresDonemBaglami;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KresController extends Controller
{
    use ResolvesKresDonem;

    public function index(Request $request): View|RedirectResponse
    {
        $donemData = $this->donemViewData($request);
        $aktifDonem = $donemData['aktifDonem'];

        if ($request->filled('donem_id') && $aktifDonem) {
            return redirect()->route('kres.index');
        }

        $kesinId = KresBasvuruDurum::idByKod('kesin_kayit');
        $yedekId = KresBasvuruDurum::idByKod('yedek');

        $okullar = KresOkul::query()
            ->orderBy('ad')
            ->get()
            ->map(function (KresOkul $okul) use ($aktifDonem, $kesinId, $yedekId) {
                $grupQuery = KresGrup::query()->where('okul_id', $okul->id);
                if ($aktifDonem) {
                    $grupQuery->where('donem_id', $aktifDonem->id);
                } else {
                    $grupQuery->whereRaw('0 = 1');
                }

                $grupIds = (clone $grupQuery)->pluck('id');
                $grupSayisi = $grupIds->count();

                $kesin = $grupIds->isEmpty() || ! $kesinId
                    ? 0
                    : KresBasvuru::query()
                        ->whereIn('grup_id', $grupIds)
                        ->where('durum_id', $kesinId)
                        ->count();

                $yedek = $grupIds->isEmpty() || ! $yedekId
                    ? 0
                    : KresBasvuru::query()
                        ->whereIn('grup_id', $grupIds)
                        ->where('durum_id', $yedekId)
                        ->count();

                $kontenjan = (int) (clone $grupQuery)->sum('kontenjan');

                return [
                    'model' => $okul,
                    'grup_sayisi' => $grupSayisi,
                    'kesin_kayit' => $kesin,
                    'yedek' => $yedek,
                    'kontenjan' => $kontenjan,
                    'doluluk' => $kontenjan > 0 ? min(100, (int) round(($kesin / $kontenjan) * 100)) : 0,
                ];
            });

        return view('kres.index', $donemData + [
            'okullar' => $okullar,
            'step' => 1,
        ]);
    }

    public function setDonem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'donem_id' => ['required', 'integer', 'exists:kres_donemler,id'],
        ]);

        $donem = \App\Models\KresDonem::query()->findOrFail($validated['donem_id']);
        app(KresDonemBaglami::class)->set($donem, $request);

        $redirect = $request->input('redirect');
        if (is_string($redirect) && str_starts_with($redirect, '/')) {
            return redirect($redirect);
        }

        return redirect()->route('kres.index');
    }
}
