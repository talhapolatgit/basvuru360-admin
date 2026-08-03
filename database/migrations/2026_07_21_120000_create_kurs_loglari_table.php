<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_loglari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_id')->nullable()->constrained('kurslar')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('islem', 60);
            $table->string('aciklama', 1000)->nullable();

            // İşlemin ilişkili olduğu model (ör. KursDers, KursBasvuru)
            $table->string('konu_tipi', 60)->nullable();
            $table->unsignedBigInteger('konu_id')->nullable();

            $table->json('eski_veriler')->nullable();
            $table->json('yeni_veriler')->nullable();
            $table->json('ekstra')->nullable();

            // İstek / cihaz bilgileri
            $table->string('ip_adresi', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('tarayici', 120)->nullable();
            $table->string('platform', 120)->nullable();
            $table->string('cihaz_tipi', 20)->nullable();
            $table->string('http_metodu', 10)->nullable();
            $table->string('url', 2048)->nullable();

            $table->timestamps();

            $table->index(['kurs_id', 'created_at']);
            $table->index('user_id');
            $table->index('islem');
            $table->index(['konu_tipi', 'konu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_loglari');
    }
};
