<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('genel_ayarlar')
            ->where('anahtar', 'header_logo')
            ->exists();

        if ($exists) {
            return;
        }

        $sidebarLogo = DB::table('genel_ayarlar')
            ->where('anahtar', 'sidebar_logo')
            ->value('deger');

        $headerPath = null;

        if (is_string($sidebarLogo) && $sidebarLogo !== '' && str_starts_with($sidebarLogo, 'uploads/')) {
            $source = public_path($sidebarLogo);
            if (is_file($source)) {
                $ext = pathinfo($source, PATHINFO_EXTENSION) ?: 'png';
                $ad = 'header-logo-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$ext;
                $hedef = public_path('uploads/genel/'.$ad);
                File::ensureDirectoryExists(dirname($hedef));
                File::copy($source, $hedef);
                $headerPath = 'uploads/genel/'.$ad;
            }
        }

        $now = now();

        DB::table('genel_ayarlar')->insert([
            'anahtar' => 'header_logo',
            'deger' => $headerPath,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $path = DB::table('genel_ayarlar')
            ->where('anahtar', 'header_logo')
            ->value('deger');

        if (is_string($path) && str_starts_with($path, 'uploads/genel/')) {
            $full = public_path($path);
            if (is_file($full)) {
                @unlink($full);
            }
        }

        DB::table('genel_ayarlar')
            ->where('anahtar', 'header_logo')
            ->delete();
    }
};
