<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kres_gruplar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('okul_id')->constrained('kres_okullar')->cascadeOnDelete();
            $table->foreignId('donem_id')->constrained('kres_donemler')->cascadeOnDelete();
            $table->string('ad');
            $table->unsignedTinyInteger('min_yas')->nullable();
            $table->unsignedTinyInteger('max_yas')->nullable();
            $table->unsignedInteger('kontenjan')->default(0);
            $table->boolean('aktif')->default(true)->index();
            $table->timestamps();

            $table->unique(['okul_id', 'donem_id', 'ad']);
            $table->index(['donem_id', 'okul_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kres_gruplar');
    }
};
