<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_kurum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_id')->constrained('kurslar')->cascadeOnDelete();
            $table->foreignId('kurum_id')->constrained('kurumlar')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['kurs_id', 'kurum_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_kurum');
    }
};
