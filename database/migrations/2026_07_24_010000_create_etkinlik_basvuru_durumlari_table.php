<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etkinlik_basvuru_durumlari', function (Blueprint $table) {
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
        $kaynak = DB::table('basvuru_durumlari')->orderBy('sira')->orderBy('id')->get();

        if ($kaynak->isEmpty()) {
            DB::table('etkinlik_basvuru_durumlari')->insert([
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
        } else {
            foreach ($kaynak as $row) {
                DB::table('etkinlik_basvuru_durumlari')->insert([
                    'kod' => $row->kod,
                    'ad' => $row->ad,
                    'aciklama' => $row->aciklama,
                    'status_sinifi' => $row->status_sinifi ?: 'status-hazirlik',
                    'sira' => (int) $row->sira,
                    'aktif' => (bool) $row->aktif,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ]);
            }
        }

        $eskiIdByKod = DB::table('basvuru_durumlari')->pluck('id', 'kod');
        $yeniIdByKod = DB::table('etkinlik_basvuru_durumlari')->pluck('id', 'kod');
        $idMap = [];
        foreach ($eskiIdByKod as $kod => $eskiId) {
            if (isset($yeniIdByKod[$kod])) {
                $idMap[(int) $eskiId] = (int) $yeniIdByKod[$kod];
            }
        }

        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            $table->dropForeign(['durum_id']);
        });

        if ($idMap !== []) {
            foreach ($idMap as $eskiId => $yeniId) {
                if ($eskiId === $yeniId) {
                    continue;
                }
                DB::table('etkinlik_basvurulari')
                    ->where('durum_id', $eskiId)
                    ->update(['durum_id' => $yeniId]);
            }
        }

        $varsayilanId = $yeniIdByKod['onay_bekliyor'] ?? $yeniIdByKod->first();
        if ($varsayilanId) {
            $gecerliIds = $yeniIdByKod->values()->all();
            DB::table('etkinlik_basvurulari')
                ->whereNotIn('durum_id', $gecerliIds)
                ->update(['durum_id' => $varsayilanId]);
        }

        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            $table->foreign('durum_id')
                ->references('id')
                ->on('etkinlik_basvuru_durumlari')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $eskiIdByKod = DB::table('basvuru_durumlari')->pluck('id', 'kod');
        $yeniIdByKod = DB::table('etkinlik_basvuru_durumlari')->pluck('id', 'kod');
        $idMap = [];
        foreach ($yeniIdByKod as $kod => $yeniId) {
            if (isset($eskiIdByKod[$kod])) {
                $idMap[(int) $yeniId] = (int) $eskiIdByKod[$kod];
            }
        }

        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            $table->dropForeign(['durum_id']);
        });

        foreach ($idMap as $yeniId => $eskiId) {
            if ($yeniId === $eskiId) {
                continue;
            }
            DB::table('etkinlik_basvurulari')
                ->where('durum_id', $yeniId)
                ->update(['durum_id' => $eskiId]);
        }

        $varsayilanId = $eskiIdByKod['onay_bekliyor'] ?? $eskiIdByKod->first();
        if ($varsayilanId) {
            $gecerliIds = $eskiIdByKod->values()->all();
            DB::table('etkinlik_basvurulari')
                ->whereNotIn('durum_id', $gecerliIds)
                ->update(['durum_id' => $varsayilanId]);
        }

        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            $table->foreign('durum_id')
                ->references('id')
                ->on('basvuru_durumlari')
                ->restrictOnDelete();
        });

        Schema::dropIfExists('etkinlik_basvuru_durumlari');
    }
};
