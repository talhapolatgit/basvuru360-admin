<?php

namespace Database\Seeders;

use App\Models\EtkinlikBasvuru;
use App\Models\EtkinlikBasvuruDurum;
use Illuminate\Database\Seeder;

class EtkinlikBasvuruDurumSeeder extends Seeder
{
    public function run(): void
    {
        $durumlar = [
            [
                'kod' => 'onay_bekliyor',
                'ad' => 'Onay Bekliyor',
                'aciklama' => 'Başvuru onay bekliyor',
                'status_sinifi' => 'status-hazirlik',
                'sira' => 10,
            ],
            [
                'kod' => 'kesin_kayit',
                'ad' => 'Kesin Kayıt',
                'aciklama' => 'Başvuru onaylandı, kesin kayıt yapıldı',
                'status_sinifi' => 'status-aktif',
                'sira' => 20,
            ],
            [
                'kod' => 'yedek',
                'ad' => 'Yedek',
                'aciklama' => 'Yedek listesinde bekliyor',
                'status_sinifi' => 'status-yedek',
                'sira' => 30,
            ],
            [
                'kod' => 'iptal',
                'ad' => 'İptal',
                'aciklama' => 'Başvuru iptal edildi',
                'status_sinifi' => 'status-iptal',
                'sira' => 40,
            ],
        ];

        foreach ($durumlar as $durum) {
            EtkinlikBasvuruDurum::updateOrCreate(
                ['kod' => $durum['kod']],
                [
                    'ad' => $durum['ad'],
                    'aciklama' => $durum['aciklama'],
                    'status_sinifi' => $durum['status_sinifi'],
                    'sira' => $durum['sira'],
                    'aktif' => true,
                ]
            );
        }

        $varsayilanId = EtkinlikBasvuruDurum::idByKod('onay_bekliyor');
        if ($varsayilanId) {
            $gecerliIds = EtkinlikBasvuruDurum::query()->pluck('id');
            EtkinlikBasvuru::query()
                ->whereNotIn('durum_id', $gecerliIds)
                ->update(['durum_id' => $varsayilanId]);
        }
    }
}
