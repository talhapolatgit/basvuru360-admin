<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use App\Models\Yetki;
use App\Support\YetkiKatalogu;
use Illuminate\Database\Seeder;

class RolYetkiSeeder extends Seeder
{
    public function run(): void
    {
        foreach (YetkiKatalogu::tumu() as $tanim) {
            Yetki::query()->updateOrCreate(
                ['kod' => $tanim['kod']],
                [
                    'ad' => $tanim['ad'],
                    'modul' => $tanim['modul'],
                    'aciklama' => $tanim['aciklama'] ?? null,
                    'sira' => $tanim['sira'],
                ]
            );
        }

        $admin = Rol::query()->updateOrCreate(
            ['kod' => 'admin'],
            [
                'ad' => 'Admin',
                'aciklama' => 'Tüm yetkilere sınırsız erişim.',
                'tum_yetkiler' => true,
                'sistem' => true,
                'sira' => 1,
                'aktif' => true,
            ]
        );

        $personel = Rol::query()->updateOrCreate(
            ['kod' => 'personel'],
            [
                'ad' => 'Personel',
                'aciklama' => 'Yönetim paneli personeli.',
                'tum_yetkiler' => false,
                'sistem' => true,
                'sira' => 2,
                'aktif' => true,
            ]
        );

        $ogretmen = Rol::query()->updateOrCreate(
            ['kod' => 'ogretmen'],
            [
                'ad' => 'Öğretmen',
                'aciklama' => 'Kurs eğitmeni.',
                'tum_yetkiler' => false,
                'sistem' => true,
                'sira' => 3,
                'aktif' => true,
            ]
        );

        $yetkiIds = Yetki::query()->pluck('id', 'kod');

        $personel->yetkiler()->sync(
            collect(YetkiKatalogu::personelYetkileri())
                ->map(fn (string $kod) => $yetkiIds[$kod] ?? null)
                ->filter()
                ->values()
                ->all()
        );

        $ogretmen->yetkiler()->sync(
            collect(YetkiKatalogu::ogretmenYetkileri())
                ->map(fn (string $kod) => $yetkiIds[$kod] ?? null)
                ->filter()
                ->values()
                ->all()
        );

        // Admin pivot tutmaz; tum_yetkiler ile bypass edilir.
        $admin->yetkiler()->detach();

        // Mevcut kullanıcıları eski rol kolonundan (varsa) veya e-postadan taşı.
        $this->kullanicilariTasi($admin, $personel, $ogretmen);
    }

    private function kullanicilariTasi(Rol $admin, Rol $personel, Rol $ogretmen): void
    {
        $hasRolColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'rol');

        User::query()->orderBy('id')->each(function (User $user) use ($admin, $personel, $ogretmen, $hasRolColumn) {
            if ($user->roller()->exists()) {
                return;
            }

            $eskiRol = $hasRolColumn ? (string) ($user->getAttributes()['rol'] ?? '') : '';

            if ($user->email === 'admin@basvuru360.test') {
                $user->roller()->syncWithoutDetaching([$admin->id]);

                return;
            }

            if ($eskiRol === 'ogretmen') {
                $user->roller()->syncWithoutDetaching([$ogretmen->id]);

                return;
            }

            $user->roller()->syncWithoutDetaching([$personel->id]);
        });
    }
}
