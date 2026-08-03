<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $baseSira = (int) Yetki::query()->where('kod', 'kurs.tum_merkezler')->value('sira');
        if ($baseSira < 1) {
            $baseSira = (int) Yetki::query()->where('kod', 'kurs.goruntule')->value('sira');
        }

        $offset = 1;
        foreach (['kurs.sadece_kendi_kurum', 'kurs.tum_kurumlar'] as $kod) {
            $tanim = collect(YetkiKatalogu::tanimlar())->firstWhere('kod', $kod);
            if (! $tanim) {
                continue;
            }

            $yetki = Yetki::query()->updateOrCreate(
                ['kod' => $kod],
                [
                    'ad' => $tanim['ad'],
                    'modul' => $tanim['modul'],
                    'aciklama' => $tanim['aciklama'] ?? null,
                    'sira' => $baseSira + $offset,
                ]
            );
            $offset++;

            if ($kod === 'kurs.sadece_kendi_kurum') {
                $ogretmen = Rol::query()->where('kod', 'ogretmen')->first();
                if ($ogretmen && ! $ogretmen->tum_yetkiler) {
                    $ogretmen->yetkiler()->syncWithoutDetaching([$yetki->id]);
                }

                $personel = Rol::query()->where('kod', 'personel')->first();
                if ($personel) {
                    $personel->yetkiler()->detach($yetki->id);
                }
            }

            if ($kod === 'kurs.tum_kurumlar') {
                $personel = Rol::query()->where('kod', 'personel')->first();
                if ($personel && ! $personel->tum_yetkiler) {
                    $personel->yetkiler()->syncWithoutDetaching([$yetki->id]);
                }

                $ogretmen = Rol::query()->where('kod', 'ogretmen')->first();
                if ($ogretmen) {
                    $ogretmen->yetkiler()->detach($yetki->id);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['kurs.sadece_kendi_kurum', 'kurs.tum_kurumlar'] as $kod) {
            $yetki = Yetki::query()->where('kod', $kod)->first();
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }
    }
};
