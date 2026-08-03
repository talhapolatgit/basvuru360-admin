<?php

namespace App\Services;

use App\Models\BasvuruDurum;
use App\Models\Kurs;
use App\Models\KursBasvuru;
use Illuminate\Validation\ValidationException;

class KursYedekListeServisi
{
    /**
     * Ana kontenjanı dolduran durumlar (onay bekleyen + kesin kayıt).
     *
     * @return list<string>
     */
    public function anaListeKodlari(): array
    {
        return ['onay_bekliyor', 'kesin_kayit'];
    }

    /**
     * Yeni başvuru için durum ve yedek sırasını belirler.
     * Kurs satırı transaction içinde kilitlenmiş olmalıdır.
     *
     * @return array{durum_kod: string, durum_id: int, yedek_sira: int|null}
     */
    public function yeniBasvuruDurumuBelirle(Kurs $kurs): array
    {
        $onayBekliyorId = BasvuruDurum::idByKod('onay_bekliyor');
        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        $yedekId = BasvuruDurum::idByKod('yedek');

        if (! $onayBekliyorId || ! $yedekId) {
            throw ValidationException::withMessages([
                'kurs_id' => 'Başvuru durum tanımları eksik (onay bekliyor / yedek).',
            ]);
        }

        $kontenjan = max(0, (int) $kurs->kontenjan);
        $yedekKontenjan = max(0, (int) $kurs->yedek_kontenjan);

        $anaListeIds = array_values(array_filter([$onayBekliyorId, $kesinKayitId]));
        $anaSayisi = KursBasvuru::query()
            ->where('kurs_id', $kurs->id)
            ->whereIn('durum_id', $anaListeIds)
            ->lockForUpdate()
            ->count();

        if ($anaSayisi < $kontenjan) {
            return [
                'durum_kod' => 'onay_bekliyor',
                'durum_id' => $onayBekliyorId,
                'yedek_sira' => null,
            ];
        }

        if ($yedekKontenjan <= 0) {
            throw ValidationException::withMessages([
                'kurs_id' => 'Bu kursun kontenjanı dolmuştur ve yedek kontenjan tanımlanmamıştır.',
            ]);
        }

        $yedekSayisi = KursBasvuru::query()
            ->where('kurs_id', $kurs->id)
            ->where('durum_id', $yedekId)
            ->lockForUpdate()
            ->count();

        if ($yedekSayisi >= $yedekKontenjan) {
            throw ValidationException::withMessages([
                'kurs_id' => 'Ana kontenjan ve yedek kontenjan ('.$yedekKontenjan.') dolmuştur. Yeni başvuru alınamaz.',
            ]);
        }

        return [
            'durum_kod' => 'yedek',
            'durum_id' => $yedekId,
            'yedek_sira' => $this->sonrakiYedekSira($kurs->id, $yedekId),
        ];
    }

    public function sonrakiYedekSira(int $kursId, ?int $yedekDurumId = null): int
    {
        $yedekId = $yedekDurumId ?? BasvuruDurum::idByKod('yedek');
        if (! $yedekId) {
            return 1;
        }

        $max = KursBasvuru::query()
            ->where('kurs_id', $kursId)
            ->where('durum_id', $yedekId)
            ->max('yedek_sira');

        return ((int) $max) + 1;
    }

    /**
     * Durum değişiminde yedek sırasını günceller.
     * Yedeğe alınan başvuruya sıra verir; yedekten çıkanı temizler ve listeyi yeniden numaralandırır.
     */
    public function durumDegisimindeYedekSirasiGuncelle(
        Kurs $kurs,
        KursBasvuru $basvuru,
        ?string $eskiKod,
        string $yeniKod,
    ): void {
        if ($yeniKod === 'yedek') {
            if ($basvuru->yedek_sira === null) {
                $basvuru->yedek_sira = $this->sonrakiYedekSira((int) $kurs->id);
                $basvuru->save();
            }

            return;
        }

        if ($eskiKod === 'yedek' || $basvuru->yedek_sira !== null) {
            $basvuru->yedek_sira = null;
            $basvuru->save();
            $this->yedekListesiniYenidenNumaralandir((int) $kurs->id);
        }
    }

    public function yedekListesiniYenidenNumaralandir(int $kursId): void
    {
        $yedekId = BasvuruDurum::idByKod('yedek');
        if (! $yedekId) {
            return;
        }

        $liste = KursBasvuru::query()
            ->where('kurs_id', $kursId)
            ->where('durum_id', $yedekId)
            ->orderByRaw('CASE WHEN yedek_sira IS NULL THEN 1 ELSE 0 END')
            ->orderBy('yedek_sira')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $sira = 1;
        foreach ($liste as $kayit) {
            if ((int) $kayit->yedek_sira !== $sira) {
                $kayit->yedek_sira = $sira;
                $kayit->save();
            }
            $sira++;
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, KursBasvuru>
     */
    public function yedekBasvurulari(Kurs $kurs)
    {
        $yedekId = BasvuruDurum::idByKod('yedek');
        if (! $yedekId) {
            return collect();
        }

        return KursBasvuru::query()
            ->where('kurs_id', $kurs->id)
            ->where('durum_id', $yedekId)
            ->with(['kisi', 'basvuran'])
            ->orderByRaw('CASE WHEN yedek_sira IS NULL THEN 1 ELSE 0 END')
            ->orderBy('yedek_sira')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int|string>  $orderedIds
     */
    public function sirayiGuncelle(Kurs $kurs, array $orderedIds): void
    {
        $yedekId = BasvuruDurum::idByKod('yedek');
        if (! $yedekId) {
            throw ValidationException::withMessages([
                'basvuru_ids' => 'Yedek başvuru durumu tanımlı değil.',
            ]);
        }

        $orderedIds = array_values(array_unique(array_map('intval', $orderedIds)));

        $mevcutIds = KursBasvuru::query()
            ->where('kurs_id', $kurs->id)
            ->where('durum_id', $yedekId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $gonderilenSirali = $orderedIds;
        sort($gonderilenSirali);

        if ($gonderilenSirali !== $mevcutIds) {
            throw ValidationException::withMessages([
                'basvuru_ids' => 'Yedek listesi değişmiş. Sayfayı yenileyip tekrar deneyin.',
            ]);
        }

        if ($orderedIds === []) {
            return;
        }

        foreach ($orderedIds as $index => $basvuruId) {
            KursBasvuru::query()
                ->whereKey($basvuruId)
                ->where('kurs_id', $kurs->id)
                ->where('durum_id', $yedekId)
                ->update(['yedek_sira' => $index + 1]);
        }
    }

    /**
     * @return array{ana_sayisi: int, yedek_sayisi: int, kontenjan: int, yedek_kontenjan: int, ana_dolu: bool, yedek_dolu: bool}
     */
    public function dolulukOzeti(Kurs $kurs): array
    {
        $onayBekliyorId = BasvuruDurum::idByKod('onay_bekliyor');
        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        $yedekId = BasvuruDurum::idByKod('yedek');

        $anaListeIds = array_values(array_filter([$onayBekliyorId, $kesinKayitId]));
        $anaSayisi = $anaListeIds === []
            ? 0
            : KursBasvuru::query()
                ->where('kurs_id', $kurs->id)
                ->whereIn('durum_id', $anaListeIds)
                ->count();

        $yedekSayisi = $yedekId
            ? KursBasvuru::query()
                ->where('kurs_id', $kurs->id)
                ->where('durum_id', $yedekId)
                ->count()
            : 0;

        $kontenjan = max(0, (int) $kurs->kontenjan);
        $yedekKontenjan = max(0, (int) $kurs->yedek_kontenjan);

        return [
            'ana_sayisi' => $anaSayisi,
            'yedek_sayisi' => $yedekSayisi,
            'kontenjan' => $kontenjan,
            'yedek_kontenjan' => $yedekKontenjan,
            'ana_dolu' => $kontenjan > 0 ? $anaSayisi >= $kontenjan : $anaSayisi > 0,
            'yedek_dolu' => $yedekKontenjan > 0 ? $yedekSayisi >= $yedekKontenjan : true,
        ];
    }
}
