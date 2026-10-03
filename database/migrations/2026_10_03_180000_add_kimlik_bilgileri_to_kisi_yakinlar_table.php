<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Yakınlar artık kişi kaydı (kisiler) olmadan tutulabilir; yakin_kisi_id yalnızca
 * aynı T.C. ile bir kişi kaydı varsa doldurulur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kisi_yakinlar', function (Blueprint $table) {
            $table->string('ad', 100)->nullable()->after('yakin_kisi_id');
            $table->string('soyad', 100)->nullable()->after('ad');
            $table->string('tc_kimlik_no', 11)->nullable()->after('soyad');
            $table->date('dogum_tarihi')->nullable()->after('tc_kimlik_no');
            $table->string('cinsiyet', 10)->nullable()->after('dogum_tarihi');
            $table->timestamp('son_sorgu_at')->nullable()->after('yakinlik_derecesi_id');
            $table->nullableMorphs('kaydeden');
        });

        DB::table('kisi_yakinlar')
            ->join('kisiler', 'kisiler.id', '=', 'kisi_yakinlar.yakin_kisi_id')
            ->update([
                'kisi_yakinlar.ad' => DB::raw('kisiler.ad'),
                'kisi_yakinlar.soyad' => DB::raw('kisiler.soyad'),
                'kisi_yakinlar.tc_kimlik_no' => DB::raw('kisiler.tc_kimlik_no'),
                'kisi_yakinlar.dogum_tarihi' => DB::raw('kisiler.dogum_tarihi'),
                'kisi_yakinlar.cinsiyet' => DB::raw('kisiler.cinsiyet'),
            ]);

        Schema::table('kisi_yakinlar', function (Blueprint $table) {
            $table->dropForeign(['yakin_kisi_id']);
        });

        Schema::table('kisi_yakinlar', function (Blueprint $table) {
            $table->foreignId('yakin_kisi_id')->nullable()->change();
            $table->foreign('yakin_kisi_id')->references('id')->on('kisiler')->nullOnDelete();
            $table->unique(['kisi_id', 'tc_kimlik_no']);
        });

        Schema::table('kisiler', function (Blueprint $table) {
            $table->timestamp('portal_hesap_at')->nullable()->after('password');
        });

        DB::table('kisiler')->whereNotNull('password')->update(['portal_hesap_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('kisiler', function (Blueprint $table) {
            $table->dropColumn('portal_hesap_at');
        });

        DB::table('kisi_yakinlar')->whereNull('yakin_kisi_id')->delete();

        Schema::table('kisi_yakinlar', function (Blueprint $table) {
            $table->dropUnique(['kisi_id', 'tc_kimlik_no']);
            $table->dropForeign(['yakin_kisi_id']);
        });

        Schema::table('kisi_yakinlar', function (Blueprint $table) {
            $table->foreignId('yakin_kisi_id')->nullable(false)->change();
            $table->foreign('yakin_kisi_id')->references('id')->on('kisiler')->cascadeOnDelete();
            $table->dropMorphs('kaydeden');
            $table->dropColumn(['ad', 'soyad', 'tc_kimlik_no', 'dogum_tarihi', 'cinsiyet', 'son_sorgu_at']);
        });
    }
};
