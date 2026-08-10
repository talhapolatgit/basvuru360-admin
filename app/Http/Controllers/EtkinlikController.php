<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Enums\EtkinlikDurum;
use App\Enums\IkametSarti;
use App\Enums\SmsGonderimSecenegi;
use App\Models\EtkinlikBasvuruDurum;
use App\Models\Etkinlik;
use App\Models\EtkinlikBasvuru;
use App\Models\EtkinlikEpostaGonderim;
use App\Models\EtkinlikSmsGonderim;
use App\Models\EtkinlikTipi;
use App\Models\EpostaLog;
use App\Models\EtkinlikBasvuruEvrak;
use App\Models\EvrakTipi;
use App\Models\IptalGerekce;
use App\Models\Kisi;
use App\Models\Kurum;
use App\Models\Merkez;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Email\EmailSender;
use App\Services\EtkinlikAyarServisi;
use App\Services\EtkinlikYedekListeServisi;
use App\Services\LogKaydedici;
use App\Services\NumaratorServisi;
use App\Services\Sms\PhoneNormalizer;
use App\Services\Sms\SmsSender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EtkinlikController extends Controller implements HasMiddleware
{
    /**
     * @return list<Middleware|string>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('etkinlik.kapsam', except: ['index', 'create', 'store', 'export']),
        ];
    }

    public function index(Request $request): View
    {
        [$etkinlikler, $sort, $direction, $filters] = $this->searchEtkinlikler($request);

        $viewData = [
            'etkinlikler' => $etkinlikler,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
            'defaultVisible' => ['no', 'ad', 'merkez', 'tip', 'baslangic', 'bitis', 'basvuru', 'kayit', 'durum', 'basvuru_durumu', 'islemler'],
            'allColumns' => [
                'no' => 'No',
                'ad' => 'Ad',
                'merkez' => 'Merkez',
                'kurum' => 'Kurum',
                'tip' => 'Tip',
                'baslangic' => 'Başlangıç',
                'bitis' => 'Bitiş',
                'basvuru' => 'Başvuru',
                'kayit' => 'Kayıt',
                'iptal' => 'İptal',
                'kontenjan' => 'Kontenjan',
                'yedek' => 'Yedek',
                'durum' => 'Durum',
                'basvuru_durumu' => 'Başvuru Durumu',
                'tarih' => 'Tarih',
                'islemler' => 'İşlemler',
            ],
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('etkinlikler._results', $viewData);
        }

        return view('etkinlikler.index', $viewData + [
            'merkezler' => Merkez::query()->kullaniciKapsami($request->user())->where('aktif', true)->orderBy('ad')->get(),
            'kurumlar' => Kurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
            'etkinlikTipleri' => EtkinlikTipi::where('aktif', true)->orderBy('ad')->get(),
            'durumlar' => EtkinlikDurum::cases(),
            'basvuruDurumlari' => [
                'acik' => 'Açık',
                'yakinda' => 'Yakında',
                'kapandi' => 'Kapandı',
                'kapali' => 'Kapalı',
            ],
            'ozet' => $this->etkinlikOzet(),
        ]);
    }

    public function create(): View
    {
        return view('etkinlikler.create', $this->formLookups());
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validateEtkinlikRequest($request);
        $etkinlik = $this->persistEtkinlik($request, $validated);

        LogKaydedici::kaydet(
            islem: 'etkinlik.olusturuldu',
            aciklama: 'Etkinlik #'.$etkinlik->etkinlik_no.' oluşturuldu.',
            konu: $etkinlik,
            yeni: $this->etkinlikSnapshot($etkinlik),
        );

        $message = 'Etkinlik #'.$etkinlik->etkinlik_no.' başarıyla oluşturuldu.';

        if ($request->expectsJson() || $request->ajax()) {
            session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'redirect' => route('etkinlikler.show', $etkinlik),
            ]);
        }

        return redirect()
            ->route('etkinlikler.show', $etkinlik)
            ->with('success', $message);
    }

    public function show(Request $request, Etkinlik $etkinlik): View
    {
        $etkinlik->load(['merkez', 'etkinlikTipi', 'sorumlular', 'evrakTipleri', 'kurumlar', 'olusturan', 'guncelleyen']);

        $basvuruDurumlari = EtkinlikBasvuruDurum::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->orderBy('ad')
            ->get();

        // Sistem durum ID'leri aktif bayrağından bağımsız alınır (pasif olsa da yoklama/özet bozulmasın).
        $onayBekliyorId = EtkinlikBasvuruDurum::idByKod('onay_bekliyor');
        $kesinKayitId = EtkinlikBasvuruDurum::idByKod('kesin_kayit');
        $yedekId = EtkinlikBasvuruDurum::idByKod('yedek');
        $iptalId = EtkinlikBasvuruDurum::idByKod('iptal');

        $basvuruOzet = [
            'aktif' => $etkinlik->basvurular()->whereIn('durum_id', array_filter([$onayBekliyorId, $kesinKayitId, $yedekId]))->count(),
            'kayit' => $kesinKayitId ? $etkinlik->basvurular()->where('durum_id', $kesinKayitId)->count() : 0,
            'beklemede' => $onayBekliyorId ? $etkinlik->basvurular()->where('durum_id', $onayBekliyorId)->count() : 0,
            'iptal' => $iptalId ? $etkinlik->basvurular()->where('durum_id', $iptalId)->count() : 0,
            'toplam' => $etkinlik->basvurular()->count(),
        ];

        $basvuruDurum = (string) $request->input('basvuru_durum', 'tumu');
        if ($basvuruDurum !== 'tumu' && ! $basvuruDurumlari->contains('kod', $basvuruDurum)) {
            $basvuruDurum = 'tumu';
        }
        $activeTab = (string) $request->input('tab', 'detay');
        $activeMesajKanal = (string) $request->input('kanal', 'sms');
        if (! in_array($activeMesajKanal, ['sms', 'eposta'], true)) {
            $activeMesajKanal = 'sms';
        }

        $basvuruColumns = $this->basvuruTableColumns();

        $yoklamaListesi = $this->yoklamaListesiForEtkinlik($etkinlik);

        return view('etkinlikler.show', [
            'etkinlik' => $etkinlik,
            'basvuruOzet' => $basvuruOzet,
            'basvuruDurum' => $basvuruDurum,
            'basvuruDurumlari' => $basvuruDurumlari,
            'basvuruColumns' => $basvuruColumns['all'],
            'basvuruDefaultVisible' => $basvuruColumns['defaultVisible'],
            'iptalGerekceleri' => IptalGerekce::query()->where('aktif', true)->orderBy('sira')->get(),
            'activeTab' => $activeTab,
            'activeMesajKanal' => $activeMesajKanal,
            'kosullarOzeti' => $this->etkinlikKosullarOzeti($etkinlik),
            'yayinlanabilir' => $etkinlik->basvuruDonemindeMi(),
            'yoklamaListesi' => $yoklamaListesi,
            'kullanicilar' => User::query()->where('aktif', true)->orderBy('ad')->orderBy('soyad')->get(),
            'smsGonderimleri' => $etkinlik->smsGonderimleri()->with('gonderen')->latest()->limit(30)->get(),
            'epostaGonderimleri' => $etkinlik->epostaGonderimleri()->with('gonderen')->latest()->limit(30)->get(),
            'smsBasvuruOnayAyar' => app(EtkinlikAyarServisi::class)->smsBasvuruOnay()->value,
            'smsBasvuruIptalAyar' => app(EtkinlikAyarServisi::class)->smsBasvuruIptal()->value,
            'smsBasvuruYedekAyar' => app(EtkinlikAyarServisi::class)->smsBasvuruYedek()->value,
            'epostaBasvuruOnayAyar' => app(EtkinlikAyarServisi::class)->epostaBasvuruOnay()->value,
            'epostaBasvuruIptalAyar' => app(EtkinlikAyarServisi::class)->epostaBasvuruIptal()->value,
            'epostaBasvuruYedekAyar' => app(EtkinlikAyarServisi::class)->epostaBasvuruYedek()->value,
        ]);
    }

    public function edit(Etkinlik $etkinlik): View
    {
        $etkinlik->load(['evrakTipleri', 'kurumlar']);

        return view('etkinlikler.edit', $this->formLookups() + [
            'etkinlik' => $etkinlik,
        ]);
    }

    public function update(Request $request, Etkinlik $etkinlik): RedirectResponse|JsonResponse
    {
        $validated = $this->validateEtkinlikRequest($request, $etkinlik);
        $onceki = $this->etkinlikSnapshot($etkinlik);
        $etkinlik = $this->persistEtkinlik($request, $validated, $etkinlik);
        $yeni = $this->etkinlikSnapshot($etkinlik);

        if ($onceki !== $yeni) {
            LogKaydedici::kaydet(
                islem: 'etkinlik.guncellendi',
                aciklama: 'Etkinlik #'.$etkinlik->etkinlik_no.' bilgileri güncellendi.',
                konu: $etkinlik,
                eski: $onceki,
                yeni: $yeni,
            );
        }

        $message = 'Etkinlik #'.$etkinlik->etkinlik_no.' başarıyla güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'redirect' => route('etkinlikler.show', $etkinlik),
            ]);
        }

        return redirect()
            ->route('etkinlikler.show', $etkinlik)
            ->with('success', $message);
    }

    public function toggleYayin(Request $request, Etkinlik $etkinlik): RedirectResponse|JsonResponse
    {
        $yayinaAliniyor = ! $etkinlik->onlinede_yayinlansin;

        $etkinlik->update([
            'onlinede_yayinlansin' => $yayinaAliniyor,
            'guncelleyen_id' => $request->user()?->id,
        ]);

        $mesaj = $yayinaAliniyor
            ? 'Etkinlik #'.$etkinlik->etkinlik_no.' yayına alındı.'
            : 'Etkinlik #'.$etkinlik->etkinlik_no.' yayından kaldırıldı.';

        LogKaydedici::kaydet(
            islem: $yayinaAliniyor ? 'etkinlik.yayina_alindi' : 'etkinlik.yayindan_kaldirildi',
            aciklama: $mesaj,
            konu: $etkinlik,
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $mesaj,
                'onlinede_yayinlansin' => (bool) $etkinlik->onlinede_yayinlansin,
                'yayinlanabilir' => $etkinlik->basvuruDonemindeMi(),
                'basvuru_durumu_kod' => $etkinlik->basvuruDurumuKod(),
                'basvuru_durumu_label' => $etkinlik->basvuruDurumuLabel(),
                'basvuru_durumu_class' => $etkinlik->basvuruDurumuStatusClass(),
            ]);
        }

        return redirect()
            ->route('etkinlikler.show', $etkinlik)
            ->with('success', $mesaj);
    }

    public function assignSorumlu(Request $request, Etkinlik $etkinlik): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'sorumlu_ids' => ['nullable', 'array'],
            'sorumlu_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['sorumlu_ids'] ?? [])));

        $oncekiSorumlular = $etkinlik->sorumlular->map(fn (User $u) => $u->tam_adi)->values()->all();

        $etkinlik->syncSorumlular($ids);
        $etkinlik->load('sorumlular');
        $etkinlik->update(['guncelleyen_id' => $request->user()?->id]);

        $mesaj = $ids !== []
            ? 'Etkinlik #'.$etkinlik->etkinlik_no.' için sorumlu ataması güncellendi.'
            : 'Etkinlik #'.$etkinlik->etkinlik_no.' sorumlu ataması kaldırıldı.';

        $sorumlularPayload = $etkinlik->sorumlular
            ->sortBy('id')
            ->values()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'tam_adi' => $user->tam_adi,
            ])
            ->all();

        LogKaydedici::kaydet(
            islem: $ids !== [] ? 'etkinlik.sorumlu_atandi' : 'etkinlik.sorumlu_kaldirildi',
            aciklama: $mesaj,
            konu: $etkinlik,
            eski: ['sorumlular' => $oncekiSorumlular],
            yeni: ['sorumlular' => array_column($sorumlularPayload, 'tam_adi')],
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $mesaj,
                'sorumlular' => $sorumlularPayload,
            ]);
        }

        return redirect()
            ->route('etkinlikler.show', $etkinlik)
            ->with('success', $mesaj);
    }

    public function basvurular(Request $request, Etkinlik $etkinlik): JsonResponse
    {
        [$basvurular, $basvuruDurum, $sort, $direction] = $this->searchEtkinlikBasvurular($request, $etkinlik);
        $columns = $this->basvuruTableColumns();

        return response()->json([
            'html' => view('etkinlikler._basvurular_list', [
                'etkinlik' => $etkinlik,
                'basvurular' => $basvurular,
                'basvuruColumns' => $columns['all'],
                'basvuruDefaultVisible' => $columns['defaultVisible'],
                'basvuruSortable' => $columns['sortable'],
                'sort' => $sort,
                'direction' => $direction,
            ])->render(),
            'total' => $basvurular->total(),
            'durum' => $basvuruDurum,
            'durumlar' => EtkinlikBasvuruDurum::query()
                ->where('aktif', true)
                ->orderBy('sira')
                ->orderBy('ad')
                ->get(['kod', 'ad'])
                ->map(fn (EtkinlikBasvuruDurum $d) => [
                    'kod' => $d->kod,
                    'ad' => $d->ad,
                ])
                ->values(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function exportBasvurular(Request $request, Etkinlik $etkinlik): StreamedResponse
    {
        [$basvurular] = $this->searchEtkinlikBasvurular($request, $etkinlik, paginate: false);

        $filename = 'etkinlik-'.$etkinlik->etkinlik_no.'-basvurular-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($basvurular) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Başvuran', 'Katılımcı', 'Veli', 'Kimlik No', 'Doğum T.', 'Telefon', 'İkamet',
                'Durum', 'Katılım', 'Onay Tarihi', 'İptal Tarihi', 'İptal Gerekçesi', 'Kaydeden', 'Başvuru Tarihi',
            ], ';');

            $basvurular->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $basvuru) {
                    fputcsv($handle, [
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

    public function showBasvuru(Etkinlik $etkinlik, EtkinlikBasvuru $basvuru): View
    {
        abort_unless((int) $basvuru->etkinlik_id === (int) $etkinlik->id, 404);

        $basvuru->load([
            'kisi',
            'basvuran',
            'veli',
            'durum',
            'iptalGerekce',
            'olusturan',
            'onaylayan',
            'iptalEden',
            'etkinlik.merkez',
            'etkinlik.etkinlikTipi',
            'etkinlik.sorumlular',
            'etkinlik.kurumlar',
        ]);

        $smsLoglari = SmsLog::query()
            ->where('etkinlik_basvuru_id', $basvuru->id)
            ->with('gonderen')
            ->latest()
            ->limit(50)
            ->get();

        $epostaLoglari = EpostaLog::query()
            ->where('etkinlik_basvuru_id', $basvuru->id)
            ->with('gonderen')
            ->latest()
            ->limit(50)
            ->get();

        $evraklarPayload = $this->basvuruEvraklariPayload($etkinlik, $basvuru);
        $evrakTipOptions = EvrakTipi::query()
            ->where('aktif', true)
            ->orderBy('ad')
            ->get(['id', 'ad', 'aciklama'])
            ->map(fn (EvrakTipi $tip) => [
                'id' => $tip->id,
                'ad' => $tip->ad,
                'aciklama' => $tip->aciklama,
            ])
            ->values();

        $etkinlikAyarlari = app(EtkinlikAyarServisi::class);

        return view('etkinlikler.basvuru-show', [
            'etkinlik' => $etkinlik,
            'basvuru' => $basvuru,
            'smsLoglari' => $smsLoglari,
            'epostaLoglari' => $epostaLoglari,
            'evraklarPayload' => $evraklarPayload,
            'evrakTipOptions' => $evrakTipOptions,
            'basvuruDurumlari' => EtkinlikBasvuruDurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
            'iptalGerekceleri' => IptalGerekce::query()->where('aktif', true)->orderBy('sira')->get(),
            'smsBasvuruOnayAyar' => $etkinlikAyarlari->smsBasvuruOnay()->value,
            'smsBasvuruIptalAyar' => $etkinlikAyarlari->smsBasvuruIptal()->value,
            'smsBasvuruYedekAyar' => $etkinlikAyarlari->smsBasvuruYedek()->value,
            'epostaBasvuruOnayAyar' => $etkinlikAyarlari->epostaBasvuruOnay()->value,
            'epostaBasvuruIptalAyar' => $etkinlikAyarlari->epostaBasvuruIptal()->value,
            'epostaBasvuruYedekAyar' => $etkinlikAyarlari->epostaBasvuruYedek()->value,
        ]);
    }

    public function storeBasvuruEvrak(Request $request, Etkinlik $etkinlik, EtkinlikBasvuru $basvuru): JsonResponse
    {
        abort_unless((int) $basvuru->etkinlik_id === (int) $etkinlik->id, 404);
        $basvuru->loadMissing('kisi');

        $allowedTipIds = EvrakTipi::query()
            ->where('aktif', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validated = $request->validate([
            'evrak_tipi_id' => ['required', 'integer', Rule::in($allowedTipIds)],
            'dosya' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'evrak_tipi_id.required' => 'Evrak tipi seçilmelidir.',
            'evrak_tipi_id.in' => 'Seçilen evrak tipi geçerli değil.',
            'dosya.required' => 'Dosya seçilmelidir.',
            'dosya.mimes' => 'Dosya PDF veya görsel (JPG/PNG) olmalıdır.',
            'dosya.max' => 'Dosya en fazla 5 MB olabilir.',
        ]);

        $file = $request->file('dosya');
        $tipId = (int) $validated['evrak_tipi_id'];

        DB::transaction(function () use ($request, $basvuru, $etkinlik, $file, $tipId) {
            $path = $file->store('etkinlik-basvuru-evraklari/'.$basvuru->id, 'public');

            $kayit = EtkinlikBasvuruEvrak::query()->create([
                'etkinlik_basvuru_id' => $basvuru->id,
                'evrak_tipi_id' => $tipId,
                'dosya_yolu' => $path,
                'orijinal_ad' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'boyut' => $file->getSize() ?: 0,
                'olusturan_id' => $request->user()?->id,
            ]);
            $kayit->load(['evrakTipi', 'olusturan']);

            LogKaydedici::kaydet(
                islem: 'etkinlik_basvuru.evrak_yuklendi',
                kurs: null,
                aciklama: ($basvuru->kisi?->tam_adi ?? 'Başvuru').' için evrak yüklendi: '.($kayit->evrakTipi?->ad ?? 'Evrak'),
                konu: $basvuru,
                yeni: [
                    'evrak_id' => $kayit->id,
                    'evrak_tipi_id' => $kayit->evrak_tipi_id,
                ],
                ekstra: ['etkinlik_id' => $etkinlik->id],
            );
        });

        return response()->json([
            'message' => 'Evrak yüklendi.',
            'evraklar' => $this->basvuruEvraklariPayload($etkinlik, $basvuru),
        ]);
    }

    public function destroyBasvuruEvrak(Request $request, Etkinlik $etkinlik, EtkinlikBasvuru $basvuru, EtkinlikBasvuruEvrak $evrak): JsonResponse
    {
        abort_unless((int) $basvuru->etkinlik_id === (int) $etkinlik->id, 404);
        abort_unless((int) $evrak->etkinlik_basvuru_id === (int) $basvuru->id, 404);

        $basvuru->loadMissing('kisi');
        $evrak->loadMissing('evrakTipi');

        DB::transaction(function () use ($request, $etkinlik, $basvuru, $evrak) {
            $evrak->update([
                'silen_id' => $request->user()?->id,
            ]);
            $evrak->delete();

            LogKaydedici::kaydet(
                islem: 'etkinlik_basvuru.evrak_silindi',
                kurs: null,
                aciklama: ($basvuru->kisi?->tam_adi ?? 'Başvuru').' için evrak silindi: '.($evrak->evrakTipi?->ad ?? 'Evrak'),
                konu: $basvuru,
                eski: [
                    'evrak_id' => $evrak->id,
                    'evrak_tipi_id' => $evrak->evrak_tipi_id,
                ],
                ekstra: ['etkinlik_id' => $etkinlik->id],
            );
        });

        return response()->json([
            'message' => 'Evrak silindi.',
            'evraklar' => $this->basvuruEvraklariPayload($etkinlik, $basvuru),
        ]);
    }

    public function showBasvuruEvrak(Etkinlik $etkinlik, EtkinlikBasvuru $basvuru, EtkinlikBasvuruEvrak $evrak): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless((int) $basvuru->etkinlik_id === (int) $etkinlik->id, 404);
        abort_unless((int) $evrak->etkinlik_basvuru_id === (int) $basvuru->id, 404);
        abort_unless($evrak->dosya_yolu && Storage::disk('public')->exists($evrak->dosya_yolu), 404);

        $path = Storage::disk('public')->path($evrak->dosya_yolu);
        $downloadName = basename((string) ($evrak->orijinal_ad ?: $evrak->dosya_yolu));

        return response()->file($path, [
            'Content-Type' => $evrak->mime ?: 'application/octet-stream',
        ])->setContentDisposition('inline', $downloadName);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function basvuruEvraklariPayload(Etkinlik $etkinlik, EtkinlikBasvuru $basvuru)
    {
        $user = request()->user();
        $canView = (bool) $user?->hasYetki('etkinlik_basvuru.evrak_goruntule');
        $canDelete = (bool) $user?->hasYetki('etkinlik_basvuru.evrak_sil');

        return $basvuru->evraklar()
            ->with(['evrakTipi', 'olusturan'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (EtkinlikBasvuruEvrak $item) => [
                'id' => $item->id,
                'tip' => $item->evrakTipi?->ad ?? 'Evrak',
                'aciklama' => $item->evrakTipi?->aciklama,
                'mime' => $item->mime,
                'boyut' => (int) ($item->boyut ?? 0),
                'url' => $canView
                    ? route('etkinlikler.basvurular.evrak.show', [$etkinlik, $basvuru, $item])
                    : null,
                'delete_url' => $canDelete
                    ? route('etkinlikler.basvurular.evrak.destroy', [$etkinlik, $basvuru, $item])
                    : null,
                'yukleyen' => $item->olusturan?->tam_adi,
                'yuklenme_tarihi' => $item->created_at?->format('d.m.Y H:i'),
            ])
            ->values();
    }

    public function updateBasvuruDurum(
        Request $request,
        Etkinlik $etkinlik,
        EtkinlikBasvuru $basvuru,
        EtkinlikAyarServisi $etkinlikAyarlari,
    ): JsonResponse
    {
        abort_unless((int) $basvuru->etkinlik_id === (int) $etkinlik->id, 404);

        $durumKod = (string) $request->input('durum_kod', '');
        if ($durumKod === '') {
            $durumKod = 'onay_bekliyor';
            $request->merge(['durum_kod' => $durumKod]);
        }

        $aktifKodlar = EtkinlikBasvuruDurum::query()
            ->where('aktif', true)
            ->pluck('kod')
            ->all();

        // Mevcut durum pasif olsa da seçilebilir kalsın.
        $mevcutKod = $basvuru->durum?->kod
            ?? EtkinlikBasvuruDurum::query()->whereKey($basvuru->durum_id)->value('kod');
        if ($mevcutKod && ! in_array($mevcutKod, $aktifKodlar, true)) {
            $aktifKodlar[] = $mevcutKod;
        }

        $validated = $request->validate([
            'durum_kod' => ['required', 'string', Rule::in($aktifKodlar)],
            'iptal_gerekce_id' => [
                Rule::requiredIf(fn () => $request->input('durum_kod') === 'iptal'),
                'nullable',
                'integer',
                Rule::exists('iptal_gerekceleri', 'id')->where(fn ($q) => $q->where('aktif', true)),
            ],
            'sms_gonder' => ['nullable', 'boolean'],
            'eposta_gonder' => ['nullable', 'boolean'],
        ], [
            'durum_kod.required' => 'Başvuru durumu seçilmelidir.',
            'durum_kod.in' => 'Geçersiz başvuru durumu.',
            'iptal_gerekce_id.required' => 'İptal gerekçesi seçilmelidir.',
            'iptal_gerekce_id.exists' => 'Seçilen iptal gerekçesi geçersiz.',
        ]);

        $mevcutDurumKod = $mevcutKod;

        $durumId = EtkinlikBasvuruDurum::idByKod($validated['durum_kod']);
        abort_unless($durumId, 422);

        if ($validated['durum_kod'] === 'yedek' && $mevcutDurumKod !== 'yedek') {
            $yedekServisi = app(EtkinlikYedekListeServisi::class);
            $doluluk = $yedekServisi->dolulukOzeti($etkinlik);
            if ($doluluk['yedek_kontenjan'] <= 0) {
                throw ValidationException::withMessages([
                    'durum_kod' => 'Bu etkinlikte yedek kontenjan tanımlı değil.',
                ]);
            }
            if ($doluluk['yedek_dolu']) {
                throw ValidationException::withMessages([
                    'durum_kod' => 'Yedek kontenjan ('.$doluluk['yedek_kontenjan'].') dolmuştur.',
                ]);
            }
        }

        if ($validated['durum_kod'] === 'kesin_kayit' && $mevcutDurumKod !== 'kesin_kayit') {
            $yedekServisi = app(EtkinlikYedekListeServisi::class);
            $doluluk = $yedekServisi->dolulukOzeti($etkinlik);
            // Yedekten kesin kayda alırken ana kontenjan doluysa engelle (boşalan yer yoksa)
            if ($mevcutDurumKod !== 'onay_bekliyor' && $doluluk['ana_dolu']) {
                throw ValidationException::withMessages([
                    'durum_kod' => 'Ana kontenjan dolu. Kesin kayıt için önce kontenjanda yer açılmalıdır.',
                ]);
            }
            // onay_bekliyor zaten ana listede yer kaplıyor; kesin kayda geçişte ek yer gerekmez
        }

        $smsAyarlari = match ($validated['durum_kod']) {
            'kesin_kayit' => [
                $etkinlikAyarlari->smsBasvuruOnay(),
                $etkinlikAyarlari->smsMetinOnay(),
                'basvuru_onay',
            ],
            'iptal' => [
                $etkinlikAyarlari->smsBasvuruIptal(),
                $etkinlikAyarlari->smsMetinIptal(),
                'basvuru_iptal',
            ],
            'yedek' => [
                $etkinlikAyarlari->smsBasvuruYedek(),
                $etkinlikAyarlari->smsMetinYedek(),
                'basvuru_yedek',
            ],
            default => null,
        };

        $epostaAyarlari = match ($validated['durum_kod']) {
            'kesin_kayit' => [
                $etkinlikAyarlari->epostaBasvuruOnay(),
                $etkinlikAyarlari->epostaKonuOnay(),
                $etkinlikAyarlari->epostaMetinOnay(),
                'basvuru_onay',
            ],
            'iptal' => [
                $etkinlikAyarlari->epostaBasvuruIptal(),
                $etkinlikAyarlari->epostaKonuIptal(),
                $etkinlikAyarlari->epostaMetinIptal(),
                'basvuru_iptal',
            ],
            'yedek' => [
                $etkinlikAyarlari->epostaBasvuruYedek(),
                $etkinlikAyarlari->epostaKonuYedek(),
                $etkinlikAyarlari->epostaMetinYedek(),
                'basvuru_yedek',
            ],
            default => null,
        };

        $smsSender = null;
        if ($smsAyarlari !== null) {
            [$smsAyar, ] = $smsAyarlari;
            if ($this->basvuruDurumSmsGonderilecekMi($smsAyar, $request->boolean('sms_gonder'))) {
                $smsSender = $this->smsSenderVeyaHata();
            }
        }

        $emailSender = null;
        if ($epostaAyarlari !== null) {
            [$epostaAyar, ] = $epostaAyarlari;
            if ($this->basvuruDurumSmsGonderilecekMi($epostaAyar, $request->boolean('eposta_gonder'))) {
                $emailSender = $this->emailSenderVeyaHata();
            }
        }

        $userId = $request->user()?->id;
        $payload = [
            'durum_id' => $durumId,
            'guncelleyen_id' => $userId,
        ];

        if ($validated['durum_kod'] === 'kesin_kayit') {
            $payload['onay_tarihi'] = now();
            $payload['onaylayan_id'] = $userId;
            $payload['iptal_tarihi'] = null;
            $payload['iptal_gerekce_id'] = null;
            $payload['iptal_eden_id'] = null;
            $mesaj = 'Başvuru onaylandı.';
        } elseif ($validated['durum_kod'] === 'iptal') {
            $payload['iptal_tarihi'] = now();
            $payload['iptal_gerekce_id'] = $validated['iptal_gerekce_id'];
            $payload['iptal_eden_id'] = $userId;
            $payload['katilim_durumu'] = null;
            $mesaj = 'Başvuru iptal edildi.';
        } else {
            $payload['onay_tarihi'] = null;
            $payload['onaylayan_id'] = null;
            $payload['iptal_tarihi'] = null;
            $payload['iptal_gerekce_id'] = null;
            $payload['iptal_eden_id'] = null;
            $payload['katilim_durumu'] = null;
            $mesaj = $validated['durum_kod'] === 'yedek'
                ? 'Başvuru yedeğe alındı.'
                : 'Başvuru durumu Onay Bekliyor olarak güncellendi.';
        }

        DB::transaction(function () use ($etkinlik, $basvuru, $payload, $mevcutDurumKod, $validated) {
            $basvuru->update($payload);
            app(EtkinlikYedekListeServisi::class)->durumDegisimindeYedekSirasiGuncelle(
                $etkinlik,
                $basvuru->fresh(),
                $mevcutDurumKod,
                $validated['durum_kod'],
            );
            $this->refreshEtkinlikSayaclari($etkinlik);
        });

        $basvuru->refresh();
        $basvuru->load(['durum', 'iptalGerekce', 'kisi', 'basvuran', 'veli']);

        if ($validated['durum_kod'] === 'yedek' && $basvuru->yedek_sira) {
            $mesaj = 'Başvuru yedeğe alındı. Yedek sırası: '.$basvuru->yedek_sira.'.';
        }

        LogKaydedici::kaydet(
            islem: 'basvuru.durum_degisti',
            aciklama: $this->basvuruSahibiAdi($basvuru).' başvurusu için '.$mesaj,
            konu: $basvuru,
            eski: ['durum' => $mevcutDurumKod],
            yeni: [
                'durum' => $validated['durum_kod'],
                'yedek_sira' => $basvuru->yedek_sira,
                'iptal_gerekce' => $basvuru->iptalGerekce?->ad,
            ],
        );

        if ($smsAyarlari !== null && $smsSender !== null) {
            [$smsAyar, $smsMetin, $smsKapsam] = $smsAyarlari;
            $smsSonuc = $this->basvuruDurumSmsGonder(
                $request,
                $etkinlik,
                $basvuru,
                $smsSender,
                $smsAyar,
                $smsMetin,
                $request->boolean('sms_gonder'),
                $smsKapsam,
                $validated['durum_kod'],
            );

            if ($smsSonuc !== null) {
                $mesaj .= ' '.$smsSonuc;
            }
        }

        if ($epostaAyarlari !== null && $emailSender !== null) {
            [$epostaAyar, $epostaKonu, $epostaMetin, $epostaKapsam] = $epostaAyarlari;
            $epostaSonuc = $this->basvuruDurumEpostaGonder(
                $request,
                $etkinlik,
                $basvuru,
                $emailSender,
                $epostaAyar,
                $epostaKonu,
                $epostaMetin,
                $request->boolean('eposta_gonder'),
                $epostaKapsam,
                $validated['durum_kod'],
            );

            if ($epostaSonuc !== null) {
                $mesaj .= ' '.$epostaSonuc;
            }
        }

        return response()->json([
            'message' => $mesaj,
            'durum' => $basvuru->durum?->only(['id', 'kod', 'ad', 'status_sinifi']),
            'yedek_sira' => $basvuru->yedek_sira,
        ]);
    }

    public function yedekSirasi(Etkinlik $etkinlik, EtkinlikYedekListeServisi $yedekListe): JsonResponse
    {
        $liste = $yedekListe->yedekBasvurulari($etkinlik);

        return response()->json([
            'etkinlik_id' => $etkinlik->id,
            'etkinlik_ad' => $etkinlik->ad,
            'items' => $liste->map(fn (EtkinlikBasvuru $basvuru) => [
                'id' => $basvuru->id,
                'yedek_sira' => $basvuru->yedek_sira,
                'ad' => $basvuru->kisi?->tam_adi
                    ?? $basvuru->basvuran?->tam_adi
                    ?? ('Başvuru #'.$basvuru->id),
                'kimlik' => $basvuru->kisi?->tc_kimlik_no,
                'basvuru_tarihi' => $basvuru->created_at?->format('d.m.Y H:i'),
            ])->values(),
        ]);
    }

    public function updateYedekSirasi(
        Request $request,
        Etkinlik $etkinlik,
        EtkinlikYedekListeServisi $yedekListe,
    ): JsonResponse {
        $validated = $request->validate([
            'basvuru_ids' => ['required', 'array', 'min:1'],
            'basvuru_ids.*' => ['integer', 'distinct'],
        ], [
            'basvuru_ids.required' => 'Yedek sıra listesi boş olamaz.',
            'basvuru_ids.min' => 'Yedek sıra listesi boş olamaz.',
        ]);

        DB::transaction(function () use ($etkinlik, $yedekListe, $validated) {
            $yedekListe->sirayiGuncelle($etkinlik, $validated['basvuru_ids']);
        });

        LogKaydedici::kaydet(
            islem: 'etkinlik.yedek_sirasi_guncellendi',
            aciklama: 'Etkinlik #'.$etkinlik->etkinlik_no.' yedek sırası güncellendi.',
            konu: $etkinlik,
            yeni: ['basvuru_ids' => array_values($validated['basvuru_ids'])],
            ekstra: ['etkinlik_id' => $etkinlik->id],
        );

        return response()->json([
            'message' => 'Yedek sırası güncellendi.',
            'items' => $yedekListe->yedekBasvurulari($etkinlik)->map(fn (EtkinlikBasvuru $basvuru) => [
                'id' => $basvuru->id,
                'yedek_sira' => $basvuru->yedek_sira,
            ])->values(),
        ]);
    }

    public function updateBasvuruIptalGerekce(Request $request, Etkinlik $etkinlik, EtkinlikBasvuru $basvuru): JsonResponse
    {
        abort_unless((int) $basvuru->etkinlik_id === (int) $etkinlik->id, 404);

        $iptalDurumId = EtkinlikBasvuruDurum::idByKod('iptal');
        if ((int) $basvuru->durum_id !== (int) $iptalDurumId) {
            throw ValidationException::withMessages([
                'iptal_gerekce_id' => 'İptal gerekçesi yalnızca başvuru durumu İptal olan kayıtlarda güncellenebilir.',
            ]);
        }

        $validated = $request->validate([
            'iptal_gerekce_id' => [
                'required',
                'integer',
                Rule::exists('iptal_gerekceleri', 'id')->where(fn ($q) => $q->where('aktif', true)),
            ],
        ], [
            'iptal_gerekce_id.required' => 'İptal gerekçesi seçilmelidir.',
            'iptal_gerekce_id.exists' => 'Seçilen iptal gerekçesi geçersiz.',
        ]);

        $eski = $basvuru->iptalGerekce?->ad;

        $basvuru->update([
            'iptal_gerekce_id' => $validated['iptal_gerekce_id'],
            'guncelleyen_id' => $request->user()?->id,
        ]);
        $basvuru->load('iptalGerekce');

        LogKaydedici::kaydet(
            islem: 'etkinlik_basvuru.iptal_gerekce_guncellendi',
            kurs: null,
            aciklama: $this->basvuruSahibiAdi($basvuru).' için iptal gerekçesi güncellendi.',
            konu: $basvuru,
            eski: ['iptal_gerekce' => $eski],
            yeni: ['iptal_gerekce' => $basvuru->iptalGerekce?->ad],
            ekstra: ['etkinlik_id' => $etkinlik->id],
        );

        return response()->json([
            'message' => 'İptal gerekçesi güncellendi.',
            'iptal_gerekce' => $basvuru->iptalGerekce?->only(['id', 'ad']),
        ]);
    }

    public function updateBasvuruVeli(Request $request, Etkinlik $etkinlik, EtkinlikBasvuru $basvuru): JsonResponse
    {
        abort_unless((int) $basvuru->etkinlik_id === (int) $etkinlik->id, 404);

        $basvuru->loadMissing(['kisi', 'veli']);
        $katilimci = $basvuru->kisi;
        $yas = $katilimci?->dogum_tarihi?->age;
        $kucuk = $yas !== null && $yas < 18;

        $validated = $request->validate([
            'veli_basvurusu' => ['required', 'boolean'],
            'veli_tc_kimlik_no' => ['nullable', 'digits:11'],
            'veli_dogum_tarihi' => ['nullable', 'date'],
            'veli_ad' => ['nullable', 'string', 'max:100'],
            'veli_soyad' => ['nullable', 'string', 'max:100'],
            'veli_telefon' => ['nullable', 'string', 'max:20'],
            'veli_email' => ['nullable', 'email', 'max:150'],
        ], [
            'veli_basvurusu.required' => 'Veli başvurusu seçilmelidir.',
            'veli_tc_kimlik_no.digits' => 'Veli TC Kimlik No 11 haneli olmalıdır.',
        ]);

        $veliBasvurusu = (bool) $validated['veli_basvurusu'];
        $eskiVeliId = $basvuru->veli_id;

        if (! $veliBasvurusu) {
            if ($kucuk) {
                throw ValidationException::withMessages([
                    'veli_basvurusu' => 'Katılımcı 18 yaşından küçük olduğu için veli başvurusu kaldırılamaz.',
                ]);
            }

            $payload = [
                'veli_id' => null,
                'guncelleyen_id' => $request->user()?->id,
            ];

            if ($basvuru->basvuran_id && $eskiVeliId && (int) $basvuru->basvuran_id === (int) $eskiVeliId) {
                $payload['basvuran_id'] = $basvuru->kisi_id;
            }

            $basvuru->update($payload);

            LogKaydedici::kaydet(
                islem: 'etkinlik_basvuru.veli_guncellendi',
                kurs: null,
                aciklama: $this->basvuruSahibiAdi($basvuru).' için veli başvurusu kaldırıldı.',
                konu: $basvuru,
                eski: ['veli_id' => $eskiVeliId],
                yeni: ['veli_id' => null],
                ekstra: ['etkinlik_id' => $etkinlik->id],
            );

            return response()->json([
                'message' => 'Veli başvurusu güncellendi.',
                'veli_basvurusu' => false,
            ]);
        }

        $veli = $basvuru->veli;
        $providingFields = filled($validated['veli_tc_kimlik_no'] ?? null)
            || filled($validated['veli_ad'] ?? null)
            || filled($validated['veli_soyad'] ?? null)
            || filled($validated['veli_dogum_tarihi'] ?? null);

        if (! $veli || $providingFields) {
            $request->validate([
                'veli_tc_kimlik_no' => [
                    'required',
                    'digits:11',
                    Rule::notIn([(string) ($katilimci?->tc_kimlik_no ?? '')]),
                ],
                'veli_dogum_tarihi' => ['required', 'date'],
                'veli_ad' => ['required', 'string', 'max:100'],
                'veli_soyad' => ['required', 'string', 'max:100'],
            ], [
                'veli_tc_kimlik_no.required' => 'Veli TC Kimlik No zorunludur.',
                'veli_tc_kimlik_no.not_in' => 'Veli TC Kimlik No katılımcıdan farklı olmalıdır.',
                'veli_dogum_tarihi.required' => 'Veli doğum tarihi zorunludur.',
                'veli_ad.required' => 'Veli adı zorunludur.',
                'veli_soyad.required' => 'Veli soyadı zorunludur.',
            ]);

            $veli = $this->kisiUpsert([
                'tc_kimlik_no' => $request->input('veli_tc_kimlik_no'),
                'dogum_tarihi' => $request->input('veli_dogum_tarihi'),
                'ad' => $request->input('veli_ad'),
                'soyad' => $request->input('veli_soyad'),
                'telefon' => $request->input('veli_telefon'),
                'email' => $request->input('veli_email'),
            ]);
        }

        $payload = [
            'veli_id' => $veli->id,
            'guncelleyen_id' => $request->user()?->id,
        ];

        if ($kucuk) {
            $payload['basvuran_id'] = $veli->id;
        }

        $basvuru->update($payload);

        LogKaydedici::kaydet(
            islem: 'etkinlik_basvuru.veli_guncellendi',
            kurs: null,
            aciklama: $this->basvuruSahibiAdi($basvuru).' için veli başvurusu güncellendi.',
            konu: $basvuru,
            eski: ['veli_id' => $eskiVeliId],
            yeni: ['veli_id' => $basvuru->veli_id],
            ekstra: ['etkinlik_id' => $etkinlik->id],
        );

        return response()->json([
            'message' => 'Veli başvurusu güncellendi.',
            'veli_basvurusu' => true,
            'veli' => $basvuru->fresh('veli')?->veli?->only(['id', 'ad', 'soyad', 'tc_kimlik_no']),
        ]);
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
            'telefon' => $data['telefon'] ?? null,
            'email' => $data['email'] ?? null,
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

    public function saveYoklama(Request $request, Etkinlik $etkinlik): JsonResponse
    {
        $validated = $request->validate([
            'yoklamalar' => ['required', 'array'],
            'yoklamalar.*.basvuru_id' => ['required', 'integer'],
            'yoklamalar.*.katilim_durumu' => ['nullable', Rule::in(['katildi', 'katilmadi'])],
        ]);

        $kesinKayitId = EtkinlikBasvuruDurum::idByKod('kesin_kayit');
        $kesinKayitIds = $kesinKayitId
            ? $etkinlik->basvurular()->where('durum_id', $kesinKayitId)->pluck('id')->all()
            : [];

        $guncellenen = 0;

        DB::transaction(function () use ($validated, $kesinKayitIds, $request, &$guncellenen) {
            foreach ($validated['yoklamalar'] as $row) {
                $basvuruId = (int) $row['basvuru_id'];

                if (! in_array($basvuruId, $kesinKayitIds, true)) {
                    continue;
                }

                EtkinlikBasvuru::whereKey($basvuruId)->update([
                    'katilim_durumu' => $row['katilim_durumu'] ?? null,
                    'guncelleyen_id' => $request->user()?->id,
                ]);

                $guncellenen++;
            }
        });

        $tekKayit = count($validated['yoklamalar']) === 1;

        LogKaydedici::kaydet(
            islem: 'etkinlik.yoklama_kaydedildi',
            aciklama: 'Etkinlik #'.$etkinlik->etkinlik_no.' için yoklama kaydedildi ('.$guncellenen.' kayıt).',
            konu: $etkinlik,
        );

        return response()->json([
            'message' => $tekKayit ? 'Yoklama güncellendi.' : 'Yoklama kaydedildi.',
            'guncellenen' => $guncellenen,
        ]);
    }

    public function smsAlicilar(Etkinlik $etkinlik): JsonResponse
    {
        $basvurular = $etkinlik->basvurular()
            ->with(['kisi', 'basvuran', 'veli', 'durum'])
            ->get()
            ->map(function (EtkinlikBasvuru $basvuru) {
                $ad = $this->basvuruSahibiAdi($basvuru);

                return [
                    'id' => $basvuru->id,
                    'ad' => $ad,
                    'tc' => $basvuru->kisi?->tc_kimlik_no
                        ?? $basvuru->basvuran?->tc_kimlik_no
                        ?? '',
                    'durum_kod' => $basvuru->durum?->kod,
                    'durum_ad' => $basvuru->durum?->ad,
                    'telefon_var' => (bool) $this->basvuruTelefon($basvuru),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['ad'], 'UTF-8'), SORT_NATURAL)
            ->values();

        return response()->json([
            'alicilar' => $basvurular,
        ]);
    }

    public function sendSms(Request $request, Etkinlik $etkinlik, SmsSender $smsSender): JsonResponse
    {
        $validated = $request->validate([
            'mesaj' => ['required', 'string', 'min:1', 'max:480'],
            'basvuru_ids' => ['required', 'array', 'min:1'],
            'basvuru_ids.*' => ['integer'],
            'basvuru_durum' => ['nullable', 'string', 'max:50'],
        ], [
            'mesaj.required' => 'SMS metni zorunludur.',
            'mesaj.max' => 'SMS metni en fazla 480 karakter olabilir.',
            'basvuru_ids.required' => 'SMS göndermek için en az bir alıcı olmalıdır.',
            'basvuru_ids.min' => 'SMS göndermek için en az bir alıcı olmalıdır.',
        ]);

        $ids = collect($validated['basvuru_ids'])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'basvuru_ids' => 'SMS göndermek için en az bir alıcı olmalıdır.',
            ]);
        }

        $basvurular = $etkinlik->basvurular()
            ->with(['kisi', 'basvuran', 'veli'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (EtkinlikBasvuru $basvuru) => mb_strtolower($this->basvuruSahibiAdi($basvuru), 'UTF-8'), SORT_NATURAL)
            ->values();

        if ($basvurular->isEmpty()) {
            throw ValidationException::withMessages([
                'basvuru_ids' => 'Seçilen alıcılar bulunamadı.',
            ]);
        }

        $detay = [];
        $gonderilen = 0;
        $atlanan = 0;

        foreach ($basvurular as $basvuru) {
            $ad = $this->basvuruSahibiAdi($basvuru);
            $telefon = $this->basvuruTelefon($basvuru);

            if (! $telefon) {
                $atlanan++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'telefon' => null,
                    'durum' => 'atlandi',
                    'hata' => 'Telefon yok',
                ];
                continue;
            }

            $kisiselMesaj = $this->mesajKisisellestir($validated['mesaj'], $ad);
            if (mb_strlen($kisiselMesaj) > 480) {
                $atlanan++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'telefon' => $telefon,
                    'durum' => 'atlandi',
                    'hata' => 'Kişiselleştirilmiş mesaj 480 karakteri aşıyor',
                    'mesaj' => $kisiselMesaj,
                ];
                continue;
            }

            $sonuc = $smsSender->send($telefon, $kisiselMesaj, [
                'etkinlik_id' => $etkinlik->id,
                'etkinlik_basvuru_id' => $basvuru->id,
                'gonderen_id' => $request->user()?->id,
            ]);
            if ($sonuc['ok'] ?? false) {
                $gonderilen++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'telefon' => $telefon,
                    'durum' => 'gonderildi',
                    'mesaj' => $kisiselMesaj,
                ];
            } else {
                $atlanan++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'telefon' => $telefon,
                    'durum' => 'atlandi',
                    'hata' => $sonuc['message'] ?? 'Gönderilemedi',
                    'mesaj' => $kisiselMesaj,
                ];
            }
        }

        $kayit = EtkinlikSmsGonderim::query()->create([
            'etkinlik_id' => $etkinlik->id,
            'gonderen_id' => $request->user()?->id,
            'mesaj' => $validated['mesaj'],
            'kapsam' => 'secilen',
            'basvuru_durum_kod' => $validated['basvuru_durum'] ?? null,
            'toplam' => $basvurular->count(),
            'gonderilen' => $gonderilen,
            'atlanan' => $atlanan,
            'detay' => $detay,
        ]);

        $mesaj = "{$gonderilen} SMS gönderildi";
        if ($atlanan > 0) {
            $mesaj .= ", {$atlanan} kayıt atlandı";
        }
        $mesaj .= '.';

        LogKaydedici::kaydet(
            islem: 'sms.gonderildi',
            aciklama: 'Etkinlik #'.$etkinlik->etkinlik_no.' için '.$mesaj,
            konu: $kayit,
            yeni: [
                'toplam' => $kayit->toplam,
                'gonderilen' => $kayit->gonderilen,
                'atlanan' => $kayit->atlanan,
            ],
        );

        return response()->json([
            'message' => $mesaj,
            'gonderim' => [
                'id' => $kayit->id,
                'toplam' => $kayit->toplam,
                'gonderilen' => $kayit->gonderilen,
                'atlanan' => $kayit->atlanan,
                'created_at' => $kayit->created_at?->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function epostaAlicilar(Etkinlik $etkinlik): JsonResponse
    {
        $basvurular = $etkinlik->basvurular()
            ->with(['kisi', 'basvuran', 'veli', 'durum'])
            ->get()
            ->map(function (EtkinlikBasvuru $basvuru) {
                $ad = $this->basvuruSahibiAdi($basvuru);
                $email = $this->basvuruEmail($basvuru);

                return [
                    'id' => $basvuru->id,
                    'ad' => $ad,
                    'tc' => $basvuru->kisi?->tc_kimlik_no
                        ?? $basvuru->basvuran?->tc_kimlik_no
                        ?? '',
                    'durum_kod' => $basvuru->durum?->kod,
                    'durum_ad' => $basvuru->durum?->ad,
                    'email' => $email,
                    'email_var' => (bool) $email,
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['ad'], 'UTF-8'), SORT_NATURAL)
            ->values();

        return response()->json([
            'alicilar' => $basvurular,
        ]);
    }

    public function sendEposta(Request $request, Etkinlik $etkinlik, EmailSender $emailSender): JsonResponse
    {
        $validated = $request->validate([
            'konu' => ['required', 'string', 'min:1', 'max:200'],
            'mesaj' => ['required', 'string', 'min:1', 'max:5000'],
            'basvuru_ids' => ['required', 'array', 'min:1'],
            'basvuru_ids.*' => ['integer'],
            'basvuru_durum' => ['nullable', 'string', 'max:50'],
        ], [
            'konu.required' => 'E-posta konusu zorunludur.',
            'konu.max' => 'E-posta konusu en fazla 200 karakter olabilir.',
            'mesaj.required' => 'E-posta metni zorunludur.',
            'mesaj.max' => 'E-posta metni en fazla 5000 karakter olabilir.',
            'basvuru_ids.required' => 'E-posta göndermek için en az bir alıcı olmalıdır.',
            'basvuru_ids.min' => 'E-posta göndermek için en az bir alıcı olmalıdır.',
        ]);

        $ids = collect($validated['basvuru_ids'])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'basvuru_ids' => 'E-posta göndermek için en az bir alıcı olmalıdır.',
            ]);
        }

        $basvurular = $etkinlik->basvurular()
            ->with(['kisi', 'basvuran', 'veli'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (EtkinlikBasvuru $basvuru) => mb_strtolower($this->basvuruSahibiAdi($basvuru), 'UTF-8'), SORT_NATURAL)
            ->values();

        if ($basvurular->isEmpty()) {
            throw ValidationException::withMessages([
                'basvuru_ids' => 'Seçilen alıcılar bulunamadı.',
            ]);
        }

        $detay = [];
        $gonderilen = 0;
        $atlanan = 0;

        foreach ($basvurular as $basvuru) {
            $ad = $this->basvuruSahibiAdi($basvuru);
            $email = $this->basvuruEmail($basvuru);

            if (! $email) {
                $atlanan++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'email' => null,
                    'durum' => 'atlandi',
                    'hata' => 'E-posta yok',
                ];
                continue;
            }

            $kisiselKonu = $this->mesajKisisellestir($validated['konu'], $ad);
            $kisiselMesaj = $this->mesajKisisellestir($validated['mesaj'], $ad);

            if (mb_strlen($kisiselKonu) > 200) {
                $atlanan++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'email' => $email,
                    'durum' => 'atlandi',
                    'hata' => 'Kişiselleştirilmiş konu 200 karakteri aşıyor',
                    'konu' => $kisiselKonu,
                    'mesaj' => $kisiselMesaj,
                ];
                continue;
            }

            if (mb_strlen($kisiselMesaj) > 5000) {
                $atlanan++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'email' => $email,
                    'durum' => 'atlandi',
                    'hata' => 'Kişiselleştirilmiş mesaj 5000 karakteri aşıyor',
                    'konu' => $kisiselKonu,
                    'mesaj' => $kisiselMesaj,
                ];
                continue;
            }

            $sonuc = $emailSender->send($email, $kisiselKonu, $kisiselMesaj, [
                'etkinlik_id' => $etkinlik->id,
                'etkinlik_basvuru_id' => $basvuru->id,
                'gonderen_id' => $request->user()?->id,
            ]);
            if ($sonuc['ok'] ?? false) {
                $gonderilen++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'email' => $email,
                    'durum' => 'gonderildi',
                    'konu' => $kisiselKonu,
                    'mesaj' => $kisiselMesaj,
                ];
            } else {
                $atlanan++;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'email' => $email,
                    'durum' => 'atlandi',
                    'hata' => $sonuc['message'] ?? 'Gönderilemedi',
                    'konu' => $kisiselKonu,
                    'mesaj' => $kisiselMesaj,
                ];
            }
        }

        $kayit = EtkinlikEpostaGonderim::query()->create([
            'etkinlik_id' => $etkinlik->id,
            'gonderen_id' => $request->user()?->id,
            'konu' => $validated['konu'],
            'mesaj' => $validated['mesaj'],
            'kapsam' => 'secilen',
            'basvuru_durum_kod' => $validated['basvuru_durum'] ?? null,
            'toplam' => $basvurular->count(),
            'gonderilen' => $gonderilen,
            'atlanan' => $atlanan,
            'detay' => $detay,
        ]);

        $mesaj = "{$gonderilen} e-posta gönderildi";
        if ($atlanan > 0) {
            $mesaj .= ", {$atlanan} kayıt atlandı";
        }
        $mesaj .= '.';

        LogKaydedici::kaydet(
            islem: 'eposta.gonderildi',
            aciklama: 'Etkinlik #'.$etkinlik->etkinlik_no.' için '.$mesaj,
            konu: $kayit,
            yeni: [
                'konu' => $validated['konu'],
                'toplam' => $kayit->toplam,
                'gonderilen' => $kayit->gonderilen,
                'atlanan' => $kayit->atlanan,
            ],
        );

        return response()->json([
            'message' => $mesaj,
            'gonderim' => [
                'id' => $kayit->id,
                'toplam' => $kayit->toplam,
                'gonderilen' => $kayit->gonderilen,
                'atlanan' => $kayit->atlanan,
                'created_at' => $kayit->created_at?->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Etkinlik::query()
            ->with(['merkez', 'etkinlikTipi', 'kurumlar'])
            ->latest('created_at');

        $user = $request->user();
        $this->applyEtkinlikScope($query, $user);
        $this->applyEtkinlikNoFilter($query, $request);
        $this->applyIndexFilters($query, $request);

        $filename = 'etkinlikler-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Etkinlik No', 'Ad', 'Merkez', 'Kurum', 'Tip', 'Başlangıç', 'Bitiş',
                'Başvuru', 'Kayıt', 'İptal', 'Kontenjan', 'Yedek', 'Durum', 'Başvuru Durumu',
            ], ';');

            $query->chunk(200, function ($etkinlikler) use ($handle) {
                foreach ($etkinlikler as $etkinlik) {
                    fputcsv($handle, [
                        $etkinlik->etkinlik_no,
                        $etkinlik->ad,
                        $etkinlik->merkez?->ad,
                        $etkinlik->kurumlar->pluck('ad')->implode(', '),
                        $etkinlik->etkinlikTipi?->ad,
                        $etkinlik->baslangic_tarihi?->format('d.m.Y'),
                        $etkinlik->bitis_tarihi?->format('d.m.Y'),
                        $etkinlik->basvuru_sayisi,
                        $etkinlik->kayit_sayisi,
                        $etkinlik->iptal_sayisi,
                        $etkinlik->kontenjan,
                        $etkinlik->yedek_kontenjan,
                        $etkinlik->durum?->label(),
                        $etkinlik->basvuruDurumuLabel(),
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function basvuruTelefon(EtkinlikBasvuru $basvuru): ?string
    {
        return PhoneNormalizer::normalize(
            $basvuru->basvuran?->telefon
                ?: $basvuru->kisi?->telefon
                ?: $basvuru->veli?->telefon
        );
    }

    /**
     * Durum güncellemesinde ayara göre SMS gönderir.
     */
    private function basvuruDurumSmsGonderilecekMi(SmsGonderimSecenegi $ayar, bool $kullaniciIstek): bool
    {
        return match ($ayar) {
            SmsGonderimSecenegi::Evet => true,
            SmsGonderimSecenegi::Hayir => false,
            SmsGonderimSecenegi::IstegeBagli => $kullaniciIstek,
        };
    }

    private function smsSenderVeyaHata(): SmsSender
    {
        try {
            return app(SmsSender::class);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'sms_gonder' => $e->getMessage() ?: 'SMS entegrasyonu kullanılamıyor.',
            ]);
        }
    }

    private function emailSenderVeyaHata(): EmailSender
    {
        try {
            return app(EmailSender::class);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'eposta_gonder' => $e->getMessage() ?: 'E-posta entegrasyonu kullanılamıyor.',
            ]);
        }
    }

    private function basvuruDurumSmsGonder(
        Request $request,
        Etkinlik $etkinlik,
        EtkinlikBasvuru $basvuru,
        SmsSender $smsSender,
        SmsGonderimSecenegi $ayar,
        string $sablon,
        bool $kullaniciIstek,
        string $kapsam = 'basvuru_onay',
        string $basvuruDurumKod = 'kesin_kayit',
    ): ?string {
        if (! $this->basvuruDurumSmsGonderilecekMi($ayar, $kullaniciIstek)) {
            return null;
        }

        $ad = $this->basvuruSahibiAdi($basvuru);
        $telefon = $this->basvuruTelefon($basvuru);
        $gonderilen = 0;
        $atlanan = 0;
        $detay = [];

        if (! $telefon) {
            $atlanan = 1;
            $detay[] = [
                'basvuru_id' => $basvuru->id,
                'ad' => $ad,
                'telefon' => null,
                'durum' => 'atlandi',
                'hata' => 'Telefon yok',
            ];
            $sonucMesaj = 'SMS gönderilemedi: telefon yok.';
        } else {
            $kisiselMesaj = $this->mesajKisisellestir($sablon, $ad);
            if (mb_strlen($kisiselMesaj) > 480) {
                $atlanan = 1;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'telefon' => $telefon,
                    'durum' => 'atlandi',
                    'hata' => 'Kişiselleştirilmiş mesaj 480 karakteri aşıyor',
                    'mesaj' => $kisiselMesaj,
                ];
                $sonucMesaj = 'SMS gönderilemedi: metin 480 karakteri aşıyor.';
            } else {
                $sonuc = $smsSender->send($telefon, $kisiselMesaj, [
                    'etkinlik_id' => $etkinlik->id,
                    'etkinlik_basvuru_id' => $basvuru->id,
                    'gonderen_id' => $request->user()?->id,
                ]);

                if ($sonuc['ok'] ?? false) {
                    $gonderilen = 1;
                    $detay[] = [
                        'basvuru_id' => $basvuru->id,
                        'ad' => $ad,
                        'telefon' => $telefon,
                        'durum' => 'gonderildi',
                        'mesaj' => $kisiselMesaj,
                    ];
                    $sonucMesaj = 'SMS gönderildi.';
                } else {
                    $atlanan = 1;
                    $detay[] = [
                        'basvuru_id' => $basvuru->id,
                        'ad' => $ad,
                        'telefon' => $telefon,
                        'durum' => 'atlandi',
                        'hata' => $sonuc['message'] ?? 'Gönderilemedi',
                        'mesaj' => $kisiselMesaj,
                    ];
                    $sonucMesaj = 'SMS gönderilemedi: '.($sonuc['message'] ?? 'bilinmeyen hata');
                }
            }
        }

        $kapsamEtiket = match ($kapsam) {
            'basvuru_iptal' => 'başvuru iptali',
            'basvuru_yedek' => 'başvuru yedeği',
            default => 'başvuru onayı',
        };

        $kayit = EtkinlikSmsGonderim::query()->create([
            'etkinlik_id' => $etkinlik->id,
            'gonderen_id' => $request->user()?->id,
            'mesaj' => $sablon,
            'kapsam' => $kapsam,
            'basvuru_durum_kod' => $basvuruDurumKod,
            'toplam' => 1,
            'gonderilen' => $gonderilen,
            'atlanan' => $atlanan,
            'detay' => $detay,
        ]);

        LogKaydedici::kaydet(
            islem: 'sms.gonderildi',
            aciklama: 'Etkinlik #'.$etkinlik->etkinlik_no.' '.$kapsamEtiket.' için SMS: '.$sonucMesaj,
            konu: $kayit,
            yeni: [
                'toplam' => $kayit->toplam,
                'gonderilen' => $kayit->gonderilen,
                'atlanan' => $kayit->atlanan,
            ],
        );

        return $sonucMesaj;
    }

    private function basvuruDurumEpostaGonder(
        Request $request,
        Etkinlik $etkinlik,
        EtkinlikBasvuru $basvuru,
        EmailSender $emailSender,
        SmsGonderimSecenegi $ayar,
        string $konuSablon,
        string $mesajSablon,
        bool $kullaniciIstek,
        string $kapsam = 'basvuru_onay',
        string $basvuruDurumKod = 'kesin_kayit',
    ): ?string {
        if (! $this->basvuruDurumSmsGonderilecekMi($ayar, $kullaniciIstek)) {
            return null;
        }

        $ad = $this->basvuruSahibiAdi($basvuru);
        $email = $this->basvuruEmail($basvuru);
        $gonderilen = 0;
        $atlanan = 0;
        $detay = [];

        if (! $email) {
            $atlanan = 1;
            $detay[] = [
                'basvuru_id' => $basvuru->id,
                'ad' => $ad,
                'email' => null,
                'durum' => 'atlandi',
                'hata' => 'E-posta yok',
            ];
            $sonucMesaj = 'E-posta gönderilemedi: e-posta yok.';
        } else {
            $kisiselKonu = $this->mesajKisisellestir($konuSablon, $ad);
            $kisiselMesaj = $this->mesajKisisellestir($mesajSablon, $ad);

            if (mb_strlen($kisiselKonu) > 200) {
                $atlanan = 1;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'email' => $email,
                    'durum' => 'atlandi',
                    'hata' => 'Kişiselleştirilmiş konu 200 karakteri aşıyor',
                    'konu' => $kisiselKonu,
                    'mesaj' => $kisiselMesaj,
                ];
                $sonucMesaj = 'E-posta gönderilemedi: konu 200 karakteri aşıyor.';
            } elseif (mb_strlen($kisiselMesaj) > 5000) {
                $atlanan = 1;
                $detay[] = [
                    'basvuru_id' => $basvuru->id,
                    'ad' => $ad,
                    'email' => $email,
                    'durum' => 'atlandi',
                    'hata' => 'Kişiselleştirilmiş mesaj 5000 karakteri aşıyor',
                    'konu' => $kisiselKonu,
                    'mesaj' => $kisiselMesaj,
                ];
                $sonucMesaj = 'E-posta gönderilemedi: metin 5000 karakteri aşıyor.';
            } else {
                $sonuc = $emailSender->send($email, $kisiselKonu, $kisiselMesaj, [
                    'etkinlik_id' => $etkinlik->id,
                    'etkinlik_basvuru_id' => $basvuru->id,
                    'gonderen_id' => $request->user()?->id,
                ]);

                if ($sonuc['ok'] ?? false) {
                    $gonderilen = 1;
                    $detay[] = [
                        'basvuru_id' => $basvuru->id,
                        'ad' => $ad,
                        'email' => $email,
                        'durum' => 'gonderildi',
                        'konu' => $kisiselKonu,
                        'mesaj' => $kisiselMesaj,
                    ];
                    $sonucMesaj = 'E-posta gönderildi.';
                } else {
                    $atlanan = 1;
                    $detay[] = [
                        'basvuru_id' => $basvuru->id,
                        'ad' => $ad,
                        'email' => $email,
                        'durum' => 'atlandi',
                        'hata' => $sonuc['message'] ?? 'Gönderilemedi',
                        'konu' => $kisiselKonu,
                        'mesaj' => $kisiselMesaj,
                    ];
                    $sonucMesaj = 'E-posta gönderilemedi: '.($sonuc['message'] ?? 'bilinmeyen hata');
                }
            }
        }

        $kapsamEtiket = match ($kapsam) {
            'basvuru_iptal' => 'başvuru iptali',
            'basvuru_yedek' => 'başvuru yedeği',
            default => 'başvuru onayı',
        };

        $kayit = EtkinlikEpostaGonderim::query()->create([
            'etkinlik_id' => $etkinlik->id,
            'gonderen_id' => $request->user()?->id,
            'konu' => $konuSablon,
            'mesaj' => $mesajSablon,
            'kapsam' => $kapsam,
            'basvuru_durum_kod' => $basvuruDurumKod,
            'toplam' => 1,
            'gonderilen' => $gonderilen,
            'atlanan' => $atlanan,
            'detay' => $detay,
        ]);

        LogKaydedici::kaydet(
            islem: 'eposta.gonderildi',
            aciklama: 'Etkinlik #'.$etkinlik->etkinlik_no.' '.$kapsamEtiket.' için e-posta: '.$sonucMesaj,
            konu: $kayit,
            yeni: [
                'toplam' => $kayit->toplam,
                'gonderilen' => $kayit->gonderilen,
                'atlanan' => $kayit->atlanan,
            ],
        );

        return $sonucMesaj;
    }

    private function basvuruEmail(EtkinlikBasvuru $basvuru): ?string
    {
        $email = $basvuru->basvuran?->email
            ?: $basvuru->kisi?->email
            ?: $basvuru->veli?->email;

        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function mesajKisisellestir(string $sablon, string $adSoyad): string
    {
        return str_ireplace('{ad_soyad}', $adSoyad, $sablon);
    }

    private function basvuruSahibiAdi(EtkinlikBasvuru $basvuru): string
    {
        return $basvuru->kisi?->tam_adi
            ?? $basvuru->basvuran?->tam_adi
            ?? ('Başvuru #'.$basvuru->id);
    }

    /**
     * @return array{all: array<string, string>, defaultVisible: list<string>, sortable: list<string>}
     */
    private function basvuruTableColumns(): array
    {
        return [
            'all' => [
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
                'katilimci', 'kimlik', 'telefon', 'durum', 'yedek_sira', 'katilim', 'basvuru_tarihi', 'islemler',
            ],
            'sortable' => [
                'basvuran', 'katilimci', 'veli', 'kimlik', 'dogum', 'telefon', 'ikamet',
                'durum', 'yedek_sira', 'onay', 'iptal', 'iptal_gerekce', 'kaydeden', 'basvuru_tarihi',
            ],
        ];
    }

    /**
     * @return array{0: LengthAwarePaginator|\Illuminate\Database\Eloquent\Relations\HasMany|Builder, 1: string, 2: string, 3: string}
     */
    private function searchEtkinlikBasvurular(Request $request, Etkinlik $etkinlik, bool $paginate = true): array
    {
        $basvuruDurumlari = EtkinlikBasvuruDurum::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->get();

        $basvuruDurum = (string) $request->input('basvuru_durum', 'tumu');
        $query = $etkinlik->basvurular()
            ->with(['kisi', 'basvuran', 'veli', 'durum', 'iptalGerekce', 'olusturan']);

        $seciliDurum = $basvuruDurumlari->firstWhere('kod', $basvuruDurum);
        if ($seciliDurum) {
            $query->where('durum_id', $seciliDurum->id);
        } else {
            $basvuruDurum = 'tumu';
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sortable = [
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

        if ($sort === '' && $basvuruDurum === 'yedek') {
            $sort = 'yedek_sira';
            $direction = 'asc';
        }

        if (isset($sortable[$sort])) {
            $query->reorder();

            if (in_array($sort, ['basvuran', 'katilimci', 'veli', 'kimlik', 'dogum', 'telefon', 'ikamet'], true)) {
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

        if ($paginate) {
            return [$query->paginate(20)->withQueryString(), $basvuruDurum, $sort, $direction];
        }

        return [$query, $basvuruDurum, $sort, $direction];
    }

    private function refreshEtkinlikSayaclari(Etkinlik $etkinlik): void
    {
        $kesinKayitId = EtkinlikBasvuruDurum::idByKod('kesin_kayit');
        $iptalId = EtkinlikBasvuruDurum::idByKod('iptal');

        $etkinlik->update([
            'basvuru_sayisi' => $etkinlik->basvurular()->count(),
            'kayit_sayisi' => $kesinKayitId ? $etkinlik->basvurular()->where('durum_id', $kesinKayitId)->count() : 0,
            'iptal_sayisi' => $iptalId ? $etkinlik->basvurular()->where('durum_id', $iptalId)->count() : 0,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, EtkinlikBasvuru>
     */
    private function yoklamaListesiForEtkinlik(Etkinlik $etkinlik, string $filtre = 'tumu')
    {
        $kesinKayitId = EtkinlikBasvuruDurum::idByKod('kesin_kayit');
        if (! $kesinKayitId) {
            return collect();
        }

        $list = $etkinlik->basvurular()
            ->where('durum_id', $kesinKayitId)
            ->with(['kisi', 'basvuran'])
            ->get()
            ->sortBy(fn (EtkinlikBasvuru $b) => mb_strtolower($this->basvuruSahibiAdi($b), 'UTF-8'), SORT_NATURAL)
            ->values();

        return match ($filtre) {
            'katildi' => $list->filter(fn (EtkinlikBasvuru $b) => $b->katilim_durumu?->value === 'katildi')->values(),
            'katilmadi' => $list->filter(fn (EtkinlikBasvuru $b) => $b->katilim_durumu?->value === 'katilmadi')->values(),
            'alinmayan' => $list->filter(fn (EtkinlikBasvuru $b) => $b->katilim_durumu === null)->values(),
            default => $list,
        };
    }

    public function mesajlar(Request $request, Etkinlik $etkinlik): JsonResponse
    {
        $basvuruDurumlari = EtkinlikBasvuruDurum::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->orderBy('ad')
            ->get();

        $mesajKanal = (string) $request->input('kanal', 'sms');
        if (! in_array($mesajKanal, ['sms', 'eposta'], true)) {
            $mesajKanal = 'sms';
        }

        return response()->json([
            'html' => view('etkinlikler._mesajlar_panel', [
                'etkinlik' => $etkinlik,
                'mesajKanal' => $mesajKanal,
                'basvuruDurumlari' => $basvuruDurumlari,
                'smsGonderimleri' => $etkinlik->smsGonderimleri()
                    ->with('gonderen')
                    ->latest()
                    ->limit(30)
                    ->get(),
                'epostaGonderimleri' => $etkinlik->epostaGonderimleri()
                    ->with('gonderen')
                    ->latest()
                    ->limit(30)
                    ->get(),
            ])->render(),
            'kanal' => $mesajKanal,
        ]);
    }

    public function yoklama(Etkinlik $etkinlik): JsonResponse
    {
        $yoklamaListesi = $this->yoklamaListesiForEtkinlik($etkinlik);

        return response()->json([
            'html' => view('etkinlikler._yoklama', [
                'etkinlik' => $etkinlik,
                'yoklamaListesi' => $yoklamaListesi,
            ])->render(),
            'total' => $yoklamaListesi->count(),
        ]);
    }

    public function exportYoklama(Request $request, Etkinlik $etkinlik): StreamedResponse
    {
        $filtre = (string) $request->input('filtre', 'tumu');
        if (! in_array($filtre, ['tumu', 'katildi', 'katilmadi', 'alinmayan'], true)) {
            $filtre = 'tumu';
        }

        $list = $this->yoklamaListesiForEtkinlik($etkinlik, $filtre);
        $filename = 'etkinlik-'.$etkinlik->etkinlik_no.'-yoklama-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($list) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Katılımcı', 'Kimlik No', 'Katılım'], ';');

            foreach ($list as $basvuru) {
                fputcsv($handle, [
                    $basvuru->kisi?->tam_adi ?? $basvuru->basvuran?->tam_adi ?? '',
                    $basvuru->kisi?->tc_kimlik_no ?? '',
                    $basvuru->katilim_durumu?->label() ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function etkinlikKosullarOzeti(Etkinlik $etkinlik): string
    {
        $parts = [];

        if ($etkinlik->minimum_yas !== null || $etkinlik->maksimum_yas !== null) {
            $parts[] = ($etkinlik->minimum_yas ?? '—').' - '.($etkinlik->maksimum_yas ?? '—').' yaş';
        }

        if ($etkinlik->cinsiyet_sarti) {
            $parts[] = $etkinlik->cinsiyet_sarti->label();
        }

        if ($etkinlik->ikamet_sarti === IkametSarti::Evet) {
            $parts[] = 'İkamet eden';
        } elseif ($etkinlik->ikamet_sarti === IkametSarti::Kismen) {
            $parts[] = 'Sınırlı ilçe dışı';
        }

        return $parts === [] ? 'Koşul tanımlanmamış' : implode('. ', $parts);
    }

    private function etkinlikBasvuruDonemindeMi(Etkinlik $etkinlik): bool
    {
        return $etkinlik->basvuruDonemindeMi();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEtkinlikRequest(Request $request, ?Etkinlik $etkinlik = null): array
    {
        if ($request->input('cinsiyet_sarti') === '') {
            $request->merge(['cinsiyet_sarti' => null]);
        }

        $validated = $request->validate([
            'ad' => ['required', 'string', 'max:255'],
            'aciklama' => ['nullable', 'string', 'max:50000'],
            'merkez_id' => ['required', 'exists:merkezler,id'],
            'etkinlik_tipi_id' => ['required', 'exists:etkinlik_tipleri,id'],
            'durum' => ['required', Rule::enum(EtkinlikDurum::class)],
            'onlinede_yayinlansin' => ['nullable', 'boolean'],
            'baslangic_tarihi' => ['required', 'date'],
            'bitis_tarihi' => ['required', 'date', 'after_or_equal:baslangic_tarihi'],
            'basvuru_baslama_tarihi' => ['required', 'date'],
            'basvuru_bitis_tarihi' => ['required', 'date', 'after_or_equal:basvuru_baslama_tarihi'],
            'kontenjan' => ['required', 'integer', 'min:0', 'max:9999'],
            'yedek_kontenjan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'minimum_yas' => ['nullable', 'integer', 'min:0', 'max:120'],
            'maksimum_yas' => [
                'nullable',
                'integer',
                'min:0',
                'max:120',
                Rule::when($request->filled('minimum_yas'), ['gte:minimum_yas']),
            ],
            'cinsiyet_sarti' => ['nullable', Rule::enum(Cinsiyet::class)],
            'ikamet_sarti' => ['required', Rule::enum(IkametSarti::class)],
            'ikamet_disi_kontenjan' => [
                Rule::requiredIf(fn () => $request->input('ikamet_sarti') === IkametSarti::Kismen->value),
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
            'evrak_tipi_ids' => ['nullable', 'array'],
            'evrak_tipi_ids.*' => ['integer', 'exists:evrak_tipleri,id'],
            'kurumlar' => ['nullable', 'array'],
            'kurumlar.*' => ['integer', 'exists:kurumlar,id'],
        ], [
            'bitis_tarihi.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
            'basvuru_bitis_tarihi.after_or_equal' => 'Başvuru bitiş tarihi başlangıçtan önce olamaz.',
            'maksimum_yas.gte' => 'Maksimum yaş, minimum yaştan küçük olamaz.',
            'ikamet_disi_kontenjan.required' => 'Sınırlı ilçe dışı seçildiğinde ikamet dışı kontenjan girilmelidir.',
        ]);

        $validated['aciklama'] = $this->normalizeRichText($validated['aciklama'] ?? null);

        $this->assertBasvuruTarihleriBitisindenSonraDegil($validated);

        return $validated;
    }

    private function normalizeRichText(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $trimmed = trim($html);
        if ($trimmed === '') {
            return null;
        }

        $plain = trim(html_entity_decode(strip_tags($trimmed), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plain = preg_replace('/\x{200B}/u', '', $plain) ?? $plain;

        if ($plain === '') {
            return null;
        }

        return $trimmed;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertBasvuruTarihleriBitisindenSonraDegil(array $validated): void
    {
        $bitis = Carbon::parse($validated['bitis_tarihi'])->endOfDay();
        $basvuruBaslama = Carbon::parse($validated['basvuru_baslama_tarihi']);
        $basvuruBitis = Carbon::parse($validated['basvuru_bitis_tarihi']);

        $errors = [];

        if ($basvuruBaslama->gt($bitis)) {
            $errors['basvuru_baslama_tarihi'] = 'Başvuru başlangıç tarihi etkinlik bitiş tarihinden sonra olamaz.';
        }

        if ($basvuruBitis->gt($bitis)) {
            $errors['basvuru_bitis_tarihi'] = 'Başvuru bitiş tarihi etkinlik bitiş tarihinden sonra olamaz.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistEtkinlik(Request $request, array $validated, ?Etkinlik $etkinlik = null): Etkinlik
    {
        $ikametSarti = IkametSarti::from($validated['ikamet_sarti']);
        $evrakTipiIds = array_values(array_unique(array_map('intval', $validated['evrak_tipi_ids'] ?? [])));
        $kurumIds = array_values(array_unique(array_map('intval', $validated['kurumlar'] ?? [])));

        $attributes = [
            'ad' => $validated['ad'],
            'aciklama' => $validated['aciklama'] ?? null,
            'merkez_id' => $validated['merkez_id'],
            'etkinlik_tipi_id' => $validated['etkinlik_tipi_id'],
            'durum' => $validated['durum'],
            'onlinede_yayinlansin' => $request->boolean('onlinede_yayinlansin'),
            'baslangic_tarihi' => $validated['baslangic_tarihi'],
            'bitis_tarihi' => $validated['bitis_tarihi'],
            'basvuru_baslama_tarihi' => $validated['basvuru_baslama_tarihi'],
            'basvuru_bitis_tarihi' => $validated['basvuru_bitis_tarihi'],
            'kontenjan' => $validated['kontenjan'],
            'yedek_kontenjan' => $validated['yedek_kontenjan'] ?? 0,
            'ikamet_disi_kontenjan' => $ikametSarti === IkametSarti::Kismen
                ? ($validated['ikamet_disi_kontenjan'] ?? 0)
                : 0,
            'minimum_yas' => $validated['minimum_yas'] ?? null,
            'maksimum_yas' => $validated['maksimum_yas'] ?? null,
            'cinsiyet_sarti' => $validated['cinsiyet_sarti'] ?? null,
            'ikamet_sarti' => $ikametSarti,
            'evrak_zorunlu' => $evrakTipiIds !== [],
            'guncelleyen_id' => $request->user()?->id,
        ];

        return DB::transaction(function () use ($attributes, $request, $etkinlik, $evrakTipiIds, $kurumIds) {
            if ($etkinlik) {
                // etkinlik_no oluşturulduktan sonra değiştirilmez.
                $etkinlik->update($attributes);
            } else {
                $attributes['etkinlik_no'] = app(NumaratorServisi::class)->sonraki(
                    NumaratorServisi::ETKINLIK,
                    fn (string $no) => Etkinlik::query()->where('etkinlik_no', $no)->exists(),
                );
                $etkinlik = Etkinlik::create($attributes + [
                    'olusturan_id' => $request->user()?->id,
                ]);
            }

            $etkinlik->evrakTipleri()->sync($evrakTipiIds);
            $etkinlik->syncKurumlar($kurumIds);

            return $etkinlik->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function etkinlikSnapshot(Etkinlik $etkinlik): array
    {
        return [
            'etkinlik_no' => $etkinlik->etkinlik_no,
            'ad' => $etkinlik->ad,
            'durum' => $etkinlik->durum instanceof EtkinlikDurum ? $etkinlik->durum->value : $etkinlik->durum,
            'merkez_id' => $etkinlik->merkez_id,
            'etkinlik_tipi_id' => $etkinlik->etkinlik_tipi_id,
            'kontenjan' => $etkinlik->kontenjan,
            'yedek_kontenjan' => $etkinlik->yedek_kontenjan,
            'baslangic_tarihi' => $etkinlik->baslangic_tarihi?->toDateString(),
            'bitis_tarihi' => $etkinlik->bitis_tarihi?->toDateString(),
            'basvuru_baslama_tarihi' => $etkinlik->basvuru_baslama_tarihi?->toDateTimeString(),
            'basvuru_bitis_tarihi' => $etkinlik->basvuru_bitis_tarihi?->toDateTimeString(),
            'onlinede_yayinlansin' => (bool) $etkinlik->onlinede_yayinlansin,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function etkinlikOzet(): array
    {
        $query = Etkinlik::query();
        $user = request()->user();

        $this->applyEtkinlikScope($query, $user);

        return [
            'toplam' => (clone $query)->count(),
            'aktif' => (clone $query)->where('durum', EtkinlikDurum::Aktif)->count(),
            'hazirlik' => (clone $query)->where('durum', EtkinlikDurum::Hazirlik)->count(),
            'basvuruya_acik' => (clone $query)->basvuruDurumu('acik')->count(),
        ];
    }

    /**
     * @param  Builder<Etkinlik>  $query
     */
    private function applyEtkinlikScope(Builder $query, ?User $user): void
    {
        if ($user && $user->sadeceAtananEtkinlikleriGorur()) {
            $query->whereHas('sorumlular', fn ($q) => $q->where('users.id', $user->id));
        }

        if ($user && $user->sadeceYetkiliMerkezleriGorurEtkinlik()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('etkinlikler.merkez_id', $merkezIds);
            }
        }

        $query->kullaniciKurumKapsami($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function formLookups(): array
    {
        return [
            'merkezler' => Merkez::query()->kullaniciKapsami(request()->user())->where('aktif', true)->orderBy('ad')->get(),
            'etkinlikTipleri' => EtkinlikTipi::where('aktif', true)->orderBy('ad')->get(),
            'durumlar' => EtkinlikDurum::cases(),
            'cinsiyetler' => Cinsiyet::cases(),
            'ikametSartlari' => IkametSarti::cases(),
            'evrakTipleri' => EvrakTipi::where('aktif', true)->orderBy('ad')->get(),
            'kurumlar' => Kurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
        ];
    }

    private function applyTextModeFilter($query, string $column, ?string $value, string $mode): void
    {
        if ($value === null || $value === '') {
            return;
        }

        match ($mode) {
            'starts' => $query->where($column, 'like', $value.'%'),
            'ends' => $query->where($column, 'like', '%'.$value),
            'exact' => $query->where($column, $value),
            default => $query->where($column, 'like', '%'.$value.'%'),
        };
    }

    private function applyEtkinlikNoFilter($query, Request $request): void
    {
        if (! $request->filled('etkinlik_no')) {
            return;
        }

        $this->applyTextModeFilter(
            $query,
            'etkinlik_no',
            (string) $request->string('etkinlik_no'),
            (string) $request->input('etkinlik_no_mode', 'exact'),
        );
    }

    private function applyIndexFilters($query, Request $request): void
    {
        if ($request->filled('ad')) {
            $this->applyTextModeFilter(
                $query,
                'ad',
                (string) $request->string('ad'),
                (string) $request->input('ad_mode', 'contains'),
            );
        }

        if ($request->filled('merkez_id')) {
            $query->where('merkez_id', $request->integer('merkez_id'));
        }

        if ($request->filled('kurum_id')) {
            $kurumId = $request->integer('kurum_id');
            $query->whereHas('kurumlar', fn ($q) => $q->where('kurumlar.id', $kurumId));
        }

        if ($request->filled('etkinlik_tipi_id')) {
            $query->where('etkinlik_tipi_id', $request->integer('etkinlik_tipi_id'));
        }

        $durum = (string) $request->input('durum', '');
        if ($durum !== '' && $durum !== 'tumu') {
            $query->where('durum', $durum);
        }

        $basvuruDurumu = (string) $request->input('basvuru_durumu', 'tumu');
        if ($basvuruDurumu !== '' && $basvuruDurumu !== 'tumu') {
            $query->basvuruDurumu($basvuruDurumu);
        }
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function searchEtkinlikler(Request $request): array
    {
        $query = Etkinlik::query()
            ->with(['merkez', 'etkinlikTipi', 'kurumlar'])
            ->select('etkinlikler.*');

        $user = $request->user();
        $this->applyEtkinlikScope($query, $user);
        $this->applyEtkinlikNoFilter($query, $request);
        $this->applyIndexFilters($query, $request);

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $sortable = [
            'no' => 'etkinlikler.etkinlik_no',
            'ad' => 'etkinlikler.ad',
            'merkez' => 'merkezler.ad',
            'tip' => 'etkinlik_tipleri.ad',
            'baslangic' => 'etkinlikler.baslangic_tarihi',
            'bitis' => 'etkinlikler.bitis_tarihi',
            'basvuru' => 'etkinlikler.basvuru_sayisi',
            'kayit' => 'etkinlikler.kayit_sayisi',
            'iptal' => 'etkinlikler.iptal_sayisi',
            'kontenjan' => 'etkinlikler.kontenjan',
            'yedek' => 'etkinlikler.yedek_kontenjan',
            'durum' => 'etkinlikler.durum',
            'tarih' => 'etkinlikler.created_at',
        ];

        if ($sort === 'basvuru_durumu') {
            $now = now()->toDateTimeString();
            $directionSql = $direction === 'asc' ? 'ASC' : 'DESC';
            $query->orderByRaw(
                'CASE
                    WHEN etkinlikler.onlinede_yayinlansin = 0 OR etkinlikler.basvuru_baslama_tarihi IS NULL OR etkinlikler.basvuru_bitis_tarihi IS NULL THEN 3
                    WHEN etkinlikler.basvuru_baslama_tarihi > ? THEN 1
                    WHEN etkinlikler.basvuru_bitis_tarihi < ? THEN 2
                    ELSE 0
                END '.$directionSql,
                [$now, $now]
            );
        } elseif (isset($sortable[$sort])) {
            if (in_array($sort, ['merkez', 'tip'], true)) {
                $query
                    ->leftJoin('merkezler', 'merkezler.id', '=', 'etkinlikler.merkez_id')
                    ->leftJoin('etkinlik_tipleri', 'etkinlik_tipleri.id', '=', 'etkinlikler.etkinlik_tipi_id');
            }

            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->latest('etkinlikler.created_at');
            $sort = '';
            $direction = 'desc';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $etkinlikler = $query->paginate($perPage)->withQueryString();

        $filters = $request->only([
            'ad', 'ad_mode', 'etkinlik_no', 'etkinlik_no_mode', 'merkez_id', 'kurum_id', 'etkinlik_tipi_id', 'durum', 'basvuru_durumu', 'per_page', 'sort', 'direction',
        ]);

        return [$etkinlikler, $sort, $direction, $filters];
    }
}
