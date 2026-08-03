<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iller', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('ad', 100);
            $table->timestamps();

            $table->unique('ad');
        });

        Schema::create('ilceler', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedSmallInteger('il_id');
            $table->string('ad', 100);
            $table->timestamps();

            $table->foreign('il_id')->references('id')->on('iller')->cascadeOnDelete();
            $table->unique(['il_id', 'ad']);
            $table->index('il_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ilceler');
        Schema::dropIfExists('iller');
    }
};
