<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entegrasyon_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->string('tur', 50)->unique();
            $table->string('aktif_saglayici', 80);
            $table->json('ayarlar')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach ((array) config('entegrasyonlar.varsayilanlar', []) as $tur => $saglayici) {
            DB::table('entegrasyon_ayarlari')->insert([
                'tur' => $tur,
                'aktif_saglayici' => $saglayici,
                'ayarlar' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entegrasyon_ayarlari');
    }
};
