<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Yeni yetki => bu yetkiye sahip rollere otomatik verilecek mevcut yetki. */
    private const YETKILER = [
        'merkez.sil' => 'merkez.guncelle',
        'alan.sil' => 'alan.guncelle',
        'brans.sil' => 'brans.guncelle',
    ];

    public function up(): void
    {
        foreach (self::YETKILER as $kod => $kaynakKod) {
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
                    'sira' => ((int) Yetki::query()->max('sira')) + 1,
                ]
            );

            Rol::query()
                ->where('tum_yetkiler', false)
                ->whereHas('yetkiler', fn ($q) => $q->where('kod', $kaynakKod))
                ->each(function (Rol $rol) use ($yetki) {
                    $rol->yetkiler()->syncWithoutDetaching([$yetki->id]);
                });
        }
    }

    public function down(): void
    {
        Yetki::query()->whereIn('kod', array_keys(self::YETKILER))->get()->each(function (Yetki $yetki) {
            $yetki->roller()->detach();
            $yetki->delete();
        });
    }
};
