<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iptal_gerekceleri', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->text('aciklama')->nullable();
            $table->unsignedSmallInteger('sira')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique('ad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iptal_gerekceleri');
    }
};
