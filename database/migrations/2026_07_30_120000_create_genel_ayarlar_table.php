<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genel_ayarlar', function (Blueprint $table) {
            $table->id();
            $table->string('anahtar', 100)->unique();
            $table->text('deger')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genel_ayarlar');
    }
};
