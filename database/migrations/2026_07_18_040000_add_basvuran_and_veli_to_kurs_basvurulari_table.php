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
            $table->foreignId('basvuran_id')
                ->nullable()
                ->after('kisi_id')
                ->constrained('kisiler')
                ->restrictOnDelete();

            $table->foreignId('veli_id')
                ->nullable()
                ->after('basvuran_id')
                ->constrained('kisiler')
                ->nullOnDelete();

            $table->index('basvuran_id');
            $table->index('veli_id');
        });

        DB::table('kurs_basvurulari')
            ->whereNull('basvuran_id')
            ->update(['basvuran_id' => DB::raw('kisi_id')]);
    }

    public function down(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->dropConstrainedForeignId('veli_id');
            $table->dropConstrainedForeignId('basvuran_id');
        });
    }
};
