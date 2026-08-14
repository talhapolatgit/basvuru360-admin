<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $kodlar = [
            'kres.goruntule',
            'kres.donem_yonet',
            'kres.okul_yonet',
            'kres.grup_yonet',
            'kres.basvuru_goruntule',
            'kres.basvuru_olustur',
            'kres.basvuru_guncelle',
            'kres.basvuru_durum_guncelle',
        ];

        $baseSira = (int) Yetki::query()->max('sira');
        $offset = 1;
        $ids = [];

        foreach ($kodlar as $kod) {
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
            $ids[] = $yetki->id;
        }

        $personel = Rol::query()->where('kod', 'personel')->first();
        if ($personel && ! $personel->tum_yetkiler && $ids !== []) {
            $personel->yetkiler()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        $kodlar = [
            'kres.goruntule',
            'kres.donem_yonet',
            'kres.okul_yonet',
            'kres.grup_yonet',
            'kres.basvuru_goruntule',
            'kres.basvuru_olustur',
            'kres.basvuru_guncelle',
            'kres.basvuru_durum_guncelle',
        ];

        foreach ($kodlar as $kod) {
            $yetki = Yetki::query()->where('kod', $kod)->first();
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }
    }
};
