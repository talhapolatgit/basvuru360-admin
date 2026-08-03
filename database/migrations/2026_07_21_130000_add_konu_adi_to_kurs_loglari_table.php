<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_loglari', function (Blueprint $table) {
            $table->string('konu_adi', 255)->nullable()->after('konu_id');
        });
    }

    public function down(): void
    {
        Schema::table('kurs_loglari', function (Blueprint $table) {
            $table->dropColumn('konu_adi');
        });
    }
};
