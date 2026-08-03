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

        $admin = User::factory()->create([
            'ad' => 'Sistem',
            'soyad' => 'Yöneticisi',
            'email' => 'admin@basvuru360.test',
            'password' => Hash::make('password'),
        ]);

        $adminRolId = Rol::query()->where('kod', 'admin')->value('id');
        if ($adminRolId) {
            $admin->syncRoller([(int) $adminRolId]);
        }

        User::factory()->count(3)->ogretmen()->create();

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
}
