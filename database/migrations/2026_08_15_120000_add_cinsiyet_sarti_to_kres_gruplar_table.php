<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kres_gruplar', function (Blueprint $table) {
            $table->string('cinsiyet_sarti')->nullable()->after('kontenjan');
        });
    }

    public function down(): void
    {
        Schema::table('kres_gruplar', function (Blueprint $table) {
            $table->dropColumn('cinsiyet_sarti');
        });
    }
};
