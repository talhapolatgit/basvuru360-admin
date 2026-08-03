<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kurs_basvurulari')->update(['basari_durumu_id' => null]);

        DB::table('basari_durumlari')->delete();

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
        DB::table('kurs_basvurulari')->update(['basari_durumu_id' => null]);
        DB::table('basari_durumlari')->delete();

        $now = now();

        DB::table('basari_durumlari')->insert([
            [
                'kod' => 'belirsiz',
                'ad' => 'Belirsiz',
                'aciklama' => 'Başarı durumu henüz belirlenmedi',
                'status_sinifi' => 'status-hazirlik',
                'sira' => 10,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'basarili',
                'ad' => 'Başarılı',
                'aciklama' => 'Kurs başarıyla tamamlandı',
                'status_sinifi' => 'status-tamamlanan',
                'sira' => 20,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'basarisiz',
                'ad' => 'Başarısız',
                'aciklama' => 'Kurs başarı kriterlerini karşılamadı',
                'status_sinifi' => 'status-iptal',
                'sira' => 30,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
};
