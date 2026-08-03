<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_dersleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_id')->constrained('kurslar')->cascadeOnDelete();
            $table->foreignId('kurs_gun_id')->nullable()->constrained('kurs_gunleri')->nullOnDelete();
            $table->date('tarih');
            $table->time('baslangic_saati');
            $table->time('bitis_saati');
            $table->decimal('ders_saati', 4, 1)->default(0);
            $table->string('sinif')->nullable();
            $table->boolean('yoklama_alindi')->default(false);
            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guncelleyen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kurs_id', 'tarih', 'baslangic_saati']);
            $table->index(['kurs_id', 'tarih']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_dersleri');
    }
};
