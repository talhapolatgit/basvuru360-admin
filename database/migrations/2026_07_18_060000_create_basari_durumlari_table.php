<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basari_durumlari', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 50);
            $table->string('ad');
            $table->text('aciklama')->nullable();
            $table->string('status_sinifi', 50)->default('status-hazirlik');
            $table->unsignedSmallInteger('sira')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique('kod');
            $table->unique('ad');
        });

        $now = now();

        DB::table('basari_durumlari')->insert([
            [
                'kod' => 'sertifika_hak_etti',
                'ad' => 'Sertifika hak etti',
                'aciklama' => 'Kursu başarıyla tamamlayarak sertifika hakkı kazandı',
                'status_sinifi' => 'status-tamamlanan',
                'sira' => 10,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'katilim_belgesi_hak_etti',
                'ad' => 'Katılım belgesi hak etti',
                'aciklama' => 'Katılım belgesi almaya hak kazandı',
                'status_sinifi' => 'status-aktif',
                'sira' => 20,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'devamsizlik',
                'ad' => 'Devamsızlık',
                'aciklama' => 'Devamsızlık nedeniyle başarısız sayıldı',
                'status_sinifi' => 'status-iptal',
                'sira' => 30,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'sinava_girmedi',
                'ad' => 'Sınava girmedi',
                'aciklama' => 'Sınava katılmadığı için belgelendirilemedi',
                'status_sinifi' => 'status-yedek',
                'sira' => 40,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'sinav_basarisiz',
                'ad' => 'Sınav başarısız',
                'aciklama' => 'Sınavda başarısız oldu',
                'status_sinifi' => 'status-hazirlik',
                'sira' => 50,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('basari_durumlari');
    }
};
