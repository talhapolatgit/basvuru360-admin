<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Enums\EtkinlikDurum;
use App\Models\Etkinlik;
use App\Models\EtkinlikBasvuru;
use App\Models\EtkinlikBasvuruDurum;
use App\Models\EtkinlikBasvuruEvrak;
use App\Models\EtkinlikTipi;
use App\Models\IptalGerekce;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Models\Merkez;
use App\Models\User;
use App\Services\BasvuruKosulDogrulayici;
use App\Services\EtkinlikAyarServisi;
use App\Services\EtkinlikYedekListeServisi;
use App\Services\LogKaydedici;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EtkinlikBasvuruController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('basvuru_durum')) {
            $request->merge(['basvuru_durum' => 'tumu']);
        }

        [$basvurular, $sort, $direction, $filters] = $this->searchBasvurular($request);

        $columns = $this->basvurularTableColumns();

        $viewData = [
            'basvurular' => $basvurular,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
            'defaultVisible' => $columns['defaultVisible'],
            'allColumns' => $columns['all'],
            'sortableColumns' => $columns['sortable'],
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('etkinlik-basvurulari._results', $viewData);
        }

        $etkinlikAyarlari = app(EtkinlikAyarServisi::class);

        return view('etkinlik-basvurulari.index', $viewData + [
            'merkezler' => Merkez::query()->kullaniciKapsami($request->user())->where('aktif', true)->orderBy('ad')->get(),
            'kurumlar' => Kurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
            'etkinlikTipleri' => EtkinlikTipi::where('aktif', true)->orderBy('ad')->get(),
            'basvuruDurumlari' => EtkinlikBasvuruDurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
            'iptalGerekceleri' => IptalGerekce::query()->where('aktif', true)->orderBy('sira')->get(),
            'smsBasvuruOnayAyar' => $etkinlikAyarlari->smsBasvuruOnay()->value,
            'smsBasvuruIptalAyar' => $etkinlikAyarlari->smsBasvuruIptal()->value,
            'smsBasvuruYedekAyar' => $etkinlikAyarlari->smsBasvuruYedek()->value,
            'epostaBasvuruOnayAyar' => $etkinlikAyarlari->epostaBasvuruOnay()->value,
            'epostaBasvuruIptalAyar' => $etkinlikAyarlari->epostaBasvuruIptal()->value,
            'epostaBasvuruYedekAyar' => $etkinlikAyarlari->epostaBasvuruYedek()->value,
            'ozet' => $this->basvuruGenelOzet(),
        ]);
    }

    public function create(Request $request): View
    {
        $etkinlikler = $this->basvuruIcinEtkinlikler($request->user());
        $seciliId = old('etkinlik_id', $request->input('etkinlik_id'));

        return view('etkinlik-basvurulari.create', [
            'etkinlikler' => $etkinlikler,
            'etkinlikOptions' => $etkinlikler->map(fn (Etkinlik $etkinlik) => [
                'value' => $etkinlik->id,
                'label' => '#'.$etkinlik->etkinlik_no.' — '.($etkinlik->ad ?? 'Etkinlik').' ('.($etkinlik->merkez?->ad ?? '—').')',
            ])->all(),
            'seciliEtkinlikId' => $seciliId,
        ]);
    }

    public function etkinlikOzet(Request $request, Etkinlik $etkinlik): JsonResponse
    {
        $this->assertEtkinlikBasvuruIcinUygun($request->user(), $etkinlik);
        $etkinlik->load([
            'merkez',
            'etkinlikTipi',
            'evrakTipleri' => fn ($q) => $q->where('aktif', true)->orderBy('ad'),
        ]);

        return response()->json([
            'id' => $etkinlik->id,
            'etkinlik_no' => $etkinlik->etkinlik_no,
            'ad' => $etkinlik->ad,
            'tip' => $etkinlik->etkinlikTipi?->ad,
            'merkez' => $etkinlik->merkez?->ad,
            'durum' => $etkinlik->durum?->label(),
            'durum_kod' => $etkinlik->durum?->value,
            'baslama' => $etkinlik->baslangic_tarihi?->format('d.m.Y'),
            'bitis' => $etkinlik->bitis_tarihi?->format('d.m.Y'),
            'basvuru_baslama' => $etkinlik->basvuru_baslama_tarihi?->format('d.m.Y H:i'),
            'basvuru_bitis' => $etkinlik->basvuru_bitis_tarihi?->format('d.m.Y H:i'),
            'kontenjan' => $etkinlik->kontenjan,
            'yedek_kontenjan' => $etkinlik->yedek_kontenjan,
            'kayit_sayisi' => $etkinlik->kayit_sayisi,
            'basvuru_sayisi' => $etkinlik->basvuru_sayisi,
            'evrak_zorunlu' => (bool) $etkinlik->evrak_zorunlu,
            'evrak_tipleri' => $etkinlik->evrakTipleri->map(fn ($tip) => [
                'id' => $tip->id,
                'ad' => $tip->ad,
                'aciklama' => $tip->aciklama,
            ])->values(),
            'doluluk' => app(EtkinlikYedekListeServisi::class)->dolulukOzeti($etkinlik),
            'kosullar' => [
                'minimum_yas' => $etkinlik->minimum_yas,
                'maksimum_yas' => $etkinlik->maksimum_yas,
                'cinsiyet_sarti' => $etkinlik->cinsiyet_sarti?->value,
                'cinsiyet_sarti_label' => $etkinlik->cinsiyet_sarti?->label(),
                'ikamet_sarti' => $etkinlik->ikamet_sarti?->value,
                'ikamet_disi_kontenjan' => $etkinlik->ikamet_disi_kontenjan,
                'hizmet_ili' => $etkinlik->merkez?->il ?: config('basvuru.hizmet_ili'),
                'hizmet_ilcesi' => $etkinlik->merkez?->ilce ?: config('basvuru.hizmet_ilcesi'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $etkinlik = Etkinlik::query()->findOrFail($request->integer('etkinlik_id'));
        $this->assertEtkinlikBasvuruIcinUygun($request->user(), $etkinlik);
        $etkinlik->load([
            'merkez',
            'evrakTipleri' => fn ($q) => $q->where('aktif', true),
        ]);

        $evrakTipiIds = $etkinlik->evrak_zorunlu
            ? $etkinlik->evrakTipleri->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $rules = [
            'etkinlik_id' => ['required', 'integer', 'exists:etkinlikler,id'],
            'tc_kimlik_no' => ['required', 'digits:11'],
            'dogum_tarihi' => ['required', 'date'],
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'cinsiyet' => [
                $etkinlik->cinsiyet_sarti ? 'required' : 'nullable',
                Rule::enum(Cinsiyet::class),
            ],
            'telefon' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'il' => ['required', 'string', 'max:100'],
            'ilce' => ['required', 'string', 'max:100'],
            'adres' => ['required', 'string', 'max:500'],
            'veli_tc_kimlik_no' => ['nullable', 'digits:11', 'different:tc_kimlik_no'],
            'veli_dogum_tarihi' => ['nullable', 'date'],
            'veli_ad' => ['nullable', 'string', 'max:100'],
            'veli_soyad' => ['nullable', 'string', 'max:100'],
            'veli_telefon' => ['nullable', 'string', 'max:20'],
            'veli_email' => ['nullable', 'email', 'max:150'],
        ];

        foreach ($evrakTipiIds as $tipId) {
            $rules["evrak.{$tipId}"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        $yas = $this->yasHesapla((string) $request->input('dogum_tarihi'));
        $kucuk = $yas !== null && $yas < 18;

        if ($kucuk) {
            $rules['veli_tc_kimlik_no'] = ['required', 'digits:11', 'different:tc_kimlik_no'];
            $rules['veli_dogum_tarihi'] = ['required', 'date'];
            $rules['veli_ad'] = ['required', 'string', 'max:100'];
            $rules['veli_soyad'] = ['required', 'string', 'max:100'];
        }

        $validated = $request->validate($rules, [
            'etkinlik_id.required' => 'Etkinlik seçilmelidir.',
            'tc_kimlik_no.required' => 'TC Kimlik No zorunludur.',
            'tc_kimlik_no.digits' => 'TC Kimlik No 11 haneli olmalıdır.',
            'dogum_tarihi.required' => 'Doğum tarihi zorunludur.',
            'ad.required' => 'Ad zorunludur.',
            'soyad.required' => 'Soyad zorunludur.',
            'cinsiyet.required' => 'Bu etkinlik için cinsiyet seçimi zorunludur.',
            'il.required' => 'İl zorunludur.',
            'ilce.required' => 'İlçe zorunludur.',
            'adres.required' => 'Adres zorunludur.',
            'veli_tc_kimlik_no.required' => '18 yaşından küçük başvurularda veli TC Kimlik No zorunludur.',
            'veli_tc_kimlik_no.different' => 'Veli TC Kimlik No katılımcıdan farklı olmalıdır.',
            'veli_dogum_tarihi.required' => '18 yaşından küçük başvurularda veli doğum tarihi zorunludur.',
            'veli_ad.required' => '18 yaşından küçük başvurularda veli adı zorunludur.',
            'veli_soyad.required' => '18 yaşından küçük başvurularda veli soyadı zorunludur.',
            'evrak.*.required' => 'Zorunlu evrak yüklenmelidir.',
            'evrak.*.mimes' => 'Evrak PDF veya görsel (JPG/PNG) olmalıdır.',
            'evrak.*.max' => 'Evrak en fazla 5 MB olabilir.',
        ]);

        app(BasvuruKosulDogrulayici::class)->dogrula($etkinlik, $validated, $yas);
        $veliDolu = filled($validated['veli_tc_kimlik_no'] ?? null)
            || filled($validated['veli_ad'] ?? null)
            || filled($validated['veli_soyad'] ?? null);

        if ($veliDolu && ! $kucuk) {
            $request->validate([
                'veli_tc_kimlik_no' => ['required', 'digits:11', 'different:tc_kimlik_no'],
                'veli_dogum_tarihi' => ['required', 'date'],
                'veli_ad' => ['required', 'string', 'max:100'],
                'veli_soyad' => ['required', 'string', 'max:100'],
            ], [
                'veli_tc_kimlik_no.required' => 'Veli bilgisi giriliyorsa TC Kimlik No zorunludur.',
                'veli_tc_kimlik_no.different' => 'Veli TC Kimlik No katılımcıdan farklı olmalıdır.',
                'veli_dogum_tarihi.required' => 'Veli bilgisi giriliyorsa doğum tarihi zorunludur.',
                'veli_ad.required' => 'Veli bilgisi giriliyorsa ad zorunludur.',
                'veli_soyad.required' => 'Veli bilgisi giriliyorsa soyad zorunludur.',
            ]);
            $validated = array_merge($validated, $request->only([
                'veli_tc_kimlik_no', 'veli_dogum_tarihi', 'veli_ad', 'veli_soyad', 'veli_telefon', 'veli_email',
            ]));
            $veliDolu = true;
        }

        $basvuru = DB::transaction(function () use ($request, $validated, $etkinlik, $kucuk, $veliDolu, $evrakTipiIds) {
            /** @var Etkinlik $etkinlik */
            $etkinlik = Etkinlik::query()->whereKey($etkinlik->id)->lockForUpdate()->firstOrFail();

            $yerlesim = app(EtkinlikYedekListeServisi::class)->yeniBasvuruDurumuBelirle($etkinlik);
            $durumId = $yerlesim['durum_id'];
            $durumKod = $yerlesim['durum_kod'];
            $yedekSira = $yerlesim['yedek_sira'];

            $katilimci = $this->kisiUpsert([
                'tc_kimlik_no' => $validated['tc_kimlik_no'],
                'dogum_tarihi' => $validated['dogum_tarihi'],
                'ad' => $validated['ad'],
                'soyad' => $validated['soyad'],
                'cinsiyet' => $validated['cinsiyet'] ?? null,
                'telefon' => $validated['telefon'] ?? null,
                'email' => $validated['email'] ?? null,
                'il' => $validated['il'],
                'ilce' => $validated['ilce'],
                'adres' => $validated['adres'],
            ]);

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

            $iptalDurumId = EtkinlikBasvuruDurum::idByKod('iptal');
            $mevcutAktif = EtkinlikBasvuru::query()
                ->where('kisi_id', $katilimci->id)
                ->where('etkinlik_id', $etkinlik->id)
                ->whereNull('deleted_at')
                ->when($iptalDurumId, fn ($q) => $q->where('durum_id', '!=', $iptalDurumId))
                ->lockForUpdate()
                ->exists();

            if ($mevcutAktif) {
                throw ValidationException::withMessages([
                    'tc_kimlik_no' => 'Bu kişi için seçilen etkinliğe ait aktif bir başvuru zaten var.',
                ]);
            }

            $basvuranId = $kucuk && $veli ? $veli->id : $katilimci->id;
            $veliId = $veli?->id;

            $basvuru = EtkinlikBasvuru::query()->create([
                'kisi_id' => $katilimci->id,
                'basvuran_id' => $basvuranId,
                'veli_id' => $veliId,
                'etkinlik_id' => $etkinlik->id,
                'durum_id' => $durumId,
                'yedek_sira' => $yedekSira,
                'olusturan_id' => $request->user()?->id,
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
                    'olusturan_id' => $request->user()?->id,
                ]);
            }

            $etkinlik->update([
                'basvuru_sayisi' => $etkinlik->basvurular()->count(),
            ]);

            LogKaydedici::kaydet(
                islem: 'etkinlik_basvuru.olusturuldu',
                kurs: null,
                aciklama: $katilimci->tam_adi.' için yeni etkinlik başvurusu oluşturuldu'
                    .($durumKod === 'yedek' ? ' (yedek sıra: '.$yedekSira.')' : '').'.',
                konu: $basvuru,
                yeni: [
                    'kisi_id' => $katilimci->id,
                    'veli_id' => $veliId,
                    'etkinlik_id' => $etkinlik->id,
                    'durum' => $durumKod,
                    'yedek_sira' => $yedekSira,
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
        $redirect = route('etkinlikler.basvurular.show', [$etkinlik, $basvuru]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'redirect' => $redirect,
                'durum' => $durumKod,
                'yedek_sira' => $yedekSira,
            ]);
        }

        return redirect($redirect)->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('basvuru_durum')) {
            $request->merge(['basvuru_durum' => 'tumu']);
        }

        [$basvurular] = $this->searchBasvurular($request, paginate: false);

        $filename = 'etkinlik-basvurulari-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($basvurular) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Etkinlik No', 'Etkinlik', 'Merkez',
                'Başvuran', 'Katılımcı', 'Veli', 'Kimlik No', 'Doğum T.', 'Telefon', 'İkamet',
                'Durum', 'Katılım', 'Onay Tarihi', 'İptal Tarihi', 'İptal Gerekçesi', 'Kaydeden', 'Başvuru Tarihi',
            ], ';');

            $basvurular->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $basvuru) {
                    fputcsv($handle, [
                        $basvuru->etkinlik?->etkinlik_no ?? '',
                        $basvuru->etkinlik?->ad ?? '',
                        $basvuru->etkinlik?->merkez?->ad ?? '',
                        $basvuru->basvuran?->tam_adi ?? ($basvuru->kisi?->tam_adi ?? ''),
                        $basvuru->kisi?->tam_adi ?? '',
                        $basvuru->veli?->tam_adi ?? '',
                        $basvuru->kisi?->tc_kimlik_no ?? '',
                        $basvuru->kisi?->dogum_tarihi?->format('d.m.Y') ?? '',
                        $basvuru->kisi?->telefon ?? ($basvuru->basvuran?->telefon ?? ''),
                        $basvuru->kisi?->ilce ?? ($basvuru->kisi?->il ?? ''),
                        $basvuru->durum?->ad ?? '',
                        $basvuru->katilim_durumu?->label() ?? '',
                        $basvuru->onay_tarihi?->format('d.m.Y H:i') ?? '',
                        $basvuru->iptal_tarihi?->format('d.m.Y H:i') ?? '',
                        $basvuru->iptalGerekce?->ad ?? '',
                        $basvuru->olusturan?->tam_adi ?? '',
                        $basvuru->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function searchBasvurular(Request $request, bool $paginate = true): array
    {
        $query = EtkinlikBasvuru::query()
            ->with(['kisi', 'basvuran', 'veli', 'durum', 'iptalGerekce', 'olusturan', 'etkinlik.merkez', 'etkinlik.etkinlikTipi']);

        $user = $request->user();
        if ($user && $user->sadeceAtananEtkinlikleriGorur()) {
            $query->whereHas('etkinlik.sorumlular', fn (Builder $u) => $u->where('users.id', $user->id));
        }

        if ($user && $user->sadeceYetkiliMerkezleriGorurEtkinlik()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereHas('etkinlik', fn (Builder $k) => $k->whereIn('merkez_id', $merkezIds));
            }
        }

        if ($user && $user->sadeceKendiKurumlariniGorurEtkinlik()) {
            $query->whereHas('etkinlik', fn (Builder $k) => $k->kullaniciKurumKapsami($user));
        }

        if ($request->filled('ad')) {
            $value = trim((string) $request->string('ad'));
            $mode = (string) $request->input('ad_mode', 'contains');
            if ($value !== '') {
                $query->where(function (Builder $outer) use ($value, $mode) {
                    $outer->whereHas('kisi', fn (Builder $k) => $this->applyKisiAdFilter($k, $value, $mode))
                        ->orWhereHas('basvuran', fn (Builder $k) => $this->applyKisiAdFilter($k, $value, $mode));
                });
            }
        }

        if ($request->filled('kimlik')) {
            $value = trim((string) $request->string('kimlik'));
            $mode = (string) $request->input('kimlik_mode', 'exact');
            if ($value !== '') {
                $query->where(function (Builder $outer) use ($value, $mode) {
                    $outer->whereHas('kisi', fn (Builder $k) => $this->applyColumnModeFilter($k, 'tc_kimlik_no', $value, $mode))
                        ->orWhereHas('basvuran', fn (Builder $k) => $this->applyColumnModeFilter($k, 'tc_kimlik_no', $value, $mode));
                });
            }
        }

        if ($request->filled('telefon')) {
            $value = trim((string) $request->string('telefon'));
            $mode = (string) $request->input('telefon_mode', 'contains');
            if ($value !== '') {
                $query->where(function (Builder $outer) use ($value, $mode) {
                    $outer->whereHas('kisi', fn (Builder $k) => $this->applyColumnModeFilter($k, 'telefon', $value, $mode))
                        ->orWhereHas('basvuran', fn (Builder $k) => $this->applyColumnModeFilter($k, 'telefon', $value, $mode));
                });
            }
        }

        if ($request->filled('etkinlik_no')) {
            $value = (string) $request->string('etkinlik_no');
            $mode = (string) $request->input('etkinlik_no_mode', 'exact');

            $query->whereHas('etkinlik', function (Builder $k) use ($value, $mode) {
                $this->applyColumnModeFilter($k, 'etkinlik_no', $value, $mode);
            });
        }

        if ($request->filled('etkinlik_ad')) {
            $value = (string) $request->string('etkinlik_ad');
            $mode = (string) $request->input('etkinlik_ad_mode', 'contains');

            $query->whereHas('etkinlik', function (Builder $k) use ($value, $mode) {
                $this->applyColumnModeFilter($k, 'ad', $value, $mode);
            });
        }

        if ($request->filled('merkez_id')) {
            $merkezId = $request->integer('merkez_id');
            $query->whereHas('etkinlik', fn (Builder $k) => $k->where('merkez_id', $merkezId));
        }

        if ($request->filled('kurum_id')) {
            $kurumId = $request->integer('kurum_id');
            $query->whereHas('etkinlik.kurumlar', fn (Builder $k) => $k->where('kurumlar.id', $kurumId));
        }

        $tipId = $request->filled('etkinlik_tipi_id')
            ? $request->integer('etkinlik_tipi_id')
            : ($request->filled('tip') ? $request->integer('tip') : null);

        if ($tipId) {
            $query->whereHas('etkinlik', fn (Builder $k) => $k->where('etkinlik_tipi_id', $tipId));
        }

        $basvuruDurumlari = EtkinlikBasvuruDurum::query()->where('aktif', true)->orderBy('sira')->get();
        $basvuruDurum = (string) $request->input('basvuru_durum', 'tumu');
        $seciliDurum = $basvuruDurumlari->firstWhere('kod', $basvuruDurum);
        if ($seciliDurum) {
            $query->where('durum_id', $seciliDurum->id);
        } else {
            $basvuruDurum = 'tumu';
        }

        if ($request->filled('basvuru_ilk')) {
            $query->whereDate('etkinlik_basvurulari.created_at', '>=', $request->string('basvuru_ilk'));
        }

        if ($request->filled('basvuru_son')) {
            $query->whereDate('etkinlik_basvurulari.created_at', '<=', $request->string('basvuru_son'));
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'etkinlik_no' => 'etkinlikler.etkinlik_no',
            'etkinlik' => 'etkinlikler.ad',
            'merkez' => 'merkezler.ad',
            'basvuran' => 'basvuran.ad',
            'katilimci' => 'kisi.ad',
            'veli' => 'veli.ad',
            'kimlik' => 'kisi.tc_kimlik_no',
            'dogum' => 'kisi.dogum_tarihi',
            'telefon' => 'kisi.telefon',
            'ikamet' => 'kisi.ilce',
            'durum' => 'etkinlik_basvuru_durumlari.ad',
            'yedek_sira' => 'etkinlik_basvurulari.yedek_sira',
            'onay' => 'etkinlik_basvurulari.onay_tarihi',
            'iptal' => 'etkinlik_basvurulari.iptal_tarihi',
            'iptal_gerekce' => 'iptal_gerekceleri.ad',
            'kaydeden' => 'olusturan.ad',
            'basvuru_tarihi' => 'etkinlik_basvurulari.created_at',
        ];

        if ($sort === '' && (string) $request->input('basvuru_durum') === 'yedek') {
            $sort = 'yedek_sira';
            $direction = 'asc';
        }

        if (isset($sortable[$sort])) {
            $query->reorder();

            if (in_array($sort, ['etkinlik_no', 'etkinlik', 'merkez'], true)) {
                $query->leftJoin('etkinlikler', 'etkinlikler.id', '=', 'etkinlik_basvurulari.etkinlik_id')
                    ->leftJoin('merkezler', 'merkezler.id', '=', 'etkinlikler.merkez_id')
                    ->orderBy($sortable[$sort], $direction)
                    ->select('etkinlik_basvurulari.*');
            } elseif (in_array($sort, ['basvuran', 'katilimci', 'veli', 'kimlik', 'dogum', 'telefon', 'ikamet'], true)) {
                $alias = match ($sort) {
                    'basvuran' => 'basvuran',
                    'veli' => 'veli',
                    default => 'kisi',
                };
                $fk = match ($sort) {
                    'basvuran' => 'basvuran_id',
                    'veli' => 'veli_id',
                    default => 'kisi_id',
                };
                $query->leftJoin('kisiler as '.$alias, $alias.'.id', '=', 'etkinlik_basvurulari.'.$fk)
                    ->orderBy($sortable[$sort], $direction)
                    ->select('etkinlik_basvurulari.*');
            } elseif ($sort === 'durum') {
                $query->leftJoin('etkinlik_basvuru_durumlari', 'etkinlik_basvuru_durumlari.id', '=', 'etkinlik_basvurulari.durum_id')
                    ->orderBy('etkinlik_basvuru_durumlari.ad', $direction)
                    ->select('etkinlik_basvurulari.*');
            } elseif ($sort === 'iptal_gerekce') {
                $query->leftJoin('iptal_gerekceleri', 'iptal_gerekceleri.id', '=', 'etkinlik_basvurulari.iptal_gerekce_id')
                    ->orderBy('iptal_gerekceleri.ad', $direction)
                    ->select('etkinlik_basvurulari.*');
            } elseif ($sort === 'kaydeden') {
                $query->leftJoin('users as olusturan', 'olusturan.id', '=', 'etkinlik_basvurulari.olusturan_id')
                    ->orderBy('olusturan.ad', $direction)
                    ->select('etkinlik_basvurulari.*');
            } else {
                $query->orderBy($sortable[$sort], $direction);
            }
        } else {
            $query->latest('etkinlik_basvurulari.created_at');
            $sort = '';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only([
            'ad', 'ad_mode', 'kimlik', 'kimlik_mode', 'telefon', 'telefon_mode',
            'etkinlik_no', 'etkinlik_no_mode', 'etkinlik_ad', 'etkinlik_ad_mode',
            'merkez_id', 'kurum_id', 'etkinlik_tipi_id',
            'basvuru_durum', 'basvuru_ilk', 'basvuru_son', 'per_page', 'sort', 'direction',
        ]);
        if (! isset($filters['etkinlik_tipi_id']) && $request->filled('tip')) {
            $filters['etkinlik_tipi_id'] = $request->input('tip');
        }
        $filters['basvuru_durum'] = $basvuruDurum;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    /**
     * @param  Builder<\App\Models\Kisi>  $query
     */
    private function applyKisiAdFilter(Builder $query, string $value, string $mode): void
    {
        $query->where(function (Builder $qq) use ($value, $mode) {
            match ($mode) {
                'starts' => $qq->where('ad', 'like', $value.'%')
                    ->orWhere('soyad', 'like', $value.'%')
                    ->orWhereRaw("CONCAT(ad, ' ', soyad) LIKE ?", [$value.'%']),
                'ends' => $qq->where('ad', 'like', '%'.$value)
                    ->orWhere('soyad', 'like', '%'.$value)
                    ->orWhereRaw("CONCAT(ad, ' ', soyad) LIKE ?", ['%'.$value]),
                'exact' => $qq->where('ad', $value)
                    ->orWhere('soyad', $value)
                    ->orWhereRaw("CONCAT(ad, ' ', soyad) = ?", [$value]),
                default => $qq->where('ad', 'like', '%'.$value.'%')
                    ->orWhere('soyad', 'like', '%'.$value.'%')
                    ->orWhereRaw("CONCAT(ad, ' ', soyad) LIKE ?", ['%'.$value.'%']),
            };
        });
    }

    private function applyColumnModeFilter(Builder $query, string $column, string $value, string $mode): void
    {
        match ($mode) {
            'starts' => $query->where($column, 'like', $value.'%'),
            'ends' => $query->where($column, 'like', '%'.$value),
            'exact' => $query->where($column, $value),
            default => $query->where($column, 'like', '%'.$value.'%'),
        };
    }

    /**
     * @return array{all: array<string, string>, defaultVisible: list<string>, sortable: list<string>}
     */
    private function basvurularTableColumns(): array
    {
        return [
            'all' => [
                'etkinlik_no' => 'Etkinlik No',
                'etkinlik' => 'Etkinlik',
                'merkez' => 'Merkez',
                'basvuran' => 'Başvuran',
                'katilimci' => 'Katılımcı',
                'veli' => 'Veli',
                'kimlik' => 'Kimlik No',
                'dogum' => 'Doğum T.',
                'telefon' => 'Telefon',
                'ikamet' => 'İkamet',
                'durum' => 'Durum',
                'yedek_sira' => 'Yedek Sıra',
                'katilim' => 'Katılım',
                'onay' => 'Onay Tarihi',
                'iptal' => 'İptal Tarihi',
                'iptal_gerekce' => 'İptal Gerekçesi',
                'kaydeden' => 'Kaydeden',
                'basvuru_tarihi' => 'Başvuru Tarihi',
                'islemler' => 'İşlemler',
            ],
            'defaultVisible' => [
                'etkinlik_no', 'etkinlik', 'merkez', 'katilimci', 'kimlik', 'telefon', 'durum', 'yedek_sira', 'basvuru_tarihi', 'islemler',
            ],
            'sortable' => [
                'etkinlik_no', 'etkinlik', 'merkez', 'basvuran', 'katilimci', 'veli', 'kimlik', 'dogum', 'telefon', 'ikamet',
                'durum', 'yedek_sira', 'onay', 'iptal', 'iptal_gerekce', 'kaydeden', 'basvuru_tarihi',
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function basvuruGenelOzet(): array
    {
        $durumIdler = EtkinlikBasvuruDurum::query()->pluck('id', 'kod');
        $base = EtkinlikBasvuru::query();
        $user = request()->user();

        if ($user && $user->sadeceYetkiliMerkezleriGorurEtkinlik()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === []) {
                $base->whereRaw('0 = 1');
            } else {
                $base->whereHas('etkinlik', fn (Builder $k) => $k->whereIn('merkez_id', $merkezIds));
            }
        }

        if ($user && $user->sadeceKendiKurumlariniGorurEtkinlik()) {
            $base->whereHas('etkinlik', fn (Builder $k) => $k->kullaniciKurumKapsami($user));
        }

        return [
            'toplam' => (clone $base)->count(),
            'onay_bekliyor' => $durumIdler->has('onay_bekliyor')
                ? (clone $base)->where('durum_id', $durumIdler['onay_bekliyor'])->count()
                : 0,
            'kesin_kayit' => $durumIdler->has('kesin_kayit')
                ? (clone $base)->where('durum_id', $durumIdler['kesin_kayit'])->count()
                : 0,
            'iptal' => $durumIdler->has('iptal')
                ? (clone $base)->where('durum_id', $durumIdler['iptal'])->count()
                : 0,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Etkinlik>
     */
    private function basvuruIcinEtkinlikler(?User $user)
    {
        $query = Etkinlik::query()
            ->with(['merkez', 'etkinlikTipi'])
            ->whereIn('durum', [EtkinlikDurum::Hazirlik, EtkinlikDurum::Aktif])
            ->orderByDesc('etkinlik_no');

        if ($user && $user->sadeceAtananEtkinlikleriGorur()) {
            $query->whereHas('sorumlular', fn (Builder $u) => $u->where('users.id', $user->id));
        }

        if ($user && $user->sadeceYetkiliMerkezleriGorurEtkinlik()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('merkez_id', $merkezIds);
            }
        }

        if ($user && $user->sadeceKendiKurumlariniGorurEtkinlik()) {
            $query->kullaniciKurumKapsami($user);
        }

        return $query->get();
    }

    private function assertEtkinlikBasvuruIcinUygun(?User $user, Etkinlik $etkinlik): void
    {
        if (! in_array($etkinlik->durum, [EtkinlikDurum::Hazirlik, EtkinlikDurum::Aktif], true)) {
            abort(422, 'Bu etkinliğe başvuru alınamaz.');
        }

        if ($user && $user->sadeceAtananEtkinlikleriGorur()) {
            $atanmis = $etkinlik->sorumlular()->where('users.id', $user->id)->exists();
            if (! $atanmis) {
                abort(403);
            }
        }

        if ($user && $user->sadeceYetkiliMerkezleriGorurEtkinlik()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === [] || ! in_array((int) $etkinlik->merkez_id, $merkezIds, true)) {
                abort(403);
            }
        }

        if ($user && $user->sadeceKendiKurumlariniGorurEtkinlik()) {
            $allowed = Etkinlik::query()
                ->whereKey($etkinlik->id)
                ->kullaniciKurumKapsami($user)
                ->exists();
            if (! $allowed) {
                abort(403);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function kisiUpsert(array $data): Kisi
    {
        $tc = trim((string) ($data['tc_kimlik_no'] ?? ''));
        $kisi = Kisi::query()->where('tc_kimlik_no', $tc)->first();

        $payload = [
            'ad' => trim((string) $data['ad']),
            'soyad' => trim((string) $data['soyad']),
            'tc_kimlik_no' => $tc,
            'dogum_tarihi' => $data['dogum_tarihi'] ?? null,
            'cinsiyet' => $data['cinsiyet'] ?? null,
            'telefon' => $data['telefon'] ?? null,
            'email' => $data['email'] ?? null,
            'il' => $data['il'] ?? null,
            'ilce' => $data['ilce'] ?? null,
            'adres' => $data['adres'] ?? null,
            'aktif' => true,
        ];

        if ($kisi) {
            $kisi->fill(array_filter(
                $payload,
                fn ($value, $key) => $key === 'aktif' || ($value !== null && $value !== ''),
                ARRAY_FILTER_USE_BOTH
            ))->save();

            return $kisi->fresh();
        }

        return Kisi::query()->create($payload);
    }

    private function yasHesapla(?string $dogumTarihi): ?int
    {
        if (! $dogumTarihi) {
            return null;
        }

        try {
            return Carbon::parse($dogumTarihi)->age;
        } catch (\Throwable) {
            return null;
        }
    }
}
