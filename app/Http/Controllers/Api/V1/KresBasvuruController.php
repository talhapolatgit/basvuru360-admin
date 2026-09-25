<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Cinsiyet;
use App\Enums\KresSoruTipi;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\V1\Concerns\ResolvesPortalBasvuruKatilimci;
use App\Http\Resources\Api\V1\KresBasvuruResource;
use App\Models\Kisi;
use App\Models\KresBasvuru;
use App\Models\KresBasvuruCevap;
use App\Models\KresBasvuruDurum;
use App\Models\KresDonem;
use App\Models\KresGrup;
use App\Models\KresOkul;
use App\Models\KresSoru;
use App\Models\KresSoruFormu;
use App\Services\Sms\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KresBasvuruController extends ApiController
{
    use ResolvesPortalBasvuruKatilimci;

    public function durum(): JsonResponse
    {
        $donem = $this->yayindakiDonem();
        if (! $donem) {
            return $this->success([
                'acik' => false,
                'mesaj' => 'Şu anda portalda açık bir kreş başvuru dönemi yok.',
                'donem' => null,
            ]);
        }

        $form = KresSoruFormu::query()
            ->where('donem_id', $donem->id)
            ->where('aktif', true)
            ->first();

        return $this->success([
            'acik' => true,
            'mesaj' => null,
            'donem' => [
                'id' => $donem->id,
                'ad' => $donem->ad,
                'baslangic' => $donem->baslangic?->format('Y-m-d'),
                'bitis' => $donem->bitis?->format('Y-m-d'),
            ],
            'soru_formu' => $form ? [
                'id' => $form->id,
                'ad' => $form->ad,
                'aciklama' => $form->aciklama,
            ] : null,
        ]);
    }

    public function okullar(Request $request): JsonResponse
    {
        $donem = $this->yayindakiDonemVeyaFail();
        $yas = $this->ogrenciYasiniAl($request);

        $okullar = KresOkul::query()
            ->where('aktif', true)
            ->with(['gruplar' => fn ($q) => $q->where('donem_id', $donem->id)->where('aktif', true)])
            ->orderBy('ad')
            ->get()
            ->filter(function (KresOkul $okul) use ($yas) {
                return $okul->gruplar->contains(fn (KresGrup $grup) => $grup->ogrenciUygunMu($yas));
            })
            ->values()
            ->map(fn (KresOkul $okul) => [
                'id' => $okul->id,
                'ad' => $okul->ad,
                'adres' => $okul->adres,
                'telefon' => $okul->telefon,
                'uygun_grup_sayisi' => $okul->gruplar
                    ->filter(fn (KresGrup $grup) => $grup->ogrenciUygunMu($yas))
                    ->count(),
            ]);

        return $this->success([
            'items' => $okullar,
            'ogrenci_yas' => $yas,
        ]);
    }

    public function gruplar(Request $request, int $okulId): JsonResponse
    {
        $donem = $this->yayindakiDonemVeyaFail();
        $yas = $this->ogrenciYasiniAl($request);
        $kesinId = KresBasvuruDurum::idByKod('kesin_kayit');

        $okul = KresOkul::query()->whereKey($okulId)->where('aktif', true)->firstOrFail();

        $gruplar = KresGrup::query()
            ->where('okul_id', $okul->id)
            ->where('donem_id', $donem->id)
            ->where('aktif', true)
            ->withCount([
                'basvurular as kesin_sayisi' => fn ($q) => $kesinId
                    ? $q->where('durum_id', $kesinId)
                    : $q->whereRaw('1 = 0'),
            ])
            ->orderBy('min_yas')
            ->orderBy('ad')
            ->get()
            ->filter(fn (KresGrup $grup) => $grup->ogrenciUygunMu($yas))
            ->values()
            ->map(fn (KresGrup $grup) => [
                'id' => $grup->id,
                'ad' => $grup->ad,
                'yas_araligi' => $grup->yasAraligiLabel(),
                'cinsiyet_sarti' => $grup->cinsiyetSartiLabel(),
                'kontenjan' => (int) $grup->kontenjan,
                'kesin_kayit' => (int) $grup->kesin_sayisi,
            ]);

        return $this->success([
            'okul' => [
                'id' => $okul->id,
                'ad' => $okul->ad,
                'adres' => $okul->adres,
            ],
            'items' => $gruplar,
            'ogrenci_yas' => $yas,
        ]);
    }

    public function soruFormu(): JsonResponse
    {
        $donem = $this->yayindakiDonemVeyaFail();
        $form = KresSoruFormu::query()
            ->where('donem_id', $donem->id)
            ->where('aktif', true)
            ->with(['sorular.secenekler'])
            ->first();

        if (! $form) {
            return $this->success([
                'form' => null,
                'sorular' => [],
            ]);
        }

        return $this->success([
            'form' => [
                'id' => $form->id,
                'ad' => $form->ad,
                'aciklama' => $form->aciklama,
            ],
            'sorular' => $form->sorular->map(fn (KresSoru $soru) => [
                'id' => $soru->id,
                'tip' => $soru->tip->value,
                'baslik' => $soru->baslik,
                'aciklama' => $soru->aciklama,
                'zorunlu' => $soru->zorunlu,
                'placeholder' => $soru->tip->placeholder(),
                'min_deger' => $soru->min_deger,
                'max_deger' => $soru->max_deger,
                'tam_sayi' => $soru->tam_sayi,
                'secenekler' => $soru->secenekler->map(fn ($s) => [
                    'id' => $s->id,
                    'etiket' => $s->etiket,
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $donem = $this->yayindakiDonemVeyaFail();

        /** @var Kisi $oturum */
        $oturum = $request->user();

        $validated = $request->validate([
            'veli_ad' => ['required', 'string', 'max:100'],
            'veli_soyad' => ['required', 'string', 'max:100'],
            'veli_tc_kimlik_no' => ['required', 'digits:11'],
            'veli_dogum_tarihi' => ['required', 'date', 'before:today'],
            'veli_telefon' => ['required', 'string', 'max:20'],
            'veli_email' => ['required', 'email', 'max:150'],
            'veli_adres' => ['required', 'string', 'max:500'],
            'ogrenci_ad' => ['required', 'string', 'max:100'],
            'ogrenci_soyad' => ['required', 'string', 'max:100'],
            'ogrenci_tc_kimlik_no' => [
                'required',
                'digits:11',
                Rule::notIn([(string) $request->input('veli_tc_kimlik_no')]),
            ],
            'ogrenci_dogum_tarihi' => ['required', 'date', 'before:today'],
            'grup_id' => ['required', 'integer', Rule::exists('kres_gruplar', 'id')],
        ], [
            'veli_ad.required' => 'Veli adı zorunludur.',
            'veli_soyad.required' => 'Veli soyadı zorunludur.',
            'veli_tc_kimlik_no.required' => 'Veli T.C. kimlik no zorunludur.',
            'veli_tc_kimlik_no.digits' => 'Veli T.C. kimlik no 11 haneli olmalıdır.',
            'veli_dogum_tarihi.required' => 'Veli doğum tarihi zorunludur.',
            'veli_telefon.required' => 'Veli telefon numarası zorunludur.',
            'veli_email.required' => 'Veli e-posta adresi zorunludur.',
            'veli_email.email' => 'Geçerli bir e-posta adresi girin.',
            'veli_adres.required' => 'Ev adresi zorunludur.',
            'ogrenci_ad.required' => 'Öğrenci adı zorunludur.',
            'ogrenci_soyad.required' => 'Öğrenci soyadı zorunludur.',
            'ogrenci_tc_kimlik_no.required' => 'Öğrenci T.C. kimlik no zorunludur.',
            'ogrenci_tc_kimlik_no.digits' => 'Öğrenci T.C. kimlik no 11 haneli olmalıdır.',
            'ogrenci_tc_kimlik_no.not_in' => 'Öğrenci ile veli aynı kişi olamaz.',
            'ogrenci_dogum_tarihi.required' => 'Öğrenci doğum tarihi zorunludur.',
            'grup_id.required' => 'Grup seçimi zorunludur.',
        ]);

        $telefon = $this->cepTelefonuDogrula((string) $validated['veli_telefon']);
        $ogrenciCinsiyet = $this->cocukKimlikDogrula([
            'cocuk_tc_kimlik_no' => $validated['ogrenci_tc_kimlik_no'],
            'cocuk_dogum_tarihi' => $validated['ogrenci_dogum_tarihi'],
            'cocuk_ad' => $validated['ogrenci_ad'],
            'cocuk_soyad' => $validated['ogrenci_soyad'],
        ]);

        $grup = KresGrup::query()
            ->with('okul')
            ->whereKey($validated['grup_id'])
            ->where('donem_id', $donem->id)
            ->where('aktif', true)
            ->first();

        if (! $grup || ! $grup->okul?->aktif) {
            throw ValidationException::withMessages([
                'grup_id' => 'Seçilen grup bu dönem için uygun değil.',
            ]);
        }

        $yas = $this->yasHesapla($validated['ogrenci_dogum_tarihi']);
        if (! $grup->ogrenciUygunMu($yas)) {
            throw ValidationException::withMessages([
                'grup_id' => 'Öğrenci bu grubun yaş aralığına uygun değil.',
            ]);
        }

        $form = KresSoruFormu::query()
            ->where('donem_id', $donem->id)
            ->where('aktif', true)
            ->with(['sorular.secenekler'])
            ->first();

        $cevaplar = $form ? $this->cevaplarDogrula($request, $form) : [];

        $durumId = KresBasvuruDurum::idByKod('onay_bekliyor');
        if (! $durumId) {
            throw ValidationException::withMessages([
                'grup_id' => 'Başvuru durumu tanımlı değil. Yöneticinizle iletişime geçin.',
            ]);
        }

        $basvuru = DB::transaction(function () use ($validated, $telefon, $oturum, $grup, $durumId, $cevaplar, $ogrenciCinsiyet) {
            $veli = $this->kisiUpsert([
                'ad' => $validated['veli_ad'],
                'soyad' => $validated['veli_soyad'],
                'tc_kimlik_no' => $validated['veli_tc_kimlik_no'],
                'dogum_tarihi' => $validated['veli_dogum_tarihi'],
                'telefon' => $telefon,
                'email' => $validated['veli_email'],
                'adres' => $validated['veli_adres'],
            ]);

            if ((int) $oturum->id === (int) $veli->id) {
                $oturum->basvuruIleProfilGuncelle([
                    'telefon' => $telefon,
                    'email' => $validated['veli_email'],
                    'adres' => $validated['veli_adres'],
                ]);
            }

            $ogrenci = $this->kisiUpsert([
                'ad' => $validated['ogrenci_ad'],
                'soyad' => $validated['ogrenci_soyad'],
                'tc_kimlik_no' => $validated['ogrenci_tc_kimlik_no'],
                'dogum_tarihi' => $validated['ogrenci_dogum_tarihi'],
                'cinsiyet' => $ogrenciCinsiyet,
            ]);

            $this->cocukYakinligiKaydet($veli, $ogrenci, $ogrenciCinsiyet);

            $iptalId = KresBasvuruDurum::idByKod('iptal');
            $mevcut = KresBasvuru::query()
                ->where('grup_id', $grup->id)
                ->where('kisi_id', $ogrenci->id)
                ->when($iptalId, fn ($q) => $q->where('durum_id', '!=', $iptalId))
                ->lockForUpdate()
                ->exists();

            if ($mevcut) {
                throw ValidationException::withMessages([
                    'grup_id' => 'Bu öğrenci için seçilen grupta aktif bir başvuru zaten var.',
                ]);
            }

            $kayit = KresBasvuru::query()->create([
                'grup_id' => $grup->id,
                'kisi_id' => $ogrenci->id,
                'basvuran_id' => $veli->id,
                'durum_id' => $durumId,
                'olusturan_id' => null,
            ]);

            foreach ($cevaplar as $cevap) {
                KresBasvuruCevap::query()->create([
                    'basvuru_id' => $kayit->id,
                    ...$cevap,
                ]);
            }

            return $kayit->load(['grup.okul', 'grup.donem', 'kisi', 'durum', 'basvuran']);
        });

        return $this->success([
            'basvuru' => (new KresBasvuruResource($basvuru))->resolve(),
            'durum' => $basvuru->durum?->ad,
        ], 'Kreş başvurunuz alındı.', 201);
    }

    private function yayindakiDonem(): ?KresDonem
    {
        return KresDonem::query()
            ->where('aktif', true)
            ->where('yayinla', true)
            ->orderByDesc('id')
            ->first();
    }

    private function yayindakiDonemVeyaFail(): KresDonem
    {
        $donem = $this->yayindakiDonem();
        if (! $donem) {
            throw ValidationException::withMessages([
                'donem' => 'Şu anda portalda açık bir kreş başvuru dönemi yok.',
            ]);
        }

        return $donem;
    }

    private function ogrenciYasiniAl(Request $request): int
    {
        $validated = $request->validate([
            'ogrenci_dogum_tarihi' => ['required', 'date', 'before:today'],
        ], [
            'ogrenci_dogum_tarihi.required' => 'Öğrenci doğum tarihi zorunludur.',
        ]);

        $yas = $this->yasHesapla($validated['ogrenci_dogum_tarihi']);
        if ($yas === null) {
            throw ValidationException::withMessages([
                'ogrenci_dogum_tarihi' => 'Geçerli bir öğrenci doğum tarihi girin.',
            ]);
        }

        return $yas;
    }

    private function cepTelefonuDogrula(string $raw): string
    {
        $normalized = PhoneNormalizer::normalize($raw);
        $digits = preg_replace('/\D+/', '', (string) $normalized);
        if ($digits && str_starts_with($digits, '90') && strlen($digits) === 12) {
            $local = '0'.substr($digits, 2);
        } else {
            $local = preg_replace('/\D+/', '', $raw) ?? '';
        }

        if (strlen($local) === 10 && str_starts_with($local, '5')) {
            $local = '0'.$local;
        }

        if (! preg_match('/^05\d{9}$/', $local)) {
            throw ValidationException::withMessages([
                'veli_telefon' => 'Cep telefonunu 05xx xxx xx xx formatında girin.',
            ]);
        }

        return substr($local, 0, 4).' '.substr($local, 4, 3).' '.substr($local, 7, 2).' '.substr($local, 9, 2);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cevaplarDogrula(Request $request, KresSoruFormu $form): array
    {
        $kayitlar = [];

        foreach ($form->sorular as $soru) {
            $key = 'cevaplar.'.$soru->id;
            $file = $request->file($key);
            $ham = $request->input($key);

            if ($soru->tip === KresSoruTipi::Dosya || $soru->tip === KresSoruTipi::Resim) {
                if ($soru->zorunlu && ! $file instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        $key => $soru->baslik.' için dosya yükleyin.',
                    ]);
                }
                if ($file instanceof UploadedFile) {
                    $mimes = $soru->tip === KresSoruTipi::Resim
                        ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
                        : null;
                    if ($mimes && ! in_array($file->getMimeType(), $mimes, true)) {
                        throw ValidationException::withMessages([
                            $key => $soru->baslik.' için geçerli bir görsel yükleyin.',
                        ]);
                    }
                    $path = $file->store('kres-basvuru-cevaplar', 'public');
                    $kayitlar[] = [
                        'soru_id' => $soru->id,
                        'deger' => null,
                        'dosya_yolu' => $path,
                        'orijinal_ad' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                        'boyut' => $file->getSize() ?: 0,
                    ];
                }

                continue;
            }

            if ($soru->tip === KresSoruTipi::Checkbox) {
                $secimler = array_values(array_filter(array_map('intval', (array) $ham)));
                $gecerli = $soru->secenekler->pluck('id')->map(fn ($id) => (int) $id)->all();
                $secimler = array_values(array_intersect($secimler, $gecerli));
                if ($soru->zorunlu && $secimler === []) {
                    throw ValidationException::withMessages([
                        $key => $soru->baslik.' için seçim yapın.',
                    ]);
                }
                if ($soru->min_deger !== null && count($secimler) < (int) $soru->min_deger) {
                    throw ValidationException::withMessages([
                        $key => $soru->baslik.' için en az '.(int) $soru->min_deger.' seçim yapın.',
                    ]);
                }
                if ($soru->max_deger !== null && count($secimler) > (int) $soru->max_deger) {
                    throw ValidationException::withMessages([
                        $key => $soru->baslik.' için en fazla '.(int) $soru->max_deger.' seçim yapın.',
                    ]);
                }
                if ($secimler !== []) {
                    $kayitlar[] = [
                        'soru_id' => $soru->id,
                        'deger' => json_encode($secimler),
                    ];
                }

                continue;
            }

            $deger = is_array($ham) ? (string) ($ham[0] ?? '') : trim((string) ($ham ?? ''));
            if ($soru->zorunlu && $deger === '') {
                throw ValidationException::withMessages([
                    $key => $soru->baslik.' zorunludur.',
                ]);
            }
            if ($deger === '') {
                continue;
            }

            $this->cevapTipiniDogrula($soru, $deger, $key);

            $kayitlar[] = [
                'soru_id' => $soru->id,
                'deger' => $deger,
            ];
        }

        return $kayitlar;
    }

    private function cevapTipiniDogrula(KresSoru $soru, string $deger, string $key): void
    {
        $mesaj = match ($soru->tip) {
            KresSoruTipi::Sayi => $this->sayiHatasi($soru, $deger),
            KresSoruTipi::Eposta => filter_var($deger, FILTER_VALIDATE_EMAIL) ? null : 'Geçerli bir e-posta girin.',
            KresSoruTipi::TcKimlik => preg_match('/^\d{11}$/', $deger) ? null : 'T.C. kimlik no 11 haneli olmalıdır.',
            KresSoruTipi::CepTelefonu => preg_match('/^05\d{9}$/', preg_replace('/\D+/', '', $deger) ?? '') ? null : 'Cep telefonunu 05xx xxx xx xx formatında girin.',
            KresSoruTipi::Tarih => strtotime($deger) ? null : 'Geçerli bir tarih girin.',
            KresSoruTipi::Liste, KresSoruTipi::Radio => $soru->secenekler->contains('id', (int) $deger)
                ? null
                : 'Geçerli bir seçenek seçin.',
            default => null,
        };

        if ($mesaj) {
            throw ValidationException::withMessages([
                $key => $soru->baslik.': '.$mesaj,
            ]);
        }
    }

    private function sayiHatasi(KresSoru $soru, string $deger): ?string
    {
        if (! is_numeric($deger)) {
            return 'Sayı girin.';
        }
        $sayi = (float) $deger;
        if ($soru->tam_sayi && floor($sayi) !== $sayi) {
            return 'Tam sayı girin.';
        }
        if ($soru->min_deger !== null && $sayi < $soru->min_deger) {
            return 'Minimum değer '.$soru->min_deger.'.';
        }
        if ($soru->max_deger !== null && $sayi > $soru->max_deger) {
            return 'Maksimum değer '.$soru->max_deger.'.';
        }

        return null;
    }
}
