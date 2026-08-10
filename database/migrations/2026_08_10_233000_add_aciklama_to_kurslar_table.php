<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurslar', function (Blueprint $table) {
            $table->longText('aciklama')->nullable()->after('onlinede_yayinlansin');
        });
    }

    public function down(): void
    {
        Schema::table('kurslar', function (Blueprint $table) {
            $table->dropColumn('aciklama');
        });
    }
};
