<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $defaults = [
            'sidebar_arkaplan' => '#0c2138',
            'sidebar_logo_arkaplan' => '#ffffff',
        ];

        foreach ($defaults as $anahtar => $deger) {
            $exists = DB::table('genel_ayarlar')
                ->where('anahtar', $anahtar)
                ->exists();

            if ($exists) {
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
            ->whereIn('anahtar', ['sidebar_arkaplan', 'sidebar_logo_arkaplan'])
            ->delete();
    }
};
