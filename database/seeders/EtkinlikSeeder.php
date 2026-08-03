<?php

namespace Database\Seeders;

use App\Enums\EtkinlikDurum;
use App\Enums\IkametSarti;
use App\Models\Etkinlik;
use App\Models\EtkinlikTipi;
use App\Models\Merkez;
use App\Models\Numarator;
use App\Models\User;
use App\Services\NumaratorServisi;
use Illuminate\Database\Seeder;

class EtkinlikSeeder extends Seeder
{
    public function run(): void
    {
        $merkezler = Merkez::query()->where('aktif', true)->get();
        if ($merkezler->isEmpty()) {
            $merkezler = collect([
                'Kasımpaşa Halk Eğitim Merkezi',
                'Cihangir Halk Eğitim Merkezi',
                'Kuledibi Halk Eğitim Merkezi',
            ])->map(fn (string $ad) => Merkez::firstOrCreate(['ad' => $ad], ['aktif' => true]));
        }

        $tipler = EtkinlikTipi::query()->where('aktif', true)->get();
        if ($tipler->isEmpty()) {
            foreach (['Seminer', 'Gezi', 'Festival', 'Açık Kapı', 'Diğer'] as $ad) {
                EtkinlikTipi::firstOrCreate(['ad' => $ad], ['aktif' => true]);
            }
            $tipler = EtkinlikTipi::query()->where('aktif', true)->get();
        }

        $admin = User::query()->where('email', 'admin@basvuru360.test')->first()
            ?? User::query()->whereHas('roller', fn ($q) => $q->where('kod', 'admin'))->first()
            ?? User::query()->first();

        $sorumlular = User::query()->where('aktif', true)->orderBy('id')->get();
        if ($sorumlular->isEmpty() && $admin) {
            $sorumlular = collect([$admin]);
        }

        $ornekler = [
            [
                'ad' => 'Bahar Açık Kapı Günü',
                'aciklama' => 'Merkezimizi ve kurs programlarımızı tanıtan açık kapı etkinliği.',
                'tip' => 'Açık Kapı',
                'durum' => EtkinlikDurum::Aktif,
                'gun' => 14,
                'sure' => 1,
                'kontenjan' => 120,
                'yedek' => 20,
                'yayin' => true,
            ],
            [
                'ad' => 'İşaret Dili Tanıtım Semineri',
                'aciklama' => 'Temel işaret dili farkındalık semineri.',
                'tip' => 'Seminer',
                'durum' => EtkinlikDurum::Aktif,
                'gun' => 21,
                'sure' => 0,
                'kontenjan' => 40,
                'yedek' => 10,
                'yayin' => true,
            ],
            [
                'ad' => 'Tarihi Yarımada Gezisi',
                'aciklama' => 'Rehberli kültür gezi programı (çok günlük).',
                'tip' => 'Gezi',
                'durum' => EtkinlikDurum::Aktif,
                'gun' => 30,
                'sure' => 2,
                'kontenjan' => 35,
                'yedek' => 8,
                'yayin' => true,
            ],
            [
                'ad' => 'Yaz Sanat Festivali',
                'aciklama' => 'Atölyeler, sergiler ve sahne gösterileri.',
                'tip' => 'Festival',
                'durum' => EtkinlikDurum::Hazirlik,
                'gun' => 45,
                'sure' => 3,
                'kontenjan' => 200,
                'yedek' => 40,
                'yayin' => false,
            ],
            [
                'ad' => 'Kariyer ve Meslek Tanıtım Günü',
                'aciklama' => 'Meslek alanları hakkında bilgilendirme etkinliği.',
                'tip' => 'Seminer',
                'durum' => EtkinlikDurum::Hazirlik,
                'gun' => 60,
                'sure' => 0,
                'kontenjan' => 80,
                'yedek' => 15,
                'yayin' => false,
            ],
            [
                'ad' => 'Gönüllülük ve Toplum Çalışmaları Buluşması',
                'aciklama' => 'Gönüllü projelerin paylaşıldığı buluşma.',
                'tip' => 'Diğer',
                'durum' => EtkinlikDurum::Tamamlanan,
                'gun' => -20,
                'sure' => 0,
                'kontenjan' => 50,
                'yedek' => 5,
                'yayin' => false,
            ],
            [
                'ad' => 'İlçe Kültür Festivali (İptal)',
                'aciklama' => 'Hava koşulları nedeniyle iptal edilen örnek etkinlik.',
                'tip' => 'Festival',
                'durum' => EtkinlikDurum::Iptal,
                'gun' => 10,
                'sure' => 1,
                'kontenjan' => 150,
                'yedek' => 25,
                'yayin' => false,
            ],
            [
                'ad' => 'Fotoğrafçılık Atölye Günü',
                'aciklama' => 'Başlangıç seviyesi fotoğrafçılık uygulamalı atölye.',
                'tip' => 'Seminer',
                'durum' => EtkinlikDurum::Aktif,
                'gun' => 18,
                'sure' => 0,
                'kontenjan' => 25,
                'yedek' => 5,
                'yayin' => true,
            ],
        ];

        foreach ($ornekler as $ornek) {
            if (Etkinlik::query()->where('ad', $ornek['ad'])->exists()) {
                continue;
            }

            $tip = $tipler->firstWhere('ad', $ornek['tip']) ?? $tipler->first();
            $baslangic = now()->addDays($ornek['gun'])->startOfDay();
            $bitis = $baslangic->copy()->addDays($ornek['sure']);

            $etkinlikNo = app(NumaratorServisi::class)->sonraki(
                NumaratorServisi::ETKINLIK,
                fn (string $no) => Etkinlik::query()->where('etkinlik_no', $no)->exists(),
            );

            $etkinlik = Etkinlik::create([
                'etkinlik_no' => $etkinlikNo,
                'ad' => $ornek['ad'],
                'aciklama' => $ornek['aciklama'],
                'merkez_id' => $merkezler->random()->id,
                'etkinlik_tipi_id' => $tip->id,
                'kontenjan' => $ornek['kontenjan'],
                'yedek_kontenjan' => $ornek['yedek'],
                'ikamet_disi_kontenjan' => 0,
                'baslangic_tarihi' => $baslangic->toDateString(),
                'bitis_tarihi' => $bitis->toDateString(),
                'basvuru_baslama_tarihi' => now()->subDays(rand(5, 20)),
                'basvuru_bitis_tarihi' => $baslangic->copy()->subDay()->setTime(23, 59),
                'ikamet_sarti' => IkametSarti::Hayir,
                'minimum_yas' => rand(0, 1) === 1 ? 18 : null,
                'evrak_zorunlu' => false,
                'onlinede_yayinlansin' => $ornek['yayin'],
                'durum' => $ornek['durum'],
                'basvuru_sayisi' => 0,
                'kayit_sayisi' => 0,
                'iptal_sayisi' => 0,
                'olusturan_id' => $admin?->id,
            ]);

            if ($sorumlular->isNotEmpty()) {
                $ids = [$sorumlular->random()->id];
                if ($sorumlular->count() > 1 && rand(0, 1) === 1) {
                    $ikinci = $sorumlular->where('id', '!=', $ids[0])->random();
                    $ids[] = $ikinci->id;
                }
                $etkinlik->syncSorumlular($ids);
            }
        }

        $maxNo = Etkinlik::query()
            ->pluck('etkinlik_no')
            ->filter(fn ($no) => is_string($no) && ctype_digit($no))
            ->map(fn ($no) => (int) $no)
            ->max() ?: 0;

        $numarator = Numarator::query()->where('kod', NumaratorServisi::ETKINLIK)->first();
        if ($numarator && (int) $numarator->son_numara < $maxNo) {
            $numarator->update(['son_numara' => $maxNo]);
        }
    }
}
