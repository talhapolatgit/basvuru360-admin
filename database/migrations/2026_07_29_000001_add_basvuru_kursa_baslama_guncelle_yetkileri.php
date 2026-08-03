<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $kod = 'basvuru.kursa_baslama_guncelle';
        $tanim = collect(YetkiKatalogu::tanimlar())->firstWhere('kod', $kod);
        if (! $tanim) {
            return;
        }

        $maxSira = (int) Yetki::query()->max('sira');
        $maxSira++;

        $yetki = Yetki::query()->updateOrCreate(
            ['kod' => $kod],
            [
                'ad' => $tanim['ad'],
                'modul' => $tanim['modul'],
                'aciklama' => $tanim['aciklama'] ?? null,
                'sira' => $maxSira,
            ]
        );

        $guncelleYetkiId = Yetki::query()->where('kod', 'basvuru.guncelle')->value('id');
        if (! $guncelleYetkiId) {
            return;
        }

        // Daha önce "Başvuru Güncelle" yetkisi olan rollere otomatik ver.
        Rol::query()
            ->where('tum_yetkiler', false)
            ->whereHas('yetkiler', fn ($q) => $q->whereKey($guncelleYetkiId))
            ->each(function (Rol $rol) use ($yetki) {
                $rol->yetkiler()->syncWithoutDetaching([$yetki->id]);
            });
    }

    public function down(): void
    {
        $yetki = Yetki::query()->where('kod', 'basvuru.kursa_baslama_guncelle')->first();
        if (! $yetki) {
            return;
        }

        $yetki->roller()->detach();
        $yetki->delete();
    }
};

