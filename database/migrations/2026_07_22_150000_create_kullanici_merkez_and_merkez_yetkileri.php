<?php

use App\Models\Rol;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kullanici_merkez', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('merkez_id')->constrained('merkezler')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'merkez_id']);
        });

        $this->syncYetkiler();
    }

    public function down(): void
    {
        foreach (['kurs.sadece_yetkili_merkez', 'kurs.tum_merkezler'] as $kod) {
            $yetki = Yetki::query()->where('kod', $kod)->first();
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }

        Schema::dropIfExists('kullanici_merkez');
    }

    private function syncYetkiler(): void
    {
        $baseSira = (int) Yetki::query()->where('kod', 'kurs.sadece_atanan')->value('sira');
        if ($baseSira < 1) {
            $baseSira = (int) Yetki::query()->where('kod', 'kurs.goruntule')->value('sira');
        }

        $offset = 1;
        foreach (['kurs.sadece_yetkili_merkez', 'kurs.tum_merkezler'] as $kod) {
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

            if ($kod === 'kurs.sadece_yetkili_merkez') {
                $ogretmen = Rol::query()->where('kod', 'ogretmen')->first();
                if ($ogretmen && ! $ogretmen->tum_yetkiler) {
                    $ogretmen->yetkiler()->syncWithoutDetaching([$yetki->id]);
                }

                $personel = Rol::query()->where('kod', 'personel')->first();
                if ($personel) {
                    $personel->yetkiler()->detach($yetki->id);
                }
            }

            if ($kod === 'kurs.tum_merkezler') {
                $personel = Rol::query()->where('kod', 'personel')->first();
                if ($personel && ! $personel->tum_yetkiler) {
                    $personel->yetkiler()->syncWithoutDetaching([$yetki->id]);
                }

                $ogretmen = Rol::query()->where('kod', 'ogretmen')->first();
                if ($ogretmen) {
                    $ogretmen->yetkiler()->detach($yetki->id);
                }
            }
        }
    }
};
