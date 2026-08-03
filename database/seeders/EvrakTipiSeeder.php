<?php

namespace Database\Seeders;

use App\Models\EvrakTipi;
use Illuminate\Database\Seeder;

class EvrakTipiSeeder extends Seeder
{
    public function run(): void
    {
        $tipler = [
            ['ad' => 'Kimlik Fotokopisi', 'aciklama' => 'T.C. kimlik belgesi fotokopisi'],
            ['ad' => 'İkametgah Belgesi', 'aciklama' => 'Güncel ikametgah belgesi'],
            ['ad' => 'Diploma / Öğrenci Belgesi', 'aciklama' => 'Eğitim durumunu gösteren belge'],
            ['ad' => 'Sağlık Raporu', 'aciklama' => 'Kurs için gerekli sağlık raporu'],
            ['ad' => 'Vesikalık Fotoğraf', 'aciklama' => 'Son 6 ay içinde çekilmiş fotoğraf'],
            ['ad' => 'İşsizlik Belgesi', 'aciklama' => 'İŞKUR veya ilgili kurum belgesi'],
        ];

        foreach ($tipler as $tip) {
            EvrakTipi::firstOrCreate(
                ['ad' => $tip['ad']],
                ['aciklama' => $tip['aciklama'], 'aktif' => true]
            );
        }
    }
}
