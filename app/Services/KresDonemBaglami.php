<?php

namespace App\Services;

use App\Models\KresDonem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KresDonemBaglami
{
    public const SESSION_KEY = 'kres_donem_id';

    public function resolve(?Request $request = null): ?KresDonem
    {
        $request ??= request();

        if ($request->filled('donem_id')) {
            $donem = KresDonem::query()->whereKey((int) $request->input('donem_id'))->first();
            if ($donem) {
                $request->session()->put(self::SESSION_KEY, $donem->id);

                return $donem;
            }
        }

        $sessionId = $request->session()->get(self::SESSION_KEY);
        if ($sessionId) {
            $donem = KresDonem::query()->whereKey((int) $sessionId)->first();
            if ($donem) {
                return $donem;
            }
        }

        $aktif = KresDonem::query()->where('aktif', true)->orderByDesc('baslangic')->orderByDesc('id')->first();
        if ($aktif) {
            $request->session()->put(self::SESSION_KEY, $aktif->id);

            return $aktif;
        }

        $any = KresDonem::query()->orderByDesc('baslangic')->orderByDesc('id')->first();
        if ($any) {
            $request->session()->put(self::SESSION_KEY, $any->id);
        }

        return $any;
    }

    public function set(KresDonem $donem, ?Request $request = null): void
    {
        ($request ?? request())->session()->put(self::SESSION_KEY, $donem->id);
    }

    /**
     * @return Collection<int, KresDonem>
     */
    public function liste(): Collection
    {
        return KresDonem::query()
            ->orderByDesc('aktif')
            ->orderByDesc('baslangic')
            ->orderByDesc('id')
            ->get();
    }
}
