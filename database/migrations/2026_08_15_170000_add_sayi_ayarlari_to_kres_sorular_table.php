<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kres_sorular', function (Blueprint $table) {
            $table->decimal('min_deger', 12, 4)->nullable()->after('zorunlu');
            $table->decimal('max_deger', 12, 4)->nullable()->after('min_deger');
            $table->boolean('tam_sayi')->default(false)->after('max_deger');
        });
    }

    public function down(): void
    {
        Schema::table('kres_sorular', function (Blueprint $table) {
            $table->dropColumn(['min_deger', 'max_deger', 'tam_sayi']);
        });
    }
};
