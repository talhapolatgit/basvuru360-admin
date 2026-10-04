<?php

namespace App\Services;

use App\Enums\SoruTipi;
use App\Models\BasvuruCevap;
use App\Models\Soru;
use App\Models\SoruFormu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Kurs, etkinlik ve kreş başvurularındaki soru formu cevaplarını doğrular, kaydeder
 * ve yönetim listelerinde kolon / filtre olarak kullanılmasını sağlar.
 *
 * İstek formatı: cevaplar[<soru_id>] (checkbox için cevaplar[<soru_id>][]).
 */
class SoruFormuCevapServisi
{
    public const METIN_MAKS = 1000;

    public const UZUN_METIN_MAKS = 5000;

    public const DOSYA_MAKS_BAYT = 10 * 1024 * 1024;

    public const FILTRE_BOS = '__bos__';

    private const RESIM_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private const DOSYA_UZANTILARI = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'txt'];

    /** @var list<string> */
    private array $yuklenenDosyalar = [];

    /**
     * Cevapları doğrular; geçerli cevapları kaydedilmeye hazır halde döndürür.
     * Koşulu sağlanmayan (gizli) sorular doğrulanmaz ve kaydedilmez.
     *
     * @return list<array{soru: Soru, deger: ?string, deger_metin: ?string, dosya: ?UploadedFile}>
     *
     * @throws ValidationException
     */
    public function dogrula(Request $request, ?SoruFormu $form): array
    {
        if (! $form || ! $form->aktif) {
            return [];
        }

        $form->loadMissing('sorular.secenekler');
        $sorular = $form->sorular->keyBy('id');
        $secimler = $this->secimHaritasi($request, $sorular);

        $hatalar = [];
        $hazir = [];

        foreach ($sorular as $soru) {
            if (! $this->gorunurMu($soru, $sorular, $secimler)) {
                continue;
            }

            $key = 'cevaplar.'.$soru->id;

            try {
                $cevap = $this->soruyuDogrula($request, $soru, $key, $secimler[$soru->id] ?? []);
            } catch (ValidationException $e) {
                $hatalar += $e->errors();

                continue;
            }

            if ($cevap !== null) {
                $hazir[] = $cevap;
            }
        }

        if ($hatalar !== []) {
            throw ValidationException::withMessages($hatalar);
        }

        return $hazir;
    }

    /**
     * Transaction içinde çağrılmalıdır. Yüklenen dosyalar transaction geri alınırsa
     * yuklenenDosyalariSil() ile temizlenmelidir.
     *
     * @param  list<array{soru: Soru, deger: ?string, deger_metin: ?string, dosya: ?UploadedFile}>  $cevaplar
     */
    public function kaydet(Model $basvuru, array $cevaplar, string $klasor): void
    {
        foreach ($cevaplar as $cevap) {
            $soru = $cevap['soru'];
            $dosya = $cevap['dosya'];
            $dosyaBilgisi = [];

            if ($dosya instanceof UploadedFile) {
                $yol = $dosya->store('basvuru-cevaplari/'.$klasor.'/'.$basvuru->getKey(), 'public');
                $this->yuklenenDosyalar[] = $yol;
                $dosyaBilgisi = [
                    'dosya_yolu' => $yol,
                    'orijinal_ad' => mb_substr($dosya->getClientOriginalName(), 0, 255),
                    'mime' => mb_substr((string) $dosya->getMimeType(), 0, 120),
                    'boyut' => $dosya->getSize() ?: 0,
                ];
            }

            BasvuruCevap::query()->create([
                'basvuru_type' => $basvuru->getMorphClass(),
                'basvuru_id' => $basvuru->getKey(),
                'soru_id' => $soru->id,
                'soru_baslik' => mb_substr($soru->baslik, 0, 500),
                'soru_tip' => $soru->tip->value,
                'deger' => $cevap['deger'],
                'deger_metin' => $cevap['deger_metin'],
            ] + $dosyaBilgisi);
        }
    }

    public function yuklenenDosyalariSil(): void
    {
        if ($this->yuklenenDosyalar !== []) {
            Storage::disk('public')->delete($this->yuklenenDosyalar);
        }
        $this->yuklenenDosyalar = [];
    }

    /**
     * Listelerde dinamik kolon olarak kullanılacak sorular (dosya soruları dahil).
     *
     * @return list<array{key: string, label: string, soru: Soru}>
     */
    public function kolonlar(?SoruFormu $form): array
    {
        if (! $form) {
            return [];
        }

        $form->loadMissing('sorular.secenekler');

        return $form->sorular
            ->map(fn (Soru $soru) => [
                'key' => self::kolonAnahtari($soru->id),
                'label' => $soru->baslik,
                'soru' => $soru,
            ])
            ->values()
            ->all();
    }

    public static function kolonAnahtari(int $soruId): string
    {
        return 'soru_'.$soruId;
    }

    /**
     * @param  list<array{key: string, label: string, soru: Soru}>  $kolonlar
     * @return array<string, string>
     */
    public function kolonEtiketleri(array $kolonlar): array
    {
        return collect($kolonlar)->mapWithKeys(fn (array $kolon) => [$kolon['key'] => $kolon['label']])->all();
    }

    /**
     * Excel satırı için kolon sırasına göre cevap değerleri.
     *
     * @param  list<array{key: string, label: string, soru: Soru}>  $kolonlar
     * @return list<string>
     */
    public function excelDegerleri(Model $basvuru, array $kolonlar): array
    {
        $harita = $this->cevapHaritasi($basvuru);

        return array_map(
            fn (array $kolon) => (string) ($harita->get($kolon['soru']->id)?->gorunenDeger() ?? ''),
            $kolonlar,
        );
    }

    /**
     * Başvurunun cevaplarını soru_id ile eşler (cevaplar ilişkisi yüklü olmalı).
     *
     * @return Collection<int, BasvuruCevap>
     */
    public function cevapHaritasi(Model $basvuru): Collection
    {
        /** @var Collection<int, BasvuruCevap> $cevaplar */
        $cevaplar = $basvuru->getRelationValue('cevaplar') ?? collect();

        return $cevaplar->whereNotNull('soru_id')->keyBy('soru_id');
    }

    /**
     * cevap_soru (soru id) ve cevap_deger (seçenek id, metin veya __bos__) parametrelerine göre filtreler.
     *
     * @return array{cevap_soru: ?int, cevap_deger: ?string}
     */
    public function filtreUygula(Builder $query, ?SoruFormu $form, Request $request): array
    {
        $bos = ['cevap_soru' => null, 'cevap_deger' => null];
        if (! $form) {
            return $bos;
        }

        $soruId = $request->integer('cevap_soru');
        $deger = trim((string) $request->input('cevap_deger', ''));
        if ($soruId <= 0 || $deger === '') {
            return $bos;
        }

        $form->loadMissing('sorular.secenekler');
        /** @var Soru|null $soru */
        $soru = $form->sorular->firstWhere('id', $soruId);
        if (! $soru) {
            return $bos;
        }

        if ($deger === self::FILTRE_BOS) {
            $query->whereDoesntHave('cevaplar', fn (Builder $q) => $q->where('soru_id', $soru->id));

            return ['cevap_soru' => $soru->id, 'cevap_deger' => $deger];
        }

        $query->whereHas('cevaplar', function (Builder $q) use ($soru, $deger) {
            $q->where('soru_id', $soru->id);

            match ($soru->tip) {
                SoruTipi::Liste, SoruTipi::Radio => $q->where('deger', (string) (int) $deger),
                SoruTipi::Checkbox => $q->whereRaw('JSON_CONTAINS(deger, ?)', [(string) (int) $deger]),
                SoruTipi::Dosya, SoruTipi::Resim => $q->where('orijinal_ad', 'like', '%'.$deger.'%'),
                default => $q->where('deger', 'like', '%'.$deger.'%'),
            };
        });

        return ['cevap_soru' => $soru->id, 'cevap_deger' => $deger];
    }

    /**
     * Seçmeli soruların seçilen seçenek id'leri (koşul değerlendirmesi için).
     *
     * @param  Collection<int, Soru>  $sorular
     * @return array<int, list<int>>
     */
    private function secimHaritasi(Request $request, Collection $sorular): array
    {
        $harita = [];

        foreach ($sorular as $soru) {
            if (! $soru->tip->secenekGerekli()) {
                continue;
            }

            $ham = $request->input('cevaplar.'.$soru->id);
            $idler = array_map('intval', array_filter((array) $ham, fn ($v) => is_scalar($v) && $v !== ''));
            $gecerli = $soru->secenekler->pluck('id')->map(fn ($id) => (int) $id)->all();
            $idler = array_values(array_unique(array_intersect($idler, $gecerli)));

            if ($soru->tip !== SoruTipi::Checkbox) {
                $idler = array_slice($idler, 0, 1);
            }

            $harita[$soru->id] = $idler;
        }

        return $harita;
    }

    /**
     * @param  Collection<int, Soru>  $sorular
     * @param  array<int, list<int>>  $secimler
     * @param  array<int, true>  $ziyaret
     */
    private function gorunurMu(Soru $soru, Collection $sorular, array $secimler, array $ziyaret = []): bool
    {
        if (! $soru->kosulluMu()) {
            return true;
        }

        $ust = $sorular->get($soru->kosul_soru_id);
        if (! $ust || isset($ziyaret[$soru->id])) {
            return true;
        }

        $ziyaret[$soru->id] = true;
        if (! $this->gorunurMu($ust, $sorular, $secimler, $ziyaret)) {
            return false;
        }

        return array_intersect($secimler[$ust->id] ?? [], $soru->kosulSecenekIdleri()) !== [];
    }

    /**
     * @param  list<int>  $secim
     * @return array{soru: Soru, deger: ?string, deger_metin: ?string, dosya: ?UploadedFile}|null
     */
    private function soruyuDogrula(Request $request, Soru $soru, string $key, array $secim): ?array
    {
        if ($soru->tip->dosyaMi()) {
            $dosya = $request->file($key);
            if (! $dosya instanceof UploadedFile) {
                $this->zorunluMu($soru, $key, $soru->baslik.' için dosya yükleyin.');

                return null;
            }
            if (! $dosya->isValid() || $dosya->getSize() > self::DOSYA_MAKS_BAYT) {
                $this->hata($key, $soru->baslik.': Dosya en fazla 10 MB olabilir.');
            }
            if ($soru->tip === SoruTipi::Resim && ! in_array($dosya->getMimeType(), self::RESIM_MIME, true)) {
                $this->hata($key, $soru->baslik.' için JPG, PNG, WEBP veya GIF görsel yükleyin.');
            }
            if ($soru->tip === SoruTipi::Dosya && ! in_array(strtolower($dosya->getClientOriginalExtension()), self::DOSYA_UZANTILARI, true)) {
                $this->hata($key, $soru->baslik.': Bu dosya türü kabul edilmiyor.');
            }

            return ['soru' => $soru, 'deger' => null, 'deger_metin' => null, 'dosya' => $dosya];
        }

        if ($soru->tip === SoruTipi::Checkbox) {
            if ($secim === []) {
                $this->zorunluMu($soru, $key, $soru->baslik.' için seçim yapın.');
            }
            if ($secim !== [] || $soru->zorunlu) {
                if ($soru->min_deger !== null && count($secim) < (int) $soru->min_deger) {
                    $this->hata($key, $soru->baslik.' için en az '.(int) $soru->min_deger.' seçim yapın.');
                }
                if ($soru->max_deger !== null && count($secim) > (int) $soru->max_deger) {
                    $this->hata($key, $soru->baslik.' için en fazla '.(int) $soru->max_deger.' seçim yapın.');
                }
            }
            if ($secim === []) {
                return null;
            }

            return [
                'soru' => $soru,
                'deger' => json_encode($secim),
                'deger_metin' => $this->secenekEtiketleri($soru, $secim),
                'dosya' => null,
            ];
        }

        $ham = $request->input($key);
        $deger = is_array($ham) ? trim((string) ($ham[0] ?? '')) : trim((string) ($ham ?? ''));

        if ($deger === '') {
            $this->zorunluMu($soru, $key, $soru->baslik.' zorunludur.');

            return null;
        }

        $mesaj = match ($soru->tip) {
            SoruTipi::Metin => mb_strlen($deger) > self::METIN_MAKS ? 'En fazla '.self::METIN_MAKS.' karakter girebilirsiniz.' : null,
            SoruTipi::UzunMetin => mb_strlen($deger) > self::UZUN_METIN_MAKS ? 'En fazla '.self::UZUN_METIN_MAKS.' karakter girebilirsiniz.' : null,
            SoruTipi::Sayi => $this->sayiHatasi($soru, $deger),
            SoruTipi::Eposta => filter_var($deger, FILTER_VALIDATE_EMAIL) && mb_strlen($deger) <= 150 ? null : 'Geçerli bir e-posta girin.',
            SoruTipi::TcKimlik => preg_match('/^\d{11}$/', $deger) ? null : 'T.C. kimlik no 11 haneli olmalıdır.',
            SoruTipi::CepTelefonu => preg_match('/^05\d{9}$/', preg_replace('/\D+/', '', $deger) ?? '') ? null : 'Cep telefonunu 05xx xxx xx xx formatında girin.',
            SoruTipi::Tarih => preg_match('/^\d{4}-\d{2}-\d{2}$/', $deger) && strtotime($deger) ? null : 'Geçerli bir tarih girin.',
            SoruTipi::Liste, SoruTipi::Radio => $secim !== [] ? null : 'Geçerli bir seçenek seçin.',
            default => null,
        };

        if ($mesaj) {
            $this->hata($key, $soru->baslik.': '.$mesaj);
        }

        if ($soru->tip->secenekGerekli()) {
            return [
                'soru' => $soru,
                'deger' => (string) $secim[0],
                'deger_metin' => $this->secenekEtiketleri($soru, $secim),
                'dosya' => null,
            ];
        }

        if ($soru->tip === SoruTipi::CepTelefonu) {
            $deger = preg_replace('/\D+/', '', $deger) ?? $deger;
        } elseif ($soru->tip === SoruTipi::Sayi) {
            $deger = str_replace(',', '.', $deger);
        }

        return ['soru' => $soru, 'deger' => $deger, 'deger_metin' => null, 'dosya' => null];
    }

    /**
     * @param  list<int>  $secim
     */
    private function secenekEtiketleri(Soru $soru, array $secim): string
    {
        return $soru->secenekler
            ->filter(fn ($secenek) => in_array((int) $secenek->id, $secim, true))
            ->pluck('etiket')
            ->implode(', ');
    }

    private function sayiHatasi(Soru $soru, string $deger): ?string
    {
        $deger = str_replace(',', '.', $deger);
        if (! is_numeric($deger)) {
            return 'Sayı girin.';
        }
        $sayi = (float) $deger;
        if ($soru->tam_sayi && floor($sayi) !== $sayi) {
            return 'Tam sayı girin.';
        }
        if ($soru->min_deger !== null && $sayi < $soru->min_deger) {
            return 'Minimum değer '.$this->sayiMetni($soru->min_deger).'.';
        }
        if ($soru->max_deger !== null && $sayi > $soru->max_deger) {
            return 'Maksimum değer '.$this->sayiMetni($soru->max_deger).'.';
        }

        return null;
    }

    private function sayiMetni(float $deger): string
    {
        return fmod($deger, 1.0) === 0.0 ? (string) (int) $deger : str_replace('.', ',', (string) $deger);
    }

    private function zorunluMu(Soru $soru, string $key, string $mesaj): void
    {
        if ($soru->zorunlu) {
            $this->hata($key, $mesaj);
        }
    }

    /**
     * @throws ValidationException
     */
    private function hata(string $key, string $mesaj): never
    {
        throw ValidationException::withMessages([$key => $mesaj]);
    }
}
