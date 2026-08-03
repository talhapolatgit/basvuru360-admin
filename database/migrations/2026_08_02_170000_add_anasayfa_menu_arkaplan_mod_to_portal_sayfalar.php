<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_sayfalar', function (Blueprint $table) {
            $table->string('anasayfa_menu_arkaplan_mod', 20)->default('kapla')->after('anasayfa_menu_arkaplan');
        });
    }

    public function down(): void
    {
        Schema::table('portal_sayfalar', function (Blueprint $table) {
            $table->dropColumn('anasayfa_menu_arkaplan_mod');
        });
    }
};
