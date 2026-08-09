<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds exactly the snapshot exported from the local DB (database/data/seed/*.json).
 * Password hashes are inserted as-is (no Eloquent hashed cast).
 */
class LocalDataSeeder extends Seeder
{
    /** Insert order (parents before children). */
    private const TABLES = [
        'roller',
        'yetkiler',
        'rol_yetki',
        'users',
        'kullanici_rol',
        'kurumlar',
        'merkezler',
        'kullanici_merkez',
        'kullanici_kurum',
        'iller',
        'ilceler',
        'alanlar',
        'branslar',
        'kurs_tipleri',
        'egitim_durumlari',
        'evrak_tipleri',
        'iptal_gerekceleri',
        'basari_durumlari',
        'basvuru_durumlari',
        'etkinlik_basvuru_durumlari',
        'etkinlik_tipleri',
        'numarator',
        'kurslar',
        'kurs_gunleri',
        'kurs_ogretmenleri',
        'kurs_evraklari',
        'kurs_kurum',
        'etkinlikler',
        'etkinlik_sorumlulari',
        'etkinlik_evraklari',
        'etkinlik_kurum',
        'kisiler',
        'kurs_basvurulari',
        'kurs_basvuru_evraklari',
        'etkinlik_basvurulari',
        'etkinlik_basvuru_evraklari',
        'portal_sayfalar',
        'portal_sayfa_kurallari',
        'genel_ayarlar',
        'sertifika_ayarlari',
        'entegrasyon_ayarlari',
        'kurs_ayarlari',
        'etkinlik_ayarlari',
    ];

    public function run(): void
    {
        $dir = database_path('data/seed');
        if (! is_dir($dir)) {
            throw new \RuntimeException('Seed snapshot missing: database/data/seed');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (array_reverse(self::TABLES) as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            foreach (self::TABLES as $table) {
                $this->seedTable($dir, $table);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function seedTable(string $dir, string $table): void
    {
        if (! Schema::hasTable($table)) {
            $this->command?->warn("Skip missing table: {$table}");

            return;
        }

        $path = $dir.DIRECTORY_SEPARATOR.$table.'.json';
        if (! is_file($path)) {
            $this->command?->warn("Skip missing snapshot: {$table}.json");

            return;
        }

        /** @var list<array<string, mixed>>|null $rows */
        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            throw new \RuntimeException("Invalid JSON snapshot: {$table}.json");
        }

        if ($rows === []) {
            $this->command?->info("{$table}: 0 rows");

            return;
        }

        $columns = Schema::getColumnListing($table);

        foreach (array_chunk($rows, 200) as $chunk) {
            $payload = [];
            foreach ($chunk as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $filtered = [];
                foreach ($columns as $column) {
                    if (array_key_exists($column, $row)) {
                        $filtered[$column] = $row[$column];
                    }
                }
                if ($filtered !== []) {
                    $payload[] = $filtered;
                }
            }

            if ($payload !== []) {
                DB::table($table)->insert($payload);
            }
        }

        $this->command?->info("{$table}: ".count($rows).' rows');
    }
}
