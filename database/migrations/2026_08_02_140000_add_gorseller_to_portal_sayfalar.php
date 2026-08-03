<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_sayfalar', function (Blueprint $table) {
            $table->string('anasayfa_logo', 255)->nullable()->after('aciklama');
            $table->string('sidebar_ikon', 255)->nullable()->after('anasayfa_logo');
        });
    }

    public function down(): void
    {
        Schema::table('portal_sayfalar', function (Blueprint $table) {
            $table->dropColumn(['anasayfa_logo', 'sidebar_ikon']);
        });
    }
};
