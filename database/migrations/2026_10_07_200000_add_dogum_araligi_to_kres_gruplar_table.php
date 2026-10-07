<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kres_gruplar', function (Blueprint $table) {
            $table->date('dogum_baslangic')->nullable()->after('max_yas');
            $table->date('dogum_bitis')->nullable()->after('dogum_baslangic');
        });
    }

    public function down(): void
    {
        Schema::table('kres_gruplar', function (Blueprint $table) {
            $table->dropColumn(['dogum_baslangic', 'dogum_bitis']);
        });
    }
};
