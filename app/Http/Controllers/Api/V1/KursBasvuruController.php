<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Cinsiyet;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\V1\Concerns\ResolvesPortalBasvuruKatilimci;
use App\Http\Resources\Api\V1\KisiResource;
use App\Http\Resources\Api\V1\KursBasvuruResource;
use App\Models\BasariDurum;
use App\Models\BasvuruDurum;
use App\Models\Kisi;
use App\Models\Kurs;
use App\Models\KursBasvuru;
use App\Models\KursBasvuruEvrak;
use App\Services\BasvuruKosulDogrulayici;
use App\Services\KursAyarServisi;
use App\Services\KursYedekListeServisi;
use App\Services\LogKaydedici;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KursBasvuruController extends ApiController
{
    use ResolvesPortalBasvuruKatilimci;

    public function store(Request $request): JsonResponse
    {
        /** @var Kisi $basvuran */
        $basvuran = $request->user();
        $cocukAdina = $this->basvuruIcinCocukMu($request);

        $kurs = Kurs::query()
            ->portaldeAktif()
            ->with([
                'merkez',
                'evrakTipleri' => fn ($q) => $q->where('aktif', true),
            ])
            ->find($request->integer('kurs_id'));

        if (! $kurs) {
            return $this->error('Kurs bulunamadı veya başvuru alınmıyor.', 404);
        }

        if ($kurs->basvuruDurumuKod() !== 'acik') {
            return $this->error('Bu kursa şu anda başvuru alınmamaktadır.', 422);
        }

        $evrakTipiIds = $kurs->evrak_zorunlu
            ? $kurs->evrakTipleri->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $kimlikAktif = $this->kimlikSorgulamaAktifMi();
        $cinsiyetZorunlu = $kurs->cinsiyet_sarti && ! $kimlikAktif;

        $rules = [
            'kurs_id' => ['required', 'integer', 'exists:kurslar,id'],
            'basvuru_icin' => ['nullable', Rule::in(['kendisi', 'cocuk'])],
            'telefon' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'il' => ['required', 'string', 'max:100'],
            'ilce' => ['required', 'string', 'max:100'],
            'adres' => ['required', 'string', 'max:500'],
            'cinsiyet' => [
                $cinsiyetZorunlu ? 'required' : 'nullable',
                Rule::enum(Cinsiyet::class),
            ],
            'veli_tc_kimlik_no' => ['nullable', 'digits:11'],
            'veli_dogum_tarihi' => ['nullable', 'date'],
            'veli_ad' => ['nullable', 'string', 'max:100'],
            'veli_soyad' => ['nullable', 'string', 'max:100'],
            'veli_telefon' => ['nullable', 'string', 'max:20'],
            'veli_email' => ['nullable', 'email', 'max:150'],
            'cocuk_ad' => ['nullable', 'string', 'max:100'],
            'cocuk_soyad' => ['nullable', 'string', 'max:100'],
            'cocuk_tc_kimlik_no' => ['nullable', 'digits:11'],
            'cocuk_dogum_tarihi' => ['nullable', 'date'],
        ];

        $onayKodlari = collect(app(KursAyarServisi::class)->basvuruOnaylari())
            ->pluck('kod');

        if ($onayKodlari->contains('kvkk')) {
            $rules['kvkk_onay'] = ['accepted'];
        }
        if ($onayKodlari->contains('aydinlatma')) {
            $rules['aydinlatma_onay'] = ['accepted'];
        }

        foreach ($evrakTipiIds as $tipId) {
            $rules["evrak.{$tipId}"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        if ($cocukAdina) {
            $rules = array_merge($rules, $this->cocukBasvuruKurallari($basvuran));
        }

        $yasBasvuran = $this->yasHesapla($basvuran->dogum_tarihi?->format('Y-m-d'));
        $kucukBasvuran = ! $cocukAdina && $yasBasvuran !== null && $yasBasvuran < 18;

        if ($kucukBasvuran) {
            $rules['veli_tc_kimlik_no'] = [
                'required',
                'digits:11',
                Rule::notIn([(string) $basvuran->tc_kimlik_no]),
            ];
            $rules['veli_dogum_tarihi'] = ['required', 'date'];
            $rules['veli_ad'] = ['required', 'string', 'max:100'];
            $rules['veli_soyad'] = ['required', 'string', 'max:100'];
        }

        $validated = $request->validate($rules, array_merge([
            'kurs_id.required' => 'Kurs seçilmelidir.',
            'cinsiyet.required' => 'Bu kurs için cinsiyet seçimi zorunludur.',
            'telefon.required' => 'Telefon zorunludur.',
            'il.required' => 'İl zorunludur.',
            'ilce.required' => 'İlçe zorunludur.',
            'adres.required' => 'Adres zorunludur.',
            'veli_tc_kimlik_no.required' => '18 yaşından küçük başvurularda veli TC Kimlik No zorunludur.',
            'veli_tc_kimlik_no.not_in' => 'Veli TC Kimlik No katılımcıdan farklı olmalıdır.',
            'veli_dogum_tarihi.required' => '18 yaşından küçük başvurularda veli doğum tarihi zorunludur.',
            'veli_ad.required' => '18 yaşından küçük başvurularda veli adı zorunludur.',
            'veli_soyad.required' => '18 yaşından küçük başvurularda veli soyadı zorunludur.',
            'evrak.*.required' => 'Zorunlu evrak yüklenmelidir.',
            'evrak.*.mimes' => 'Evrak PDF veya görsel (JPG/PNG) olmalıdır.',
            'evrak.*.max' => 'Evrak en fazla 5 MB olabilir.',
            'kvkk_onay.accepted' => 'Başvuru için KVKK metnini onaylamanız gerekir.',
            'aydinlatma_onay.accepted' => 'Başvuru için aydınlatma metnini onaylamanız gerekir.',
        ], $this->cocukBasvuruMesajlari()));

        if (! $basvuran->ad || ! $basvuran->soyad || ! $basvuran->tc_kimlik_no) {
            throw ValidationException::withMessages([
                'ad' => 'Başvuru için profil bilgileriniz eksik. Lütfen profilinizi tamamlayın.',
            ]);
        }

        if ($cocukAdina) {
            $this->cocukBasvurusuIcinBasvuranUygunMu($basvuran);
            $kimlikCinsiyet = $this->cocukKimlikDogrula([
                'cocuk_tc_kimlik_no' => $validated['cocuk_tc_kimlik_no'],
                'cocuk_dogum_tarihi' => $validated['cocuk_dogum_tarihi'],
                'cocuk_ad' => $validated['cocuk_ad'],
                'cocuk_soyad' => $validated['cocuk_soyad'],
            ]);

            $cinsiyet = $this->cozulmusCinsiyet($validated['cinsiyet'] ?? null)
                ?? $kimlikCinsiyet;

            $katilimci = $this->kisiUpsert([
                'tc_kimlik_no' => $validated['cocuk_tc_kimlik_no'],
                'dogum_tarihi' => $validated['cocuk_dogum_tarihi'],
                'ad' => $validated['cocuk_ad'],
                'soyad' => $validated['cocuk_soyad'],
                'cinsiyet' => $cinsiyet,
            ]);

            $yas = $this->yasHesapla($validated['cocuk_dogum_tarihi']);
            $katilimciPayload = [
                'dogum_tarihi' => $validated['cocuk_dogum_tarihi'],
                'cinsiyet' => $cinsiyet ?? $katilimci->cinsiyet?->value,
                'il' => $validated['il'],
                'ilce' => $validated['ilce'],
            ];
            app(BasvuruKosulDogrulayici::class)->dogrula($kurs, $katilimciPayload, $yas);

            $kucuk = false;
            $veliDolu = false;
        } else {
            if (! $basvuran->dogum_tarihi) {
                throw ValidationException::withMessages([
                    'dogum_tarihi' => 'Başvuru için profilinizde doğum tarihi tanımlı olmalıdır.',
                ]);
            }

            $katilimci = $basvuran;
            $yas = $yasBasvuran;
            $cinsiyet = $this->cozulmusCinsiyet($validated['cinsiyet'] ?? null)
                ?? $basvuran->cinsiyet?->value
                ?? $this->kimliktenCinsiyetAl(
                    (string) $basvuran->tc_kimlik_no,
                    $basvuran->dogum_tarihi->format('Y-m-d'),
                );

            if ($cinsiyet && ! $basvuran->cinsiyet) {
                $basvuran->cinsiyet = $cinsiyet;
                $basvuran->save();
                $katilimci = $basvuran->fresh();
            }

            $katilimciPayload = [
                'dogum_tarihi' => $basvuran->dogum_tarihi->format('Y-m-d'),
                'cinsiyet' => $cinsiyet,
                'il' => $validated['il'],
                'ilce' => $validated['ilce'],
            ];
            app(BasvuruKosulDogrulayici::class)->dogrula($kurs, $katilimciPayload, $yas);

            $veliDolu = filled($validated['veli_tc_kimlik_no'] ?? null)
                || filled($validated['veli_ad'] ?? null)
                || filled($validated['veli_soyad'] ?? null);

            if ($veliDolu && ! $kucukBasvuran) {
                $request->validate([
                    'veli_tc_kimlik_no' => [
                        'required',
                        'digits:11',
                        Rule::notIn([(string) $basvuran->tc_kimlik_no]),
                    ],
                    'veli_dogum_tarihi' => ['required', 'date'],
                    'veli_ad' => ['required', 'string', 'max:100'],
                    'veli_soyad' => ['required', 'string', 'max:100'],
                ], [
                    'veli_tc_kimlik_no.required' => 'Veli bilgisi giriliyorsa TC Kimlik No zorunludur.',
                    'veli_tc_kimlik_no.not_in' => 'Veli TC Kimlik No katılımcıdan farklı olmalıdır.',
                    'veli_dogum_tarihi.required' => 'Veli bilgisi giriliyorsa doğum tarihi zorunludur.',
                    'veli_ad.required' => 'Veli bilgisi giriliyorsa ad zorunludur.',
                    'veli_soyad.required' => 'Veli bilgisi giriliyorsa soyad zorunludur.',
                ]);
                $validated = array_merge($validated, $request->only([
                    'veli_tc_kimlik_no', 'veli_dogum_tarihi', 'veli_ad', 'veli_soyad', 'veli_telefon', 'veli_email',
                ]));
                $veliDolu = true;
            }

            $kucuk = $kucukBasvuran;
        }

        $basvuru = DB::transaction(function () use (
            $request,
            $validated,
            $kurs,
            $basvuran,
            $katilimci,
            $cocukAdina,
            $kucuk,
            $veliDolu,
            $evrakTipiIds,
        ) {
            /** @var Kurs $kurs */
            $kurs = Kurs::query()->whereKey($kurs->id)->lockForUpdate()->firstOrFail();

            $yerlesim = app(KursYedekListeServisi::class)->yeniBasvuruDurumuBelirle($kurs);
            $durumId = $yerlesim['durum_id'];
            $durumKod = $yerlesim['durum_kod'];
            $yedekSira = $yerlesim['yedek_sira'];

            $basvuran->basvuruIleProfilGuncelle($validated);

            if ($cocukAdina) {
                $finalBasvuranId = $basvuran->id;
                $finalVeliId = $basvuran->id;
                $finalKisiId = $katilimci->id;
            } else {
                $veli = null;
                if ($kucuk || $veliDolu) {
                    $veli = $this->kisiUpsert([
                        'tc_kimlik_no' => $validated['veli_tc_kimlik_no'],
                        'dogum_tarihi' => $validated['veli_dogum_tarihi'],
                        'ad' => $validated['veli_ad'],
                        'soyad' => $validated['veli_soyad'],
                        'telefon' => $validated['veli_telefon'] ?? null,
                        'email' => $validated['veli_email'] ?? null,
                    ]);
                }

                $finalBasvuranId = $kucuk && $veli ? $veli->id : $basvuran->id;
                $finalVeliId = $veli?->id;
                $finalKisiId = $basvuran->id;
            }

            $iptalDurumId = BasvuruDurum::idByKod('iptal');
            $mevcutAktif = KursBasvuru::query()
                ->where('kisi_id', $finalKisiId)
                ->where('kurs_id', $kurs->id)
                ->whereNull('deleted_at')
                ->when($iptalDurumId, fn ($q) => $q->where('durum_id', '!=', $iptalDurumId))
                ->lockForUpdate()
                ->exists();

            if ($mevcutAktif) {
                throw ValidationException::withMessages([
                    'kurs_id' => $cocukAdina
                        ? 'Bu kursa ait çocuk için aktif bir başvuru zaten var.'
                        : 'Bu kursa ait aktif bir başvurunuz zaten var.',
                ]);
            }

            $basvuru = KursBasvuru::query()->create([
                'kisi_id' => $finalKisiId,
                'basvuran_id' => $finalBasvuranId,
                'veli_id' => $finalVeliId,
                'kurs_id' => $kurs->id,
                'durum_id' => $durumId,
                'yedek_sira' => $yedekSira,
                'olusturan_id' => null,
            ]);

            foreach ($evrakTipiIds as $tipId) {
                $file = $request->file("evrak.{$tipId}");
                if (! $file) {
                    continue;
                }

                $path = $file->store('basvuru-evraklari/'.$basvuru->id, 'public');
                KursBasvuruEvrak::query()->create([
                    'kurs_basvuru_id' => $basvuru->id,
                    'evrak_tipi_id' => $tipId,
                    'dosya_yolu' => $path,
                    'orijinal_ad' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'boyut' => $file->getSize() ?: 0,
                    'olusturan_id' => null,
                ]);
            }

            $kurs->update([
                'basvuru_sayisi' => $kurs->basvurular()->count(),
            ]);

            LogKaydedici::kaydet(
                islem: 'basvuru.olusturuldu',
                kurs: $kurs,
                aciklama: $basvuran->tam_adi.' portal üzerinden kurs başvurusu oluşturdu'
                    .($cocukAdina ? ' (çocuk: '.$katilimci->tam_adi.')' : '')
                    .($durumKod === 'yedek' ? ' (yedek sıra: '.$yedekSira.')' : '').'.',
                konu: $basvuru,
                yeni: [
                    'kisi_id' => $finalKisiId,
                    'basvuran_id' => $finalBasvuranId,
                    'veli_id' => $finalVeliId,
                    'durum' => $durumKod,
                    'yedek_sira' => $yedekSira,
                    'kanal' => 'portal',
                    'basvuru_icin' => $cocukAdina ? 'cocuk' : 'kendisi',
                ],
            );

            $basvuru->setAttribute('_olusturma_durum_kod', $durumKod);
            $basvuru->setAttribute('_olusturma_yedek_sira', $yedekSira);

            return $basvuru;
        });

        $durumKod = (string) $basvuru->getAttribute('_olusturma_durum_kod');
        $yedekSira = $basvuru->getAttribute('_olusturma_yedek_sira');
        $message = $durumKod === 'yedek'
            ? 'Kontenjan dolu olduğu için başvuru yedek listeye alındı. Yedek sırası: '.$yedekSira.'.'
            : 'Başvuru başarıyla kaydedildi.';

        $basvuru->load([
            'durum',
            'iptalGerekce',
            'kisi',
            'veli',
            'kurs.merkez',
            'kurs.brans',
            'evraklar.evrakTipi',
        ]);

        return $this->success([
            'basvuru' => new KursBasvuruResource($basvuru),
            'durum' => $durumKod,
            'yedek_sira' => $yedekSira,
            'kisi' => new KisiResource($basvuran->fresh()),
        ], $message, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $basvuru = $this->sahipBasvuru($request, $id);

        if (! $basvuru) {
            return $this->error('Başvuru bulunamadı.', 404);
        }

        $basvuru->load([
            'durum',
            'iptalGerekce',
            'kisi',
            'veli',
            'kurs.merkez',
            'kurs.brans',
            'evraklar.evrakTipi',
        ]);

        return $this->success(new KursBasvuruResource($basvuru));
    }

    public function iptal(Request $request, int $id): JsonResponse
    {
        $basvuru = $this->sahipBasvuru($request, $id);

        if (! $basvuru) {
            return $this->error('Başvuru bulunamadı.', 404);
        }

        $basvuru->load(['durum', 'basariDurum', 'kurs']);

        $mevcutDurumKod = $basvuru->durum?->kod
            ?? BasvuruDurum::query()->whereKey($basvuru->durum_id)->value('kod');

        if (! in_array($mevcutDurumKod, ['onay_bekliyor', 'yedek', 'kesin_kayit'], true)) {
            return $this->error('Bu başvuru iptal edilemez.', 422);
        }

        if ($mevcutDurumKod === 'kesin_kayit' && ! app(KursAyarServisi::class)->kisiOnaylanmisBasvuruIptalEdebilir()) {
            return $this->error('Onaylanmış başvurular iptal edilemez.', 422);
        }

        if ($mevcutDurumKod === 'kesin_kayit') {
            $basariKod = $basvuru->basariDurum?->kod
                ?? BasariDurum::query()->whereKey($basvuru->basari_durumu_id)->value('kod');

            if (in_array($basariKod, ['sertifika_hak_etti', 'katilim_belgesi_hak_etti'], true)) {
                return $this->error('Kesin kaydı iptal etmek için kurumla iletişime geçiniz.', 422);
            }
        }

        $validated = $request->validate([
            'iptal_gerekce_id' => [
                'nullable',
                'integer',
                Rule::exists('iptal_gerekceleri', 'id')->where(fn ($q) => $q->where('aktif', true)),
            ],
        ], [
            'iptal_gerekce_id.exists' => 'Seçilen iptal gerekçesi geçersiz.',
        ]);

        $iptalDurumId = BasvuruDurum::idByKod('iptal');
        if (! $iptalDurumId) {
            return $this->error('İptal durumu tanımlı değil.', 500);
        }

        $kurs = $basvuru->kurs;

        DB::transaction(function () use ($basvuru, $kurs, $validated, $iptalDurumId, $mevcutDurumKod) {
            $basvuru->update([
                'durum_id' => $iptalDurumId,
                'iptal_tarihi' => now(),
                'iptal_gerekce_id' => $validated['iptal_gerekce_id'] ?? null,
                'iptal_eden_id' => null,
            ]);

            if ($kurs) {
                app(KursYedekListeServisi::class)->durumDegisimindeYedekSirasiGuncelle(
                    $kurs,
                    $basvuru->fresh(),
                    $mevcutDurumKod,
                    'iptal',
                );
            }
        });

        $basvuru->refresh()->load([
            'durum',
            'iptalGerekce',
            'kisi',
            'veli',
            'kurs.merkez',
            'kurs.brans',
            'evraklar.evrakTipi',
        ]);

        LogKaydedici::kaydet(
            islem: 'basvuru.iptal',
            kurs: $kurs,
            aciklama: ($basvuru->kisi?->tam_adi ?? 'Vatandaş').' portal üzerinden başvurusunu iptal etti.',
            konu: $basvuru,
            eski: ['durum' => $mevcutDurumKod],
            yeni: [
                'durum' => 'iptal',
                'iptal_gerekce' => $basvuru->iptalGerekce?->ad,
                'kanal' => 'portal',
            ],
        );

        return $this->success(new KursBasvuruResource($basvuru), 'Başvuru iptal edildi.');
    }

    private function sahipBasvuru(Request $request, int $id): ?KursBasvuru
    {
        /** @var Kisi $kisi */
        $kisi = $request->user();

        return KursBasvuru::query()
            ->whereKey($id)
            ->where(function ($q) use ($kisi) {
                $q->where('kisi_id', $kisi->id)
                    ->orWhere('basvuran_id', $kisi->id)
                    ->orWhere('veli_id', $kisi->id);
            })
            ->first();
    }
}
