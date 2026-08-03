<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merkezler', function (Blueprint $table) {
            if (! Schema::hasColumn('merkezler', 'il')) {
                $table->string('il', 100)->nullable()->after('ad');
            }
            if (! Schema::hasColumn('merkezler', 'ilce')) {
                $table->string('ilce', 100)->nullable()->after('il');
            }
        });
    }

    public function down(): void
    {
        Schema::table('merkezler', function (Blueprint $table) {
            if (Schema::hasColumn('merkezler', 'ilce')) {
                $table->dropColumn('ilce');
            }
            if (Schema::hasColumn('merkezler', 'il')) {
                $table->dropColumn('il');
            }
        });
    }
};
