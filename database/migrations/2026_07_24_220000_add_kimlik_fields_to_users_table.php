<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cinsiyet', 20)->nullable()->after('dogum_tarihi');
            $table->string('dogum_yeri', 100)->nullable()->after('cinsiyet');
            $table->string('medeni_durum', 50)->nullable()->after('dogum_yeri');
            $table->string('uyruk', 100)->nullable()->after('medeni_durum');
            $table->string('anne_adi', 100)->nullable()->after('uyruk');
            $table->string('baba_adi', 100)->nullable()->after('anne_adi');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cinsiyet', 'dogum_yeri', 'medeni_durum', 'uyruk', 'anne_adi', 'baba_adi']);
        });
    }
};
