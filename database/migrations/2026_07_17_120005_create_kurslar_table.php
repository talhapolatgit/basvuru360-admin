<?php

use App\Enums\IkametSarti;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurslar', function (Blueprint $table) {
            $table->id();
            $table->string('kurs_no')->unique();

            // İlişkiler
            $table->foreignId('merkez_id')->constrained('merkezler')->restrictOnDelete();
            $table->foreignId('alan_id')->constrained('alanlar')->restrictOnDelete();
            $table->foreignId('brans_id')->constrained('branslar')->restrictOnDelete();
            $table->foreignId('kurs_tipi_id')->constrained('kurs_tipleri')->restrictOnDelete();
            $table->foreignId('ogretmen_id')->nullable()->constrained('users')->nullOnDelete();

            // Kontenjan
            $table->unsignedInteger('kontenjan')->default(0);
            $table->unsignedInteger('yedek_kontenjan')->default(0);
            $table->unsignedInteger('ikamet_disi_kontenjan')->default(0);

            $table->string('meb_numarasi')->nullable();

            // Tarihler
            $table->date('kurs_baslama_tarihi');
            $table->date('kurs_bitis_tarihi');
            $table->dateTime('basvuru_baslama_tarihi');
            $table->dateTime('basvuru_bitis_tarihi');

            $table->unsignedInteger('toplam_kurs_saati')->default(0);

            // Başvuru şartları
            $table->string('cinsiyet_sarti')->nullable();
            $table->unsignedTinyInteger('minimum_yas')->nullable();
            $table->unsignedTinyInteger('maksimum_yas')->nullable();
            $table->string('ikamet_sarti')->default(IkametSarti::Hayir->value);
            $table->boolean('ogrenci_olma_sarti')->default(false);
            $table->boolean('engelli_olma_sarti')->default(false);
            $table->foreignId('egitim_durumu_id')->nullable()->constrained('egitim_durumlari')->nullOnDelete();
            $table->boolean('mezun_olma_sarti')->default(false);

            // Evrak
            $table->boolean('evrak_zorunlu')->default(false);

            // Yayın / görünüm
            $table->boolean('onlinede_yayinlansin')->default(false);
            $table->string('takvim_rengi', 20)->nullable();
            $table->unsignedInteger('basvuru_sayisi')->default(0);

            // Kayıt bilgileri
            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guncelleyen_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurslar');
    }
};
