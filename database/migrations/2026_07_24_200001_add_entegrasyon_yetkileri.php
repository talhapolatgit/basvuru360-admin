<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $baseSira = (int) Yetki::query()->max('sira');
        $offset = 1;

        foreach (['entegrasyon.goruntule', 'entegrasyon.guncelle'] as $kod) {
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

            $personel = Rol::query()->where('kod', 'personel')->first();
            if ($personel && ! $personel->tum_yetkiler) {
                $personel->yetkiler()->syncWithoutDetaching([$yetki->id]);
            }
        }
    }

    public function down(): void
    {
        foreach (['entegrasyon.goruntule', 'entegrasyon.guncelle'] as $kod) {
            $yetki = Yetki::query()->where('kod', $kod)->first();
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }
    }
};
