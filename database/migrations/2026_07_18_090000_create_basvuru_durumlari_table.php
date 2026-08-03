<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basvuru_durumlari', function (Blueprint $table) {
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

        DB::table('basvuru_durumlari')->insert([
            [
                'kod' => 'onay_bekliyor',
                'ad' => 'Onay Bekliyor',
                'aciklama' => 'Başvuru onay bekliyor',
                'status_sinifi' => 'status-hazirlik',
                'sira' => 10,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'kesin_kayit',
                'ad' => 'Kesin Kayıt',
                'aciklama' => 'Başvuru onaylandı, kesin kayıt yapıldı',
                'status_sinifi' => 'status-aktif',
                'sira' => 20,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'yedek',
                'ad' => 'Yedek',
                'aciklama' => 'Yedek listesinde bekliyor',
                'status_sinifi' => 'status-yedek',
                'sira' => 30,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kod' => 'iptal',
                'ad' => 'İptal',
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
        Schema::dropIfExists('basvuru_durumlari');
    }
};
