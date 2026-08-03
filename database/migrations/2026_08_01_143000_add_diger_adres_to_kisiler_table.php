<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kisiler', function (Blueprint $table) {
            $table->text('diger_adres')->nullable()->after('adres');
        });
    }

    public function down(): void
    {
        Schema::table('kisiler', function (Blueprint $table) {
            $table->dropColumn('diger_adres');
        });
    }
};
