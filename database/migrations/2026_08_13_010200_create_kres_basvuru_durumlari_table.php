<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kres_basvuru_durumlari', function (Blueprint $table) {
            $table->id();
            $table->string('kod', 50)->unique();
            $table->string('ad');
            $table->string('aciklama')->nullable();
            $table->string('status_sinifi', 50)->default('status-hazirlik');
            $table->unsignedInteger('sira')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('kres_basvuru_durumlari')->insert([
            [
                'kod' => 'onay_bekliyor',
                'ad' => 'Onay Bekliyor',
                'aciklama' => 'Başvuru inceleme bekliyor',
                'status_sinifi' => 'status-hazirlik',
                'sira' => 10,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'kesin_kayit',
                'ad' => 'Kesin Kayıt',
                'aciklama' => 'Öğrenci kesin kayıtlı',
                'status_sinifi' => 'status-aktif',
                'sira' => 20,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'yedek',
                'ad' => 'Yedek',
                'aciklama' => 'Yedek listede',
                'status_sinifi' => 'status-yedek',
                'sira' => 30,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'iptal',
                'ad' => 'İptal Edildi',
                'aciklama' => 'Başvuru iptal edildi',
                'status_sinifi' => 'status-iptal',
                'sira' => 40,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kres_basvuru_durumlari');
    }
};
