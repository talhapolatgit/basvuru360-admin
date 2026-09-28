<?php

use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const YETKILER = ['guvenilir_ip.goruntule', 'guvenilir_ip.guncelle'];

    public function up(): void
    {
        Schema::create('guvenilir_ip_adresleri', function (Blueprint $table) {
            $table->id();
            $table->string('ip_adresi', 64)->unique();
            $table->string('aciklama', 255)->nullable();
            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guncelleyen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $baseSira = (int) Yetki::query()->max('sira');
        $offset = 1;

        foreach (self::YETKILER as $kod) {
            $tanim = collect(YetkiKatalogu::tanimlar())->firstWhere('kod', $kod);
            if (! $tanim) {
                continue;
            }

            Yetki::query()->updateOrCreate(
                ['kod' => $kod],
                [
                    'ad' => $tanim['ad'],
                    'modul' => $tanim['modul'],
                    'aciklama' => $tanim['aciklama'] ?? null,
                    'sira' => $baseSira + $offset,
                ]
            );
            $offset++;
        }
    }

    public function down(): void
    {
        foreach (self::YETKILER as $kod) {
            $yetki = Yetki::query()->where('kod', $kod)->first();
            if ($yetki) {
                $yetki->roller()->detach();
                $yetki->delete();
            }
        }

        Schema::dropIfExists('guvenilir_ip_adresleri');
    }
};
