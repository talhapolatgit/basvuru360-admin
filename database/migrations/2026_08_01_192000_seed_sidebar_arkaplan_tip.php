<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('genel_ayarlar')
            ->where('anahtar', 'sidebar_arkaplan_tip')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        DB::table('genel_ayarlar')->insert([
            'anahtar' => 'sidebar_arkaplan_tip',
            'deger' => 'gradient',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('genel_ayarlar')
            ->where('anahtar', 'sidebar_arkaplan_tip')
            ->delete();
    }
};
