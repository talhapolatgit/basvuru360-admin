<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const KOLONLAR = [
        'sms_basvuru_onay',
        'sms_basvuru_iptal',
        'sms_basvuru_yedek',
        'sms_metin_onay',
        'sms_metin_iptal',
        'sms_metin_yedek',
        'eposta_basvuru_onay',
        'eposta_basvuru_iptal',
        'eposta_basvuru_yedek',
        'eposta_konu_onay',
        'eposta_konu_iptal',
        'eposta_konu_yedek',
        'eposta_metin_onay',
        'eposta_metin_iptal',
        'eposta_metin_yedek',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('etkinlik_ayarlari') || ! Schema::hasColumn('etkinlik_ayarlari', 'sms_basvuru_onay')) {
            return;
        }

        $row = DB::table('etkinlik_ayarlari')->orderBy('id')->first();
        $veriler = [];

        if ($row) {
            foreach (self::KOLONLAR as $anahtar) {
                if (! property_exists($row, $anahtar) && ! isset($row->{$anahtar})) {
                    continue;
                }
                $deger = $row->{$anahtar} ?? null;
                if ($deger === null || $deger === '') {
                    continue;
                }
                $veriler[$anahtar] = (string) $deger;
            }
        }

        Schema::drop('etkinlik_ayarlari');

        Schema::create('etkinlik_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->string('anahtar', 100)->unique();
            $table->text('deger')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach ($veriler as $anahtar => $deger) {
            DB::table('etkinlik_ayarlari')->insert([
                'anahtar' => $anahtar,
                'deger' => $deger,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('etkinlik_ayarlari') || Schema::hasColumn('etkinlik_ayarlari', 'sms_basvuru_onay')) {
            return;
        }

        $harita = DB::table('etkinlik_ayarlari')->pluck('deger', 'anahtar')->all();

        Schema::drop('etkinlik_ayarlari');

        Schema::create('etkinlik_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->string('sms_basvuru_onay', 20)->default('istege_bagli');
            $table->string('sms_basvuru_iptal', 20)->default('istege_bagli');
            $table->string('sms_basvuru_yedek', 20)->default('istege_bagli');
            $table->text('sms_metin_onay')->nullable();
            $table->text('sms_metin_iptal')->nullable();
            $table->text('sms_metin_yedek')->nullable();
            $table->string('eposta_basvuru_onay', 20)->default('istege_bagli');
            $table->string('eposta_basvuru_iptal', 20)->default('istege_bagli');
            $table->string('eposta_basvuru_yedek', 20)->default('istege_bagli');
            $table->string('eposta_konu_onay', 200)->nullable();
            $table->string('eposta_konu_iptal', 200)->nullable();
            $table->string('eposta_konu_yedek', 200)->nullable();
            $table->text('eposta_metin_onay')->nullable();
            $table->text('eposta_metin_iptal')->nullable();
            $table->text('eposta_metin_yedek')->nullable();
            $table->timestamps();
        });

        $satir = [
            'sms_basvuru_onay' => $harita['sms_basvuru_onay'] ?? 'istege_bagli',
            'sms_basvuru_iptal' => $harita['sms_basvuru_iptal'] ?? 'istege_bagli',
            'sms_basvuru_yedek' => $harita['sms_basvuru_yedek'] ?? 'istege_bagli',
            'sms_metin_onay' => $harita['sms_metin_onay'] ?? null,
            'sms_metin_iptal' => $harita['sms_metin_iptal'] ?? null,
            'sms_metin_yedek' => $harita['sms_metin_yedek'] ?? null,
            'eposta_basvuru_onay' => $harita['eposta_basvuru_onay'] ?? 'istege_bagli',
            'eposta_basvuru_iptal' => $harita['eposta_basvuru_iptal'] ?? 'istege_bagli',
            'eposta_basvuru_yedek' => $harita['eposta_basvuru_yedek'] ?? 'istege_bagli',
            'eposta_konu_onay' => $harita['eposta_konu_onay'] ?? null,
            'eposta_konu_iptal' => $harita['eposta_konu_iptal'] ?? null,
            'eposta_konu_yedek' => $harita['eposta_konu_yedek'] ?? null,
            'eposta_metin_onay' => $harita['eposta_metin_onay'] ?? null,
            'eposta_metin_iptal' => $harita['eposta_metin_iptal'] ?? null,
            'eposta_metin_yedek' => $harita['eposta_metin_yedek'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('etkinlik_ayarlari')->insert($satir);
    }
};
