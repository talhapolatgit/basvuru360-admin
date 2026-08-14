<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kres_donemler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->date('baslangic')->nullable();
            $table->date('bitis')->nullable();
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kres_donemler');
    }
};
