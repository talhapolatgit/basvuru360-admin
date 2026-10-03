<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kisiler', function (Blueprint $table) {
            $table->string('mahalle', 150)->nullable()->after('ilce');
            $table->string('sokak', 150)->nullable()->after('mahalle');
            $table->string('kapi', 30)->nullable()->after('sokak');
            $table->string('daire', 30)->nullable()->after('kapi');
            $table->string('uavt_adres_no', 20)->nullable()->after('daire');
        });
    }

    public function down(): void
    {
        Schema::table('kisiler', function (Blueprint $table) {
            $table->dropColumn(['mahalle', 'sokak', 'kapi', 'daire', 'uavt_adres_no']);
        });
    }
};
