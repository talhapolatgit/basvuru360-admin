<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_evraklari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_id')->constrained('kurslar')->cascadeOnDelete();
            $table->foreignId('evrak_tipi_id')->constrained('evrak_tipleri')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['kurs_id', 'evrak_tipi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_evraklari');
    }
};
