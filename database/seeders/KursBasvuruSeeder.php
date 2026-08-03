<?php

namespace Database\Seeders;

use App\Enums\Cinsiyet;
use App\Models\BasariDurum;
use App\Models\BasvuruDurum;
use App\Models\IptalGerekce;
use App\Models\Kisi;
use App\Models\Kurs;
use App\Models\KursBasvuru;
use App\Models\User;
use Illuminate\Database\Seeder;

class KursBasvuruSeeder extends Seeder
{
    public function run(): void
    {
        $kurslar = Kurs::query()->take(5)->get();
        $adminId = User::query()->value('id');
        $iptalGerekceId = IptalGerekce::query()->orderBy('sira')->value('id');
        $sertifikaId = BasariDurum::query()->where('kod', 'sertifika_hak_etti')->value('id');
        $katilimId = BasariDurum::query()->where('kod', 'katilim_belgesi_hak_etti')->value('id');
        $devamsizlikId = BasariDurum::query()->where('kod', 'devamsizlik')->value('id');
        $ornekBasariDurumlari = array_values(array_filter([$sertifikaId, $katilimId, $devamsizlikId]));

        $onayBekliyorId = BasvuruDurum::idByKod('onay_bekliyor');
        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        $yedekId = BasvuruDurum::idByKod('yedek');
        $iptalId = BasvuruDurum::idByKod('iptal');

        if ($kurslar->isEmpty() || ! $onayBekliyorId) {
            return;
        }

        $ornekKisiler = [
            ['ad' => 'Ayşe', 'soyad' => 'Yılmaz', 'tc' => '10000000146', 'telefon' => '0532 111 2233'],
            ['ad' => 'Mehmet', 'soyad' => 'Demir', 'tc' => '10000000217', 'telefon' => '0533 222 3344'],
            ['ad' => 'Zeynep', 'soyad' => 'Kaya', 'tc' => '10000000388', 'telefon' => '0535 333 4455'],
            ['ad' => 'Ali', 'soyad' => 'Çelik', 'tc' => '10000000459', 'telefon' => '0536 444 5566'],
            ['ad' => 'Elif', 'soyad' => 'Şahin', 'tc' => '10000000520', 'telefon' => '0537 555 6677'],
            ['ad' => 'Can', 'soyad' => 'Aydın', 'tc' => '10000000691', 'telefon' => '0538 666 7788'],
            ['ad' => 'Fatma', 'soyad' => 'Öztürk', 'tc' => '10000000762', 'telefon' => '0539 777 8899'],
            ['ad' => 'Emre', 'soyad' => 'Arslan', 'tc' => '10000000833', 'telefon' => '0541 888 9900'],
            ['ad' => 'Ece', 'soyad' => 'Yılmaz', 'tc' => '10000000904', 'telefon' => '0532 111 2234'],
            ['ad' => 'Deniz', 'soyad' => 'Demir', 'tc' => '10000001075', 'telefon' => '0533 222 3345'],
        ];

        $kisiler = collect($ornekKisiler)->map(function (array $row) {
            return Kisi::firstOrCreate(
                ['tc_kimlik_no' => $row['tc']],
                [
                    'ad' => $row['ad'],
                    'soyad' => $row['soyad'],
                    'telefon' => $row['telefon'],
                    'email' => mb_strtolower($row['ad']).'.'.mb_strtolower($row['soyad']).'@ornek.test',
                    'cinsiyet' => in_array($row['ad'], ['Ayşe', 'Zeynep', 'Elif', 'Fatma', 'Ece'], true) ? Cinsiyet::Kadin : Cinsiyet::Erkek,
                    'il' => 'İstanbul',
                    'ilce' => 'Beyoğlu',
                    'aktif' => true,
                ]
            );
        })->values();

        $veliCocukCiftleri = [
            [$kisiler[0], $kisiler[8]],
            [$kisiler[1], $kisiler[9]],
        ];

        $durumIds = array_values(array_filter([
            $onayBekliyorId,
            $kesinKayitId,
            $yedekId,
            $iptalId,
            $kesinKayitId,
        ]));

        foreach ($kurslar as $kursIndex => $kurs) {
            $atanan = $kisiler->take(8)->shuffle()->take(rand(4, 7));

            foreach ($atanan->values() as $i => $kisi) {
                $durumId = $durumIds[$i % count($durumIds)];

                KursBasvuru::firstOrCreate(
                    [
                        'kisi_id' => $kisi->id,
                        'kurs_id' => $kurs->id,
                    ],
                    $this->basvuruAttributes($kurs, $durumId, $kisi->id, null, $adminId, $kursIndex, $iptalGerekceId, $ornekBasariDurumlari, $kesinKayitId, $iptalId)
                );
            }

            if ($kursIndex === 0) {
                foreach ($veliCocukCiftleri as $j => [$veli, $cocuk]) {
                    $durumId = $durumIds[$j % count($durumIds)];

                    KursBasvuru::firstOrCreate(
                        [
                            'kisi_id' => $cocuk->id,
                            'kurs_id' => $kurs->id,
                        ],
                        $this->basvuruAttributes($kurs, $durumId, $veli->id, $veli->id, $adminId, $kursIndex, $iptalGerekceId, $ornekBasariDurumlari, $kesinKayitId, $iptalId)
                    );
                }
            }

            $kurs->update([
                'basvuru_sayisi' => $kurs->basvurular()->count(),
                'kayit_sayisi' => $kesinKayitId ? $kurs->basvurular()->where('durum_id', $kesinKayitId)->count() : 0,
                'iptal_sayisi' => $iptalId ? $kurs->basvurular()->where('durum_id', $iptalId)->count() : 0,
            ]);
        }
    }

    /**
     * @param  list<int>  $ornekBasariDurumlari
     * @return array<string, mixed>
     */
    private function basvuruAttributes(
        Kurs $kurs,
        int $durumId,
        int $basvuranId,
        ?int $veliId,
        ?int $adminId,
        int $kursIndex,
        ?int $iptalGerekceId,
        array $ornekBasariDurumlari,
        ?int $kesinKayitId,
        ?int $iptalId,
    ): array {
        $onaylandi = $kesinKayitId !== null && $durumId === $kesinKayitId;
        $iptal = $iptalId !== null && $durumId === $iptalId;

        $basariDurumuId = null;
        if ($onaylandi && $kursIndex === 0 && $ornekBasariDurumlari !== []) {
            $basariDurumuId = $ornekBasariDurumlari[array_rand($ornekBasariDurumlari)];
        }

        $kursaBaslamaTarihi = $kurs->kurs_baslama_tarihi?->toDateString();
        if ($onaylandi && $kursaBaslamaTarihi && rand(0, 3) === 0) {
            $kursaBaslamaTarihi = $kurs->kurs_baslama_tarihi
                ->copy()
                ->addDays(rand(3, 14))
                ->min($kurs->kurs_bitis_tarihi ?? $kurs->kurs_baslama_tarihi)
                ->toDateString();
        }

        return [
            'basvuran_id' => $basvuranId,
            'veli_id' => $veliId,
            'durum_id' => $durumId,
            'basari_durumu_id' => $basariDurumuId,
            'kursa_baslama_tarihi' => $kursaBaslamaTarihi,
            'onay_tarihi' => $onaylandi ? now()->subDays(rand(1, 10)) : null,
            'onaylayan_id' => $onaylandi ? $adminId : null,
            'iptal_tarihi' => $iptal ? now()->subDays(rand(1, 5)) : null,
            'iptal_gerekce_id' => $iptal ? $iptalGerekceId : null,
            'iptal_eden_id' => $iptal ? $adminId : null,
            'olusturan_id' => $adminId,
            'guncelleyen_id' => $adminId,
        ];
    }
}
