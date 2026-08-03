<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifika_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->string('kurum_adi')->nullable();
            $table->json('sablonlar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifika_ayarlari');
    }
};
