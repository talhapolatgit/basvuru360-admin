<?php

namespace App\Services;

use App\Models\Kisi;
use App\Models\KisiYakin;
use App\Models\YakinlikDerecesi;
use App\Services\Entegrasyon\EntegrasyonAyarServisi;
use App\Services\Yakin\YakinSorgulama;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Kişi yakınlarını kişi kaydı (üyelik) oluşturmadan kaydeder.
 */
class KisiYakinServisi
{
    /** Entegrasyondan gelen yakınlık kodu => yakinlik_dereceleri.kod */
    private const ENTEGRASYON_DERECELERI = [
        'ESI' => 'ESI',
        'OGLU' => 'OGLU',
        'KIZI' => 'KIZI',
        'ANNE' => 'ANNE',
        'ANNESI' => 'ANNE',
        'BABA' => 'BABA',
        'BABASI' => 'BABA',
    ];

    /**
     * Yakını T.C. kimlik numarasına göre ekler veya günceller.
     *
     * @param  array{ad: string, soyad: string, tc_kimlik_no: string, dogum_tarihi?: string|null, cinsiyet?: string|null, yakinlik_derecesi_id: int}  $veri
     * @param  Model|null  $kaydeden  Yönetim paneli kullanıcısı (User) veya portal kişisi (Kisi)
     */
    public function kaydet(Kisi $kisi, array $veri, ?Model $kaydeden, bool $sorgulandi = false): KisiYakin
    {
        $tc = trim($veri['tc_kimlik_no']);

        $yakin = KisiYakin::query()->firstOrNew(['kisi_id' => $kisi->id, 'tc_kimlik_no' => $tc]);
        $yakin->fill([
            'ad' => $this->buyukHarf($veri['ad']),
            'soyad' => $this->buyukHarf($veri['soyad']),
            'dogum_tarihi' => $veri['dogum_tarihi'] ?? $yakin->dogum_tarihi,
            'cinsiyet' => $veri['cinsiyet'] ?? $yakin->cinsiyet,
            'yakinlik_derecesi_id' => $veri['yakinlik_derecesi_id'],
        ]);
        $yakin->yakin_kisi_id = Kisi::query()
            ->where('tc_kimlik_no', $tc)
            ->whereKeyNot($kisi->id)
            ->value('id');

        if ($sorgulandi) {
            $yakin->son_sorgu_at = now();
        }
        if (! $yakin->exists && $kaydeden) {
            $yakin->kaydeden()->associate($kaydeden);
        }

        $yakin->save();

        return $yakin;
    }

    /**
     * Aktif yakın sorgulama entegrasyonundan kişinin 1. derece yakınlarını çekip kaydeder.
     * Eşi varsa eşinin bilgileriyle de sorgu yapılır ve eşin çocukları listeye eklenir.
     * Vefat etmiş ve yakınlık derecesi tanımsız olanlar atlanır.
     *
     * @return array{eklenen: int, guncellenen: int, atlanan: int}
     */
    public function entegrasyondanAktar(Kisi $kisi, ?Model $kaydeden): array
    {
        if (! app(EntegrasyonAyarServisi::class)->turAktifMi('yakin_sorgulama')) {
            throw new RuntimeException('Yakın sorgulama entegrasyonu pasif durumda.');
        }

        if (blank($kisi->tc_kimlik_no) || $kisi->dogum_tarihi === null) {
            throw new RuntimeException('Yakın sorgulaması için kişinin T.C. kimlik numarası ve doğum tarihi kayıtlı olmalıdır.');
        }

        $sorgulama = app(YakinSorgulama::class);
        $sonuc = $sorgulama->sorgula($kisi->tc_kimlik_no, $kisi->dogum_tarihi->format('Y-m-d'));
        if (! ($sonuc['ok'] ?? false)) {
            throw new RuntimeException($sonuc['message'] ?? 'Yakın bilgileri alınamadı.');
        }

        $yakinlar = $this->esinCocuklariniEkle($sorgulama, $kisi, $sonuc['yakinlar'] ?? []);

        $dereceler = YakinlikDerecesi::query()->pluck('id', 'kod');
        $sayac = ['eklenen' => 0, 'guncellenen' => 0, 'atlanan' => 0];

        foreach ($yakinlar as $kayit) {
            $derece = $dereceler[self::ENTEGRASYON_DERECELERI[$kayit['yakinlik_kodu'] ?? ''] ?? ''] ?? null;
            $tc = (string) ($kayit['tc_kimlik_no'] ?? '');

            if (! empty($kayit['olum_tarihi']) || $derece === null || ! preg_match('/^\d{11}$/', $tc)
                || blank($kayit['ad'] ?? null) || $tc === $kisi->tc_kimlik_no) {
                $sayac['atlanan']++;

                continue;
            }

            $yakin = $this->kaydet($kisi, [
                'ad' => (string) $kayit['ad'],
                'soyad' => (string) ($kayit['soyad'] ?? ''),
                'tc_kimlik_no' => $tc,
                'dogum_tarihi' => $kayit['dogum_tarihi'] ?? null,
                'cinsiyet' => $kayit['cinsiyet'] ?? null,
                'yakinlik_derecesi_id' => (int) $derece,
            ], $kaydeden, sorgulandi: true);

            $sayac[$yakin->wasRecentlyCreated ? 'eklenen' : 'guncellenen']++;
        }

        return $sayac;
    }

    /**
     * Sağ olan eşin T.C. ve doğum tarihiyle sorgu yapıp eşin çocuklarını (Oğlu/Kızı) listeye ekler.
     * Aynı T.C. iki listede de varsa kişinin kendi sorgusundaki kayıt korunur.
     * Eş sorgusu başarısız olursa kişinin kendi sonucu kullanılır.
     *
     * @param  list<array<string, mixed>>  $yakinlar
     * @return list<array<string, mixed>>
     */
    private function esinCocuklariniEkle(YakinSorgulama $sorgulama, Kisi $kisi, array $yakinlar): array
    {
        $birlesik = [];
        foreach ($yakinlar as $kayit) {
            $birlesik[(string) ($kayit['tc_kimlik_no'] ?? '')] ??= $kayit;
        }

        $esler = array_filter($yakinlar, fn (array $kayit) => ($kayit['yakinlik_kodu'] ?? null) === 'ESI'
            && empty($kayit['olum_tarihi'])
            && preg_match('/^\d{11}$/', (string) ($kayit['tc_kimlik_no'] ?? ''))
            && ! empty($kayit['dogum_tarihi']));

        foreach ($esler as $es) {
            try {
                $esSonuc = $sorgulama->sorgula((string) $es['tc_kimlik_no'], (string) $es['dogum_tarihi']);
            } catch (Throwable $e) {
                Log::warning('Eş yakın sorgulaması başarısız', ['kisi_id' => $kisi->id, 'hata' => $e->getMessage()]);

                continue;
            }

            if (! ($esSonuc['ok'] ?? false)) {
                continue;
            }

            foreach ($esSonuc['yakinlar'] ?? [] as $kayit) {
                $tc = (string) ($kayit['tc_kimlik_no'] ?? '');
                if (in_array($kayit['yakinlik_kodu'] ?? null, ['OGLU', 'KIZI'], true) && $tc !== '' && $tc !== $kisi->tc_kimlik_no) {
                    $birlesik[$tc] ??= $kayit;
                }
            }
        }

        return array_values($birlesik);
    }

    /**
     * Yeni oluşan kişi kaydını, aynı T.C. ile tutulan yakın kayıtlarına bağlar.
     */
    public function kisiyeBagla(Kisi $kisi): void
    {
        if (blank($kisi->tc_kimlik_no)) {
            return;
        }

        KisiYakin::query()
            ->where('tc_kimlik_no', $kisi->tc_kimlik_no)
            ->where('kisi_id', '!=', $kisi->id)
            ->whereNull('yakin_kisi_id')
            ->update(['yakin_kisi_id' => $kisi->id]);
    }

    private function buyukHarf(string $deger): string
    {
        $deger = mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], trim($deger)), 'UTF-8');

        return preg_replace('/\s+/u', ' ', $deger) ?? $deger;
    }
}
