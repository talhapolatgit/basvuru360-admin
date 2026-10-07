<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kres_basvurulari', 'aktif_kayit_anahtari') || ! $this->generatedColumn()) {
            return;
        }

        Schema::withoutForeignKeyConstraints(function () {
            if (Schema::hasIndex('kres_basvurulari', 'kres_basvurulari_aktif_kayit_anahtari_unique')) {
                Schema::table('kres_basvurulari', function (Blueprint $table) {
                    $table->dropUnique('kres_basvurulari_aktif_kayit_anahtari_unique');
                });
            }

            Schema::table('kres_basvurulari', function (Blueprint $table) {
                $table->dropColumn('aktif_kayit_anahtari');
            });

            Schema::table('kres_basvurulari', function (Blueprint $table) {
                $table->string('aktif_kayit_anahtari', 64)->nullable();
            });
        });

        DB::table('kres_basvurulari')
            ->whereNull('deleted_at')
            ->update([
                'aktif_kayit_anahtari' => DB::raw("CONCAT(grup_id, '-', kisi_id)"),
            ]);

        Schema::table('kres_basvurulari', function (Blueprint $table) {
            $table->unique('aktif_kayit_anahtari');
        });
    }

    public function down(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            Schema::table('kres_basvurulari', function (Blueprint $table) {
                $table->dropUnique('kres_basvurulari_aktif_kayit_anahtari_unique');
                $table->dropColumn('aktif_kayit_anahtari');
            });

            Schema::table('kres_basvurulari', function (Blueprint $table) {
                $table->string('aktif_kayit_anahtari', 64)
                    ->nullable()
                    ->storedAs("IF(deleted_at IS NULL, CONCAT(grup_id, '-', kisi_id), NULL)");
                $table->unique('aktif_kayit_anahtari');
            });
        });
    }

    private function generatedColumn(): bool
    {
        $column = DB::selectOne(
            'select EXTRA as extra from information_schema.COLUMNS where TABLE_SCHEMA = database() and TABLE_NAME = ? and COLUMN_NAME = ?',
            ['kres_basvurulari', 'aktif_kayit_anahtari']
        );

        return str_contains(strtoupper((string) ($column->extra ?? '')), 'GENERATED');
    }
};
