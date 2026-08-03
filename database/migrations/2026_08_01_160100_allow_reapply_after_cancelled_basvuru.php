<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unique index ayni zamanda kisi_id FK icin kullanildigindan
        // once alternatif index eklenmeli, sonra unique dusurulmeli.
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->index(['kisi_id', 'kurs_id'], 'kurs_basvurulari_kisi_id_kurs_id_index');
        });

        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->dropUnique('kurs_basvurulari_kisi_id_kurs_id_unique');
        });

        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            $table->index(['kisi_id', 'etkinlik_id'], 'etkinlik_basvurulari_kisi_id_etkinlik_id_index');
        });

        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            $table->dropUnique('etkinlik_basvurulari_kisi_id_etkinlik_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->unique(['kisi_id', 'kurs_id'], 'kurs_basvurulari_kisi_id_kurs_id_unique');
        });

        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->dropIndex('kurs_basvurulari_kisi_id_kurs_id_index');
        });

        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            $table->unique(['kisi_id', 'etkinlik_id'], 'etkinlik_basvurulari_kisi_id_etkinlik_id_unique');
        });

        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            $table->dropIndex('etkinlik_basvurulari_kisi_id_etkinlik_id_index');
        });
    }
};