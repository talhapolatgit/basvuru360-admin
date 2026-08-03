<?php

namespace Database\Seeders;

use App\Models\BasvuruDurum;
use App\Models\KursBasvuru;
use Illuminate\Database\Seeder;

class BasvuruDurumSeeder extends Seeder
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

        $keepKods = collect($durumlar)->pluck('kod')->all();

        foreach ($durumlar as $durum) {
            BasvuruDurum::updateOrCreate(
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

        $silinecekIds = BasvuruDurum::query()->whereNotIn('kod', $keepKods)->pluck('id');

        if ($silinecekIds->isNotEmpty()) {
            $varsayilanId = BasvuruDurum::idByKod('onay_bekliyor');

            KursBasvuru::query()
                ->whereIn('durum_id', $silinecekIds)
                ->update(['durum_id' => $varsayilanId]);

            BasvuruDurum::query()->whereIn('id', $silinecekIds)->delete();
        }
    }
}
