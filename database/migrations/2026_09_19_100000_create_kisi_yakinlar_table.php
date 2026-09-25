<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kisi_yakinlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kisi_id')->constrained('kisiler')->cascadeOnDelete();
            $table->foreignId('yakin_kisi_id')->constrained('kisiler')->cascadeOnDelete();
            $table->foreignId('yakinlik_derecesi_id')->constrained('yakinlik_dereceleri')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['kisi_id', 'yakin_kisi_id']);
            $table->index('yakin_kisi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kisi_yakinlar');
    }
};
