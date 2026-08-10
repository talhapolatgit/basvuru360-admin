<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etkinlikler', function (Blueprint $table) {
            $table->longText('aciklama')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('etkinlikler', function (Blueprint $table) {
            $table->text('aciklama')->nullable()->change();
        });
    }
};
