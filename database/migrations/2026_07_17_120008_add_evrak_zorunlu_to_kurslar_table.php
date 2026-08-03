<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurslar', function (Blueprint $table) {
            if (! Schema::hasColumn('kurslar', 'evrak_zorunlu')) {
                $table->boolean('evrak_zorunlu')->default(false)->after('mezun_olma_sarti');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kurslar', function (Blueprint $table) {
            if (Schema::hasColumn('kurslar', 'evrak_zorunlu')) {
                $table->dropColumn('evrak_zorunlu');
            }
        });
    }
};
