<?php

namespace App\Services;

use App\Models\Kurs;
use App\Models\KursBasvuru;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class SertifikaPdfOlusturucu
{
    public function __construct(
        private readonly SertifikaAyarServisi $ayarServisi,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function belgeler(Kurs $kurs): Collection
    {
        $ayarlar = $this->ayarServisi->ayarlar();
        $kodlar = $ayarlar['hak_eden_kodlar'] ?: [
            'sertifika_hak_etti',
            'katilim_belgesi_hak_etti',
        ];

        $kurs->loadMissing(['merkez', 'alan', 'brans', 'ogretmenler']);

        $basvurular = KursBasvuru::query()
            ->where('kurs_id', $kurs->id)
            ->whereHas('basariDurum', fn ($q) => $q->whereIn('kod', $kodlar))
            ->with(['kisi', 'basariDurum'])
            ->orderBy('id')
            ->get();

        return $basvurular->map(fn (KursBasvuru $basvuru) => $this->belgeVerisi($kurs, $basvuru, $ayarlar));
    }

    /**
     * @param  array<string, mixed>|null  $ayarlar
     * @return array<string, mixed>
     */
    public function belgeVerisi(Kurs $kurs, KursBasvuru $basvuru, ?array $ayarlar = null): array
    {
        $ayarlar ??= $this->ayarServisi->ayarlar();
        $basariKod = $basvuru->basariDurum?->kod ?? '';
        $sablon = $ayarlar['sablonlar'][$basariKod] ?? null;

        if (! is_array($sablon)) {
            $sablon = $ayarlar['sablonlar']['sertifika_hak_etti'] ?? [];
        }

        $kisiAdi = $basvuru->kisi?->tam_adi ?: '—';
        $kursAdi = $kurs->brans?->ad ?? ('Kurs #'.$kurs->kurs_no);
        $kurumAdi = (string) ($ayarlar['kurum_adi'] ?? config('app.name'));
        $egitmen = $kurs->ogretmenAdlari() !== '—' ? $kurs->ogretmenAdlari() : '';
        $belgeKodu = (string) ($sablon['kod'] ?? 'BLG');
        $belgeNo = sprintf('%s-%s-%04d', $belgeKodu, $kurs->kurs_no, $basvuru->id);
        $tarih = now()->format('d.m.Y');
        $baslangic = $kurs->kurs_baslama_tarihi?->format('d.m.Y') ?? '';
        $bitis = $kurs->kurs_bitis_tarihi?->format('d.m.Y') ?? '';
        $donem = collect([$baslangic, $bitis])->filter()->implode(' – ');
        $sure = $kurs->toplam_kurs_saati ? (string) $kurs->toplam_kurs_saati : '';

        $yerTutucular = [
            ':kisi' => $kisiAdi,
            ':tc' => (string) ($basvuru->kisi?->tc_kimlik_no ?? ''),
            ':kurs' => $kursAdi,
            ':kurs_no' => (string) $kurs->kurs_no,
            ':alan' => (string) ($kurs->alan?->ad ?? ''),
            ':merkez' => (string) ($kurs->merkez?->ad ?? ''),
            ':kurum' => $kurumAdi,
            ':egitmen' => $egitmen,
            ':sure' => $sure,
            ':baslangic' => $baslangic,
            ':bitis' => $bitis,
            ':donem' => $donem,
            ':belge_no' => $belgeNo,
            ':tarih' => $tarih,
        ];

        // Uzun anahtarlar önce (ör. :kurs_no, :baslangic) değiştirilsin.
        uksort($yerTutucular, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        $metin = $this->yerTutucuUygula((string) ($sablon['metin'] ?? ''), $yerTutucular);
        $altMetin = $this->yerTutucuUygula((string) ($sablon['alt_metin'] ?? ''), $yerTutucular);
        $altMetin = $this->bosEtiketleriTemizle($altMetin);

        $arkaPlan = $this->arkaPlanDataUri((string) ($sablon['arka_plan'] ?? ''));
        $kenarlik = max(0, min(40, (float) ($sablon['kenarlik_olcusu'] ?? 12)));

        $imzaSol = '';
        if (! empty($sablon['egitmen_imzasi'])) {
            $imzaSol = ($egitmen !== '' ? $egitmen : '………………')."\nEğitmen";
        }

        $imzaSag = '';
        if (! empty($sablon['diger_imzaci'])) {
            $digerAd = trim((string) ($sablon['diger_imzaci_ad_soyad'] ?? ''));
            $digerUnvan = trim((string) ($sablon['diger_imzaci_unvan'] ?? ''));
            $imzaSag = ($digerAd !== '' ? $digerAd : '………………')."\n".($digerUnvan !== '' ? $digerUnvan : '………………');
        }

        return [
            'sablon' => $sablon,
            'arka_plan' => $arkaPlan,
            'kenarlik_olcusu' => $kenarlik,
            'kurum_adi' => $kurumAdi,
            'baslik' => (string) ($sablon['baslik'] ?? ''),
            'alt_baslik' => (string) ($sablon['alt_baslik'] ?? ''),
            'kisi_adi' => $kisiAdi,
            'metin' => $metin,
            'detay' => $altMetin,
            'belge_no' => ! empty($sablon['belge_no_yazdir']) ? 'Belge No: '.$belgeNo : '',
            'tarih' => ! empty($sablon['tarih_yazdir']) ? 'Tarih: '.$tarih : '',
            'imza_sol' => $imzaSol,
            'imza_sag' => $imzaSag,
            'renk' => (string) ($sablon['renk'] ?? '#1e3a5f'),
            'vurgu' => (string) ($sablon['vurgu'] ?? '#b8860b'),
            'alanlar' => is_array($sablon['alanlar'] ?? null) ? $sablon['alanlar'] : [],
        ];
    }

    /**
     * @param  array<string, string>  $yerTutucular
     */
    public function yerTutucuUygula(string $metin, array $yerTutucular): string
    {
        return strtr($metin, $yerTutucular);
    }

    /**
     * "Alan:  · Merkez: X" gibi boş değerli etiketleri temizler.
     */
    public function bosEtiketleriTemizle(string $metin): string
    {
        $metin = trim($metin);
        if ($metin === '') {
            return '';
        }

        $parcalar = preg_split('/\s*·\s*/u', $metin) ?: [];
        $parcalar = array_values(array_filter(array_map('trim', $parcalar), function (string $parca) {
            if ($parca === '') {
                return false;
            }

            // "Etiket:" veya "Etiket:   " → boş değer
            return ! preg_match('/^[^:]+:\s*$/u', $parca);
        }));

        return implode(' · ', $parcalar);
    }

    public function arkaPlanDataUri(string $path): ?string
    {
        if ($path === '' || ! File::isFile($path)) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => null,
        };

        if ($mime === null) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) File::get($path));
    }
}
