<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eposta_loglari', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('konu', 200);
            $table->text('mesaj');
            $table->string('durum', 20); // gonderildi | basarisiz
            $table->string('hata_mesaji', 500)->nullable();
            $table->foreignId('kurs_id')->nullable()->constrained('kurslar')->nullOnDelete();
            $table->foreignId('basvuru_id')->nullable()->constrained('kurs_basvurulari')->nullOnDelete();
            $table->foreignId('gonderen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['kurs_id', 'created_at']);
            $table->index(['durum', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eposta_loglari');
    }
};
