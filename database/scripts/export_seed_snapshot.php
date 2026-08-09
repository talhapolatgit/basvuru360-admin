<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = [
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

$outDir = __DIR__.'/../data/seed';
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$manifest = [];

foreach ($tables as $table) {
    if (! Schema::hasTable($table)) {
        echo "SKIP missing: {$table}\n";
        continue;
    }

    $query = DB::table($table);
    if (Schema::hasColumn($table, 'id')) {
        $query->orderBy('id');
    }

    $rows = $query->get()->map(fn ($row) => (array) $row)->all();

    $path = $outDir.'/'.$table.'.json';
    file_put_contents(
        $path,
        json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n"
    );

    $manifest[$table] = count($rows);
    echo str_pad($table, 35).count($rows)."\n";
}

file_put_contents(
    $outDir.'/manifest.json',
    json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
);

echo "Done. Wrote ".count($manifest)." tables to {$outDir}\n";
