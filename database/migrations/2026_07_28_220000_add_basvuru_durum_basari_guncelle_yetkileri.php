<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $kodlar = ['basvuru.durum_guncelle', 'basvuru.basari_guncelle'];
        $maxSira = (int) Yetki::query()->max('sira');
        $yeniYetkiIds = [];

        foreach ($kodlar as $kod) {
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
            $yeniYetkiIds[] = $yetki->id;
        }

        if ($yeniYetkiIds === []) {
            return;
        }

        // Daha önce "Başvuru Güncelle" yetkisi olan rollere otomatik ver (davranış bozulmasın).
        $guncelleYetkiId = Yetki::query()->where('kod', 'basvuru.guncelle')->value('id');
        if (! $guncelleYetkiId) {
            $personel = Rol::query()->where('kod', 'personel')->first();
            if ($personel && ! $personel->tum_yetkiler) {
                $personel->yetkiler()->syncWithoutDetaching($yeniYetkiIds);
            }

            return;
        }

        Rol::query()
            ->where('tum_yetkiler', false)
            ->whereHas('yetkiler', fn ($q) => $q->whereKey($guncelleYetkiId))
            ->each(function (Rol $rol) use ($yeniYetkiIds) {
                $rol->yetkiler()->syncWithoutDetaching($yeniYetkiIds);
            });
    }

    public function down(): void
    {
        foreach (['basvuru.durum_guncelle', 'basvuru.basari_guncelle'] as $kod) {
            $yetki = Yetki::query()->where('kod', $kod)->first();
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }
    }
};
