<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tanim = collect(YetkiKatalogu::tanimlar())->firstWhere('kod', 'kurs.ogretmen_ata');
        if (! $tanim) {
            return;
        }

        $sira = (int) Yetki::query()->where('modul', 'kurs')->max('sira');

        $yetki = Yetki::query()->updateOrCreate(
            ['kod' => $tanim['kod']],
            [
                'ad' => $tanim['ad'],
                'modul' => $tanim['modul'],
                'aciklama' => $tanim['aciklama'] ?? null,
                'sira' => $sira + 1,
            ]
        );

        // Personel rolüne ekle; ayrıca kurs.guncelle yetkisi olan rollere de ver (geriye uyumluluk).
        $rolIds = Rol::query()
            ->where('kod', 'personel')
            ->orWhereHas('yetkiler', fn ($q) => $q->where('kod', 'kurs.guncelle'))
            ->pluck('id')
            ->unique();

        foreach ($rolIds as $rolId) {
            $rol = Rol::query()->find($rolId);
            if (! $rol || $rol->tum_yetkiler) {
                continue;
            }
            $rol->yetkiler()->syncWithoutDetaching([$yetki->id]);
        }
    }

    public function down(): void
    {
        $yetki = Yetki::query()->where('kod', 'kurs.ogretmen_ata')->first();
        if (! $yetki) {
            return;
        }

        $yetki->roller()->detach();
        $yetki->delete();
    }
};
