<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('entegrasyon_ayarlari')->where('tur', 'eposta')->exists();

        if ($exists) {
            return;
        }

        $now = now();

        DB::table('entegrasyon_ayarlari')->insert([
            'tur' => 'eposta',
            'aktif_saglayici' => (string) (config('entegrasyonlar.varsayilanlar.eposta') ?: 'demo_eposta'),
            'aktif' => true,
            'ayarlar' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('entegrasyon_ayarlari')->where('tur', 'eposta')->delete();
    }
};
