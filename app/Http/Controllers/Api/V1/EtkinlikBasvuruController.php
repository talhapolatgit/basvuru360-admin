<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Cinsiyet;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\V1\Concerns\ResolvesPortalBasvuruKatilimci;
use App\Http\Resources\Api\V1\EtkinlikBasvuruResource;
use App\Http\Resources\Api\V1\KisiResource;
use App\Models\Etkinlik;
use App\Models\EtkinlikBasvuru;
use App\Models\EtkinlikBasvuruDurum;
use App\Models\EtkinlikBasvuruEvrak;
use App\Models\Kisi;
use App\Services\BasvuruKosulDogrulayici;
use App\Services\EtkinlikAyarServisi;
use App\Services\EtkinlikYedekListeServisi;
use App\Services\LogKaydedici;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EtkinlikBasvuruController extends ApiController
{
    use ResolvesPortalBasvuruKatilimci;

    public function store(Request $request): JsonResponse
    {
        /** @var Kisi $basvuran */
        $basvuran = $request->user();
        $cocukAdina = $this->basvuruIcinCocukMu($request);

        $etkinlik = Etkinlik::query()
            ->portaldeAktif()
            ->with([
                'merkez',
                'evrakTipleri' => fn ($q) => $q->where('aktif', true),
            ])
            ->find($request->integer('etkinlik_id'));

        if (! $etkinlik) {
            return $this->error('Etkinlik bulunamadı veya başvuru alınmıyor.', 404);
        }

        if ($etkinlik->basvuruDurumuKod() !== 'acik') {
            return $this->error('Bu etkinliğe şu anda başvuru alınmamaktadır.', 422);
        }

        $evrakTipiIds = $etkinlik->evrak_zorunlu
            ? $etkinlik->evrakTipleri->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $kimlikAktif = $this->kimlikSorgulamaAktifMi();
        $cinsiyetZorunlu = $etkinlik->cinsiyet_sarti && ! $kimlikAktif;

        $rules = [
            'etkinlik_id' => ['required', 'integer', 'exists:etkinlikler,id'],
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
            'yakinlik_derecesi' => ['nullable', Rule::in(['ESI', 'OGLU', 'KIZI'])],
        ];

        $onayKodlari = collect(app(EtkinlikAyarServisi::class)->basvuruOnaylari())
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
            'etkinlik_id.required' => 'Etkinlik seçilmelidir.',
            'cinsiyet.required' => 'Bu etkinlik için cinsiyet seçimi zorunludur.',
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
            $this->manuelYakinEklemeIzinliMi($basvuran, (string) $validated['cocuk_tc_kimlik_no']);
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

            $this->cocukYakinligiKaydet(
                $basvuran,
                $katilimci,
                $cinsiyet,
                $validated['yakinlik_derecesi'] ?? null,
            );

            $yas = $this->yasHesapla($validated['cocuk_dogum_tarihi']);
            $katilimciPayload = [
                'dogum_tarihi' => $validated['cocuk_dogum_tarihi'],
                'cinsiyet' => $cinsiyet ?? $katilimci->cinsiyet?->value,
                'il' => $validated['il'],
                'ilce' => $validated['ilce'],
            ];
            app(BasvuruKosulDogrulayici::class)->dogrula($etkinlik, $katilimciPayload, $yas);

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
            app(BasvuruKosulDogrulayici::class)->dogrula($etkinlik, $katilimciPayload, $yas);

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
            $etkinlik,
            $basvuran,
            $katilimci,
            $cocukAdina,
            $kucuk,
            $veliDolu,
            $evrakTipiIds,
        ) {
            /** @var Etkinlik $etkinlik */
            $etkinlik = Etkinlik::query()->whereKey($etkinlik->id)->lockForUpdate()->firstOrFail();

            $yerlesim = app(EtkinlikYedekListeServisi::class)->yeniBasvuruDurumuBelirle($etkinlik);
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

            $iptalDurumId = EtkinlikBasvuruDurum::idByKod('iptal');
            $mevcutAktif = EtkinlikBasvuru::query()
                ->where('kisi_id', $finalKisiId)
                ->where('etkinlik_id', $etkinlik->id)
                ->whereNull('deleted_at')
                ->when($iptalDurumId, fn ($q) => $q->where('durum_id', '!=', $iptalDurumId))
                ->lockForUpdate()
                ->exists();

            if ($mevcutAktif) {
                throw ValidationException::withMessages([
                    'etkinlik_id' => $cocukAdina
                        ? 'Bu etkinliğe ait çocuk için aktif bir başvuru zaten var.'
                        : 'Bu etkinliğe ait aktif bir başvurunuz zaten var.',
                ]);
            }

            $basvuru = EtkinlikBasvuru::query()->create([
                'kisi_id' => $finalKisiId,
                'basvuran_id' => $finalBasvuranId,
                'veli_id' => $finalVeliId,
                'etkinlik_id' => $etkinlik->id,
                'durum_id' => $durumId,
                'yedek_sira' => $yedekSira,
                'olusturan_id' => null,
            ]);

            foreach ($evrakTipiIds as $tipId) {
                $file = $request->file("evrak.{$tipId}");
                if (! $file) {
                    continue;
                }

                $path = $file->store('etkinlik-basvuru-evraklari/'.$basvuru->id, 'public');
                EtkinlikBasvuruEvrak::query()->create([
                    'etkinlik_basvuru_id' => $basvuru->id,
                    'evrak_tipi_id' => $tipId,
                    'dosya_yolu' => $path,
                    'orijinal_ad' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'boyut' => $file->getSize() ?: 0,
                    'olusturan_id' => null,
                ]);
            }

            $etkinlik->update([
                'basvuru_sayisi' => $etkinlik->basvurular()->count(),
            ]);

            LogKaydedici::kaydet(
                islem: 'etkinlik_basvuru.olusturuldu',
                kurs: null,
                aciklama: $basvuran->tam_adi.' portal üzerinden etkinlik başvurusu oluşturdu'
                    .($cocukAdina ? ' (yakın: '.$katilimci->tam_adi.')' : '')
                    .($durumKod === 'yedek' ? ' (yedek sıra: '.$yedekSira.')' : '').'.',
                konu: $basvuru,
                yeni: [
                    'kisi_id' => $finalKisiId,
                    'basvuran_id' => $finalBasvuranId,
                    'veli_id' => $finalVeliId,
                    'etkinlik_id' => $etkinlik->id,
                    'durum' => $durumKod,
                    'yedek_sira' => $yedekSira,
                    'kanal' => 'portal',
                    'basvuru_icin' => $cocukAdina ? 'cocuk' : 'kendisi',
                ],
                ekstra: ['etkinlik_id' => $etkinlik->id],
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
            'etkinlik.merkez',
            'evraklar.evrakTipi',
        ]);

        return $this->success([
            'basvuru' => new EtkinlikBasvuruResource($basvuru),
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
            'etkinlik.merkez',
            'evraklar.evrakTipi',
        ]);

        return $this->success(new EtkinlikBasvuruResource($basvuru));
    }

    public function iptal(Request $request, int $id): JsonResponse
    {
        $basvuru = $this->sahipBasvuru($request, $id);

        if (! $basvuru) {
            return $this->error('Başvuru bulunamadı.', 404);
        }

        $basvuru->load(['durum', 'etkinlik']);

        $mevcutDurumKod = $basvuru->durum?->kod
            ?? EtkinlikBasvuruDurum::query()->whereKey($basvuru->durum_id)->value('kod');

        if (! in_array($mevcutDurumKod, ['onay_bekliyor', 'yedek', 'kesin_kayit'], true)) {
            return $this->error('Bu başvuru iptal edilemez.', 422);
        }

        if ($mevcutDurumKod === 'kesin_kayit' && ! app(EtkinlikAyarServisi::class)->kisiOnaylanmisBasvuruIptalEdebilir()) {
            return $this->error('Onaylanmış başvurular iptal edilemez.', 422);
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

        $iptalDurumId = EtkinlikBasvuruDurum::idByKod('iptal');
        if (! $iptalDurumId) {
            return $this->error('İptal durumu tanımlı değil.', 500);
        }

        $etkinlik = $basvuru->etkinlik;

        DB::transaction(function () use ($basvuru, $etkinlik, $validated, $iptalDurumId, $mevcutDurumKod) {
            $basvuru->update([
                'durum_id' => $iptalDurumId,
                'iptal_tarihi' => now(),
                'iptal_gerekce_id' => $validated['iptal_gerekce_id'] ?? null,
                'iptal_eden_id' => null,
            ]);

            if ($etkinlik) {
                app(EtkinlikYedekListeServisi::class)->durumDegisimindeYedekSirasiGuncelle(
                    $etkinlik,
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
            'etkinlik.merkez',
            'evraklar.evrakTipi',
        ]);

        LogKaydedici::kaydet(
            islem: 'etkinlik_basvuru.iptal',
            kurs: null,
            aciklama: ($basvuru->kisi?->tam_adi ?? 'Vatandaş').' portal üzerinden etkinlik başvurusunu iptal etti.',
            konu: $basvuru,
            eski: ['durum' => $mevcutDurumKod],
            yeni: [
                'durum' => 'iptal',
                'iptal_gerekce' => $basvuru->iptalGerekce?->ad,
                'kanal' => 'portal',
            ],
            ekstra: ['etkinlik_id' => $etkinlik?->id],
        );

        return $this->success(new EtkinlikBasvuruResource($basvuru), 'Başvuru iptal edildi.');
    }

    private function sahipBasvuru(Request $request, int $id): ?EtkinlikBasvuru
    {
        /** @var Kisi $kisi */
        $kisi = $request->user();

        return EtkinlikBasvuru::query()
            ->whereKey($id)
            ->where(function ($q) use ($kisi) {
                $q->where('kisi_id', $kisi->id)
                    ->orWhere('basvuran_id', $kisi->id)
                    ->orWhere('veli_id', $kisi->id);
            })
            ->first();
    }
}
