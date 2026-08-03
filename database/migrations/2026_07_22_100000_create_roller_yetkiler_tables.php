<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roller', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 50)->unique();
            $table->string('ad', 100);
            $table->string('aciklama', 500)->nullable();
            $table->boolean('tum_yetkiler')->default(false);
            $table->boolean('sistem')->default(false);
            $table->unsignedSmallInteger('sira')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('yetkiler', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 80)->unique();
            $table->string('ad', 150);
            $table->string('modul', 50)->index();
            $table->string('aciklama', 500)->nullable();
            $table->unsignedSmallInteger('sira')->default(0);
            $table->timestamps();
        });

        Schema::create('rol_yetki', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roller')->cascadeOnDelete();
            $table->foreignId('yetki_id')->constrained('yetkiler')->cascadeOnDelete();
            $table->unique(['rol_id', 'yetki_id']);
        });

        Schema::create('kullanici_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rol_id')->constrained('roller')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'rol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kullanici_rol');
        Schema::dropIfExists('rol_yetki');
        Schema::dropIfExists('yetkiler');
        Schema::dropIfExists('roller');
    }
};
