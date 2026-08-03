<?php

use App\Enums\IkametSarti;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etkinlik_tipleri', function (Blueprint $table) {
            $table->id();
            $table->string('ad')->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('etkinlik_tipleri')->insert([
            ['ad' => 'Seminer', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['ad' => 'Gezi', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['ad' => 'Festival', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['ad' => 'Açık Kapı', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            ['ad' => 'Diğer', 'aktif' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('etkinlikler', function (Blueprint $table) {
            $table->id();
            $table->string('etkinlik_no')->unique();
            $table->string('ad');
            $table->text('aciklama')->nullable();

            $table->foreignId('merkez_id')->constrained('merkezler')->restrictOnDelete();
            $table->foreignId('etkinlik_tipi_id')->constrained('etkinlik_tipleri')->restrictOnDelete();

            $table->unsignedInteger('kontenjan')->default(0);
            $table->unsignedInteger('yedek_kontenjan')->default(0);
            $table->unsignedInteger('ikamet_disi_kontenjan')->default(0);

            $table->date('baslangic_tarihi');
            $table->date('bitis_tarihi');
            $table->dateTime('basvuru_baslama_tarihi');
            $table->dateTime('basvuru_bitis_tarihi');

            $table->string('cinsiyet_sarti')->nullable();
            $table->unsignedTinyInteger('minimum_yas')->nullable();
            $table->unsignedTinyInteger('maksimum_yas')->nullable();
            $table->string('ikamet_sarti')->default(IkametSarti::Hayir->value);
            $table->boolean('ogrenci_olma_sarti')->default(false);
            $table->boolean('engelli_olma_sarti')->default(false);
            $table->foreignId('egitim_durumu_id')->nullable()->constrained('egitim_durumlari')->nullOnDelete();
            $table->boolean('mezun_olma_sarti')->default(false);

            $table->boolean('evrak_zorunlu')->default(false);
            $table->boolean('onlinede_yayinlansin')->default(false);
            $table->string('takvim_rengi', 20)->nullable();

            $table->unsignedInteger('basvuru_sayisi')->default(0);
            $table->unsignedInteger('kayit_sayisi')->default(0);
            $table->unsignedInteger('iptal_sayisi')->default(0);
            $table->string('durum')->default('hazirlik')->index();

            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guncelleyen_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('etkinlik_kurum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etkinlik_id')->constrained('etkinlikler')->cascadeOnDelete();
            $table->foreignId('kurum_id')->constrained('kurumlar')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['etkinlik_id', 'kurum_id']);
        });

        Schema::create('etkinlik_evraklari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etkinlik_id')->constrained('etkinlikler')->cascadeOnDelete();
            $table->foreignId('evrak_tipi_id')->constrained('evrak_tipleri')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['etkinlik_id', 'evrak_tipi_id']);
        });

        Schema::create('etkinlik_sorumlulari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etkinlik_id')->constrained('etkinlikler')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['etkinlik_id', 'user_id']);
        });

        Schema::create('etkinlik_basvurulari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kisi_id')->constrained('kisiler')->restrictOnDelete();
            $table->foreignId('basvuran_id')->nullable()->constrained('kisiler')->restrictOnDelete();
            $table->foreignId('veli_id')->nullable()->constrained('kisiler')->nullOnDelete();
            $table->foreignId('etkinlik_id')->constrained('etkinlikler')->restrictOnDelete();

            $table->foreignId('durum_id')->constrained('basvuru_durumlari')->restrictOnDelete();

            $table->string('katilim_durumu')->nullable()->index();
            $table->boolean('katilim_belgesi')->default(false);

            $table->timestamp('onay_tarihi')->nullable()->index();
            $table->foreignId('onaylayan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('iptal_tarihi')->nullable()->index();
            $table->foreignId('iptal_gerekce_id')->nullable()->constrained('iptal_gerekceleri')->nullOnDelete();
            $table->foreignId('iptal_eden_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('olusturan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guncelleyen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('silen_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kisi_id', 'etkinlik_id']);
            $table->index(['etkinlik_id', 'durum_id']);
        });

        Schema::create('etkinlik_sms_gonderimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etkinlik_id')->constrained('etkinlikler')->cascadeOnDelete();
            $table->foreignId('gonderen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('mesaj');
            $table->string('kapsam')->nullable();
            $table->string('basvuru_durum_kod')->nullable();
            $table->unsignedInteger('toplam')->default(0);
            $table->unsignedInteger('gonderilen')->default(0);
            $table->unsignedInteger('atlanan')->default(0);
            $table->json('detay')->nullable();
            $table->timestamps();
            $table->index(['etkinlik_id', 'created_at']);
        });

        Schema::create('etkinlik_eposta_gonderimleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etkinlik_id')->constrained('etkinlikler')->cascadeOnDelete();
            $table->foreignId('gonderen_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('konu');
            $table->text('mesaj');
            $table->string('kapsam')->nullable();
            $table->string('basvuru_durum_kod')->nullable();
            $table->unsignedInteger('toplam')->default(0);
            $table->unsignedInteger('gonderilen')->default(0);
            $table->unsignedInteger('atlanan')->default(0);
            $table->json('detay')->nullable();
            $table->timestamps();
            $table->index(['etkinlik_id', 'created_at']);
        });

        Schema::table('sms_loglari', function (Blueprint $table) {
            $table->foreignId('etkinlik_id')->nullable()->after('kurs_id')->constrained('etkinlikler')->nullOnDelete();
            $table->foreignId('etkinlik_basvuru_id')->nullable()->after('basvuru_id')->constrained('etkinlik_basvurulari')->nullOnDelete();
            $table->index(['etkinlik_id', 'created_at']);
        });

        Schema::table('eposta_loglari', function (Blueprint $table) {
            $table->foreignId('etkinlik_id')->nullable()->after('kurs_id')->constrained('etkinlikler')->nullOnDelete();
            $table->foreignId('etkinlik_basvuru_id')->nullable()->after('basvuru_id')->constrained('etkinlik_basvurulari')->nullOnDelete();
            $table->index(['etkinlik_id', 'created_at']);
        });

        if (! DB::table('numarator')->where('kod', 'etkinlik')->exists()) {
            DB::table('numarator')->insert([
                'kod' => 'etkinlik',
                'son_numara' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('eposta_loglari', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etkinlik_basvuru_id');
            $table->dropConstrainedForeignId('etkinlik_id');
        });

        Schema::table('sms_loglari', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etkinlik_basvuru_id');
            $table->dropConstrainedForeignId('etkinlik_id');
        });

        Schema::dropIfExists('etkinlik_eposta_gonderimleri');
        Schema::dropIfExists('etkinlik_sms_gonderimleri');
        Schema::dropIfExists('etkinlik_basvurulari');
        Schema::dropIfExists('etkinlik_sorumlulari');
        Schema::dropIfExists('etkinlik_evraklari');
        Schema::dropIfExists('etkinlik_kurum');
        Schema::dropIfExists('etkinlikler');
        Schema::dropIfExists('etkinlik_tipleri');

        DB::table('numarator')->where('kod', 'etkinlik')->delete();
    }
};
