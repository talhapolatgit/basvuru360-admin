<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Enums\KursDurum;
use App\Models\Alan;
use App\Models\BasariDurum;
use App\Models\BasvuruDurum;
use App\Models\Brans;
use App\Models\IptalGerekce;
use App\Models\Kisi;
use App\Models\Kurs;
use App\Models\KursBasvuru;
use App\Models\KursBasvuruEvrak;
use App\Models\Kurum;
use App\Models\User;
use App\Models\Merkez;
use App\Services\BasvuruKosulDogrulayici;
use App\Services\KursAyarServisi;
use App\Services\KursYedekListeServisi;
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

class BasvuruController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('basvuru_durum')) {
            $request->merge(['basvuru_durum' => 'tumu']);
        }

        if (! $request->filled('kurs_durum')) {
            $request->merge(['kurs_durum' => 'tumu']);
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
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('basvurular._results', $viewData);
        }

        return view('basvurular.index', $viewData + [
            'alanlar' => Alan::where('aktif', true)->orderBy('ad')->get(),
            'branslar' => Brans::where('aktif', true)->orderBy('ad')->get(),
            'merkezler' => Merkez::query()->kullaniciKapsami($request->user())->where('aktif', true)->orderBy('ad')->get(),
            'kurumlar' => Kurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
            'ogretmenler' => User::query()->egitmen()->where('aktif', true)->orderBy('ad')->orderBy('soyad')->get(),
            'basvuruDurumlari' => BasvuruDurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
            'basariDurumlari' => BasariDurum::query()->where('aktif', true)->orderBy('sira')->get(),
            'iptalGerekceleri' => IptalGerekce::query()->where('aktif', true)->orderBy('sira')->get(),
            'kursDurumlari' => KursDurum::cases(),
            'ozet' => $this->basvuruGenelOzet(),
            'smsBasvuruOnayAyar' => app(KursAyarServisi::class)->smsBasvuruOnay()->value,
            'smsBasvuruIptalAyar' => app(KursAyarServisi::class)->smsBasvuruIptal()->value,
            'smsBasvuruYedekAyar' => app(KursAyarServisi::class)->smsBasvuruYedek()->value,
            'epostaBasvuruOnayAyar' => app(KursAyarServisi::class)->epostaBasvuruOnay()->value,
            'epostaBasvuruIptalAyar' => app(KursAyarServisi::class)->epostaBasvuruIptal()->value,
            'epostaBasvuruYedekAyar' => app(KursAyarServisi::class)->epostaBasvuruYedek()->value,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('basvuru_durum')) {
            $request->merge(['basvuru_durum' => 'tumu']);
        }

        if (! $request->filled('kurs_durum')) {
            $request->merge(['kurs_durum' => 'tumu']);
        }

        [$basvurular] = $this->searchBasvurular($request, paginate: false);

        $filename = 'kurs-basvurulari-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($basvurular) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Kurs No', 'Branş', 'Merkez',
                'Başvuran', 'Katılımcı', 'Veli', 'Kimlik No', 'Doğum T.', 'Telefon', 'İkamet',
                'Durum', 'Yedek Sıra', 'Başarı', 'Kursa Başlama', 'Onay Tarihi', 'İptal Tarihi', 'İptal Gerekçesi', 'Kaydeden', 'Başvuru Tarihi',
            ], ';');

            $basvurular->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $basvuru) {
                    fputcsv($handle, [
                        $basvuru->kurs?->kurs_no ?? '',
                        $basvuru->kurs?->brans?->ad ?? '',
                        $basvuru->kurs?->merkez?->ad ?? '',
                        $basvuru->basvuran?->tam_adi ?? ($basvuru->kisi?->tam_adi ?? ''),
                        $basvuru->kisi?->tam_adi ?? '',
                        $basvuru->veli?->tam_adi ?? '',
                        $basvuru->kisi?->tc_kimlik_no ?? '',
                        $basvuru->kisi?->dogum_tarihi?->format('d.m.Y') ?? '',
                        $basvuru->kisi?->telefon ?? ($basvuru->basvuran?->telefon ?? ''),
                        $basvuru->kisi?->ilce ?? ($basvuru->kisi?->il ?? ''),
                        $basvuru->durum?->ad ?? '',
                        $basvuru->durum?->kod === 'yedek' ? ($basvuru->yedek_sira ?? '') : '',
                        $basvuru->basariDurum?->ad ?? '',
                        $basvuru->kursa_baslama_tarihi?->format('d.m.Y') ?? '',
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

    public function create(Request $request): View
    {
        $kurslar = $this->basvuruIcinKurslar($request->user());
        $seciliId = old('kurs_id', $request->input('kurs_id'));

        return view('basvurular.create', [
            'kurslar' => $kurslar,
            'kursOptions' => $kurslar->map(fn (Kurs $kurs) => [
                'value' => $kurs->id,
                'label' => '#'.$kurs->kurs_no.' — '.($kurs->brans?->ad ?? 'Kurs').' ('.($kurs->merkez?->ad ?? '—').')',
            ])->all(),
            'seciliKursId' => $seciliId,
        ]);
    }

    public function kursOzet(Request $request, Kurs $kurs): JsonResponse
    {
        $this->assertKursBasvuruIcinUygun($request->user(), $kurs);
        $kurs->load(['brans', 'alan', 'merkez', 'evrakTipleri' => fn ($q) => $q->where('aktif', true)->orderBy('ad')]);

        return response()->json([
            'id' => $kurs->id,
            'kurs_no' => $kurs->kurs_no,
            'brans' => $kurs->brans?->ad,
            'alan' => $kurs->alan?->ad,
            'merkez' => $kurs->merkez?->ad,
            'durum' => $kurs->durum?->label(),
            'durum_kod' => $kurs->durum?->value,
            'baslama' => $kurs->kurs_baslama_tarihi?->format('d.m.Y'),
            'bitis' => $kurs->kurs_bitis_tarihi?->format('d.m.Y'),
            'basvuru_baslama' => $kurs->basvuru_baslama_tarihi?->format('d.m.Y H:i'),
            'basvuru_bitis' => $kurs->basvuru_bitis_tarihi?->format('d.m.Y H:i'),
            'kontenjan' => $kurs->kontenjan,
            'yedek_kontenjan' => $kurs->yedek_kontenjan,
            'kayit_sayisi' => $kurs->kayit_sayisi,
            'basvuru_sayisi' => $kurs->basvuru_sayisi,
            'evrak_zorunlu' => (bool) $kurs->evrak_zorunlu,
            'evrak_tipleri' => $kurs->evrakTipleri->map(fn ($tip) => [
                'id' => $tip->id,
                'ad' => $tip->ad,
                'aciklama' => $tip->aciklama,
            ])->values(),
            'doluluk' => app(KursYedekListeServisi::class)->dolulukOzeti($kurs),
            'kosullar' => [
                'minimum_yas' => $kurs->minimum_yas,
                'maksimum_yas' => $kurs->maksimum_yas,
                'cinsiyet_sarti' => $kurs->cinsiyet_sarti?->value,
                'cinsiyet_sarti_label' => $kurs->cinsiyet_sarti?->label(),
                'ikamet_sarti' => $kurs->ikamet_sarti?->value,
                'ikamet_disi_kontenjan' => $kurs->ikamet_disi_kontenjan,
                'hizmet_ili' => $kurs->merkez?->il ?: config('basvuru.hizmet_ili'),
                'hizmet_ilcesi' => $kurs->merkez?->ilce ?: config('basvuru.hizmet_ilcesi'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $kurs = Kurs::query()->findOrFail($request->integer('kurs_id'));
        $this->assertKursBasvuruIcinUygun($request->user(), $kurs);
        $kurs->load([
            'merkez',
            'evrakTipleri' => fn ($q) => $q->where('aktif', true),
        ]);

        $evrakTipiIds = $kurs->evrak_zorunlu
            ? $kurs->evrakTipleri->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $rules = [
            'kurs_id' => ['required', 'integer', 'exists:kurslar,id'],
            'tc_kimlik_no' => ['required', 'digits:11'],
            'dogum_tarihi' => ['required', 'date'],
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'cinsiyet' => [
                $kurs->cinsiyet_sarti ? 'required' : 'nullable',
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
            'kurs_id.required' => 'Kurs seçilmelidir.',
            'tc_kimlik_no.required' => 'TC Kimlik No zorunludur.',
            'tc_kimlik_no.digits' => 'TC Kimlik No 11 haneli olmalıdır.',
            'dogum_tarihi.required' => 'Doğum tarihi zorunludur.',
            'ad.required' => 'Ad zorunludur.',
            'soyad.required' => 'Soyad zorunludur.',
            'cinsiyet.required' => 'Bu kurs için cinsiyet seçimi zorunludur.',
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

        app(BasvuruKosulDogrulayici::class)->dogrula($kurs, $validated, $yas);
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

        $basvuru = DB::transaction(function () use ($request, $validated, $kurs, $kucuk, $veliDolu, $evrakTipiIds) {
            /** @var Kurs $kurs */
            $kurs = Kurs::query()->whereKey($kurs->id)->lockForUpdate()->firstOrFail();

            $yerlesim = app(KursYedekListeServisi::class)->yeniBasvuruDurumuBelirle($kurs);
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

            $iptalDurumId = BasvuruDurum::idByKod('iptal');
            $mevcutAktif = KursBasvuru::query()
                ->where('kisi_id', $katilimci->id)
                ->where('kurs_id', $kurs->id)
                ->whereNull('deleted_at')
                ->when($iptalDurumId, fn ($q) => $q->where('durum_id', '!=', $iptalDurumId))
                ->lockForUpdate()
                ->exists();

            if ($mevcutAktif) {
                throw ValidationException::withMessages([
                    'tc_kimlik_no' => 'Bu kişi için seçilen kursa ait aktif bir başvuru zaten var.',
                ]);
            }

            $basvuranId = $kucuk && $veli ? $veli->id : $katilimci->id;
            $veliId = $veli?->id;

            $basvuru = KursBasvuru::query()->create([
                'kisi_id' => $katilimci->id,
                'basvuran_id' => $basvuranId,
                'veli_id' => $veliId,
                'kurs_id' => $kurs->id,
                'durum_id' => $durumId,
                'yedek_sira' => $yedekSira,
                'olusturan_id' => $request->user()?->id,
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
                    'olusturan_id' => $request->user()?->id,
                ]);
            }

            $kurs->update([
                'basvuru_sayisi' => $kurs->basvurular()->count(),
            ]);

            LogKaydedici::kaydet(
                islem: 'basvuru.olusturuldu',
                kurs: $kurs,
                aciklama: $katilimci->tam_adi.' için yeni kurs başvurusu oluşturuldu'
                    .($durumKod === 'yedek' ? ' (yedek sıra: '.$yedekSira.')' : '').'.',
                konu: $basvuru,
                yeni: [
                    'kisi_id' => $katilimci->id,
                    'veli_id' => $veliId,
                    'durum' => $durumKod,
                    'yedek_sira' => $yedekSira,
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
        $redirect = route('kurslar.basvurular.show', [$kurs, $basvuru]);

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

    /**
     * @return \Illuminate\Support\Collection<int, Kurs>
     */
    /**
     * @return \Illuminate\Support\Collection<int, Kurs>
     */
    private function basvuruIcinKurslar(?User $user)
    {
        return Kurs::query()
            ->with(['brans', 'merkez', 'alan'])
            ->whereIn('durum', [KursDurum::Hazirlik, KursDurum::Aktif])
            ->kullaniciKapsami($user)
            ->orderByDesc('kurs_no')
            ->get();
    }

    private function assertKursBasvuruIcinUygun(?User $user, Kurs $kurs): void
    {
        if (! in_array($kurs->durum, [KursDurum::Hazirlik, KursDurum::Aktif], true)) {
            abort(422, 'Bu kursa başvuru alınamaz.');
        }

        if ($user) {
            $allowed = Kurs::query()
                ->whereKey($kurs->id)
                ->kullaniciKapsami($user)
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

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function searchBasvurular(Request $request, bool $paginate = true): array
    {
        $query = KursBasvuru::query()
            ->with([
                'kisi', 'basvuran', 'veli', 'durum', 'basariDurum', 'iptalGerekce', 'olusturan',
                'kurs.merkez', 'kurs.brans', 'kurs.alan',
                'evraklar' => fn ($q) => $q->with(['evrakTipi', 'olusturan'])->orderBy('created_at'),
            ]);

        $user = $request->user();
        if ($user) {
            $query->whereHas('kurs', fn (Builder $k) => $k->kullaniciKapsami($user));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            if ($q !== '') {
                $query->where(function (Builder $outer) use ($q) {
                    $outer->whereHas('kisi', fn (Builder $k) => $this->applyKisiSearch($k, $q))
                        ->orWhereHas('basvuran', fn (Builder $k) => $this->applyKisiSearch($k, $q));
                });
            }
        }

        if ($request->filled('kurs_no')) {
            $value = (string) $request->string('kurs_no');
            $mode = (string) $request->input('kurs_no_mode', 'exact');

            $query->whereHas('kurs', function (Builder $k) use ($value, $mode) {
                match ($mode) {
                    'starts' => $k->where('kurs_no', 'like', $value.'%'),
                    'ends' => $k->where('kurs_no', 'like', '%'.$value),
                    'exact' => $k->where('kurs_no', $value),
                    default => $k->where('kurs_no', 'like', '%'.$value.'%'),
                };
            });
        }

        if ($request->filled('alan_id')) {
            $alanId = $request->integer('alan_id');
            $query->whereHas('kurs', fn (Builder $k) => $k->where('alan_id', $alanId));
        }

        if ($request->filled('brans_id')) {
            $bransId = $request->integer('brans_id');
            $query->whereHas('kurs', fn (Builder $k) => $k->where('brans_id', $bransId));
        }

        if ($request->filled('merkez_id')) {
            $merkezId = $request->integer('merkez_id');
            $query->whereHas('kurs', fn (Builder $k) => $k->where('merkez_id', $merkezId));
        }

        if ($request->filled('kurum_id')) {
            $kurumId = $request->integer('kurum_id');
            $query->whereHas('kurs.kurumlar', fn (Builder $k) => $k->where('kurumlar.id', $kurumId));
        }

        if ($request->filled('ogretmen_id')) {
            $ogretmenId = $request->integer('ogretmen_id');
            $query->whereHas('kurs', fn (Builder $k) => $k->whereHas(
                'ogretmenler',
                fn (Builder $o) => $o->where('users.id', $ogretmenId)
            ));
        }

        $basvuruDurumlari = BasvuruDurum::query()->where('aktif', true)->orderBy('sira')->get();
        $basvuruDurum = (string) $request->input('basvuru_durum', 'tumu');
        $seciliDurum = $basvuruDurumlari->firstWhere('kod', $basvuruDurum);
        if ($seciliDurum) {
            $query->where('durum_id', $seciliDurum->id);
        } else {
            $basvuruDurum = 'tumu';
        }

        if ($request->filled('basari_durumu_id')) {
            $query->where('basari_durumu_id', $request->integer('basari_durumu_id'));
        }

        $kursDurum = (string) $request->input('kurs_durum', 'tumu');
        if ($kursDurum !== '' && $kursDurum !== 'tumu' && KursDurum::tryFrom($kursDurum)) {
            $query->whereHas('kurs', fn (Builder $k) => $k->where('durum', $kursDurum));
        } else {
            $kursDurum = 'tumu';
        }

        if ($request->filled('basvuru_ilk')) {
            $query->whereDate('kurs_basvurulari.created_at', '>=', $request->string('basvuru_ilk'));
        }

        if ($request->filled('basvuru_son')) {
            $query->whereDate('kurs_basvurulari.created_at', '<=', $request->string('basvuru_son'));
        }

        if ($request->filled('kursa_baslama_ilk')) {
            $query->whereDate('kurs_basvurulari.kursa_baslama_tarihi', '>=', $request->string('kursa_baslama_ilk'));
        }

        if ($request->filled('kursa_baslama_son')) {
            $query->whereDate('kurs_basvurulari.kursa_baslama_tarihi', '<=', $request->string('kursa_baslama_son'));
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'kurs_no' => 'kurslar.kurs_no',
            'brans' => 'branslar.ad',
            'merkez' => 'merkezler.ad',
            'basvuran' => 'basvuran.ad',
            'katilimci' => 'kisi.ad',
            'veli' => 'veli.ad',
            'kimlik' => 'kisi.tc_kimlik_no',
            'dogum' => 'kisi.dogum_tarihi',
            'telefon' => 'kisi.telefon',
            'ikamet' => 'kisi.ilce',
            'durum' => 'basvuru_durumlari.ad',
            'yedek_sira' => 'kurs_basvurulari.yedek_sira',
            'basari' => 'basari_durumlari.ad',
            'onay' => 'kurs_basvurulari.onay_tarihi',
            'iptal' => 'kurs_basvurulari.iptal_tarihi',
            'iptal_gerekce' => 'iptal_gerekceleri.ad',
            'kaydeden' => 'olusturan.ad',
            'kursa_baslama' => 'kurs_basvurulari.kursa_baslama_tarihi',
            'basvuru_tarihi' => 'kurs_basvurulari.created_at',
        ];

        if ($sort === '' && $basvuruDurum === 'yedek') {
            $sort = 'yedek_sira';
            $direction = 'asc';
        }

        if (isset($sortable[$sort])) {
            $query->reorder();

            if (in_array($sort, ['kurs_no', 'brans', 'merkez'], true)) {
                $query->leftJoin('kurslar', 'kurslar.id', '=', 'kurs_basvurulari.kurs_id')
                    ->leftJoin('branslar', 'branslar.id', '=', 'kurslar.brans_id')
                    ->leftJoin('merkezler', 'merkezler.id', '=', 'kurslar.merkez_id')
                    ->orderBy($sortable[$sort], $direction)
                    ->select('kurs_basvurulari.*');
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
                $query->leftJoin('kisiler as '.$alias, $alias.'.id', '=', 'kurs_basvurulari.'.$fk)
                    ->orderBy($sortable[$sort], $direction)
                    ->select('kurs_basvurulari.*');
            } elseif ($sort === 'durum') {
                $query->leftJoin('basvuru_durumlari', 'basvuru_durumlari.id', '=', 'kurs_basvurulari.durum_id')
                    ->orderBy('basvuru_durumlari.ad', $direction)
                    ->select('kurs_basvurulari.*');
            } elseif ($sort === 'basari') {
                $query->leftJoin('basari_durumlari', 'basari_durumlari.id', '=', 'kurs_basvurulari.basari_durumu_id')
                    ->orderBy('basari_durumlari.ad', $direction)
                    ->select('kurs_basvurulari.*');
            } elseif ($sort === 'iptal_gerekce') {
                $query->leftJoin('iptal_gerekceleri', 'iptal_gerekceleri.id', '=', 'kurs_basvurulari.iptal_gerekce_id')
                    ->orderBy('iptal_gerekceleri.ad', $direction)
                    ->select('kurs_basvurulari.*');
            } elseif ($sort === 'kaydeden') {
                $query->leftJoin('users as olusturan', 'olusturan.id', '=', 'kurs_basvurulari.olusturan_id')
                    ->orderBy('olusturan.ad', $direction)
                    ->select('kurs_basvurulari.*');
            } else {
                $query->orderBy($sortable[$sort], $direction);
            }
        } else {
            $query->latest('kurs_basvurulari.created_at');
            $sort = '';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only([
            'q', 'kurs_no', 'kurs_no_mode', 'alan_id', 'brans_id', 'merkez_id', 'kurum_id', 'ogretmen_id',
            'basvuru_durum', 'basari_durumu_id', 'kurs_durum',
            'basvuru_ilk', 'basvuru_son', 'kursa_baslama_ilk', 'kursa_baslama_son',
            'per_page', 'sort', 'direction',
        ]);
        $filters['basvuru_durum'] = $basvuruDurum;
        $filters['kurs_durum'] = $kursDurum;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    /**
     * @param  Builder<\App\Models\Kisi>  $query
     */
    private function applyKisiSearch(Builder $query, string $q): void
    {
        $query->where(function (Builder $qq) use ($q) {
            $qq->where('ad', 'like', "%{$q}%")
                ->orWhere('soyad', 'like', "%{$q}%")
                ->orWhere('tc_kimlik_no', 'like', "%{$q}%")
                ->orWhere('telefon', 'like', "%{$q}%")
                ->orWhereRaw("CONCAT(ad, ' ', soyad) LIKE ?", ["%{$q}%"]);
        });
    }

    /**
     * @return array{all: array<string, string>, defaultVisible: list<string>, sortable: list<string>}
     */
    private function basvurularTableColumns(): array
    {
        return [
            'all' => [
                'kurs_no' => 'Kurs No',
                'brans' => 'Branş',
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
                'basari' => 'Başarı',
                'kursa_baslama' => 'Kursa Başlama',
                'onay' => 'Onay Tarihi',
                'iptal' => 'İptal Tarihi',
                'iptal_gerekce' => 'İptal Gerekçesi',
                'kaydeden' => 'Kaydeden',
                'basvuru_tarihi' => 'Başvuru Tarihi',
                'islemler' => 'İşlemler',
            ],
            'defaultVisible' => [
                'kurs_no', 'brans', 'merkez', 'katilimci', 'kimlik', 'telefon', 'durum', 'yedek_sira', 'basari', 'basvuru_tarihi', 'islemler',
            ],
            'sortable' => [
                'kurs_no', 'brans', 'merkez', 'basvuran', 'katilimci', 'veli', 'kimlik', 'dogum', 'telefon', 'ikamet',
                'durum', 'yedek_sira', 'basari', 'kursa_baslama', 'onay', 'iptal', 'iptal_gerekce', 'kaydeden', 'basvuru_tarihi',
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function basvuruGenelOzet(): array
    {
        $durumIdler = BasvuruDurum::query()->pluck('id', 'kod');
        $base = KursBasvuru::query();
        $user = request()->user();

        if ($user) {
            $base->whereHas('kurs', fn (Builder $k) => $k->kullaniciKapsami($user));
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
}
