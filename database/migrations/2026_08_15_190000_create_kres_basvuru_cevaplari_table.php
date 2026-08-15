<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kres_basvuru_cevaplari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('basvuru_id')->constrained('kres_basvurulari')->cascadeOnDelete();
            $table->foreignId('soru_id')->constrained('kres_sorular')->cascadeOnDelete();
            $table->text('deger')->nullable();
            $table->string('dosya_yolu')->nullable();
            $table->string('orijinal_ad')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('boyut')->nullable();
            $table->timestamps();

            $table->unique(['basvuru_id', 'soru_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kres_basvuru_cevaplari');
    }
};
