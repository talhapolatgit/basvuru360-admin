<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolYetkiSeeder::class,
        ]);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@basvuru360.test'],
            [
                'ad' => 'Sistem',
                'soyad' => 'Yöneticisi',
                'password' => Hash::make('password'),
                'aktif' => true,
                'email_verified_at' => now(),
            ]
        );

        $adminRolId = Rol::query()->where('kod', 'admin')->value('id');
        if ($adminRolId) {
            $admin->syncRoller([(int) $adminRolId]);
        }

        $this->createOgretmenler();

        $this->call([
            IlIlceSeeder::class,
            EvrakTipiSeeder::class,
            IptalGerekceSeeder::class,
            BasariDurumSeeder::class,
            BasvuruDurumSeeder::class,
            EtkinlikBasvuruDurumSeeder::class,
            KursSeeder::class,
            KursBasvuruSeeder::class,
            EtkinlikSeeder::class,
            EtkinlikBasvuruSeeder::class,
        ]);
    }

    private function createOgretmenler(): void
    {
        $ogretmenRolId = Rol::query()->where('kod', 'ogretmen')->value('id');

        $ogretmenler = [
            ['ad' => 'Ahmet', 'soyad' => 'Yılmaz', 'email' => 'ogretmen1@basvuru360.test'],
            ['ad' => 'Ayşe', 'soyad' => 'Demir', 'email' => 'ogretmen2@basvuru360.test'],
            ['ad' => 'Mehmet', 'soyad' => 'Kaya', 'email' => 'ogretmen3@basvuru360.test'],
        ];

        foreach ($ogretmenler as $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'ad' => $data['ad'],
                    'soyad' => $data['soyad'],
                    'password' => Hash::make('password'),
                    'aktif' => true,
                    'email_verified_at' => now(),
                ]
            );

            if ($ogretmenRolId) {
                $user->syncRoller([(int) $ogretmenRolId]);
            }
        }
    }
}
