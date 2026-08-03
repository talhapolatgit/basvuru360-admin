<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->foreignId('onaylayan_id')
                ->nullable()
                ->after('onay_tarihi')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('iptal_eden_id')
                ->nullable()
                ->after('iptal_gerekce_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('silen_id')
                ->nullable()
                ->after('guncelleyen_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->dropConstrainedForeignId('onaylayan_id');
            $table->dropConstrainedForeignId('iptal_eden_id');
            $table->dropConstrainedForeignId('silen_id');
        });
    }
};
