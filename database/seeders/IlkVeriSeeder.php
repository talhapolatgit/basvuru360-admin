<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * database/data/ilk-veri paketini (tanımlar, ayarlar, yönetici ve öğretmen) yükler.
 * Paket `php artisan ilk-veri:disari-aktar` ile üretilir.
 *
 * Kullanım (boş veritabanında):
 *   php artisan migrate:fresh --force
 *   ILK_YONETICI_SIFRE='...' php artisan db:seed --class=IlkVeriSeeder --force
 *
 * ILK_YONETICI_SIFRE verilmezse rastgele şifre üretilip ekrana bir kez yazılır.
 */
class IlkVeriSeeder extends Seeder
{
    /** Bu tablolarda kayıt varsa canlı veriyi ezmemek için yükleme durdurulur. */
    private const BOS_OLMALI = [
        'kisiler',
        'kurslar',
        'etkinlikler',
        'kurs_basvurulari',
        'etkinlik_basvurulari',
        'kres_basvurulari',
    ];

    public function run(): void
    {
        $klasor = database_path('data/ilk-veri');
        $manifest = json_decode((string) @file_get_contents("{$klasor}/manifest.json"), true);
        if (! is_array($manifest['tablolar'] ?? null)) {
            throw new RuntimeException("İlk veri paketi bulunamadı: {$klasor}/manifest.json");
        }

        foreach (self::BOS_OLMALI as $tablo) {
            if (Schema::hasTable($tablo) && DB::table($tablo)->exists()) {
                throw new RuntimeException(
                    "'{$tablo}' tablosunda kayıt var. İlk veri yalnızca boş veritabanına yüklenir; "
                    .'önce yedek alıp `php artisan migrate:fresh --force` çalıştırın.'
                );
            }
        }

        Schema::disableForeignKeyConstraints();
        try {
            foreach (array_keys($manifest['tablolar']) as $tablo) {
                $this->yukle($tablo, "{$klasor}/{$tablo}.json");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->yoneticiSifresiniAyarla();
    }

    private function yukle(string $tablo, string $dosya): void
    {
        if (! Schema::hasTable($tablo)) {
            $this->command?->warn("Tablo yok, atlandı: {$tablo}");

            return;
        }

        $satirlar = json_decode((string) file_get_contents($dosya), true) ?: [];
        $kolonlar = array_flip(Schema::getColumnListing($tablo));

        DB::table($tablo)->truncate();
        foreach (array_chunk($satirlar, 200) as $parca) {
            DB::table($tablo)->insert(array_map(
                fn (array $satir) => array_intersect_key($satir, $kolonlar),
                $parca
            ));
        }

        $this->command?->line(sprintf('  %-28s %d', $tablo, count($satirlar)));
    }

    private function yoneticiSifresiniAyarla(): void
    {
        $yonetici = DB::table('users')->orderBy('id')->first();
        if (! $yonetici) {
            return;
        }

        $this->sifreYaz($yonetici, (string) env('ILK_YONETICI_SIFRE', ''), 'Yönetici');

        $ogretmen = DB::table('users')->where('email', 'ogretmen@basvuru360.com')->first();
        if ($ogretmen && (int) $ogretmen->id !== (int) $yonetici->id) {
            $ogretmenSifre = (string) env('ILK_OGRETMEN_SIFRE', '');
            $this->sifreYaz(
                $ogretmen,
                $ogretmenSifre !== '' ? $ogretmenSifre : 'Ogretmen360!',
                'Öğretmen',
            );
        }
    }

    private function sifreYaz(object $kullanici, string $sifre, string $etiket): void
    {
        $uretildi = $sifre === '';
        if ($uretildi) {
            $sifre = Str::password(16, symbols: false);
        }

        DB::table('users')->where('id', $kullanici->id)->update([
            'password' => Hash::make($sifre),
            'remember_token' => null,
            'updated_at' => now(),
        ]);

        $this->command?->info("{$etiket}: {$kullanici->email}");
        if ($uretildi) {
            $this->command?->warn("Üretilen şifre (bir kez gösterilir): {$sifre}");
        }
    }
}
