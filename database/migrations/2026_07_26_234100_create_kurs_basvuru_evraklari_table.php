<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_basvuru_evraklari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_basvuru_id')->constrained('kurs_basvurulari')->cascadeOnDelete();
            $table->foreignId('evrak_tipi_id')->constrained('evrak_tipleri')->restrictOnDelete();
            $table->string('dosya_yolu');
            $table->string('orijinal_ad');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('boyut')->default(0);
            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kurs_basvuru_id', 'evrak_tipi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_basvuru_evraklari');
    }
};
