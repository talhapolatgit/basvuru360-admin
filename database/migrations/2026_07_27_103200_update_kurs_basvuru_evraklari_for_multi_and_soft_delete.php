<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_basvuru_evraklari', function (Blueprint $table) {
            $table->dropForeign(['kurs_basvuru_id']);
            $table->dropForeign(['evrak_tipi_id']);
        });

        Schema::table('kurs_basvuru_evraklari', function (Blueprint $table) {
            $table->dropUnique(['kurs_basvuru_id', 'evrak_tipi_id']);
        });

        Schema::table('kurs_basvuru_evraklari', function (Blueprint $table) {
            $table->foreign('kurs_basvuru_id')->references('id')->on('kurs_basvurulari')->cascadeOnDelete();
            $table->foreign('evrak_tipi_id')->references('id')->on('evrak_tipleri')->restrictOnDelete();
            $table->index(['kurs_basvuru_id', 'evrak_tipi_id']);
            $table->foreignId('silen_id')->nullable()->after('olusturan_id')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('kurs_basvuru_evraklari', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('silen_id');
            $table->dropIndex(['kurs_basvuru_id', 'evrak_tipi_id']);
            $table->dropForeign(['kurs_basvuru_id']);
            $table->dropForeign(['evrak_tipi_id']);
        });

        // Soft-deleted duplicates would block re-adding unique; purge them first if rolling back.
        DB::table('kurs_basvuru_evraklari')->whereNotNull('deleted_at')->delete();

        Schema::table('kurs_basvuru_evraklari', function (Blueprint $table) {
            $table->unique(['kurs_basvuru_id', 'evrak_tipi_id']);
            $table->foreign('kurs_basvuru_id')->references('id')->on('kurs_basvurulari')->cascadeOnDelete();
            $table->foreign('evrak_tipi_id')->references('id')->on('evrak_tipleri')->restrictOnDelete();
        });
    }
};
