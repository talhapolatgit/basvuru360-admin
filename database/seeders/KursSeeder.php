<?php

namespace Database\Seeders;

use App\Enums\HaftaGunu;
use App\Enums\KursDurum;
use App\Models\Alan;
use App\Models\Brans;
use App\Models\EgitimDurumu;
use App\Models\Kurs;
use App\Models\KursGun;
use App\Models\KursTipi;
use App\Models\Rol;
use App\Models\User;
use App\Models\Merkez;
use Illuminate\Database\Seeder;

class KursSeeder extends Seeder
{
    public function run(): void
    {
        $merkezler = collect([
            'Kasımpaşa Halk Eğitim Merkezi',
            'Cihangir Halk Eğitim Merkezi',
            'Kuledibi Halk Eğitim Merkezi',
            'Kemankeş Halk Eğitim Merkezi',
            'Hasköy Halk Eğitim Merkezi',
            'Kaptanpaşa Halk Eğitim Merkezi',
            'Karaköy Halk Eğitim Merkezi',
            'Kuledibi Halk Eğitim Merkezi',
        ])->map(fn (string $ad) => Merkez::firstOrCreate(['ad' => $ad]));

        $alan = Alan::firstOrCreate(['ad' => 'Mesleki Eğitim']);

        $branslar = collect([
            'Barista',
            'İlk Yardım',
            'İngilizce A1',
            'İngilizce A2',
            'İngilizce B1',
            'İngilizce B2',
            'İngilizce C1',
            'İngilizce C2',
            'İşaret Dili',
            'Keman',
            'Keman (Çocuk)',
            'Keman (Yetişkin)',
            'Keman (İleri)',
            'Keman (Başlangıç)',
            'Keman (Orta)',
            'Keman (Uzman)',
            'Keman (Profesyonel)',
            'Keman (Amatör)',
            'Keman (Hobi)',
            'Keman (Master)',
        ])->map(fn (string $ad) => Brans::firstOrCreate(['ad' => $ad, 'alan_id' => $alan->id]));

        $sertifika = KursTipi::firstOrCreate(['ad' => 'Sertifika']);
        KursTipi::firstOrCreate(['ad' => 'Katılım']);
        KursTipi::firstOrCreate(['ad' => 'Belgesiz']);

        EgitimDurumu::firstOrCreate(['ad' => 'İlkokul', 'seviye' => 1]);
        EgitimDurumu::firstOrCreate(['ad' => 'Ortaokul', 'seviye' => 2]);
        EgitimDurumu::firstOrCreate(['ad' => 'Lise', 'seviye' => 3]);
        EgitimDurumu::firstOrCreate(['ad' => 'Üniversite', 'seviye' => 4]);

        $admin = User::query()->where('email', 'admin@basvuru360.test')->first()
            ?? User::query()->whereHas('roller', fn ($q) => $q->where('kod', 'admin'))->first()
            ?? User::query()->first();
        $ogretmenler = User::query()->whereHas('roller', fn ($q) => $q->where('kod', 'ogretmen'))->get();
        if ($ogretmenler->isEmpty()) {
            $ogretmenler = User::query()->egitmen()->get();
        }

        if ($ogretmenler->isEmpty()) {
            $ogretmenRolId = Rol::query()->where('kod', 'ogretmen')->value('id');
            $ogretmenler = collect([
                ['ad' => 'Ahmet', 'soyad' => 'Yılmaz', 'email' => 'ogretmen1@basvuru360.test'],
                ['ad' => 'Ayşe', 'soyad' => 'Demir', 'email' => 'ogretmen2@basvuru360.test'],
                ['ad' => 'Mehmet', 'soyad' => 'Kaya', 'email' => 'ogretmen3@basvuru360.test'],
            ])->map(function (array $data) use ($ogretmenRolId) {
                $user = User::query()->firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'ad' => $data['ad'],
                        'soyad' => $data['soyad'],
                        'password' => 'password',
                        'aktif' => true,
                        'email_verified_at' => now(),
                    ]
                );

                if ($ogretmenRolId) {
                    $user->syncRoller([(int) $ogretmenRolId]);
                }

                return $user;
            });
        }

        $durumlar = [
            KursDurum::Aktif,
            KursDurum::Aktif,
            KursDurum::Aktif,
            KursDurum::Hazirlik,
            KursDurum::Hazirlik,
            KursDurum::Tamamlanan,
            KursDurum::Iptal,
        ];

        foreach (range(1, 24) as $i) {
            $kursNo = (string) (52649 - $i);
            if (Kurs::where('kurs_no', $kursNo)->exists()) {
                continue;
            }

            $baslama = now()->addDays(rand(10, 90));
            $bitis = $baslama->copy()->addDays(rand(30, 120));

            $kurs = Kurs::create([
                'kurs_no' => $kursNo,
                'merkez_id' => $merkezler->random()->id,
                'alan_id' => $alan->id,
                'brans_id' => $branslar->random()->id,
                'kurs_tipi_id' => $sertifika->id,
                'ogretmen_id' => $ogretmenler->random()->id,
                'kontenjan' => rand(15, 30),
                'yedek_kontenjan' => rand(3, 10),
                'ikamet_disi_kontenjan' => rand(0, 5),
                'meb_numarasi' => 'MEB-'.rand(100000, 999999),
                'kurs_baslama_tarihi' => $baslama,
                'kurs_bitis_tarihi' => $bitis,
                'basvuru_baslama_tarihi' => now()->subDays(rand(1, 30)),
                'basvuru_bitis_tarihi' => $baslama->copy()->subDays(3),
                'toplam_kurs_saati' => rand(40, 120),
                'basvuru_sayisi' => rand(0, 25),
                'kayit_sayisi' => rand(0, 20),
                'iptal_sayisi' => rand(0, 5),
                'durum' => $durumlar[array_rand($durumlar)],
                'onlinede_yayinlansin' => (bool) rand(0, 1),
                'olusturan_id' => $admin?->id,
            ]);

            $atananIds = [$kurs->ogretmen_id];
            if ($ogretmenler->count() > 1 && rand(0, 1) === 1) {
                $ikinci = $ogretmenler->where('id', '!=', $kurs->ogretmen_id)->random();
                $atananIds[] = $ikinci->id;
            }
            $kurs->syncOgretmenler($atananIds);

            $this->seedKursGunleri($kurs);
        }

        // Mevcut kurslara gün programı yoksa ekle
        Kurs::query()
            ->whereDoesntHave('gunler')
            ->each(fn (Kurs $kurs) => $this->seedKursGunleri($kurs));
    }

    private function seedKursGunleri(Kurs $kurs): void
    {
        $gunSecenekleri = HaftaGunu::cases();
        shuffle($gunSecenekleri);
        $secilenGunler = array_slice($gunSecenekleri, 0, rand(1, 3));

        $saatAraliklari = [
            ['09:00', '11:00', 2.0],
            ['10:00', '12:00', 2.0],
            ['13:00', '15:00', 2.0],
            ['14:00', '16:30', 2.5],
            ['18:00', '20:00', 2.0],
            ['19:00', '21:00', 2.0],
        ];

        foreach ($secilenGunler as $gun) {
            [$baslangic, $bitis, $dersSaati] = $saatAraliklari[array_rand($saatAraliklari)];

            KursGun::create([
                'kurs_id' => $kurs->id,
                'gun' => $gun,
                'baslangic_saati' => $baslangic,
                'bitis_saati' => $bitis,
                'ders_saati' => $dersSaati,
            ]);
        }
    }
}
