<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_dersleri', function (Blueprint $table) {
            $table->foreignId('yoklama_alan_id')
                ->nullable()
                ->after('yoklama_alindi')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kurs_dersleri', function (Blueprint $table) {
            $table->dropConstrainedForeignId('yoklama_alan_id');
        });
    }
};
