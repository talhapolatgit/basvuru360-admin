<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            if (! Schema::hasColumn('etkinlik_basvurulari', 'yedek_sira')) {
                $table->unsignedInteger('yedek_sira')->nullable()->after('durum_id');
                $table->index(['etkinlik_id', 'yedek_sira']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('etkinlik_basvurulari', function (Blueprint $table) {
            if (Schema::hasColumn('etkinlik_basvurulari', 'yedek_sira')) {
                $table->dropIndex(['etkinlik_id', 'yedek_sira']);
                $table->dropColumn('yedek_sira');
            }
        });
    }
};
