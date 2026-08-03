<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('yerler') && ! Schema::hasTable('merkezler')) {
            Schema::rename('yerler', 'merkezler');
        }

        if (
            Schema::hasTable('kurslar')
            && Schema::hasColumn('kurslar', 'yer_id')
            && ! Schema::hasColumn('kurslar', 'merkez_id')
        ) {
            Schema::table('kurslar', function (Blueprint $table) {
                $table->dropForeign(['yer_id']);
            });

            Schema::table('kurslar', function (Blueprint $table) {
                $table->renameColumn('yer_id', 'merkez_id');
            });

            Schema::table('kurslar', function (Blueprint $table) {
                $table->foreign('merkez_id')->references('id')->on('merkezler')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('kurslar')
            && Schema::hasColumn('kurslar', 'merkez_id')
            && ! Schema::hasColumn('kurslar', 'yer_id')
        ) {
            Schema::table('kurslar', function (Blueprint $table) {
                $table->dropForeign(['merkez_id']);
            });

            Schema::table('kurslar', function (Blueprint $table) {
                $table->renameColumn('merkez_id', 'yer_id');
            });

            Schema::table('kurslar', function (Blueprint $table) {
                $table->foreign('yer_id')->references('id')->on('yerler')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('merkezler') && ! Schema::hasTable('yerler')) {
            Schema::rename('merkezler', 'yerler');
        }
    }
};
