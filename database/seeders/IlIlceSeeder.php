<?php

namespace Database\Seeders;

use App\Models\Il;
use App\Models\Ilce;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IlIlceSeeder extends Seeder
{
    public function run(): void
    {
        $provincesPath = database_path('data/provinces.json');
        $districtsPath = database_path('data/districts.json');

        if (! is_file($provincesPath) || ! is_file($districtsPath)) {
            throw new \RuntimeException(
                'Il/ilce veri dosyalari eksik. Beklenen: database/data/provinces.json ve districts.json'
            );
        }

        /** @var list<array{id:int,name:string}> $provinces */
        $provinces = json_decode((string) file_get_contents($provincesPath), true, 512, JSON_THROW_ON_ERROR);
        /** @var list<array{id:int,name:string,provinceId:int}> $districts */
        $districts = json_decode((string) file_get_contents($districtsPath), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($provinces, $districts) {
            foreach ($provinces as $province) {
                Il::query()->updateOrCreate(
                    ['id' => (int) $province['id']],
                    ['ad' => trim((string) $province['name'])],
                );
            }

            foreach ($districts as $district) {
                Ilce::query()->updateOrCreate(
                    ['id' => (int) $district['id']],
                    [
                        'il_id' => (int) $district['provinceId'],
                        'ad' => trim((string) $district['name']),
                    ],
                );
            }
        });
    }
}