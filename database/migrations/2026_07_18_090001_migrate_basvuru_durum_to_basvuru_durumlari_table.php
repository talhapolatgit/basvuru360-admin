<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->foreignId('durum_id')
                ->nullable()
                ->after('kurs_id')
                ->constrained('basvuru_durumlari')
                ->restrictOnDelete();
        });

        if (Schema::hasColumn('kurs_basvurulari', 'durum')) {
            $map = DB::table('basvuru_durumlari')->pluck('id', 'kod');

            $eskiMap = [
                'beklemede' => 'onay_bekliyor',
                'onaylandi' => 'kesin_kayit',
                'yedek' => 'yedek',
                'iptal' => 'iptal',
                'reddedildi' => 'iptal',
            ];

            foreach ($eskiMap as $eski => $yeni) {
                $id = $map[$yeni] ?? null;
                if (! $id) {
                    continue;
                }

                DB::table('kurs_basvurulari')
                    ->where('durum', $eski)
                    ->update(['durum_id' => $id]);
            }

            $varsayilanId = $map['onay_bekliyor'] ?? null;
            if ($varsayilanId) {
                DB::table('kurs_basvurulari')
                    ->whereNull('durum_id')
                    ->update(['durum_id' => $varsayilanId]);
            }

            Schema::table('kurs_basvurulari', function (Blueprint $table) {
                $table->dropColumn('durum');
            });
        }
    }

    public function down(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->string('durum')->default('beklemede')->after('kurs_id')->index();
        });

        $map = DB::table('basvuru_durumlari')->pluck('kod', 'id');
        $tersMap = [
            'onay_bekliyor' => 'beklemede',
            'kesin_kayit' => 'onaylandi',
            'yedek' => 'yedek',
            'iptal' => 'iptal',
        ];

        foreach ($map as $id => $kod) {
            DB::table('kurs_basvurulari')
                ->where('durum_id', $id)
                ->update(['durum' => $tersMap[$kod] ?? 'beklemede']);
        }

        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->dropConstrainedForeignId('durum_id');
        });
    }
};
