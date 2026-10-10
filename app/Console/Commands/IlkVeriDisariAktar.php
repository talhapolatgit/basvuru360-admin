<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

/**
 * Yerel veritabanındaki tanım ve ayar tablolarını, yönetici ve öğretmen
 * hesaplarıyla birlikte database/data/ilk-veri altına JSON olarak yazar.
 * IlkVeriSeeder bu paketi yükler.
 *
 * Kurs, etkinlik, kreş, kişi, başvuru, soru formu ve log verileri pakete girmez.
 * Entegrasyon şifreleri/anahtarları ve yönetici şifresi boşaltılır.
 */
class IlkVeriDisariAktar extends Command
{
    protected $signature = 'ilk-veri:disari-aktar {--kullanici=1 : Pakete alınacak yönetici kullanıcı ID}';

    protected $description = 'Tanım/ayar tablolarını, yönetici ve öğretmen hesaplarını ilk veri paketi olarak dışa aktarır';

    /** Yükleme sırası (üst tablolar önce). */
    public const TABLOLAR = [
        'roller',
        'yetkiler',
        'rol_yetki',
        'users',
        'kullanici_rol',
        'kurumlar',
        'merkezler',
        'kullanici_merkez',
        'kullanici_kurum',
        'iller',
        'ilceler',
        'alanlar',
        'branslar',
        'kurs_tipleri',
        'egitim_durumlari',
        'evrak_tipleri',
        'iptal_gerekceleri',
        'basari_durumlari',
        'basvuru_durumlari',
        'etkinlik_basvuru_durumlari',
        'etkinlik_tipleri',
        'kres_basvuru_durumlari',
        'yakinlik_dereceleri',
        'portal_sayfalar',
        'portal_sayfa_kurallari',
        'genel_ayarlar',
        'kurs_ayarlari',
        'etkinlik_ayarlari',
        'sertifika_ayarlari',
        'entegrasyon_ayarlari',
    ];

    private const GIZLI_ANAHTAR = '/pass|parola|sifre|şifre|secret|token|authorization|api_?key|app_password/i';

    /** @var array<int, string> */
    private array $notlar = [];

    /** @var array<string, bool>|null */
    private ?array $gitDosyalari = null;

    public function handle(): int
    {
        $kullaniciId = (int) $this->option('kullanici');
        if (! DB::table('users')->where('id', $kullaniciId)->exists()) {
            $this->error("Kullanıcı bulunamadı: #{$kullaniciId}");

            return self::FAILURE;
        }

        $veri = [];
        foreach (self::TABLOLAR as $tablo) {
            if (! Schema::hasTable($tablo)) {
                $this->warn("Tablo yok, atlandı: {$tablo}");

                continue;
            }
            $veri[$tablo] = DB::table($tablo)->orderBy(Schema::hasColumn($tablo, 'id') ? 'id' : DB::raw('1'))->get()
                ->map(fn ($r) => (array) $r);
        }

        $rolIdleri = $veri['roller']->where('sistem', 1)->pluck('id')->all();
        $silinenRoller = $veri['roller']->whereNotIn('id', $rolIdleri)->pluck('ad')->all();
        if ($silinenRoller) {
            $this->notlar[] = 'Sistem dışı roller alınmadı: '.implode(', ', $silinenRoller);
        }
        $veri['roller'] = $veri['roller']->whereIn('id', $rolIdleri)->values();
        $veri['rol_yetki'] = $veri['rol_yetki']->whereIn('rol_id', $rolIdleri)->values();

        $ogretmenRolIdleri = $veri['roller']->where('kod', 'ogretmen')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $paketKullaniciIdleri = array_values(array_unique(array_merge(
            [$kullaniciId],
            $veri['kullanici_rol']->whereIn('rol_id', $ogretmenRolIdleri)->pluck('user_id')->map(fn ($id) => (int) $id)->all(),
        )));

        $veri['users'] = $veri['users']->whereIn('id', $paketKullaniciIdleri)->map(function ($u) {
            $u['password'] = '';
            $u['remember_token'] = null;

            return $u;
        })->values();
        foreach (['kullanici_rol', 'kullanici_merkez', 'kullanici_kurum'] as $tablo) {
            if (isset($veri[$tablo])) {
                $veri[$tablo] = $veri[$tablo]->whereIn('user_id', $paketKullaniciIdleri)->values();
            }
        }
        $veri['kullanici_rol'] = $veri['kullanici_rol']->whereIn('rol_id', $rolIdleri)->values();

        $this->portalSayfalariniAyikla($veri);

        foreach (['genel_ayarlar', 'kurs_ayarlari', 'etkinlik_ayarlari'] as $tablo) {
            $veri[$tablo] = $veri[$tablo]->map(function ($r) use ($tablo) {
                if (preg_match(self::GIZLI_ANAHTAR, (string) $r['anahtar']) && filled($r['deger'])) {
                    $this->notlar[] = "{$tablo}.{$r['anahtar']} boşaltıldı (gizli bilgi).";
                    $r['deger'] = null;
                }

                return $r;
            });
        }

        $veri['entegrasyon_ayarlari'] = $veri['entegrasyon_ayarlari']->map(function ($r) {
            if (filled($r['ayarlar'])) {
                $ayarlar = json_decode($r['ayarlar'], true);
                if (is_array($ayarlar)) {
                    $r['ayarlar'] = json_encode($this->gizlileriBosalt($ayarlar, $r['tur']), JSON_UNESCAPED_UNICODE);
                }
            }

            return $r;
        });

        foreach ($veri as $tablo => $satirlar) {
            $veri[$tablo] = $satirlar->map(fn ($r) => $this->eksikDosyalariTemizle($tablo, $r))->values();
        }

        $klasor = database_path('data/ilk-veri');
        File::ensureDirectoryExists($klasor);
        foreach (File::glob($klasor.'/*.json') as $eski) {
            File::delete($eski);
        }

        $manifest = ['olusturulma' => now()->toDateTimeString(), 'tablolar' => []];
        foreach ($veri as $tablo => $satirlar) {
            File::put(
                "{$klasor}/{$tablo}.json",
                json_encode($satirlar->all(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            );
            $manifest['tablolar'][$tablo] = $satirlar->count();
        }
        File::put("{$klasor}/manifest.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n");

        $this->table(['Tablo', 'Satır'], collect($manifest['tablolar'])->map(fn ($n, $t) => [$t, $n])->values()->all());
        foreach (array_unique($this->notlar) as $not) {
            $this->line("  - {$not}");
        }
        $this->info("Paket yazıldı: {$klasor}");

        return self::SUCCESS;
    }

    /**
     * Belirli kurs/etkinliğe bağlı kurallar paketle birlikte geçersiz kalır; kuralı
     * kalmayan özel sayfalar da alınmaz.
     *
     * @param  array<string, Collection>  $veri
     */
    private function portalSayfalariniAyikla(array &$veri): void
    {
        $kurallar = $veri['portal_sayfa_kurallari'];
        $silinenKural = $kurallar->filter(fn ($k) => $k['secim_tipi'] === $k['kaynak']);
        $kurallar = $kurallar->reject(fn ($k) => $k['secim_tipi'] === $k['kaynak']);

        $sayfalar = $veri['portal_sayfalar']->filter(function ($s) use ($kurallar, $silinenKural) {
            if ((int) $s['sistem'] === 1 || $kurallar->contains('portal_sayfa_id', $s['id'])) {
                return true;
            }
            if ($silinenKural->contains('portal_sayfa_id', $s['id'])) {
                $this->notlar[] = "Portal sayfası alınmadı (yalnızca belirli kurs/etkinliğe bağlı): {$s['baslik']} (/{$s['slug']})";

                return false;
            }

            return true;
        });

        $veri['portal_sayfalar'] = $sayfalar->values();
        $veri['portal_sayfa_kurallari'] = $kurallar->whereIn('portal_sayfa_id', $sayfalar->pluck('id')->all())->values();
    }

    private function gizlileriBosalt(array $ayarlar, string $tur, string $yol = ''): array
    {
        foreach ($ayarlar as $anahtar => $deger) {
            $tamYol = $yol === '' ? (string) $anahtar : "{$yol}.{$anahtar}";
            if (is_array($deger)) {
                $ayarlar[$anahtar] = $this->gizlileriBosalt($deger, $tur, $tamYol);
            } elseif (preg_match(self::GIZLI_ANAHTAR, (string) $anahtar) && filled($deger)) {
                $ayarlar[$anahtar] = '';
                $this->notlar[] = "entegrasyon_ayarlari [{$tur}] {$tamYol} boşaltıldı (gizli bilgi).";
            }
        }

        return $ayarlar;
    }

    /**
     * Sunucuya imajla yalnızca git'te izlenen uploads dosyaları gider; diğer yolları boşaltır.
     */
    private function eksikDosyalariTemizle(string $tablo, array $satir): array
    {
        foreach ($satir as $kolon => $deger) {
            if (! is_string($deger) || ! preg_match('#^/?uploads/\S+$#', $deger)) {
                continue;
            }
            $yol = ltrim($deger, '/');
            if (! $this->sunucudaOlacak($yol)) {
                $satir[$kolon] = null;
                $this->notlar[] = "{$tablo}#".($satir['id'] ?? '?').".{$kolon} boşaltıldı (dosya git'te yok: {$yol}).";
            }
        }

        return $satir;
    }

    private function sunucudaOlacak(string $yol): bool
    {
        if ($this->gitDosyalari === null) {
            $process = new Process(['git', 'ls-files', 'public/uploads'], base_path());
            $process->run();
            $this->gitDosyalari = $process->isSuccessful()
                ? array_fill_keys(array_filter(preg_split('/\R/', $process->getOutput())), true)
                : [];
            if (! $process->isSuccessful()) {
                $this->warn('git ls-files çalışmadı; dosya kontrolü diskteki varlığa göre yapılacak.');
            }
        }

        return $this->gitDosyalari === []
            ? is_file(public_path($yol))
            : isset($this->gitDosyalari['public/'.$yol]);
    }
}
