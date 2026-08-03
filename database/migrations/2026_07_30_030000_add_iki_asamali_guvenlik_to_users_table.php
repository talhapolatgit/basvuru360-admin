<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'iki_asamali_guvenlik')) {
                $table->string('iki_asamali_guvenlik', 20)->default('hayir')->after('aktif');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'iki_asamali_guvenlik')) {
                $table->dropColumn('iki_asamali_guvenlik');
            }
        });
    }
};
