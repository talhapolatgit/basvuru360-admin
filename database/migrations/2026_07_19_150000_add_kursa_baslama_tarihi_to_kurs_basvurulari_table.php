<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->date('kursa_baslama_tarihi')->nullable()->after('basari_durumu_id');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('
                UPDATE kurs_basvurulari
                SET kursa_baslama_tarihi = (
                    SELECT kurslar.kurs_baslama_tarihi
                    FROM kurslar
                    WHERE kurslar.id = kurs_basvurulari.kurs_id
                )
                WHERE kursa_baslama_tarihi IS NULL
            ');
        } else {
            DB::statement('
                UPDATE kurs_basvurulari AS b
                INNER JOIN kurslar AS k ON k.id = b.kurs_id
                SET b.kursa_baslama_tarihi = k.kurs_baslama_tarihi
                WHERE b.kursa_baslama_tarihi IS NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->dropColumn('kursa_baslama_tarihi');
        });
    }
};
