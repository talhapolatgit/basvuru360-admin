<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $maxSira = (int) Yetki::query()->max('sira');

        $tanimlar = [
            'kurs.yoklama_goruntule' => 'kurs.yoklama',
            'kurs.mesaj_goruntule' => null,
        ];

        foreach ($tanimlar as $kod => $kaynakKod) {
            $tanim = collect(YetkiKatalogu::tanimlar())->firstWhere('kod', $kod);
            if (! $tanim) {
                continue;
            }

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

            $kaynakKodlar = $kaynakKod
                ? [$kaynakKod]
                : ['kurs.sms', 'kurs.eposta'];

            Rol::query()
                ->where('tum_yetkiler', false)
                ->whereHas('yetkiler', fn ($q) => $q->whereIn('kod', $kaynakKodlar))
                ->each(function (Rol $rol) use ($yetki) {
                    $rol->yetkiler()->syncWithoutDetaching([$yetki->id]);
                });
        }
    }

    public function down(): void
    {
        foreach (['kurs.yoklama_goruntule', 'kurs.mesaj_goruntule'] as $kod) {
            $yetki = Yetki::query()->where('kod', $kod)->first();
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }
    }
};
