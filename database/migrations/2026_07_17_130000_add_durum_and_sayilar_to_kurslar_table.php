<?php

use App\Enums\KursDurum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurslar', function (Blueprint $table) {
            if (! Schema::hasColumn('kurslar', 'durum')) {
                $table->string('durum')->default(KursDurum::Hazirlik->value)->after('basvuru_sayisi')->index();
            }
            if (! Schema::hasColumn('kurslar', 'kayit_sayisi')) {
                $table->unsignedInteger('kayit_sayisi')->default(0)->after('basvuru_sayisi');
            }
            if (! Schema::hasColumn('kurslar', 'iptal_sayisi')) {
                $table->unsignedInteger('iptal_sayisi')->default(0)->after('kayit_sayisi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kurslar', function (Blueprint $table) {
            $columns = ['durum', 'kayit_sayisi', 'iptal_sayisi'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('kurslar', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
