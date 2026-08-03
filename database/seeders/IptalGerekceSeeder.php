<?php

namespace Database\Seeders;

use App\Models\IptalGerekce;
use Illuminate\Database\Seeder;

class IptalGerekceSeeder extends Seeder
{
    public function run(): void
    {
        $gerekceler = [
            ['ad' => 'Kişi talebi', 'aciklama' => 'Başvuranın kendi isteğiyle iptal', 'sira' => 10],
            ['ad' => 'Yanlış başvuru', 'aciklama' => 'Hatalı veya mükerrer başvuru', 'sira' => 20],
            ['ad' => 'Koşulları sağlamıyor', 'aciklama' => 'Kurs başvuru koşullarını karşılamıyor', 'sira' => 30],
            ['ad' => 'Evrak eksikliği', 'aciklama' => 'Gerekli belgeler tamamlanmadı', 'sira' => 40],
            ['ad' => 'Kontenjan dolu', 'aciklama' => 'Kurs kontenjanı dolduğu için iptal', 'sira' => 50],
            ['ad' => 'Kurs iptal edildi', 'aciklama' => 'Kursun iptal edilmesi nedeniyle', 'sira' => 60],
            ['ad' => 'Diğer', 'aciklama' => 'Listede yer almayan diğer gerekçeler', 'sira' => 99],
        ];

        foreach ($gerekceler as $gerekce) {
            IptalGerekce::firstOrCreate(
                ['ad' => $gerekce['ad']],
                [
                    'aciklama' => $gerekce['aciklama'],
                    'sira' => $gerekce['sira'],
                    'aktif' => true,
                ]
            );
        }
    }
}
