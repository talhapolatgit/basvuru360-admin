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
            $table->foreignId('basari_durumu_id')
                ->nullable()
                ->after('durum')
                ->constrained('basari_durumlari')
                ->restrictOnDelete();
        });

        if (Schema::hasColumn('kurs_basvurulari', 'basari_durumu')) {
            $map = DB::table('basari_durumlari')->pluck('id', 'kod');

            foreach ($map as $kod => $id) {
                DB::table('kurs_basvurulari')
                    ->where('basari_durumu', $kod)
                    ->update(['basari_durumu_id' => $id]);
            }

            $belirsizId = $map['belirsiz'] ?? null;
            if ($belirsizId) {
                DB::table('kurs_basvurulari')
                    ->whereNull('basari_durumu_id')
                    ->update(['basari_durumu_id' => $belirsizId]);
            }

            Schema::table('kurs_basvurulari', function (Blueprint $table) {
                $table->dropColumn('basari_durumu');
            });
        }
    }

    public function down(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->string('basari_durumu')->default('belirsiz')->after('durum')->index();
        });

        $map = DB::table('basari_durumlari')->pluck('kod', 'id');

        foreach ($map as $id => $kod) {
            DB::table('kurs_basvurulari')
                ->where('basari_durumu_id', $id)
                ->update(['basari_durumu' => $kod]);
        }

        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->dropConstrainedForeignId('basari_durumu_id');
        });
    }
};
