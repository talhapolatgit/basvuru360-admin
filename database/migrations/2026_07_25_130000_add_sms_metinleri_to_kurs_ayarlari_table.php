<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eski kolonlu kurs_ayarlari şeması için; key-value yapıdaysa atlanır.
        if (
            ! Schema::hasTable('kurs_ayarlari')
            || ! Schema::hasColumn('kurs_ayarlari', 'sms_basvuru_onay')
            || Schema::hasColumn('kurs_ayarlari', 'sms_metin_onay')
        ) {
            return;
        }

        Schema::table('kurs_ayarlari', function (Blueprint $table) {
            $table->text('sms_metin_onay')->nullable()->after('sms_basvuru_yedek');
            $table->text('sms_metin_iptal')->nullable()->after('sms_metin_onay');
            $table->text('sms_metin_yedek')->nullable()->after('sms_metin_iptal');
        });
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('kurs_ayarlari')
            || ! Schema::hasColumn('kurs_ayarlari', 'sms_metin_onay')
        ) {
            return;
        }

        Schema::table('kurs_ayarlari', function (Blueprint $table) {
            $table->dropColumn(['sms_metin_onay', 'sms_metin_iptal', 'sms_metin_yedek']);
        });
    }
};
