<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entegrasyon_ayarlari', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('aktif_saglayici');
        });
    }

    public function down(): void
    {
        Schema::table('entegrasyon_ayarlari', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }
};
