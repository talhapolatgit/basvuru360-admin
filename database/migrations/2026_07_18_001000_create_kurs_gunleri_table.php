<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_gunleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_id')->constrained('kurslar')->cascadeOnDelete();
            $table->string('gun');
            $table->time('baslangic_saati');
            $table->time('bitis_saati');
            $table->decimal('ders_saati', 4, 1);
            $table->timestamps();

            $table->index(['kurs_id', 'gun']);
            $table->unique(['kurs_id', 'gun', 'baslangic_saati']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_gunleri');
    }
};
