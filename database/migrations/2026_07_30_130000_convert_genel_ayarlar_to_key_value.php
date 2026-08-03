<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KOLONLAR = [
        'kurum_adi',
        'telefon',
        'eposta',
        'il',
        'ilce',
        'adres',
        'logo',
        'web_sitesi',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('genel_ayarlar') || ! Schema::hasColumn('genel_ayarlar', 'kurum_adi')) {
            return;
        }

        $row = DB::table('genel_ayarlar')->orderBy('id')->first();
        $veriler = [];

        if ($row) {
            foreach (self::KOLONLAR as $anahtar) {
                $deger = $row->{$anahtar} ?? null;
                if ($deger === null || $deger === '') {
                    continue;
                }
                $veriler[$anahtar] = (string) $deger;
            }
        }

        Schema::drop('genel_ayarlar');

        Schema::create('genel_ayarlar', function (Blueprint $table) {
            $table->id();
            $table->string('anahtar', 100)->unique();
            $table->text('deger')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach ($veriler as $anahtar => $deger) {
            DB::table('genel_ayarlar')->insert([
                'anahtar' => $anahtar,
                'deger' => $deger,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('genel_ayarlar') || Schema::hasColumn('genel_ayarlar', 'kurum_adi')) {
            return;
        }

        $harita = DB::table('genel_ayarlar')->pluck('deger', 'anahtar')->all();

        Schema::drop('genel_ayarlar');

        Schema::create('genel_ayarlar', function (Blueprint $table) {
            $table->id();
            $table->string('kurum_adi', 200)->nullable();
            $table->string('telefon', 20)->nullable();
            $table->string('eposta', 255)->nullable();
            $table->string('il', 100)->nullable();
            $table->string('ilce', 100)->nullable();
            $table->string('adres', 500)->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('web_sitesi', 255)->nullable();
            $table->timestamps();
        });

        $satir = ['created_at' => now(), 'updated_at' => now()];
        foreach (self::KOLONLAR as $anahtar) {
            $satir[$anahtar] = $harita[$anahtar] ?? null;
        }

        DB::table('genel_ayarlar')->insert($satir);
    }
};
