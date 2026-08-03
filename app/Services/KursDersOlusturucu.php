<?php

namespace App\Services;

use App\Models\Kurs;
use App\Models\KursDers;
use App\Models\KursGun;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class KursDersOlusturucu
{
    /**
     * Kurs başlama–bitiş aralığında, tanımlı hafta günlerine göre ders oturumlarını üretir.
     * Mevcut aynı tarih+saat kayıtları korunur (güncellenir); yeni olanlar eklenir;
     * aralık/programa uymayanlar soft-delete edilir.
     *
     * @return array{olusturulan: int, guncellenen: int, silinen: int}
     */
    public function sync(Kurs $kurs, ?int $userId = null): array
    {
        $kurs->loadMissing('gunler');

        if (! $kurs->kurs_baslama_tarihi || ! $kurs->kurs_bitis_tarihi || $kurs->gunler->isEmpty()) {
            $silinen = KursDers::query()->where('kurs_id', $kurs->id)->delete();

            return ['olusturulan' => 0, 'guncellenen' => 0, 'silinen' => $silinen];
        }

        $olusturulan = 0;
        $guncellenen = 0;
        $silinen = 0;
        $korunanIds = [];

        $gunHaritasi = $kurs->gunler->groupBy(
            fn (KursGun $gun) => $gun->gun?->carbonIso()
        );

        $period = CarbonPeriod::create(
            $kurs->kurs_baslama_tarihi->copy()->startOfDay(),
            $kurs->kurs_bitis_tarihi->copy()->startOfDay()
        );

        DB::transaction(function () use ($period, $gunHaritasi, $kurs, $userId, &$olusturulan, &$guncellenen, &$silinen, &$korunanIds) {
            // Tarihi manuel olarak değiştirilmiş (taşınmış) dersler; bunlar programın
            // kendi akışıyla asla yeniden oluşturulmamalı veya silinmemelidir.
            $tasinanlar = KursDers::withTrashed()
                ->where('kurs_id', $kurs->id)
                ->whereNotNull('orijinal_tarih')
                ->get()
                ->keyBy(fn (KursDers $ders) => $ders->kurs_gun_id.'|'.$ders->orijinal_tarih->toDateString());

            foreach ($tasinanlar as $tasinan) {
                if ($tasinan->trashed()) {
                    $tasinan->restore();
                }
                $korunanIds[] = $tasinan->id;
            }

            foreach ($period as $gun) {
                /** @var Carbon $gun */
                $iso = $gun->dayOfWeekIso;
                $slots = $gunHaritasi->get($iso, collect());

                foreach ($slots as $slot) {
                    /** @var KursGun $slot */

                    // Bu program yuvası (gün + saat) başka bir tarihe taşınmış bir dersin
                    // aslıysa, burada tekrar bir kayıt oluşturulmamalı.
                    if ($tasinanlar->has($slot->id.'|'.$gun->toDateString())) {
                        continue;
                    }

                    $attrs = [
                        'kurs_gun_id' => $slot->id,
                        'bitis_saati' => $slot->bitis_saati,
                        'ders_saati' => $slot->ders_saati,
                        'sinif' => $slot->sinif,
                        'guncelleyen_id' => $userId,
                    ];

                    $existing = KursDers::withTrashed()
                        ->where('kurs_id', $kurs->id)
                        ->whereDate('tarih', $gun->toDateString())
                        ->where('baslangic_saati', $slot->baslangic_saati)
                        ->first();

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update($attrs);
                        $korunanIds[] = $existing->id;
                        $guncellenen++;
                    } else {
                        $created = KursDers::create($attrs + [
                            'kurs_id' => $kurs->id,
                            'tarih' => $gun->toDateString(),
                            'baslangic_saati' => $slot->baslangic_saati,
                            'olusturan_id' => $userId,
                        ]);
                        $korunanIds[] = $created->id;
                        $olusturulan++;
                    }
                }
            }

            $silinecek = KursDers::query()
                ->where('kurs_id', $kurs->id)
                ->when(
                    $korunanIds !== [],
                    fn ($query) => $query->whereNotIn('id', $korunanIds)
                );

            $silinen = (clone $silinecek)->count();
            $silinecek->delete();
        });

        return compact('olusturulan', 'guncellenen', 'silinen');
    }
}
