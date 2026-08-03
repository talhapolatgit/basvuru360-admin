<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kisiler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('soyad');
            $table->string('tc_kimlik_no', 11)->unique();
            $table->date('dogum_tarihi')->nullable();
            $table->string('telefon', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('cinsiyet')->nullable();
            $table->string('il')->nullable();
            $table->string('ilce')->nullable();
            $table->text('adres')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kisiler');
    }
};
