<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_eposta_gonderimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_id')->constrained('kurslar')->cascadeOnDelete();
            $table->foreignId('gonderen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('konu', 200);
            $table->text('mesaj');
            $table->string('kapsam', 20); // filtre | secilen
            $table->string('basvuru_durum_kod', 50)->nullable();
            $table->unsignedInteger('toplam')->default(0);
            $table->unsignedInteger('gonderilen')->default(0);
            $table->unsignedInteger('atlanan')->default(0);
            $table->json('detay')->nullable();
            $table->timestamps();

            $table->index(['kurs_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_eposta_gonderimleri');
    }
};
