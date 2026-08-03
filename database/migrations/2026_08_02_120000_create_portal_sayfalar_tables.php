<?php

use App\Models\PortalSayfa;
use App\Models\PortalSayfaKurali;
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
        Schema::create('portal_sayfalar', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 50)->nullable()->unique();
            $table->string('baslik', 200);
            $table->string('slug', 120)->unique();
            $table->boolean('sistem')->default(false);
            $table->boolean('menude_goster')->default(true);
            $table->unsignedInteger('sira')->default(0);
            $table->timestamps();
        });

        Schema::create('portal_sayfa_kurallari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_sayfa_id')->constrained('portal_sayfalar')->cascadeOnDelete();
            $table->string('kaynak', 20);
            $table->string('secim_tipi', 20);
            $table->unsignedBigInteger('hedef_id')->nullable();
            $table->unsignedInteger('sira')->default(0);
            $table->timestamps();

            $table->index(['portal_sayfa_id', 'kaynak']);
        });

        $kurslar = PortalSayfa::query()->create([
            'kod' => 'kurslar',
            'baslik' => 'Kurslar',
            'slug' => 'kurslar',
            'sistem' => true,
            'menude_goster' => true,
            'sira' => 1,
        ]);

        PortalSayfaKurali::query()->create([
            'portal_sayfa_id' => $kurslar->id,
            'kaynak' => 'kurs',
            'secim_tipi' => 'tum',
            'hedef_id' => null,
            'sira' => 1,
        ]);

        $etkinlikler = PortalSayfa::query()->create([
            'kod' => 'etkinlikler',
            'baslik' => 'Etkinlikler',
            'slug' => 'etkinlikler',
            'sistem' => true,
            'menude_goster' => true,
            'sira' => 2,
        ]);

        PortalSayfaKurali::query()->create([
            'portal_sayfa_id' => $etkinlikler->id,
            'kaynak' => 'etkinlik',
            'secim_tipi' => 'tum',
            'hedef_id' => null,
            'sira' => 1,
        ]);

        $baseSira = (int) Yetki::query()->max('sira');
        $offset = 1;

        foreach (['portal_ayar.goruntule', 'portal_ayar.guncelle'] as $kod) {
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

            $personel = Rol::query()->where('kod', 'personel')->first();
            if ($personel && ! $personel->tum_yetkiler) {
                $personel->yetkiler()->syncWithoutDetaching([$yetki->id]);
            }
        }
    }

    public function down(): void
    {
        foreach (['portal_ayar.goruntule', 'portal_ayar.guncelle'] as $kod) {
            $yetki = Yetki::query()->where('kod', $kod)->first();
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }

        Schema::dropIfExists('portal_sayfa_kurallari');
        Schema::dropIfExists('portal_sayfalar');
    }
};
