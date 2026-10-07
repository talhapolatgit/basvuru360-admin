<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('kres_basvurulari', 'kres_basvurulari_grup_id_index')) {
            Schema::table('kres_basvurulari', function (Blueprint $table) {
                $table->index('grup_id', 'kres_basvurulari_grup_id_index');
            });
        }

        if (Schema::hasIndex('kres_basvurulari', 'kres_basvurulari_grup_id_kisi_id_unique', 'unique')) {
            Schema::table('kres_basvurulari', function (Blueprint $table) {
                $table->dropUnique('kres_basvurulari_grup_id_kisi_id_unique');
            });
        }

        if (! Schema::hasColumn('kres_basvurulari', 'aktif_kayit_anahtari')) {
            Schema::table('kres_basvurulari', function (Blueprint $table) {
                $table->string('aktif_kayit_anahtari', 64)
                    ->nullable()
                    ->storedAs("IF(deleted_at IS NULL, CONCAT(grup_id, '-', kisi_id), NULL)");
            });
        }

        if (! Schema::hasIndex('kres_basvurulari', 'kres_basvurulari_aktif_kayit_anahtari_unique', 'unique')) {
            Schema::table('kres_basvurulari', function (Blueprint $table) {
                $table->unique('aktif_kayit_anahtari');
            });
        }
    }

    public function down(): void
    {
        Schema::table('kres_basvurulari', function (Blueprint $table) {
            $table->dropUnique(['aktif_kayit_anahtari']);
            $table->dropColumn('aktif_kayit_anahtari');
            $table->unique(['grup_id', 'kisi_id']);
        });

        Schema::table('kres_basvurulari', function (Blueprint $table) {
            $table->dropIndex('kres_basvurulari_grup_id_index');
        });
    }
};
