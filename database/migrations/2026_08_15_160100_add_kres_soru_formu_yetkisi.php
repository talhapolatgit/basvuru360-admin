<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $kod = 'kres.soru_formu_yonet';
        $tanim = collect(YetkiKatalogu::tanimlar())->firstWhere('kod', $kod);
        if (! $tanim) {
            return;
        }

        $yetki = Yetki::query()->updateOrCreate(
            ['kod' => $kod],
            [
                'ad' => $tanim['ad'],
                'modul' => $tanim['modul'],
                'aciklama' => $tanim['aciklama'] ?? null,
                'sira' => ((int) Yetki::query()->max('sira')) + 1,
            ]
        );

        $personel = Rol::query()->where('kod', 'personel')->first();
        if ($personel && ! $personel->tum_yetkiler) {
            $personel->yetkiler()->syncWithoutDetaching([$yetki->id]);
        }
    }

    public function down(): void
    {
        $yetki = Yetki::query()->where('kod', 'kres.soru_formu_yonet')->first();
        if ($yetki) {
            $yetki->roller()->detach();
            $yetki->delete();
        }
    }
};
