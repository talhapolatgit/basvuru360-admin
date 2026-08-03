<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egitim_durumlari', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->unsignedTinyInteger('seviye')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egitim_durumlari');
    }
};
