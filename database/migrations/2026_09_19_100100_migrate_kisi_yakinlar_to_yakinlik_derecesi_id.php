<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kisi_yakinlar')) {
            return;
        }

        // Yeni kurulumda tablo zaten FK ile oluşmuş olabilir.
        if (! Schema::hasColumn('kisi_yakinlar', 'yakinlik_derecesi')) {
            return;
        }

        if (! Schema::hasColumn('kisi_yakinlar', 'yakinlik_derecesi_id')) {
            Schema::table('kisi_yakinlar', function (Blueprint $table) {
                $table->foreignId('yakinlik_derecesi_id')
                    ->nullable()
                    ->after('yakin_kisi_id')
                    ->constrained('yakinlik_dereceleri')
                    ->restrictOnDelete();
            });
        }

        $dereceler = DB::table('yakinlik_dereceleri')->pluck('id', 'kod');

        foreach ($dereceler as $kod => $id) {
            DB::table('kisi_yakinlar')
                ->where('yakinlik_derecesi', $kod)
                ->whereNull('yakinlik_derecesi_id')
                ->update(['yakinlik_derecesi_id' => $id]);
        }

        DB::table('kisi_yakinlar')->whereNull('yakinlik_derecesi_id')->delete();

        Schema::table('kisi_yakinlar', function (Blueprint $table) {
            $table->dropColumn('yakinlik_derecesi');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('kisi_yakinlar')) {
            return;
        }

        if (Schema::hasColumn('kisi_yakinlar', 'yakinlik_derecesi')) {
            return;
        }

        Schema::table('kisi_yakinlar', function (Blueprint $table) {
            $table->string('yakinlik_derecesi', 10)->nullable()->after('yakin_kisi_id');
        });

        if (Schema::hasColumn('kisi_yakinlar', 'yakinlik_derecesi_id')) {
            $dereceler = DB::table('yakinlik_dereceleri')->pluck('kod', 'id');

            foreach ($dereceler as $id => $kod) {
                DB::table('kisi_yakinlar')
                    ->where('yakinlik_derecesi_id', $id)
                    ->update(['yakinlik_derecesi' => $kod]);
            }

            Schema::table('kisi_yakinlar', function (Blueprint $table) {
                $table->dropConstrainedForeignId('yakinlik_derecesi_id');
            });
        }
    }
};
