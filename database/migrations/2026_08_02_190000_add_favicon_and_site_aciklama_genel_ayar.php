<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['favicon' => null, 'site_aciklama' => null] as $anahtar => $deger) {
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
        $path = DB::table('genel_ayarlar')
            ->where('anahtar', 'favicon')
            ->value('deger');

        if (is_string($path) && str_starts_with($path, 'uploads/genel/')) {
            $full = public_path($path);
            if (is_file($full)) {
                @unlink($full);
            }
        }

        DB::table('genel_ayarlar')
            ->whereIn('anahtar', ['favicon', 'site_aciklama'])
            ->delete();
    }
};
