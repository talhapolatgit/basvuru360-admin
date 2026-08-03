<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_basvurulari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kisi_id')->constrained('kisiler')->restrictOnDelete();
            $table->foreignId('kurs_id')->constrained('kurslar')->restrictOnDelete();
            $table->string('durum')->default('beklemede')->index();
            $table->string('basari_durumu')->default('belirsiz')->index();
            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guncelleyen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kisi_id', 'kurs_id']);
            $table->index(['kurs_id', 'durum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_basvurulari');
    }
};
