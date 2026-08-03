<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurs_dersleri', function (Blueprint $table) {
            $table->boolean('iptal_edildi')->default(false)->after('sinif');
            $table->text('iptal_gerekcesi')->nullable()->after('iptal_edildi');
            $table->foreignId('iptal_eden_id')->nullable()->after('iptal_gerekcesi')->constrained('users')->nullOnDelete();
            $table->timestamp('iptal_tarihi')->nullable()->after('iptal_eden_id');

            $table->date('orijinal_tarih')->nullable()->after('iptal_tarihi');
            $table->foreignId('tarih_degistiren_id')->nullable()->after('orijinal_tarih')->constrained('users')->nullOnDelete();
            $table->timestamp('tarih_degisiklik_tarihi')->nullable()->after('tarih_degistiren_id');
        });
    }

    public function down(): void
    {
        Schema::table('kurs_dersleri', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tarih_degistiren_id');
            $table->dropColumn(['tarih_degisiklik_tarihi', 'orijinal_tarih']);
            $table->dropConstrainedForeignId('iptal_eden_id');
            $table->dropColumn(['iptal_tarihi', 'iptal_gerekcesi', 'iptal_edildi']);
        });
    }
};
