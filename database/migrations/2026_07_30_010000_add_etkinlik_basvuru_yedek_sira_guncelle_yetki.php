<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $kod = 'etkinlik_basvuru.yedek_sira_guncelle';
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

        // Daha önce "Etkinlik Başvurusu Güncelle" yetkisi olan rollere otomatik ver.
        Rol::query()
            ->where('tum_yetkiler', false)
            ->whereHas('yetkiler', fn ($q) => $q->where('kod', 'etkinlik_basvuru.guncelle'))
            ->each(function (Rol $rol) use ($yetki) {
                $rol->yetkiler()->syncWithoutDetaching([$yetki->id]);
            });
    }

    public function down(): void
    {
        $yetki = Yetki::query()->where('kod', 'etkinlik_basvuru.yedek_sira_guncelle')->first();
        if ($yetki) {
            $yetki->roller()->detach();
            $yetki->delete();
        }
    }
};
