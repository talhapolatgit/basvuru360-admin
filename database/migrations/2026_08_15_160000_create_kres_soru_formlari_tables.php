<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kres_soru_formlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donem_id')->unique()->constrained('kres_donemler')->cascadeOnDelete();
            $table->string('ad');
            $table->text('aciklama')->nullable();
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('kres_sorular', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('kres_soru_formlari')->cascadeOnDelete();
            $table->string('tip');
            $table->string('baslik');
            $table->text('aciklama')->nullable();
            $table->boolean('zorunlu')->default(false);
            $table->unsignedInteger('sira')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('kres_soru_secenekler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soru_id')->constrained('kres_sorular')->cascadeOnDelete();
            $table->string('etiket');
            $table->unsignedInteger('sira')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kres_soru_secenekler');
        Schema::dropIfExists('kres_sorular');
        Schema::dropIfExists('kres_soru_formlari');
    }
};
