<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_sayfalar', function (Blueprint $table) {
            $table->string('aciklama', 500)->nullable()->after('baslik');
        });

        DB::table('portal_sayfalar')->where('kod', 'kurslar')->update([
            'aciklama' => 'Açık ve yaklaşan kurs programlarını inceleyin, size uygun olanı seçin.',
        ]);

        DB::table('portal_sayfalar')->where('kod', 'etkinlikler')->update([
            'aciklama' => 'Atölye, seminer ve gezi programlarını keşfedin.',
        ]);
    }

    public function down(): void
    {
        Schema::table('portal_sayfalar', function (Blueprint $table) {
            $table->dropColumn('aciklama');
        });
    }
};
