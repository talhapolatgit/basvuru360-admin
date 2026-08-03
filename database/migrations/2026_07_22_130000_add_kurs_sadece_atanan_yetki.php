<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tanim = collect(YetkiKatalogu::tanimlar())->firstWhere('kod', 'kurs.sadece_atanan');
        if (! $tanim) {
            return;
        }

        $sira = (int) Yetki::query()->where('modul', 'kurs')->where('kod', 'kurs.goruntule')->value('sira');

        $yetki = Yetki::query()->updateOrCreate(
            ['kod' => $tanim['kod']],
            [
                'ad' => $tanim['ad'],
                'modul' => $tanim['modul'],
                'aciklama' => $tanim['aciklama'] ?? 'Seçiliyse kullanıcı yalnızca kendisine eğitmen olarak atanmış kursları listeler ve görüntüler.',
                'sira' => $sira + 1,
            ]
        );

        // Öğretmen rolüne varsayılan olarak ekle.
        $ogretmen = Rol::query()->where('kod', 'ogretmen')->first();
        if ($ogretmen && ! $ogretmen->tum_yetkiler) {
            $ogretmen->yetkiler()->syncWithoutDetaching([$yetki->id]);
        }

        // Personel rolünden çıkar (kısıtlayıcı yetki; personelde olmamalı).
        $personel = Rol::query()->where('kod', 'personel')->first();
        if ($personel) {
            $personel->yetkiler()->detach($yetki->id);
        }
    }

    public function down(): void
    {
        $yetki = Yetki::query()->where('kod', 'kurs.sadece_atanan')->first();
        if (! $yetki) {
            return;
        }

        $yetki->roller()->detach();
        $yetki->delete();
    }
};
