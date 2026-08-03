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
            Schema::hasTable('kurs_ayarlari')
            && Schema::hasColumn('kurs_ayarlari', 'sms_basvuru_onay')
            && ! Schema::hasColumn('kurs_ayarlari', 'eposta_basvuru_onay')
        ) {
            Schema::table('kurs_ayarlari', function (Blueprint $table) {
                $table->string('eposta_basvuru_onay', 20)->default('istege_bagli')->after('sms_metin_yedek');
                $table->string('eposta_basvuru_iptal', 20)->default('istege_bagli')->after('eposta_basvuru_onay');
                $table->string('eposta_basvuru_yedek', 20)->default('istege_bagli')->after('eposta_basvuru_iptal');
                $table->string('eposta_konu_onay', 200)->nullable()->after('eposta_basvuru_yedek');
                $table->string('eposta_konu_iptal', 200)->nullable()->after('eposta_konu_onay');
                $table->string('eposta_konu_yedek', 200)->nullable()->after('eposta_konu_iptal');
                $table->text('eposta_metin_onay')->nullable()->after('eposta_konu_yedek');
                $table->text('eposta_metin_iptal')->nullable()->after('eposta_metin_onay');
                $table->text('eposta_metin_yedek')->nullable()->after('eposta_metin_iptal');
            });
        }

        // Eski kolonlu etkinlik_ayarlari şeması için; key-value yapıdaysa atlanır.
        if (
            Schema::hasTable('etkinlik_ayarlari')
            && Schema::hasColumn('etkinlik_ayarlari', 'sms_basvuru_onay')
            && ! Schema::hasColumn('etkinlik_ayarlari', 'eposta_basvuru_onay')
        ) {
            Schema::table('etkinlik_ayarlari', function (Blueprint $table) {
                $table->string('eposta_basvuru_onay', 20)->default('istege_bagli')->after('sms_metin_yedek');
                $table->string('eposta_basvuru_iptal', 20)->default('istege_bagli')->after('eposta_basvuru_onay');
                $table->string('eposta_basvuru_yedek', 20)->default('istege_bagli')->after('eposta_basvuru_iptal');
                $table->string('eposta_konu_onay', 200)->nullable()->after('eposta_basvuru_yedek');
                $table->string('eposta_konu_iptal', 200)->nullable()->after('eposta_konu_onay');
                $table->string('eposta_konu_yedek', 200)->nullable()->after('eposta_konu_iptal');
                $table->text('eposta_metin_onay')->nullable()->after('eposta_konu_yedek');
                $table->text('eposta_metin_iptal')->nullable()->after('eposta_metin_onay');
                $table->text('eposta_metin_yedek')->nullable()->after('eposta_metin_iptal');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('kurs_ayarlari')
            && Schema::hasColumn('kurs_ayarlari', 'eposta_basvuru_onay')
        ) {
            Schema::table('kurs_ayarlari', function (Blueprint $table) {
                $table->dropColumn([
                    'eposta_basvuru_onay',
                    'eposta_basvuru_iptal',
                    'eposta_basvuru_yedek',
                    'eposta_konu_onay',
                    'eposta_konu_iptal',
                    'eposta_konu_yedek',
                    'eposta_metin_onay',
                    'eposta_metin_iptal',
                    'eposta_metin_yedek',
                ]);
            });
        }

        if (
            Schema::hasTable('etkinlik_ayarlari')
            && Schema::hasColumn('etkinlik_ayarlari', 'eposta_basvuru_onay')
        ) {
            Schema::table('etkinlik_ayarlari', function (Blueprint $table) {
                $table->dropColumn([
                    'eposta_basvuru_onay',
                    'eposta_basvuru_iptal',
                    'eposta_basvuru_yedek',
                    'eposta_konu_onay',
                    'eposta_konu_iptal',
                    'eposta_konu_yedek',
                    'eposta_metin_onay',
                    'eposta_metin_iptal',
                    'eposta_metin_yedek',
                ]);
            });
        }
    }
};
