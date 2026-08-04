<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_sayfalar', function (Blueprint $table) {
            $table->boolean('sadece_giris')->default(false)->after('menude_goster');
        });
    }

    public function down(): void
    {
        Schema::table('portal_sayfalar', function (Blueprint $table) {
            $table->dropColumn('sadece_giris');
        });
    }
};
