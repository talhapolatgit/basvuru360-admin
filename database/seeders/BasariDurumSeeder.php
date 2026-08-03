<?php

namespace Database\Seeders;

use App\Models\BasariDurum;
use App\Models\KursBasvuru;
use Illuminate\Database\Seeder;

class BasariDurumSeeder extends Seeder
{
    public function run(): void
    {
        $durumlar = [
            [
                'kod' => 'sertifika_hak_etti',
                'ad' => 'Sertifika hak etti',
                'aciklama' => 'Kursu başarıyla tamamlayarak sertifika hakkı kazandı',
                'status_sinifi' => 'status-aktif',
                'sira' => 10,
            ],
            [
                'kod' => 'katilim_belgesi_hak_etti',
                'ad' => 'Katılım belgesi hak etti',
                'aciklama' => 'Katılım belgesi almaya hak kazandı',
                'status_sinifi' => 'status-aktif',
                'sira' => 20,
            ],
            [
                'kod' => 'devamsizlik',
                'ad' => 'Devamsızlık',
                'aciklama' => 'Devamsızlık nedeniyle başarısız sayıldı',
                'status_sinifi' => 'status-iptal',
                'sira' => 30,
            ],
            [
                'kod' => 'sinava_girmedi',
                'ad' => 'Sınava girmedi',
                'aciklama' => 'Sınava katılmadığı için belgelendirilemedi',
                'status_sinifi' => 'status-iptal',
                'sira' => 40,
            ],
            [
                'kod' => 'sinav_basarisiz',
                'ad' => 'Sınav başarısız',
                'aciklama' => 'Sınavda başarısız oldu',
                'status_sinifi' => 'status-iptal',
                'sira' => 50,
            ],
        ];

        $keepKods = collect($durumlar)->pluck('kod')->all();

        foreach ($durumlar as $durum) {
            BasariDurum::updateOrCreate(
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

        $silinecekIds = BasariDurum::query()->whereNotIn('kod', $keepKods)->pluck('id');

        if ($silinecekIds->isNotEmpty()) {
            KursBasvuru::query()
                ->whereIn('basari_durumu_id', $silinecekIds)
                ->update(['basari_durumu_id' => null]);

            BasariDurum::query()->whereIn('id', $silinecekIds)->delete();
        }
    }
}
