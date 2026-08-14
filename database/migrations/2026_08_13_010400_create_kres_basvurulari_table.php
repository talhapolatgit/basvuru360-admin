<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kres_basvurulari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grup_id')->constrained('kres_gruplar')->cascadeOnDelete();
            $table->foreignId('kisi_id')->constrained('kisiler')->cascadeOnDelete();
            $table->foreignId('basvuran_id')->nullable()->constrained('kisiler')->nullOnDelete();
            $table->foreignId('durum_id')->constrained('kres_basvuru_durumlari')->restrictOnDelete();
            $table->unsignedInteger('yedek_sira')->nullable();
            $table->text('notlar')->nullable();
            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['grup_id', 'kisi_id']);
            $table->index(['durum_id', 'grup_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kres_basvurulari');
    }
};
