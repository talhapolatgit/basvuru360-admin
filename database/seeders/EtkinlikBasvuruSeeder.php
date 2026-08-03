<?php

namespace Database\Seeders;

use App\Enums\Cinsiyet;
use App\Enums\KatilimDurumu;
use App\Models\EtkinlikBasvuruDurum;
use App\Models\Etkinlik;
use App\Models\EtkinlikBasvuru;
use App\Models\IptalGerekce;
use App\Models\Kisi;
use App\Models\User;
use Illuminate\Database\Seeder;

class EtkinlikBasvuruSeeder extends Seeder
{
    public function run(): void
    {
        $etkinlikler = Etkinlik::query()->orderBy('id')->get();
        $adminId = User::query()->value('id');
        $iptalGerekceId = IptalGerekce::query()->orderBy('sira')->value('id');

        $onayBekliyorId = EtkinlikBasvuruDurum::idByKod('onay_bekliyor');
        $kesinKayitId = EtkinlikBasvuruDurum::idByKod('kesin_kayit');
        $yedekId = EtkinlikBasvuruDurum::idByKod('yedek');
        $iptalId = EtkinlikBasvuruDurum::idByKod('iptal');

        if ($etkinlikler->isEmpty() || ! $onayBekliyorId) {
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
            ['ad' => 'Burak', 'soyad' => 'Koç', 'tc' => '10000001146', 'telefon' => '0542 101 1122'],
            ['ad' => 'Selin', 'soyad' => 'Aksoy', 'tc' => '10000001217', 'telefon' => '0543 202 2233'],
        ];

        $kisiler = collect($ornekKisiler)->map(function (array $row) {
            return Kisi::firstOrCreate(
                ['tc_kimlik_no' => $row['tc']],
                [
                    'ad' => $row['ad'],
                    'soyad' => $row['soyad'],
                    'telefon' => $row['telefon'],
                    'email' => mb_strtolower($row['ad']).'.'.mb_strtolower($row['soyad']).'@ornek.test',
                    'cinsiyet' => in_array($row['ad'], ['Ayşe', 'Zeynep', 'Elif', 'Fatma', 'Ece', 'Selin'], true)
                        ? Cinsiyet::Kadin
                        : Cinsiyet::Erkek,
                    'il' => 'İstanbul',
                    'ilce' => 'Beyoğlu',
                    'aktif' => true,
                ]
            );
        })->values();

        $durumIds = array_values(array_filter([
            $onayBekliyorId,
            $kesinKayitId,
            $yedekId,
            $iptalId,
            $kesinKayitId,
            $onayBekliyorId,
        ]));

        foreach ($etkinlikler as $etkinlikIndex => $etkinlik) {
            $atanan = $kisiler->shuffle()->take(rand(5, 9));

            foreach ($atanan->values() as $i => $kisi) {
                $durumId = $durumIds[$i % count($durumIds)];

                EtkinlikBasvuru::firstOrCreate(
                    [
                        'kisi_id' => $kisi->id,
                        'etkinlik_id' => $etkinlik->id,
                    ],
                    $this->basvuruAttributes(
                        $durumId,
                        $kisi->id,
                        $adminId,
                        $iptalGerekceId,
                        $kesinKayitId,
                        $iptalId,
                    )
                );
            }

            // İlk etkinlikte birkaç veli-çocuk başvurusu
            if ($etkinlikIndex === 0 && $kisiler->count() >= 10) {
                foreach ([[$kisiler[0], $kisiler[8]], [$kisiler[1], $kisiler[9]]] as $j => [$veli, $cocuk]) {
                    EtkinlikBasvuru::firstOrCreate(
                        [
                            'kisi_id' => $cocuk->id,
                            'etkinlik_id' => $etkinlik->id,
                        ],
                        $this->basvuruAttributes(
                            $durumIds[$j % count($durumIds)],
                            $veli->id,
                            $adminId,
                            $iptalGerekceId,
                            $kesinKayitId,
                            $iptalId,
                            $veli->id,
                        )
                    );
                }
            }

            $etkinlik->update([
                'basvuru_sayisi' => $etkinlik->basvurular()->count(),
                'kayit_sayisi' => $kesinKayitId ? $etkinlik->basvurular()->where('durum_id', $kesinKayitId)->count() : 0,
                'iptal_sayisi' => $iptalId ? $etkinlik->basvurular()->where('durum_id', $iptalId)->count() : 0,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function basvuruAttributes(
        int $durumId,
        int $basvuranId,
        ?int $adminId,
        ?int $iptalGerekceId,
        ?int $kesinKayitId,
        ?int $iptalId,
        ?int $veliId = null,
    ): array {
        $onaylandi = $kesinKayitId !== null && $durumId === $kesinKayitId;
        $iptal = $iptalId !== null && $durumId === $iptalId;

        $katilimDurumu = null;

        if ($onaylandi) {
            // Tamamlanan / aktif örneklerde yoklama çeşitliliği
            $roll = rand(0, 4);
            if ($roll <= 2) {
                $katilimDurumu = KatilimDurumu::Katildi->value;
            } elseif ($roll === 3) {
                $katilimDurumu = KatilimDurumu::Katilmadi->value;
            }
        }

        return [
            'basvuran_id' => $basvuranId,
            'veli_id' => $veliId,
            'durum_id' => $durumId,
            'katilim_durumu' => $katilimDurumu,
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
