<?php

namespace App\Http\Controllers\Concerns;

use App\Models\KresDonem;
use App\Services\KresDonemBaglami;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ResolvesKresDonem
{
    protected function donemBaglami(Request $request): ?KresDonem
    {
        return app(KresDonemBaglami::class)->resolve($request);
    }

    /**
     * @return Collection<int, KresDonem>
     */
    protected function donemListesi(): Collection
    {
        return app(KresDonemBaglami::class)->liste();
    }

    /**
     * @return array{aktifDonem: ?KresDonem, donemler: Collection<int, KresDonem>}
     */
    protected function donemViewData(Request $request): array
    {
        return [
            'aktifDonem' => $this->donemBaglami($request),
            'donemler' => $this->donemListesi(),
        ];
    }
}
