<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            'yakin_icin_basvuru_aktif' => '1',
            'manuel_yakin_ekleme_aktif' => '1',
        ] as $anahtar => $deger) {
            $varMi = DB::table('genel_ayarlar')->where('anahtar', $anahtar)->exists();
            if ($varMi) {
                continue;
            }

            DB::table('genel_ayarlar')->insert([
                'anahtar' => $anahtar,
                'deger' => $deger,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('genel_ayarlar')
            ->whereIn('anahtar', [
                'yakin_icin_basvuru_aktif',
                'manuel_yakin_ekleme_aktif',
            ])
            ->delete();
    }
};
