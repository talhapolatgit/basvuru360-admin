<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kisiler', function (Blueprint $table) {
            $table->unsignedSmallInteger('hatali_giris_sayisi')->default(0)->after('aktif');
            $table->timestamp('ilk_hatali_giris_at')->nullable()->after('hatali_giris_sayisi');
            $table->timestamp('giris_kilit_bitis')->nullable()->after('ilk_hatali_giris_at');
        });
    }

    public function down(): void
    {
        Schema::table('kisiler', function (Blueprint $table) {
            $table->dropColumn(['hatali_giris_sayisi', 'ilk_hatali_giris_at', 'giris_kilit_bitis']);
        });
    }
};
