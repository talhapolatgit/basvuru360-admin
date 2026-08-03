<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $kodlar = array_values(array_filter(
            YetkiKatalogu::kodlar(),
            fn (string $kod) => str_starts_with($kod, 'etkinlik.') || str_starts_with($kod, 'etkinlik_basvuru.')
        ));

        $baseSira = (int) Yetki::query()->max('sira');
        $offset = 1;

        $personel = Rol::query()->where('kod', 'personel')->first();
        $ogretmen = Rol::query()->where('kod', 'ogretmen')->first();
        $ogretmenKodlari = YetkiKatalogu::ogretmenYetkileri();
        $personelKodlari = YetkiKatalogu::personelYetkileri();

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

            if ($personel && ! $personel->tum_yetkiler) {
                if (in_array($kod, $personelKodlari, true)) {
                    $personel->yetkiler()->syncWithoutDetaching([$yetki->id]);
                } else {
                    $personel->yetkiler()->detach($yetki->id);
                }
            }

            if ($ogretmen && ! $ogretmen->tum_yetkiler) {
                if (in_array($kod, $ogretmenKodlari, true)) {
                    $ogretmen->yetkiler()->syncWithoutDetaching([$yetki->id]);
                } else {
                    $ogretmen->yetkiler()->detach($yetki->id);
                }
            }
        }
    }

    public function down(): void
    {
        $kodlar = Yetki::query()
            ->where('kod', 'like', 'etkinlik.%')
            ->orWhere('kod', 'like', 'etkinlik_basvuru.%')
            ->pluck('id');

        foreach ($kodlar as $id) {
            $yetki = Yetki::query()->find($id);
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }
    }
};
