<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->timestamp('onay_tarihi')->nullable()->after('basari_durumu');
            $table->timestamp('iptal_tarihi')->nullable()->after('onay_tarihi');
            $table->foreignId('iptal_gerekce_id')
                ->nullable()
                ->after('iptal_tarihi')
                ->constrained('iptal_gerekceleri')
                ->nullOnDelete();

            $table->index('onay_tarihi');
            $table->index('iptal_tarihi');
        });
    }

    public function down(): void
    {
        Schema::table('kurs_basvurulari', function (Blueprint $table) {
            $table->dropConstrainedForeignId('iptal_gerekce_id');
            $table->dropColumn(['onay_tarihi', 'iptal_tarihi']);
        });
    }
};
