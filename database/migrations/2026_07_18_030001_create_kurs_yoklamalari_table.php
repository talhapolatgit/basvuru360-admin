<?php

use App\Enums\YoklamaDurum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_yoklamalari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurs_ders_id')->constrained('kurs_dersleri')->cascadeOnDelete();
            $table->foreignId('kurs_basvuru_id')->constrained('kurs_basvurulari')->restrictOnDelete();
            $table->foreignId('kisi_id')->constrained('kisiler')->restrictOnDelete();
            $table->string('durum')->default(YoklamaDurum::Yok->value)->index();
            $table->text('aciklama')->nullable();
            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guncelleyen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kurs_ders_id', 'kurs_basvuru_id']);
            $table->index(['kisi_id', 'kurs_ders_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurs_yoklamalari');
    }
};
