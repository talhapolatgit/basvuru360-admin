<?php

namespace App\Services;

use App\Models\Numarator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NumaratorServisi
{
    public const KURS = 'kurs';

    public const ETKINLIK = 'etkinlik';

    private const MAX_DENEME = 10000;

    /**
     * Verilen kod için bir sonraki numarayı atomik olarak üretir.
     *
     * @param  (callable(string): bool)|null  $kullaniliyorMu  true dönerse numara atlanır
     */
    public function sonraki(string $kod, ?callable $kullaniliyorMu = null): string
    {
        return DB::transaction(function () use ($kod, $kullaniliyorMu) {
            $row = Numarator::query()
                ->where('kod', $kod)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                Numarator::query()->create([
                    'kod' => $kod,
                    'son_numara' => 0,
                ]);

                $row = Numarator::query()
                    ->where('kod', $kod)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $row) {
                throw new RuntimeException('Numaratör kaydı oluşturulamadı: '.$kod);
            }

            $aday = null;

            for ($i = 0; $i < self::MAX_DENEME; $i++) {
                $row->son_numara = (int) $row->son_numara + 1;
                $aday = (string) $row->son_numara;

                if (! $kullaniliyorMu || ! $kullaniliyorMu($aday)) {
                    $row->save();

                    return $aday;
                }
            }

            throw new RuntimeException('Boş numara bulunamadı: '.$kod);
        });
    }
}
