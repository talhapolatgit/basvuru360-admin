<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Enums\HaftaGunu;
use App\Enums\IkametSarti;
use App\Enums\KursDurum;
use App\Enums\SmsGonderimSecenegi;
use App\Enums\YoklamaDurum;
use App\Models\Alan;
use App\Models\BasariDurum;
use App\Models\BasvuruDurum;
use App\Models\Brans;
use App\Models\EpostaLog;
use App\Models\EvrakTipi;
use App\Models\IptalGerekce;
use App\Models\Kisi;
use App\Models\Kurs;
use App\Models\KursDers;
use App\Models\KursTipi;
use App\Models\KursYoklama;
use App\Models\KursBasvuru;
use App\Models\KursBasvuruEvrak;
use App\Models\KursEpostaGonderim;
use App\Models\KursSmsGonderim;
use App\Models\Kurum;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\Merkez;
use App\Services\Email\EmailSender;
use App\Services\KursAyarServisi;
use App\Services\KursDersOlusturucu;
use App\Services\KursYedekListeServisi;
use App\Services\LogKaydedici;
use App\Services\SertifikaPdfOlusturucu;
use App\Services\NumaratorServisi;
use App\Services\Sms\PhoneNormalizer;
use App\Services\Sms\SmsSender;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KursController extends Controller implements HasMiddleware
{
    /**
     * @return list<Middleware|string>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('kurs.kapsam', except: ['index', 'create', 'store', 'export']),
        ];
    }

    public function index(Request $request): View
    {
        // Eksik durum = varsayılan Tüm Kurslar.
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        if (! $request->filled('basvuru_durumu')) {
            $request->merge(['basvuru_durumu' => 'tumu']);
        }

        [$kurslar, $sort, $direction, $filters] = $this->searchKurslar($request);

        $viewData = [
            'kurslar' => $kurslar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
            'defaultVisible' => ['no', 'brans', 'merkez', 'gunler', 'basvuru', 'kayit', 'durum', 'basvuru_durumu', 'islemler'],
            'allColumns' => [
                'no' => 'No',
                'alan' => 'Alan',
                'brans' => 'Branş',
                'merkez' => 'Merkez',
                'kurum' => 'Kurum',
                'baslama' => 'Başlama',
                'bitis' => 'Bitiş',
                'gunler' => 'Günler',
                'basvuru' => 'Başvuru',
                'kayit' => 'Kayıt',
                'iptal' => 'İptal',
                'kontenjan' => 'Kontenjan',
                'yedek' => 'Yedek',
                'egitmen' => 'Eğitmen',
                'belge' => 'Belge Türü',
                'durum' => 'Durum',
                'basvuru_durumu' => 'Başvuru Durumu',
                'tarih' => 'Tarih',
                'islemler' => 'İşlemler',
            ],
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('kurslar._results', $viewData);
        }

        return view('kurslar.index', $viewData + [
            'alanlar' => Alan::where('aktif', true)->orderBy('ad')->get(),
            'branslar' => Brans::where('aktif', true)->orderBy('ad')->get(),
            'merkezler' => Merkez::query()->kullaniciKapsami($request->user())->where('aktif', true)->orderBy('ad')->get(),
            'kurumlar' => Kurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
            'kursTipleri' => KursTipi::where('aktif', true)->orderBy('ad')->get(),
            'ogretmenler' => $this->filtreOgretmenleri(),
            'durumlar' => KursDurum::cases(),
            'basvuruDurumlari' => [
                'acik' => 'Açık',
                'yakinda' => 'Yakında',
                'kapandi' => 'Kapandı',
                'kapali' => 'Kapalı',
            ],
            'ozet' => $this->kursOzet(),
        ]);
    }

    /**
     * "Öğretmen" filtresi için: rolü öğretmen olan veya en az bir kursa
     * öğretmen olarak atanmış (rolü sonradan değişmiş olsa da) kullanıcılar.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function filtreOgretmenleri(): \Illuminate\Support\Collection
    {
        return User::query()
            ->egitmen()
            ->where('aktif', true)
            ->orderBy('ad')
            ->orderBy('soyad')
            ->get();
    }

    public function create(): View
    {
        return view('kurslar.create', $this->formLookups());
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validateKursRequest($request);
        $kurs = $this->persistKurs($request, $validated);

        LogKaydedici::kaydet(
            islem: 'kurs.olusturuldu',
            kurs: $kurs,
            aciklama: 'Kurs #'.$kurs->kurs_no.' oluşturuldu.',
            konu: $kurs,
            yeni: $this->kursSnapshot($kurs),
        );

        $message = 'Kurs #'.$kurs->kurs_no.' başarıyla oluşturuldu.';

        if ($request->expectsJson() || $request->ajax()) {
            session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'redirect' => route('kurslar.show', $kurs),
            ]);
        }

        return redirect()
            ->route('kurslar.show', $kurs)
            ->with('success', $message);
    }

    public function show(Request $request, Kurs $kurs): View
    {
        $kurs->load([
            'merkez',
            'alan',
            'brans',
            'kursTipi',
            'ogretmenler',
            'egitimDurumu',
            'gunler',
            'evrakTipleri',
            'kurumlar',
            'olusturan',
            'guncelleyen',
        ]);

        $basvuruDurumlari = BasvuruDurum::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->orderBy('ad')
            ->get();

        $onayBekliyorId = $basvuruDurumlari->firstWhere('kod', 'onay_bekliyor')?->id;
        $kesinKayitId = $basvuruDurumlari->firstWhere('kod', 'kesin_kayit')?->id;
        $yedekId = $basvuruDurumlari->firstWhere('kod', 'yedek')?->id;
        $iptalId = $basvuruDurumlari->firstWhere('kod', 'iptal')?->id;

        $basvuruOzet = [
            'aktif' => $kurs->basvurular()->whereIn('durum_id', array_filter([$onayBekliyorId, $kesinKayitId, $yedekId]))->count(),
            'kayit' => $kesinKayitId ? $kurs->basvurular()->where('durum_id', $kesinKayitId)->count() : 0,
            'beklemede' => $onayBekliyorId ? $kurs->basvurular()->where('durum_id', $onayBekliyorId)->count() : 0,
            'iptal' => $iptalId ? $kurs->basvurular()->where('durum_id', $iptalId)->count() : 0,
            'toplam' => $kurs->basvurular()->count(),
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

        app(KursDersOlusturucu::class)->sync($kurs, $request->user()?->id);

        $dersTakvim = $kurs->dersler()
            ->orderBy('tarih')
            ->orderBy('baslangic_saati')
            ->get();

        return view('kurslar.show', [
            'kurs' => $kurs,
            'basvuruOzet' => $basvuruOzet,
            'basvuruDurum' => $basvuruDurum,
            'basvuruDurumlari' => $basvuruDurumlari,
            'basvuruColumns' => $basvuruColumns['all'],
            'basvuruDefaultVisible' => $basvuruColumns['defaultVisible'],
            'basariDurumlari' => BasariDurum::query()->where('aktif', true)->orderBy('sira')->get(),
            'iptalGerekceleri' => IptalGerekce::query()->where('aktif', true)->orderBy('sira')->get(),
            'smsBasvuruOnayAyar' => app(KursAyarServisi::class)->smsBasvuruOnay()->value,
            'smsBasvuruIptalAyar' => app(KursAyarServisi::class)->smsBasvuruIptal()->value,
            'smsBasvuruYedekAyar' => app(KursAyarServisi::class)->smsBasvuruYedek()->value,
            'epostaBasvuruOnayAyar' => app(KursAyarServisi::class)->epostaBasvuruOnay()->value,
            'epostaBasvuruIptalAyar' => app(KursAyarServisi::class)->epostaBasvuruIptal()->value,
            'epostaBasvuruYedekAyar' => app(KursAyarServisi::class)->epostaBasvuruYedek()->value,
            'activeTab' => $activeTab,
            'activeMesajKanal' => $activeMesajKanal,
            'kosullarOzeti' => $this->kursKosullarOzeti($kurs),
            'yayinlanabilir' => $kurs->basvuruDonemindeMi(),
            'dersTakvim' => $dersTakvim,
            'ogretmenler' => User::query()
                ->egitmen()
                ->where('aktif', true)
                ->orderBy('ad')
                ->orderBy('soyad')
                ->get(),
            'smsGonderimleri' => $kurs->smsGonderimleri()
                ->with('gonderen')
                ->latest()
                ->limit(30)
                ->get(),
            'epostaGonderimleri' => $kurs->epostaGonderimleri()
                ->with('gonderen')
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function storeBasvuruEvrak(Request $request, Kurs $kurs, KursBasvuru $basvuru): JsonResponse
    {
        abort_unless((int) $basvuru->kurs_id === (int) $kurs->id, 404);
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

        DB::transaction(function () use ($request, $basvuru, $kurs, $file, $tipId) {
            $path = $file->store('basvuru-evraklari/'.$basvuru->id, 'public');

            $kayit = KursBasvuruEvrak::query()->create([
                'kurs_basvuru_id' => $basvuru->id,
                'evrak_tipi_id' => $tipId,
                'dosya_yolu' => $path,
                'orijinal_ad' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'boyut' => $file->getSize() ?: 0,
                'olusturan_id' => $request->user()?->id,
            ]);
            $kayit->load(['evrakTipi', 'olusturan']);

            LogKaydedici::kaydet(
                islem: 'basvuru.evrak_yuklendi',
                kurs: $kurs,
                aciklama: ($basvuru->kisi?->tam_adi ?? 'Başvuru').' için evrak yüklendi: '.($kayit->evrakTipi?->ad ?? 'Evrak'),
                konu: $basvuru,
                yeni: [
                    'evrak_id' => $kayit->id,
                    'evrak_tipi_id' => $kayit->evrak_tipi_id,
                ],
            );
        });

        return response()->json([
            'message' => 'Evrak yüklendi.',
            'evraklar' => $this->basvuruEvraklariPayload($kurs, $basvuru),
        ]);
    }

    public function destroyBasvuruEvrak(Request $request, Kurs $kurs, KursBasvuru $basvuru, KursBasvuruEvrak $evrak): JsonResponse
    {
        abort_unless((int) $basvuru->kurs_id === (int) $kurs->id, 404);
        abort_unless((int) $evrak->kurs_basvuru_id === (int) $basvuru->id, 404);

        $basvuru->loadMissing('kisi');
        $evrak->loadMissing('evrakTipi');

        DB::transaction(function () use ($request, $kurs, $basvuru, $evrak) {
            $evrak->update([
                'silen_id' => $request->user()?->id,
            ]);
            $evrak->delete();

            LogKaydedici::kaydet(
                islem: 'basvuru.evrak_silindi',
                kurs: $kurs,
                aciklama: ($basvuru->kisi?->tam_adi ?? 'Başvuru').' için evrak silindi: '.($evrak->evrakTipi?->ad ?? 'Evrak'),
                konu: $basvuru,
                eski: [
                    'evrak_id' => $evrak->id,
                    'evrak_tipi_id' => $evrak->evrak_tipi_id,
                ],
            );
        });

        return response()->json([
            'message' => 'Evrak silindi.',
            'evraklar' => $this->basvuruEvraklariPayload($kurs, $basvuru),
        ]);
    }

    public function showBasvuruEvrak(Kurs $kurs, KursBasvuru $basvuru, KursBasvuruEvrak $evrak): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless((int) $basvuru->kurs_id === (int) $kurs->id, 404);
        abort_unless((int) $evrak->kurs_basvuru_id === (int) $basvuru->id, 404);
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
    private function basvuruEvraklariPayload(Kurs $kurs, KursBasvuru $basvuru)
    {
        $user = request()->user();
        $canView = (bool) $user?->hasYetki('basvuru.evrak_goruntule');
        $canDelete = (bool) $user?->hasYetki('basvuru.evrak_sil');

        return $basvuru->evraklar()
            ->with(['evrakTipi', 'olusturan'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (KursBasvuruEvrak $item) => [
                'id' => $item->id,
                'tip' => $item->evrakTipi?->ad ?? 'Evrak',
                'aciklama' => $item->evrakTipi?->aciklama,
                'mime' => $item->mime,
                'boyut' => (int) ($item->boyut ?? 0),
                'url' => $canView
                    ? route('kurslar.basvurular.evrak.show', [$kurs, $basvuru, $item])
                    : null,
                'delete_url' => $canDelete
                    ? route('kurslar.basvurular.evrak.destroy', [$kurs, $basvuru, $item])
                    : null,
                'yukleyen' => $item->olusturan?->tam_adi,
                'yuklenme_tarihi' => $item->created_at?->format('d.m.Y H:i'),
            ])
            ->values();
    }

    public function showBasvuru(Kurs $kurs, KursBasvuru $basvuru): View
    {
        abort_unless((int) $basvuru->kurs_id === (int) $kurs->id, 404);

        $basvuru->load([
            'kisi',
            'basvuran',
            'veli',
            'durum',
            'basariDurum',
            'iptalGerekce',
            'olusturan',
            'onaylayan',
            'iptalEden',
            'kurs.merkez',
            'kurs.brans',
            'kurs.alan',
            'kurs.kursTipi',
            'kurs.ogretmenler',
        ]);

        $yoklamalar = $basvuru->yoklamalar()
            ->with('ders')
            ->get()
            ->sortBy(function (KursYoklama $yoklama) {
                $tarih = $yoklama->ders?->tarih?->format('Y-m-d') ?? '9999-99-99';
                $saat = (string) ($yoklama->ders?->baslangic_saati ?? '99:99');

                return $tarih.' '.$saat;
            })
            ->values();

        $smsLoglari = SmsLog::query()
            ->where('basvuru_id', $basvuru->id)
            ->with('gonderen')
            ->latest()
            ->limit(50)
            ->get();

        $epostaLoglari = EpostaLog::query()
            ->where('basvuru_id', $basvuru->id)
            ->with('gonderen')
            ->latest()
            ->limit(50)
            ->get();

        $evraklarPayload = $this->basvuruEvraklariPayload($kurs, $basvuru);
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

        return view('kurslar.basvuru-show', [
            'kurs' => $kurs,
            'basvuru' => $basvuru,
            'yoklamalar' => $yoklamalar,
            'smsLoglari' => $smsLoglari,
            'epostaLoglari' => $epostaLoglari,
            'evraklarPayload' => $evraklarPayload,
            'evrakTipOptions' => $evrakTipOptions,
            'basariDurumlari' => BasariDurum::query()->where('aktif', true)->orderBy('sira')->get(),
            'iptalGerekceleri' => IptalGerekce::query()->where('aktif', true)->orderBy('sira')->get(),
            'smsBasvuruOnayAyar' => app(KursAyarServisi::class)->smsBasvuruOnay()->value,
            'smsBasvuruIptalAyar' => app(KursAyarServisi::class)->smsBasvuruIptal()->value,
            'smsBasvuruYedekAyar' => app(KursAyarServisi::class)->smsBasvuruYedek()->value,
            'epostaBasvuruOnayAyar' => app(KursAyarServisi::class)->epostaBasvuruOnay()->value,
            'epostaBasvuruIptalAyar' => app(KursAyarServisi::class)->epostaBasvuruIptal()->value,
            'epostaBasvuruYedekAyar' => app(KursAyarServisi::class)->epostaBasvuruYedek()->value,
        ]);
    }

    public function basvurular(Request $request, Kurs $kurs): JsonResponse
    {
        [$basvurular, $basvuruDurum, $sort, $direction] = $this->searchKursBasvurular($request, $kurs);
        $columns = $this->basvuruTableColumns();

        return response()->json([
            'html' => view('kurslar._basvurular_list', [
                'kurs' => $kurs,
                'basvurular' => $basvurular,
                'basvuruColumns' => $columns['all'],
                'basvuruDefaultVisible' => $columns['defaultVisible'],
                'basvuruSortable' => $columns['sortable'],
                'sort' => $sort,
                'direction' => $direction,
            ])->render(),
            'total' => $basvurular->total(),
            'durum' => $basvuruDurum,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function exportBasvurular(Request $request, Kurs $kurs): StreamedResponse
    {
        [$basvurular] = $this->searchKursBasvurular($request, $kurs, paginate: false);

        $filename = 'kurs-'.$kurs->kurs_no.'-basvurular-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($basvurular) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Başvuran', 'Katılımcı', 'Veli', 'Kimlik No', 'Doğum T.', 'Telefon', 'İkamet',
                'Durum', 'Yedek Sıra', 'Başarı', 'Yoklama', 'Kursa Başlama', 'Onay Tarihi', 'İptal Tarihi', 'İptal Gerekçesi', 'Kaydeden', 'Başvuru Tarihi',
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
                        $basvuru->durum?->kod === 'yedek' ? ($basvuru->yedek_sira ?? '') : '',
                        $basvuru->basariDurum?->ad ?? '',
                        ! is_null($basvuru->yoklamaVarOrani()) ? '%'.$basvuru->yoklamaVarOrani() : '',
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

    /**
     * @return array{0: \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\HasMany, 1: string, 2: string, 3: string}
     */
    private function searchKursBasvurular(Request $request, Kurs $kurs, bool $paginate = true): array
    {
        $basvuruDurumlari = BasvuruDurum::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->get();

        $basvuruDurum = (string) $request->input('basvuru_durum', 'tumu');
        $query = $kurs->basvurular()
            ->with([
                'kisi', 'basvuran', 'veli', 'durum', 'basariDurum', 'iptalGerekce', 'olusturan',
                'evraklar' => fn ($q) => $q->with(['evrakTipi', 'olusturan'])->orderBy('created_at'),
                'yoklamalar' => fn ($q) => $q->select('id', 'kurs_basvuru_id', 'kurs_ders_id', 'saatlik_durumlar', 'durum')
                    ->with('ders:id,iptal_edildi'),
            ]);

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

        if ($paginate) {
            return [$query->paginate(20)->withQueryString(), $basvuruDurum, $sort, $direction];
        }

        return [$query, $basvuruDurum, $sort, $direction];
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
                'basari' => 'Başarı',
                'yoklama' => 'Yoklama',
                'kursa_baslama' => 'Kursa Başlama',
                'onay' => 'Onay Tarihi',
                'iptal' => 'İptal Tarihi',
                'iptal_gerekce' => 'İptal Gerekçesi',
                'kaydeden' => 'Kaydeden',
                'basvuru_tarihi' => 'Başvuru Tarihi',
                'islemler' => 'İşlemler',
            ],
            'defaultVisible' => [
                'katilimci', 'kimlik', 'telefon', 'durum', 'yedek_sira', 'basari', 'yoklama', 'kursa_baslama', 'basvuru_tarihi', 'islemler',
            ],
            'sortable' => [
                'basvuran', 'katilimci', 'veli', 'kimlik', 'dogum', 'telefon', 'ikamet',
                'durum', 'yedek_sira', 'basari', 'kursa_baslama', 'onay', 'iptal', 'iptal_gerekce', 'kaydeden', 'basvuru_tarihi',
            ],
        ];
    }

    public function mesajlar(Request $request, Kurs $kurs): JsonResponse
    {
        $basvuruDurumlari = BasvuruDurum::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->orderBy('ad')
            ->get();

        $mesajKanal = (string) $request->input('kanal', 'sms');
        if (! in_array($mesajKanal, ['sms', 'eposta'], true)) {
            $mesajKanal = 'sms';
        }

        return response()->json([
            'html' => view('kurslar._mesajlar_panel', [
                'kurs' => $kurs,
                'mesajKanal' => $mesajKanal,
                'basvuruDurumlari' => $basvuruDurumlari,
                'smsGonderimleri' => $kurs->smsGonderimleri()
                    ->with('gonderen')
                    ->latest()
                    ->limit(30)
                    ->get(),
                'epostaGonderimleri' => $kurs->epostaGonderimleri()
                    ->with('gonderen')
                    ->latest()
                    ->limit(30)
                    ->get(),
            ])->render(),
            'kanal' => $mesajKanal,
        ]);
    }

    public function yoklamalar(Request $request, Kurs $kurs): JsonResponse
    {
        app(KursDersOlusturucu::class)->sync($kurs, $request->user()?->id);

        $bugun = now()->toDateString();

        if ($request->filled('ders')) {
            $seciliDers = $kurs->dersler()
                ->whereKey($request->integer('ders'))
                ->whereDate('tarih', '<=', $bugun)
                ->firstOrFail();

            abort_if($seciliDers->iptal_edildi, 422, 'İptal edilen bir ders için yoklama alınamaz.');

            $yoklamaDuzenleyebilir = (bool) $request->user()?->hasYetki('kurs.yoklama');
            abort_unless(
                $yoklamaDuzenleyebilir || $seciliDers->yoklama_alindi,
                403,
                'Bu ders için yoklama alma yetkiniz bulunmuyor.'
            );

            $yoklamaKayitlari = $this->yoklamaListesiForDers($kurs, $seciliDers);
            $saatAdedi = $seciliDers->saatAdedi();

            return response()->json([
                'view' => 'form',
                'ders_id' => $seciliDers->id,
                'html' => view('kurslar._yoklama_form', [
                    'kurs' => $kurs,
                    'seciliDers' => $seciliDers,
                    'yoklamaKayitlari' => $yoklamaKayitlari,
                    'yoklamaDurumlari' => YoklamaDurum::cases(),
                    'saatAdedi' => $saatAdedi,
                    'yoklamaDuzenleyebilir' => $yoklamaDuzenleyebilir,
                ])->render(),
            ]);
        }

        $dersler = $kurs->dersler()
            ->whereDate('tarih', '<=', $bugun)
            ->with([
                'yoklamaAlan',
                'yoklamalar' => fn ($q) => $this->scopeYoklamaAktifBasvuru($q),
            ])
            ->orderByDesc('tarih')
            ->orderByDesc('baslangic_saati')
            ->get()
            ->each(function (KursDers $ders) {
                $ogrenciSayisi = $ders->yoklamalar->count();
                $katilanSayisi = $ders->yoklamalar
                    ->filter(fn (KursYoklama $y) => $y->herhangiBirSaatteVarMi())
                    ->count();

                $ders->setAttribute('yoklamalar_count', $ogrenciSayisi);
                $ders->setAttribute('var_sayisi', $katilanSayisi);
            });

        return response()->json([
            'view' => 'list',
            'html' => view('kurslar._yoklamalar_list', [
                'kurs' => $kurs,
                'dersler' => $dersler,
            ])->render(),
        ]);
    }

    public function exportProgram(Kurs $kurs): StreamedResponse
    {
        $kurs->loadMissing('gunler');

        $gunler = $kurs->gunler->sortBy(fn ($g) => $g->gun?->sira() ?? 99)->values();
        $filename = 'kurs-'.$kurs->kurs_no.'-ders-programi-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($gunler) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Gün', 'Başlangıç', 'Bitiş', 'Ders Saati', 'Sınıf'], ';');

            foreach ($gunler as $gun) {
                fputcsv($handle, [
                    $gun->gun?->label() ?? '',
                    substr((string) $gun->baslangic_saati, 0, 5),
                    substr((string) $gun->bitis_saati, 0, 5),
                    rtrim(rtrim(number_format((float) $gun->ders_saati, 1, '.', ''), '0'), '.'),
                    $gun->sinif ?: '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportTakvim(Request $request, Kurs $kurs): StreamedResponse
    {
        app(KursDersOlusturucu::class)->sync($kurs, $request->user()?->id);

        $dersler = $kurs->dersler()
            ->orderBy('tarih')
            ->orderBy('baslangic_saati')
            ->get();

        $bugun = now()->toDateString();
        $filename = 'kurs-'.$kurs->kurs_no.'-takvim-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($dersler, $bugun) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Tarih', 'Gün', 'Başlangıç', 'Bitiş', 'Ders Saati', 'Sınıf', 'Yoklama'], ';');

            foreach ($dersler as $ders) {
                $tarihStr = $ders->tarih?->toDateString();
                $yoklama = $ders->yoklama_alindi
                    ? 'Alındı'
                    : ($tarihStr && $tarihStr <= $bugun ? 'Bekliyor' : 'Planlandı');

                fputcsv($handle, [
                    $ders->tarih?->format('d.m.Y') ?? '',
                    $ders->tarih?->locale('tr')->isoFormat('dddd') ?? '',
                    substr((string) $ders->baslangic_saati, 0, 5),
                    substr((string) $ders->bitis_saati, 0, 5),
                    rtrim(rtrim(number_format((float) $ders->ders_saati, 1, '.', ''), '0'), '.'),
                    $ders->sinif ?: '',
                    $yoklama,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportTakvimPdf(Request $request, Kurs $kurs): Response
    {
        app(KursDersOlusturucu::class)->sync($kurs, $request->user()?->id);
        $kurs->loadMissing(['merkez', 'alan', 'brans', 'ogretmenler']);

        $dersler = $kurs->dersler()
            ->orderBy('tarih')
            ->orderBy('baslangic_saati')
            ->get();

        $ay = (string) $request->input('ay', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $ay)) {
            $ay = now()->format('Y-m');
        }

        [$yil, $ayNo] = array_map('intval', explode('-', $ay));
        $ayBaslangic = Carbon::create($yil, $ayNo, 1)->startOfDay();
        $ayBitis = $ayBaslangic->copy()->endOfMonth();

        $aylikDersler = $dersler->filter(function ($ders) use ($ayBaslangic, $ayBitis) {
            if (! $ders->tarih) {
                return false;
            }

            return $ders->tarih->betweenIncluded($ayBaslangic, $ayBitis);
        })->values();

        $byDate = $aylikDersler->groupBy(fn ($ders) => $ders->tarih->format('Y-m-d'));

        $filename = 'kurs-'.$kurs->kurs_no.'-takvim-'.$ay.'.pdf';

        $pdf = Pdf::loadView('kurslar.takvim_pdf', [
            'kurs' => $kurs,
            'ayBaslangic' => $ayBaslangic,
            'ayBitis' => $ayBitis,
            'byDate' => $byDate,
            'aylikDersler' => $aylikDersler,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    public function exportSertifikalar(Request $request, Kurs $kurs): Response|RedirectResponse
    {
        $belgeler = app(SertifikaPdfOlusturucu::class)->belgeler($kurs);

        if ($belgeler->isEmpty()) {
            return back()->with('error', 'Sertifika veya katılım belgesi hak eden başvuru bulunamadı.');
        }

        $filename = 'kurs-'.$kurs->kurs_no.'-sertifikalar-'.now()->format('Ymd-His').'.pdf';

        $pdf = Pdf::loadView('kurslar.sertifika_pdf', [
            'kurs' => $kurs,
            'belgeler' => $belgeler,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    public function exportYoklamaFormu(Request $request, Kurs $kurs, KursDers $ders): Response
    {
        abort_unless($ders->kurs_id === $kurs->id, 404);
        abort_unless($ders->tarih && $ders->tarih->toDateString() <= now()->toDateString(), 404);

        $kurs->loadMissing(['merkez', 'alan', 'brans', 'ogretmenler']);

        app(KursDersOlusturucu::class)->sync($kurs, $request->user()?->id);

        $yoklamaKayitlari = $this->yoklamaListesiForDers($kurs, $ders);
        $saatAdedi = $ders->saatAdedi();
        $orientation = $saatAdedi > 2 ? 'landscape' : 'portrait';

        $filename = 'kurs-'.$kurs->kurs_no.'-yoklama-formu-'.$ders->tarih?->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadView('kurslar.yoklama_formu_pdf', [
            'kurs' => $kurs,
            'ders' => $ders,
            'yoklamaKayitlari' => $yoklamaKayitlari,
            'saatAdedi' => $saatAdedi,
        ])->setPaper('a4', $orientation);

        return $pdf->download($filename);
    }

    public function exportYoklamalar(Request $request, Kurs $kurs): StreamedResponse
    {
        app(KursDersOlusturucu::class)->sync($kurs, $request->user()?->id);

        $bugun = now()->toDateString();

        if ($request->filled('ders')) {
            $seciliDers = $kurs->dersler()
                ->whereKey($request->integer('ders'))
                ->whereDate('tarih', '<=', $bugun)
                ->firstOrFail();

            $yoklamaKayitlari = $this->yoklamaListesiForDers($kurs, $seciliDers);
            $saatAdedi = $seciliDers->saatAdedi();

            $filename = 'kurs-'.$kurs->kurs_no.'-yoklama-'.$seciliDers->tarih?->format('Y-m-d').'-'.now()->format('His').'.csv';

            return response()->streamDownload(function () use ($yoklamaKayitlari, $seciliDers, $saatAdedi) {
                $handle = fopen('php://output', 'w');
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                $headers = ['Ders', 'Sınıf', '#', 'Öğrenci', 'Kimlik No'];
                for ($s = 1; $s <= $saatAdedi; $s++) {
                    $headers[] = 'Saat '.$s;
                }
                $headers[] = 'Açıklama';
                fputcsv($handle, $headers, ';');

                foreach ($yoklamaKayitlari as $index => $yoklama) {
                    $saatlik = $yoklama->normalizeSaatlikDurumlar($saatAdedi);
                    $row = [
                        $seciliDers->ozet(),
                        $seciliDers->sinif ?: '',
                        $index + 1,
                        $yoklama->kisi?->tam_adi ?? '',
                        $yoklama->kisi?->tc_kimlik_no ?? '',
                    ];
                    foreach ($saatlik as $durum) {
                        $row[] = YoklamaDurum::tryFrom($durum)?->label() ?? $durum;
                    }
                    $row[] = $yoklama->aciklama ?? '';
                    fputcsv($handle, $row, ';');
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $dersler = $kurs->dersler()
            ->whereDate('tarih', '<=', $bugun)
            ->with([
                'yoklamaAlan',
                'yoklamalar' => fn ($q) => $this->scopeYoklamaAktifBasvuru($q),
            ])
            ->orderByDesc('tarih')
            ->orderByDesc('baslangic_saati')
            ->get()
            ->each(function (KursDers $ders) {
                $ogrenciSayisi = $ders->yoklamalar->count();
                $katilanSayisi = $ders->yoklamalar
                    ->filter(fn (KursYoklama $y) => $y->herhangiBirSaatteVarMi())
                    ->count();

                $ders->setAttribute('yoklamalar_count', $ogrenciSayisi);
                $ders->setAttribute('var_sayisi', $katilanSayisi);
            });

        $filename = 'kurs-'.$kurs->kurs_no.'-yoklamalar-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($dersler) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Tarih', 'Gün', 'Saat', 'Süre', 'Sınıf', 'Yoklama', 'Katılım', 'Kaydeden'], ';');

            foreach ($dersler as $ders) {
                fputcsv($handle, [
                    $ders->tarih?->format('d.m.Y') ?? '',
                    $ders->tarih?->locale('tr')->isoFormat('dddd') ?? '',
                    substr((string) $ders->baslangic_saati, 0, 5).' – '.substr((string) $ders->bitis_saati, 0, 5),
                    rtrim(rtrim(number_format((float) $ders->ders_saati, 1, '.', ''), '0'), '.'),
                    $ders->sinif ?: '',
                    $ders->yoklama_alindi ? 'Alındı' : 'Bekliyor',
                    ((int) $ders->var_sayisi).' / '.((int) $ders->yoklamalar_count),
                    $ders->yoklamaAlan?->tam_adi ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function assignOgretmen(Request $request, Kurs $kurs): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'ogretmen_ids' => ['nullable', 'array'],
            'ogretmen_ids.*' => [
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $ok = User::query()
                        ->whereKey($value)
                        ->where('aktif', true)
                        ->egitmen()
                        ->exists();
                    if (! $ok) {
                        $fail('Seçilen öğretmen geçersiz.');
                    }
                },
            ],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ogretmen_ids'] ?? [])));

        $oncekiOgretmenler = $kurs->ogretmenler->map(fn (User $u) => $u->tam_adi)->values()->all();

        $kurs->syncOgretmenler($ids);
        $kurs->load('ogretmenler');
        $kurs->update(['guncelleyen_id' => $request->user()?->id]);

        $mesaj = $ids !== []
            ? 'Kurs #'.$kurs->kurs_no.' için öğretmen ataması güncellendi.'
            : 'Kurs #'.$kurs->kurs_no.' öğretmen ataması kaldırıldı.';

        $ogretmenlerPayload = $kurs->ogretmenler
            ->sortBy('id')
            ->values()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'tam_adi' => $user->tam_adi,
            ])
            ->all();

        LogKaydedici::kaydet(
            islem: $ids !== [] ? 'kurs.ogretmen_atandi' : 'kurs.ogretmen_kaldirildi',
            kurs: $kurs,
            aciklama: $mesaj,
            konu: $kurs,
            eski: ['ogretmenler' => $oncekiOgretmenler],
            yeni: ['ogretmenler' => array_column($ogretmenlerPayload, 'tam_adi')],
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $mesaj,
                'ogretmenler' => $ogretmenlerPayload,
            ]);
        }

        return redirect()
            ->route('kurslar.show', $kurs)
            ->with('success', $mesaj);
    }

    public function updateKursaBaslama(Request $request, Kurs $kurs, KursBasvuru $basvuru): JsonResponse
    {
        abort_unless($basvuru->kurs_id === $kurs->id, 404);

        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        if ((int) $basvuru->durum_id !== (int) $kesinKayitId) {
            throw ValidationException::withMessages([
                'kursa_baslama_tarihi' => 'Kursa başlama tarihi yalnızca başvuru durumu Kesin Kayıt olan kayıtlar için güncellenebilir.',
            ]);
        }

        $validated = $request->validate([
            'kursa_baslama_tarihi' => [
                'required',
                'date',
                'after_or_equal:'.$kurs->kurs_baslama_tarihi?->toDateString(),
                'before_or_equal:'.$kurs->kurs_bitis_tarihi?->toDateString(),
            ],
        ], [
            'kursa_baslama_tarihi.after_or_equal' => 'Kursa başlama tarihi, kurs başlama tarihinden önce olamaz.',
            'kursa_baslama_tarihi.before_or_equal' => 'Kursa başlama tarihi, kurs bitiş tarihinden sonra olamaz.',
        ]);

        $eskiTarih = $basvuru->kursa_baslama_tarihi?->toDateString();

        $basvuru->update([
            'kursa_baslama_tarihi' => $validated['kursa_baslama_tarihi'],
            'guncelleyen_id' => $request->user()?->id,
        ]);

        LogKaydedici::kaydet(
            islem: 'basvuru.kursa_baslama_guncellendi',
            kurs: $kurs,
            aciklama: $this->basvuruSahibiAdi($basvuru).' için kursa başlama tarihi güncellendi.',
            konu: $basvuru,
            eski: ['kursa_baslama_tarihi' => $eskiTarih],
            yeni: ['kursa_baslama_tarihi' => $basvuru->kursa_baslama_tarihi?->toDateString()],
        );

        return response()->json([
            'message' => 'Kursa başlama tarihi güncellendi.',
            'kursa_baslama_tarihi' => $basvuru->kursa_baslama_tarihi?->format('Y-m-d'),
            'kursa_baslama_tarihi_formatted' => $basvuru->kursa_baslama_tarihi?->format('d.m.Y'),
        ]);
    }

    public function updateBasvuruDurum(Request $request, Kurs $kurs, KursBasvuru $basvuru, KursAyarServisi $kursAyarlari): JsonResponse
    {
        abort_unless($basvuru->kurs_id === $kurs->id, 404);

        $durumKod = (string) $request->input('durum_kod', '');
        if ($durumKod === '') {
            $durumKod = 'onay_bekliyor';
            $request->merge(['durum_kod' => $durumKod]);
        }

        $validated = $request->validate([
            'durum_kod' => ['required', 'string', Rule::in(['onay_bekliyor', 'kesin_kayit', 'yedek', 'iptal'])],
            'iptal_gerekce_id' => [
                Rule::requiredIf(fn () => $request->input('durum_kod') === 'iptal'),
                'nullable',
                'integer',
                Rule::exists('iptal_gerekceleri', 'id')->where(fn ($q) => $q->where('aktif', true)),
            ],
            'kursa_baslama_tarihi' => [
                Rule::requiredIf(fn () => $request->input('durum_kod') === 'kesin_kayit'),
                'nullable',
                'date',
                'after_or_equal:'.$kurs->kurs_baslama_tarihi?->toDateString(),
                'before_or_equal:'.$kurs->kurs_bitis_tarihi?->toDateString(),
            ],
            'sms_gonder' => ['nullable', 'boolean'],
            'eposta_gonder' => ['nullable', 'boolean'],
        ], [
            'durum_kod.required' => 'Başvuru durumu seçilmelidir.',
            'durum_kod.in' => 'Geçersiz başvuru durumu.',
            'iptal_gerekce_id.required' => 'İptal gerekçesi seçilmelidir.',
            'iptal_gerekce_id.exists' => 'Seçilen iptal gerekçesi geçersiz.',
            'kursa_baslama_tarihi.required' => 'Kursa başlama tarihi seçilmelidir.',
            'kursa_baslama_tarihi.after_or_equal' => 'Kursa başlama tarihi, kurs başlama tarihinden önce olamaz.',
            'kursa_baslama_tarihi.before_or_equal' => 'Kursa başlama tarihi, kurs bitiş tarihinden sonra olamaz.',
        ]);

        $mevcutDurumKod = $basvuru->durum?->kod
            ?? BasvuruDurum::query()->whereKey($basvuru->durum_id)->value('kod');

        if (
            $mevcutDurumKod === 'kesin_kayit'
            && $validated['durum_kod'] !== 'kesin_kayit'
        ) {
            $basariKod = $basvuru->basariDurum?->kod
                ?? BasariDurum::query()->whereKey($basvuru->basari_durumu_id)->value('kod');

            if (in_array($basariKod, ['sertifika_hak_etti', 'katilim_belgesi_hak_etti'], true)) {
                throw ValidationException::withMessages([
                    'durum_kod' => 'Kesin kaydı iptal etmek için önce başarı durumunu güncelleyiniz.',
                ]);
            }
        }

        $durumId = BasvuruDurum::idByKod($validated['durum_kod']);
        abort_unless($durumId, 422);

        if ($validated['durum_kod'] === 'yedek' && $mevcutDurumKod !== 'yedek') {
            $yedekServisi = app(KursYedekListeServisi::class);
            $doluluk = $yedekServisi->dolulukOzeti($kurs);
            if ($doluluk['yedek_kontenjan'] <= 0) {
                throw ValidationException::withMessages([
                    'durum_kod' => 'Bu kursta yedek kontenjan tanımlı değil.',
                ]);
            }
            if ($doluluk['yedek_dolu']) {
                throw ValidationException::withMessages([
                    'durum_kod' => 'Yedek kontenjan ('.$doluluk['yedek_kontenjan'].') dolmuştur.',
                ]);
            }
        }

        if ($validated['durum_kod'] === 'kesin_kayit' && $mevcutDurumKod !== 'kesin_kayit') {
            $yedekServisi = app(KursYedekListeServisi::class);
            $doluluk = $yedekServisi->dolulukOzeti($kurs);
            if ($mevcutDurumKod !== 'onay_bekliyor' && $doluluk['ana_dolu']) {
                throw ValidationException::withMessages([
                    'durum_kod' => 'Ana kontenjan dolu. Kesin kayıt için önce kontenjanda yer açılmalıdır.',
                ]);
            }
        }

        $smsAyarlari = match ($validated['durum_kod']) {
            'kesin_kayit' => [
                $kursAyarlari->smsBasvuruOnay(),
                $kursAyarlari->smsMetinOnay(),
                'basvuru_onay',
            ],
            'iptal' => [
                $kursAyarlari->smsBasvuruIptal(),
                $kursAyarlari->smsMetinIptal(),
                'basvuru_iptal',
            ],
            'yedek' => [
                $kursAyarlari->smsBasvuruYedek(),
                $kursAyarlari->smsMetinYedek(),
                'basvuru_yedek',
            ],
            default => null,
        };

        $epostaAyarlari = match ($validated['durum_kod']) {
            'kesin_kayit' => [
                $kursAyarlari->epostaBasvuruOnay(),
                $kursAyarlari->epostaKonuOnay(),
                $kursAyarlari->epostaMetinOnay(),
                'basvuru_onay',
            ],
            'iptal' => [
                $kursAyarlari->epostaBasvuruIptal(),
                $kursAyarlari->epostaKonuIptal(),
                $kursAyarlari->epostaMetinIptal(),
                'basvuru_iptal',
            ],
            'yedek' => [
                $kursAyarlari->epostaBasvuruYedek(),
                $kursAyarlari->epostaKonuYedek(),
                $kursAyarlari->epostaMetinYedek(),
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
            $payload['kursa_baslama_tarihi'] = $validated['kursa_baslama_tarihi'];
            $mesaj = 'Başvuru onaylandı.';
        } elseif ($validated['durum_kod'] === 'iptal') {
            $payload['iptal_tarihi'] = now();
            $payload['iptal_gerekce_id'] = $validated['iptal_gerekce_id'];
            $payload['iptal_eden_id'] = $userId;
            $mesaj = 'Başvuru iptal edildi.';
        } else {
            $payload['onay_tarihi'] = null;
            $payload['onaylayan_id'] = null;
            $payload['iptal_tarihi'] = null;
            $payload['iptal_gerekce_id'] = null;
            $payload['iptal_eden_id'] = null;
            $mesaj = $validated['durum_kod'] === 'yedek'
                ? 'Başvuru yedeğe alındı.'
                : 'Başvuru durumu Onay Bekliyor olarak güncellendi.';
        }

        DB::transaction(function () use ($kurs, $basvuru, $payload, $mevcutDurumKod, $validated) {
            $basvuru->update($payload);
            app(KursYedekListeServisi::class)->durumDegisimindeYedekSirasiGuncelle(
                $kurs,
                $basvuru->fresh(),
                $mevcutDurumKod,
                $validated['durum_kod'],
            );
        });

        $basvuru->refresh();
        $basvuru->load(['durum', 'basariDurum', 'iptalGerekce', 'kisi', 'basvuran', 'veli']);

        if ($validated['durum_kod'] === 'yedek' && $basvuru->yedek_sira) {
            $mesaj = 'Başvuru yedeğe alındı. Yedek sırası: '.$basvuru->yedek_sira.'.';
        }

        LogKaydedici::kaydet(
            islem: 'basvuru.durum_degisti',
            kurs: $kurs,
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
                $kurs,
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
                $kurs,
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
            'basari' => $basvuru->basariDurum?->only(['id', 'kod', 'ad', 'status_sinifi']),
            'yedek_sira' => $basvuru->yedek_sira,
        ]);
    }

    public function yedekSirasi(Kurs $kurs, KursYedekListeServisi $yedekListe): JsonResponse
    {
        $liste = $yedekListe->yedekBasvurulari($kurs);

        return response()->json([
            'kurs_id' => $kurs->id,
            'kurs_ad' => 'Kurs #'.$kurs->kurs_no,
            'items' => $liste->map(fn (KursBasvuru $basvuru) => [
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
        Kurs $kurs,
        KursYedekListeServisi $yedekListe,
    ): JsonResponse {
        $validated = $request->validate([
            'basvuru_ids' => ['required', 'array', 'min:1'],
            'basvuru_ids.*' => ['integer', 'distinct'],
        ], [
            'basvuru_ids.required' => 'Yedek sıra listesi boş olamaz.',
            'basvuru_ids.min' => 'Yedek sıra listesi boş olamaz.',
        ]);

        DB::transaction(function () use ($kurs, $yedekListe, $validated) {
            $yedekListe->sirayiGuncelle($kurs, $validated['basvuru_ids']);
        });

        LogKaydedici::kaydet(
            islem: 'kurs.yedek_sirasi_guncellendi',
            kurs: $kurs,
            aciklama: 'Kurs #'.$kurs->kurs_no.' yedek sırası güncellendi.',
            konu: $kurs,
            yeni: ['basvuru_ids' => array_values($validated['basvuru_ids'])],
            ekstra: ['kurs_id' => $kurs->id],
        );

        return response()->json([
            'message' => 'Yedek sırası güncellendi.',
            'items' => $yedekListe->yedekBasvurulari($kurs)->map(fn (KursBasvuru $basvuru) => [
                'id' => $basvuru->id,
                'yedek_sira' => $basvuru->yedek_sira,
            ])->values(),
        ]);
    }

    public function updateBasvuruBasari(Request $request, Kurs $kurs, KursBasvuru $basvuru): JsonResponse
    {
        abort_unless($basvuru->kurs_id === $kurs->id, 404);

        $validated = $request->validate([
            'basari_durumu_id' => [
                'nullable',
                'integer',
                Rule::exists('basari_durumlari', 'id')->where(fn ($q) => $q->where('aktif', true)),
            ],
        ], [
            'basari_durumu_id.exists' => 'Seçilen başarı durumu geçersiz.',
        ]);

        $basariId = $validated['basari_durumu_id'] ?? null;

        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        if ((int) $basvuru->durum_id !== (int) $kesinKayitId) {
            throw ValidationException::withMessages([
                'basari_durumu_id' => 'Başarı durumu yalnızca başvuru durumu Kesin Kayıt olan kayıtlar için güncellenebilir.',
            ]);
        }

        if ($basariId) {
            $basariKod = BasariDurum::query()->whereKey($basariId)->value('kod');
            $belgeKodlari = ['sertifika_hak_etti', 'katilim_belgesi_hak_etti'];

            if (in_array($basariKod, $belgeKodlari, true) && $kurs->durum !== KursDurum::Tamamlanan) {
                throw ValidationException::withMessages([
                    'basari_durumu_id' => 'Sertifika veya katılım belgesi hakkı yalnızca durumu Tamamlanan olan kurslarda seçilebilir.',
                ]);
            }
        }

        $eskiBasari = $basvuru->basariDurum?->ad;

        $basvuru->update([
            'basari_durumu_id' => $basariId,
            'guncelleyen_id' => $request->user()?->id,
        ]);
        $basvuru->load('basariDurum');

        LogKaydedici::kaydet(
            islem: 'basvuru.basari_guncellendi',
            kurs: $kurs,
            aciklama: $this->basvuruSahibiAdi($basvuru).' için başarı durumu güncellendi.',
            konu: $basvuru,
            eski: ['basari' => $eskiBasari],
            yeni: ['basari' => $basvuru->basariDurum?->ad],
        );

        return response()->json([
            'message' => 'Başarı durumu güncellendi.',
            'basari' => $basvuru->basariDurum?->only(['id', 'kod', 'ad', 'status_sinifi']),
        ]);
    }

    public function updateBasvuruIptalGerekce(Request $request, Kurs $kurs, KursBasvuru $basvuru): JsonResponse
    {
        abort_unless((int) $basvuru->kurs_id === (int) $kurs->id, 404);

        $iptalDurumId = BasvuruDurum::idByKod('iptal');
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
            islem: 'basvuru.iptal_gerekce_guncellendi',
            kurs: $kurs,
            aciklama: $this->basvuruSahibiAdi($basvuru).' için iptal gerekçesi güncellendi.',
            konu: $basvuru,
            eski: ['iptal_gerekce' => $eski],
            yeni: ['iptal_gerekce' => $basvuru->iptalGerekce?->ad],
        );

        return response()->json([
            'message' => 'İptal gerekçesi güncellendi.',
            'iptal_gerekce' => $basvuru->iptalGerekce?->only(['id', 'ad']),
        ]);
    }

    public function updateBasvuruVeli(Request $request, Kurs $kurs, KursBasvuru $basvuru): JsonResponse
    {
        abort_unless((int) $basvuru->kurs_id === (int) $kurs->id, 404);

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
                islem: 'basvuru.veli_guncellendi',
                kurs: $kurs,
                aciklama: $this->basvuruSahibiAdi($basvuru).' için veli başvurusu kaldırıldı.',
                konu: $basvuru,
                eski: ['veli_id' => $eskiVeliId],
                yeni: ['veli_id' => null],
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
            islem: 'basvuru.veli_guncellendi',
            kurs: $kurs,
            aciklama: $this->basvuruSahibiAdi($basvuru).' için veli başvurusu güncellendi.',
            konu: $basvuru,
            eski: ['veli_id' => $eskiVeliId],
            yeni: ['veli_id' => $basvuru->veli_id],
        );

        return response()->json([
            'message' => 'Veli başvurusu güncellendi.',
            'veli_basvurusu' => true,
            'veli' => $basvuru->fresh('veli')?->veli?->only(['id', 'ad', 'soyad', 'tc_kimlik_no']),
        ]);
    }

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

    public function smsAlicilar(Kurs $kurs): JsonResponse
    {
        $basvurular = $kurs->basvurular()
            ->with(['kisi', 'basvuran', 'veli', 'durum'])
            ->get()
            ->map(function (KursBasvuru $basvuru) {
                $ad = $basvuru->kisi?->tam_adi
                    ?? $basvuru->basvuran?->tam_adi
                    ?? ('Başvuru #'.$basvuru->id);

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

    public function sendSms(Request $request, Kurs $kurs, SmsSender $smsSender): JsonResponse
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

        $basvurular = $kurs->basvurular()
            ->with(['kisi', 'basvuran', 'veli'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (KursBasvuru $basvuru) => mb_strtolower(
                $basvuru->kisi?->tam_adi
                    ?? $basvuru->basvuran?->tam_adi
                    ?? '',
                'UTF-8'
            ), SORT_NATURAL)
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
            $ad = $basvuru->kisi?->tam_adi
                ?? $basvuru->basvuran?->tam_adi
                ?? ('Başvuru #'.$basvuru->id);
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
                'kurs_id' => $kurs->id,
                'basvuru_id' => $basvuru->id,
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

        $kayit = KursSmsGonderim::query()->create([
            'kurs_id' => $kurs->id,
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
            kurs: $kurs,
            aciklama: 'Kurs #'.$kurs->kurs_no.' için '.$mesaj,
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

    public function epostaAlicilar(Kurs $kurs): JsonResponse
    {
        $basvurular = $kurs->basvurular()
            ->with(['kisi', 'basvuran', 'veli', 'durum'])
            ->get()
            ->map(function (KursBasvuru $basvuru) {
                $ad = $basvuru->kisi?->tam_adi
                    ?? $basvuru->basvuran?->tam_adi
                    ?? ('Başvuru #'.$basvuru->id);
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

    public function sendEposta(Request $request, Kurs $kurs, EmailSender $emailSender): JsonResponse
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

        $basvurular = $kurs->basvurular()
            ->with(['kisi', 'basvuran', 'veli'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (KursBasvuru $basvuru) => mb_strtolower(
                $basvuru->kisi?->tam_adi
                    ?? $basvuru->basvuran?->tam_adi
                    ?? '',
                'UTF-8'
            ), SORT_NATURAL)
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
            $ad = $basvuru->kisi?->tam_adi
                ?? $basvuru->basvuran?->tam_adi
                ?? ('Başvuru #'.$basvuru->id);
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
                'kurs_id' => $kurs->id,
                'basvuru_id' => $basvuru->id,
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

        $kayit = KursEpostaGonderim::query()->create([
            'kurs_id' => $kurs->id,
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
            kurs: $kurs,
            aciklama: 'Kurs #'.$kurs->kurs_no.' için '.$mesaj,
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

    private function basvuruTelefon(KursBasvuru $basvuru): ?string
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
        Kurs $kurs,
        KursBasvuru $basvuru,
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
                    'kurs_id' => $kurs->id,
                    'basvuru_id' => $basvuru->id,
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

        $kayit = KursSmsGonderim::query()->create([
            'kurs_id' => $kurs->id,
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
            kurs: $kurs,
            aciklama: 'Kurs #'.$kurs->kurs_no.' '.$kapsamEtiket.' için SMS: '.$sonucMesaj,
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
        Kurs $kurs,
        KursBasvuru $basvuru,
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
                    'kurs_id' => $kurs->id,
                    'basvuru_id' => $basvuru->id,
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

        $kayit = KursEpostaGonderim::query()->create([
            'kurs_id' => $kurs->id,
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
            kurs: $kurs,
            aciklama: 'Kurs #'.$kurs->kurs_no.' '.$kapsamEtiket.' için e-posta: '.$sonucMesaj,
            konu: $kayit,
            yeni: [
                'toplam' => $kayit->toplam,
                'gonderilen' => $kayit->gonderilen,
                'atlanan' => $kayit->atlanan,
            ],
        );

        return $sonucMesaj;
    }

    private function basvuruEmail(KursBasvuru $basvuru): ?string
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

    public function saveYoklama(Request $request, Kurs $kurs, KursDers $ders): RedirectResponse|JsonResponse
    {
        abort_unless($ders->kurs_id === $kurs->id, 404);
        abort_unless($ders->tarih && $ders->tarih->toDateString() <= now()->toDateString(), 404);
        abort_if($ders->iptal_edildi, 422, 'İptal edilen bir ders için yoklama kaydedilemez.');

        $saatAdedi = $ders->saatAdedi();

        $validated = $request->validate([
            'yoklamalar' => ['required', 'array'],
            'yoklamalar.*.basvuru_id' => ['required', 'integer'],
            'yoklamalar.*.saatlik_durumlar' => ['required', 'array', 'size:'.$saatAdedi],
            'yoklamalar.*.saatlik_durumlar.*' => ['required', Rule::enum(YoklamaDurum::class)],
            'yoklamalar.*.aciklama' => ['nullable', 'string', 'max:500'],
        ]);

        // İzinli başvurular (form ile aynı uygunluk): basvuru_id => kisi_id
        $izinli = $this->yoklamaListesiForDers($kurs, $ders)
            ->mapWithKeys(fn (KursYoklama $y) => [(int) $y->kurs_basvuru_id => $y->kisi_id]);

        DB::transaction(function () use ($validated, $ders, $request, $izinli, $saatAdedi) {
            foreach ($validated['yoklamalar'] as $row) {
                $basvuruId = (int) $row['basvuru_id'];

                if (! $izinli->has($basvuruId)) {
                    continue;
                }

                $saatlik = array_values(array_map(
                    fn ($durum) => $durum instanceof YoklamaDurum ? $durum->value : (string) $durum,
                    $row['saatlik_durumlar']
                ));

                $yoklama = KursYoklama::withTrashed()->firstOrNew([
                    'kurs_ders_id' => $ders->id,
                    'kurs_basvuru_id' => $basvuruId,
                ]);

                if ($yoklama->trashed()) {
                    $yoklama->restore();
                }

                $saatlik = $yoklama->forceFill(['saatlik_durumlar' => $saatlik])
                    ->normalizeSaatlikDurumlar($saatAdedi);

                $yoklama->kisi_id = $izinli->get($basvuruId) ?? $yoklama->kisi_id;
                $yoklama->saatlik_durumlar = $saatlik;
                $yoklama->durum = $yoklama->syncDurumFromSaatlik($saatlik);
                $yoklama->aciklama = $row['aciklama'] ?? null;
                $yoklama->guncelleyen_id = $request->user()?->id;

                if (! $yoklama->exists) {
                    $yoklama->olusturan_id = $request->user()?->id;
                }

                $yoklama->save();
            }

            $ders->update([
                'yoklama_alindi' => true,
                'yoklama_alan_id' => $request->user()?->id,
                'guncelleyen_id' => $request->user()?->id,
            ]);
        });

        $mesaj = 'Yoklama kaydedildi: '.$ders->ozet();

        LogKaydedici::kaydet(
            islem: 'ders.yoklama_kaydedildi',
            kurs: $kurs,
            aciklama: $ders->ozet().' dersi için yoklama kaydedildi.',
            konu: $ders,
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $mesaj,
                'ders_id' => $ders->id,
            ]);
        }

        return redirect()
            ->route('kurslar.show', [$kurs, 'tab' => 'yoklamalar', 'ders' => $ders->id])
            ->with('success', $mesaj);
    }

    public function deleteYoklama(Request $request, Kurs $kurs, KursDers $ders): JsonResponse
    {
        abort_unless($ders->kurs_id === $kurs->id, 404);

        DB::transaction(function () use ($ders, $request) {
            // Yoklama kayıtlarını "yok" bırakmak yerine tamamen kaldır (soft delete),
            // böylece silinen yoklamalar katılım oranı hesabına da dahil olmaz.
            $ders->yoklamalar()->delete();

            $ders->update([
                'yoklama_alindi' => false,
                'yoklama_alan_id' => null,
                'guncelleyen_id' => $request->user()?->id,
            ]);
        });

        LogKaydedici::kaydet(
            islem: 'ders.yoklama_silindi',
            kurs: $kurs,
            aciklama: $ders->ozet().' dersinin yoklaması silindi.',
            konu: $ders,
        );

        return response()->json([
            'message' => 'Yoklama silindi: '.$ders->ozet(),
        ]);
    }

    public function cancelDers(Request $request, Kurs $kurs, KursDers $ders): JsonResponse
    {
        abort_unless($ders->kurs_id === $kurs->id, 404);

        if ($ders->yoklama_alindi) {
            return response()->json([
                'message' => 'Yoklaması alınmış bir ders iptal edilemez.',
            ], 422);
        }

        $validated = $request->validate([
            'gerekce' => ['required', 'string', 'max:1000'],
        ], [
            'gerekce.required' => 'İptal gerekçesi girilmelidir.',
        ]);

        $ders->update([
            'iptal_edildi' => true,
            'iptal_gerekcesi' => $validated['gerekce'],
            'iptal_eden_id' => $request->user()?->id,
            'iptal_tarihi' => now(),
            'guncelleyen_id' => $request->user()?->id,
        ]);

        LogKaydedici::kaydet(
            islem: 'ders.iptal_edildi',
            kurs: $kurs,
            aciklama: $ders->ozet().' dersi iptal edildi.',
            konu: $ders,
            yeni: ['iptal_gerekcesi' => $validated['gerekce']],
        );

        return response()->json([
            'message' => 'Ders iptal edildi: '.$ders->ozet(),
        ]);
    }

    public function cancelDersGeriAl(Request $request, Kurs $kurs, KursDers $ders): JsonResponse
    {
        abort_unless($ders->kurs_id === $kurs->id, 404);

        $ders->update([
            'iptal_edildi' => false,
            'iptal_gerekcesi' => null,
            'iptal_eden_id' => null,
            'iptal_tarihi' => null,
            'guncelleyen_id' => $request->user()?->id,
        ]);

        LogKaydedici::kaydet(
            islem: 'ders.iptal_geri_alindi',
            kurs: $kurs,
            aciklama: $ders->ozet().' dersinin iptali geri alındı.',
            konu: $ders,
        );

        return response()->json([
            'message' => 'Ders iptali geri alındı: '.$ders->ozet(),
        ]);
    }

    public function rescheduleDers(Request $request, Kurs $kurs, KursDers $ders): JsonResponse
    {
        abort_unless($ders->kurs_id === $kurs->id, 404);

        if ($ders->yoklama_alindi) {
            return response()->json([
                'message' => 'Yoklaması alınmış bir dersin tarihi değiştirilemez.',
            ], 422);
        }

        $validated = $request->validate([
            'tarih' => ['required', 'date'],
        ], [
            'tarih.required' => 'Yeni tarih seçilmelidir.',
        ]);

        $yeniTarih = Carbon::parse($validated['tarih'])->toDateString();

        if ($yeniTarih === $ders->tarih?->toDateString()) {
            return response()->json([
                'message' => 'Yeni tarih, mevcut tarihle aynı olamaz.',
            ], 422);
        }

        $cakisma = KursDers::query()
            ->where('kurs_id', $kurs->id)
            ->whereKeyNot($ders->id)
            ->whereDate('tarih', $yeniTarih)
            ->where('baslangic_saati', $ders->baslangic_saati)
            ->exists();

        if ($cakisma) {
            return response()->json([
                'message' => 'Seçilen tarih ve saatte bu kursa ait başka bir ders bulunuyor.',
            ], 422);
        }

        $eskiTarih = $ders->tarih?->toDateString();

        $ders->update([
            'orijinal_tarih' => $ders->orijinal_tarih ?? $ders->tarih,
            'tarih' => $yeniTarih,
            'tarih_degistiren_id' => $request->user()?->id,
            'tarih_degisiklik_tarihi' => now(),
            'guncelleyen_id' => $request->user()?->id,
        ]);

        LogKaydedici::kaydet(
            islem: 'ders.tarih_degistirildi',
            kurs: $kurs,
            aciklama: 'Ders tarihi değiştirildi: '.$ders->ozet(),
            konu: $ders,
            eski: ['tarih' => $eskiTarih],
            yeni: ['tarih' => $yeniTarih],
        );

        return response()->json([
            'message' => 'Ders tarihi değiştirildi: '.$ders->ozet(),
        ]);
    }

    private function ensureYoklamaKayitlari(Kurs $kurs, KursDers $ders, ?int $userId = null): void
    {
        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        if (! $kesinKayitId) {
            return;
        }

        $dersTarihi = $ders->tarih?->toDateString();
        if (! $dersTarihi) {
            return;
        }

        $saatAdedi = $ders->saatAdedi();
        $varsayilanSaatlik = array_fill(0, $saatAdedi, YoklamaDurum::Yok->value);

        $basvurular = $kurs->basvurular()
            ->where('durum_id', $kesinKayitId)
            ->where(function ($query) use ($dersTarihi) {
                $query->whereNull('kursa_baslama_tarihi')
                    ->orWhereDate('kursa_baslama_tarihi', '<=', $dersTarihi);
            })
            ->get(['id', 'kisi_id']);

        foreach ($basvurular as $basvuru) {
            $yoklama = KursYoklama::withTrashed()->firstOrNew([
                'kurs_ders_id' => $ders->id,
                'kurs_basvuru_id' => $basvuru->id,
            ]);

            if ($yoklama->trashed()) {
                $yoklama->restore();
            }

            $saatlik = $yoklama->exists
                ? $yoklama->normalizeSaatlikDurumlar($saatAdedi)
                : $varsayilanSaatlik;

            $yoklama->fill([
                'kisi_id' => $basvuru->kisi_id,
                'durum' => $yoklama->syncDurumFromSaatlik($saatlik),
                'saatlik_durumlar' => $saatlik,
                'guncelleyen_id' => $userId,
            ]);

            if (! $yoklama->exists) {
                $yoklama->olusturan_id = $userId;
            }

            $yoklama->save();
        }

        // Geç başlayanların ders tarihinden önceki yoklama kayıtlarını gizle
        KursYoklama::query()
            ->where('kurs_ders_id', $ders->id)
            ->whereHas('basvuru', function ($query) use ($dersTarihi) {
                $query->whereNotNull('kursa_baslama_tarihi')
                    ->whereDate('kursa_baslama_tarihi', '>', $dersTarihi);
            })
            ->delete();
    }

    /**
     * Bir ders için yoklama listesini veritabanına kayıt atmadan hazırlar.
     * Kesin kayıtlı ve ilgili ders tarihinde aktif olan başvurular için,
     * varsa mevcut (kaydedilmiş) yoklama, yoksa hafızada yeni (kaydedilmemiş)
     * bir KursYoklama örneği döndürülür. Kayıt yalnızca "Kaydet" ile oluşur.
     *
     * @return \Illuminate\Support\Collection<int, KursYoklama>
     */
    private function yoklamaListesiForDers(Kurs $kurs, KursDers $ders)
    {
        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        $dersTarihi = $ders->tarih?->toDateString();

        if (! $kesinKayitId || ! $dersTarihi) {
            return collect();
        }

        $saatAdedi = $ders->saatAdedi();
        $varsayilanSaatlik = array_fill(0, $saatAdedi, YoklamaDurum::Yok->value);

        $basvurular = $kurs->basvurular()
            ->where('durum_id', $kesinKayitId)
            ->where(function ($query) use ($dersTarihi) {
                $query->whereNull('kursa_baslama_tarihi')
                    ->orWhereDate('kursa_baslama_tarihi', '<=', $dersTarihi);
            })
            ->with('kisi')
            ->get();

        $mevcut = $ders->yoklamalar()->get()->keyBy('kurs_basvuru_id');

        return $basvurular
            ->map(function (KursBasvuru $basvuru) use ($mevcut, $ders, $varsayilanSaatlik, $saatAdedi) {
                $yoklama = $mevcut->get($basvuru->id);

                if (! $yoklama) {
                    $yoklama = new KursYoklama([
                        'kurs_ders_id' => $ders->id,
                        'kurs_basvuru_id' => $basvuru->id,
                        'kisi_id' => $basvuru->kisi_id,
                        'durum' => YoklamaDurum::Yok,
                        'saatlik_durumlar' => $varsayilanSaatlik,
                    ]);
                }

                $yoklama->setAttribute('saatlik_durumlar', $yoklama->normalizeSaatlikDurumlar($saatAdedi));
                $yoklama->setRelation('kisi', $basvuru->kisi);
                $yoklama->setRelation('basvuru', $basvuru);

                return $yoklama;
            })
            ->sortBy(fn (KursYoklama $y) => mb_strtolower($y->kisi?->tam_adi ?? ''))
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, KursYoklama>
     */
    private function yoklamaKayitlariForDers(KursDers $ders)
    {
        $dersTarihi = $ders->tarih?->toDateString();
        $saatAdedi = $ders->saatAdedi();

        return $ders->yoklamalar()
            ->with(['kisi', 'basvuru'])
            ->where(function ($query) use ($dersTarihi) {
                $this->scopeYoklamaAktifBasvuru($query, $dersTarihi);
            })
            ->get()
            ->each(function (KursYoklama $yoklama) use ($saatAdedi) {
                $saatlik = $yoklama->normalizeSaatlikDurumlar($saatAdedi);
                if ($yoklama->saatlik_durumlar !== $saatlik) {
                    $yoklama->forceFill([
                        'saatlik_durumlar' => $saatlik,
                        'durum' => $yoklama->syncDurumFromSaatlik($saatlik),
                    ])->saveQuietly();
                }
                $yoklama->setAttribute('saatlik_durumlar', $saatlik);
            })
            ->sortBy(fn (KursYoklama $y) => mb_strtolower($y->kisi?->tam_adi ?? ''))
            ->values();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\KursYoklama>|\Illuminate\Database\Eloquent\Relations\Relation  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\KursYoklama>|\Illuminate\Database\Eloquent\Relations\Relation
     */
    private function scopeYoklamaAktifBasvuru($query, ?string $dersTarihi = null)
    {
        return $query->whereHas('basvuru', function ($basvuruQuery) use ($dersTarihi) {
            $basvuruQuery->where(function ($q) use ($dersTarihi) {
                $q->whereNull('kurs_basvurulari.kursa_baslama_tarihi');

                if ($dersTarihi) {
                    $q->orWhereDate('kurs_basvurulari.kursa_baslama_tarihi', '<=', $dersTarihi);
                } else {
                    $q->orWhereRaw(
                        'kurs_basvurulari.kursa_baslama_tarihi <= (select kd.tarih from kurs_dersleri kd where kd.id = kurs_yoklamalari.kurs_ders_id)'
                    );
                }
            });
        });
    }

    private function kursKosullarOzeti(Kurs $kurs): string
    {
        $parts = [];

        if ($kurs->minimum_yas !== null || $kurs->maksimum_yas !== null) {
            $parts[] = ($kurs->minimum_yas ?? '—').' - '.($kurs->maksimum_yas ?? '—').' yaş';
        }

        if ($kurs->cinsiyet_sarti) {
            $parts[] = $kurs->cinsiyet_sarti->label();
        }

        if ($kurs->ikamet_sarti === IkametSarti::Evet) {
            $parts[] = 'İkamet eden';
        } elseif ($kurs->ikamet_sarti === IkametSarti::Kismen) {
            $parts[] = 'Sınırlı ilçe dışı';
        }

        return $parts === [] ? 'Koşul tanımlanmamış' : implode('. ', $parts);
    }

    public function edit(Kurs $kurs): View
    {
        $kurs->load(['gunler', 'evrakTipleri', 'kurumlar']);

        return view('kurslar.edit', $this->formLookups() + [
            'kurs' => $kurs,
        ]);
    }

    public function update(Request $request, Kurs $kurs): RedirectResponse|JsonResponse
    {
        $validated = $this->validateKursRequest($request, $kurs);
        $onceki = $this->kursSnapshot($kurs);
        $kurs = $this->persistKurs($request, $validated, $kurs);
        $yeni = $this->kursSnapshot($kurs);

        if ($onceki !== $yeni) {
            LogKaydedici::kaydet(
                islem: 'kurs.guncellendi',
                kurs: $kurs,
                aciklama: 'Kurs #'.$kurs->kurs_no.' bilgileri güncellendi.',
                konu: $kurs,
                eski: $onceki,
                yeni: $yeni,
            );
        }

        $message = 'Kurs #'.$kurs->kurs_no.' başarıyla güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'redirect' => route('kurslar.show', $kurs),
            ]);
        }

        return redirect()
            ->route('kurslar.show', $kurs)
            ->with('success', $message);
    }

    public function toggleYayin(Request $request, Kurs $kurs): RedirectResponse|JsonResponse
    {
        $yayinaAliniyor = ! $kurs->onlinede_yayinlansin;

        $kurs->update([
            'onlinede_yayinlansin' => $yayinaAliniyor,
            'guncelleyen_id' => $request->user()?->id,
        ]);

        $mesaj = $yayinaAliniyor
            ? 'Kurs #'.$kurs->kurs_no.' yayına alındı.'
            : 'Kurs #'.$kurs->kurs_no.' yayından kaldırıldı.';

        LogKaydedici::kaydet(
            islem: $yayinaAliniyor ? 'kurs.yayina_alindi' : 'kurs.yayindan_kaldirildi',
            kurs: $kurs,
            aciklama: $mesaj,
            konu: $kurs,
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $mesaj,
                'onlinede_yayinlansin' => (bool) $kurs->onlinede_yayinlansin,
                'yayinlanabilir' => $kurs->basvuruDonemindeMi(),
                'basvuru_durumu_kod' => $kurs->basvuruDurumuKod(),
                'basvuru_durumu_label' => $kurs->basvuruDurumuLabel(),
                'basvuru_durumu_class' => $kurs->basvuruDurumuStatusClass(),
            ]);
        }

        return redirect()
            ->route('kurslar.show', $kurs)
            ->with('success', $mesaj);
    }

    private function kursBasvuruDonemindeMi(Kurs $kurs): bool
    {
        return $kurs->basvuruDonemindeMi();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateKursRequest(Request $request, ?Kurs $kurs = null): array
    {
        if ($request->input('cinsiyet_sarti') === '') {
            $request->merge(['cinsiyet_sarti' => null]);
        }

        $validated = $request->validate([
            'merkez_id' => ['required', 'exists:merkezler,id'],
            'alan_id' => ['required', 'exists:alanlar,id'],
            'brans_id' => ['required', 'exists:branslar,id'],
            'kurs_tipi_id' => ['required', 'exists:kurs_tipleri,id'],
            'durum' => ['required', Rule::enum(KursDurum::class)],
            'onlinede_yayinlansin' => ['nullable', 'boolean'],
            'meb_numarasi' => ['nullable', 'string', 'max:100'],
            'kurs_baslama_tarihi' => ['required', 'date'],
            'kurs_bitis_tarihi' => ['required', 'date', 'after_or_equal:kurs_baslama_tarihi'],
            'basvuru_baslama_tarihi' => ['required', 'date'],
            'basvuru_bitis_tarihi' => ['required', 'date', 'after_or_equal:basvuru_baslama_tarihi'],
            'toplam_kurs_saati' => ['nullable', 'integer', 'min:0', 'max:9999'],
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
            'gunler' => ['required', 'array', 'min:1'],
            'gunler.*.gun' => ['required', Rule::enum(HaftaGunu::class)],
            'gunler.*.baslangic_saati' => ['required', 'date_format:H:i'],
            'gunler.*.bitis_saati' => ['required', 'date_format:H:i'],
            'gunler.*.ders_saati' => ['required', 'numeric', 'gt:0', 'max:24'],
            'gunler.*.sinif' => ['nullable', 'string', 'max:50'],
            'aciklama' => ['nullable', 'string', 'max:50000'],
        ], [
            'kurs_bitis_tarihi.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.',
            'basvuru_bitis_tarihi.after_or_equal' => 'Başvuru bitiş tarihi başlangıçtan önce olamaz.',
            'maksimum_yas.gte' => 'Maksimum yaş, minimum yaştan küçük olamaz.',
            'ikamet_disi_kontenjan.required' => 'Sınırlı ilçe dışı seçildiğinde ikamet dışı kontenjan girilmelidir.',
            'gunler.required' => 'Haftalık programa en az bir gün ekleyin.',
            'gunler.min' => 'Haftalık programa en az bir gün ekleyin.',
            'gunler.*.gun.required' => 'Haftalık programda gün seçilmelidir.',
            'gunler.*.baslangic_saati.required' => 'Haftalık programda başlangıç saati zorunludur.',
            'gunler.*.bitis_saati.required' => 'Haftalık programda bitiş saati zorunludur.',
            'gunler.*.ders_saati.required' => 'Haftalık programda ders saati zorunludur.',
            'gunler.*.ders_saati.gt' => 'Ders saati 0\'dan büyük olmalıdır.',
        ]);

        $validated['aciklama'] = $this->normalizeRichText($validated['aciklama'] ?? null);

        $this->assertBasvuruTarihleriKursBitisindenSonraDegil($validated);
        $this->assertBaslamaGunuProgramda($validated);

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
    private function assertBasvuruTarihleriKursBitisindenSonraDegil(array $validated): void
    {
        $kursBitis = Carbon::parse($validated['kurs_bitis_tarihi'])->startOfDay();
        $basvuruBaslama = Carbon::parse($validated['basvuru_baslama_tarihi'])->startOfDay();
        $basvuruBitis = Carbon::parse($validated['basvuru_bitis_tarihi'])->startOfDay();

        $errors = [];

        if ($basvuruBaslama->gt($kursBitis)) {
            $errors['basvuru_baslama_tarihi'] = 'Başvuru başlangıç tarihi kurs bitiş tarihinden sonra olamaz.';
        }

        if ($basvuruBitis->gt($kursBitis)) {
            $errors['basvuru_bitis_tarihi'] = 'Başvuru bitiş tarihi kurs bitiş tarihinden sonra olamaz.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertBaslamaGunuProgramda(array $validated): void
    {
        $programGunleri = collect($validated['gunler'] ?? [])
            ->filter(function ($row) {
                return ! empty($row['gun'])
                    && ! empty($row['baslangic_saati'])
                    && ! empty($row['bitis_saati'])
                    && (float) ($row['ders_saati'] ?? 0) > 0;
            })
            ->map(fn ($row) => HaftaGunu::tryFrom((string) $row['gun']))
            ->filter()
            ->unique(fn (HaftaGunu $gun) => $gun->value)
            ->values();

        if ($programGunleri->isEmpty()) {
            throw ValidationException::withMessages([
                'gunler' => 'Haftalık programda Gün, Başlangıç, Bitiş ve Ders Saati doldurulmalıdır.',
            ]);
        }

        $baslama = Carbon::parse($validated['kurs_baslama_tarihi']);
        $baslamaGunu = HaftaGunu::fromCarbonIso($baslama->dayOfWeekIso);

        if (! $baslamaGunu || ! $programGunleri->contains(fn (HaftaGunu $gun) => $gun === $baslamaGunu)) {
            $programLabels = $programGunleri
                ->sortBy(fn (HaftaGunu $gun) => $gun->sira())
                ->map(fn (HaftaGunu $gun) => $gun->label())
                ->implode(', ');

            throw ValidationException::withMessages([
                'kurs_baslama_tarihi' => 'Kurs başlangıç tarihi '.($baslamaGunu?->label() ?? 'bilinmeyen').' gününe denk geliyor; haftalık programda ('.$programLabels.') yer almıyor.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistKurs(Request $request, array $validated, ?Kurs $kurs = null): Kurs
    {
        $ikametSarti = IkametSarti::from($validated['ikamet_sarti']);
        $evrakTipiIds = array_values(array_unique(array_map('intval', $validated['evrak_tipi_ids'] ?? [])));
        $kurumIds = array_values(array_unique(array_map('intval', $validated['kurumlar'] ?? [])));

        $attributes = [
            'merkez_id' => $validated['merkez_id'],
            'alan_id' => $validated['alan_id'],
            'brans_id' => $validated['brans_id'],
            'kurs_tipi_id' => $validated['kurs_tipi_id'],
            'durum' => $validated['durum'],
            'onlinede_yayinlansin' => $request->boolean('onlinede_yayinlansin'),
            'meb_numarasi' => $validated['meb_numarasi'] ?? null,
            'kurs_baslama_tarihi' => $validated['kurs_baslama_tarihi'],
            'kurs_bitis_tarihi' => $validated['kurs_bitis_tarihi'],
            'basvuru_baslama_tarihi' => $validated['basvuru_baslama_tarihi'],
            'basvuru_bitis_tarihi' => $validated['basvuru_bitis_tarihi'],
            'toplam_kurs_saati' => $validated['toplam_kurs_saati'] ?? 0,
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
            'aciklama' => $validated['aciklama'] ?? null,
            'guncelleyen_id' => $request->user()?->id,
        ];

        return DB::transaction(function () use ($attributes, $validated, $request, $kurs, $evrakTipiIds, $kurumIds) {
            if ($kurs) {
                // kurs_no oluşturulduktan sonra değiştirilmez.
                $kurs->update($attributes);
            } else {
                $attributes['kurs_no'] = app(NumaratorServisi::class)->sonraki(
                    NumaratorServisi::KURS,
                    fn (string $no) => Kurs::query()->where('kurs_no', $no)->exists(),
                );
                $kurs = Kurs::create($attributes + [
                    'olusturan_id' => $request->user()?->id,
                ]);
            }

            $kurs->evrakTipleri()->sync($evrakTipiIds);
            $kurs->syncKurumlar($kurumIds);

            $kurs->gunler()->delete();
            foreach ($validated['gunler'] ?? [] as $gunRow) {
                if (empty($gunRow['gun']) || empty($gunRow['baslangic_saati']) || empty($gunRow['bitis_saati'])) {
                    continue;
                }

                $kurs->gunler()->create([
                    'gun' => $gunRow['gun'],
                    'baslangic_saati' => $gunRow['baslangic_saati'],
                    'bitis_saati' => $gunRow['bitis_saati'],
                    'ders_saati' => $gunRow['ders_saati'] ?? 0,
                    'sinif' => $gunRow['sinif'] ?? null,
                ]);
            }

            $kurs = $kurs->fresh(['gunler']);
            app(KursDersOlusturucu::class)->sync($kurs, $request->user()?->id);

            return $kurs->fresh();
        });
    }

    /**
     * Log kayıtları için kursun okunabilir bir anlık görüntüsünü üretir.
     *
     * @return array<string, mixed>
     */
    private function kursSnapshot(Kurs $kurs): array
    {
        return [
            'kurs_no' => $kurs->kurs_no,
            'durum' => $kurs->durum instanceof KursDurum ? $kurs->durum->value : $kurs->durum,
            'merkez_id' => $kurs->merkez_id,
            'alan_id' => $kurs->alan_id,
            'brans_id' => $kurs->brans_id,
            'kurs_tipi_id' => $kurs->kurs_tipi_id,
            'kontenjan' => $kurs->kontenjan,
            'yedek_kontenjan' => $kurs->yedek_kontenjan,
            'kurs_baslama_tarihi' => $kurs->kurs_baslama_tarihi?->toDateString(),
            'kurs_bitis_tarihi' => $kurs->kurs_bitis_tarihi?->toDateString(),
            'basvuru_baslama_tarihi' => $kurs->basvuru_baslama_tarihi?->toDateString(),
            'basvuru_bitis_tarihi' => $kurs->basvuru_bitis_tarihi?->toDateString(),
            'onlinede_yayinlansin' => (bool) $kurs->onlinede_yayinlansin,
            'haftalik_program' => $this->kursProgramOzeti($kurs),
        ];
    }

    /**
     * Kursun haftalık programını (günler) log için okunabilir bir listeye çevirir.
     *
     * @return array<int, string>
     */
    private function kursProgramOzeti(Kurs $kurs): array
    {
        return $kurs->gunler()
            ->get()
            ->sortBy(fn ($gun) => $gun->gun?->sira() ?? 99)
            ->map(function ($gun) {
                $ozet = $gun->ozet();

                return $gun->sinif ? $ozet.' · '.$gun->sinif : $ozet;
            })
            ->values()
            ->all();
    }

    private function basvuruSahibiAdi(KursBasvuru $basvuru): string
    {
        return $basvuru->kisi?->tam_adi
            ?? $basvuru->basvuran?->tam_adi
            ?? ('Başvuru #'.$basvuru->id);
    }

    /**
     * @return array<string, int>
     */
    private function kursOzet(): array
    {
        $query = Kurs::query();
        $user = request()->user();

        if ($user && $user->sadeceAtananKurslariGorur()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('ogretmenler', fn ($oq) => $oq->where('users.id', $user->id))
                    ->orWhere('kurslar.ogretmen_id', $user->id);
            });
        }

        if ($user && $user->sadeceYetkiliMerkezleriGorur()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('kurslar.merkez_id', $merkezIds);
            }
        }

        $query->kullaniciKurumKapsami($user);

        return [
            'toplam' => (clone $query)->count(),
            'aktif' => (clone $query)->where('durum', KursDurum::Aktif)->count(),
            'hazirlik' => (clone $query)->where('durum', KursDurum::Hazirlik)->count(),
            'basvuruya_acik' => (clone $query)->basvuruDurumu('acik')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formLookups(): array
    {
        return [
            'alanlar' => Alan::where('aktif', true)->orderBy('ad')->get(),
            'branslar' => Brans::where('aktif', true)->orderBy('ad')->get(),
            'merkezler' => Merkez::query()->kullaniciKapsami(request()->user())->where('aktif', true)->orderBy('ad')->get(),
            'kursTipleri' => KursTipi::where('aktif', true)->orderBy('ad')->get(),
            'ogretmenler' => User::query()->egitmen()->where('aktif', true)->orderBy('ad')->orderBy('soyad')->get(),
            'durumlar' => KursDurum::cases(),
            'haftaGunleri' => HaftaGunu::cases(),
            'cinsiyetler' => Cinsiyet::cases(),
            'ikametSartlari' => IkametSarti::cases(),
            'evrakTipleri' => EvrakTipi::where('aktif', true)->orderBy('ad')->get(),
            'kurumlar' => Kurum::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
        ];
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Kurs::query()
            ->with(['merkez', 'alan', 'brans', 'kursTipi', 'ogretmenler', 'gunler', 'kurumlar'])
            ->latest('created_at');

        $user = $request->user();
        if ($user && $user->sadeceAtananKurslariGorur()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('ogretmenler', fn ($oq) => $oq->where('users.id', $user->id))
                    ->orWhere('kurslar.ogretmen_id', $user->id);
            });
        }

        if ($user && $user->sadeceYetkiliMerkezleriGorur()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('kurslar.merkez_id', $merkezIds);
            }
        }

        $query->kullaniciKurumKapsami($user);

        $this->applyKursNoFilter($query, $request);

        if ($request->filled('alan_id')) {
            $query->where('alan_id', $request->integer('alan_id'));
        }

        if ($request->filled('brans_id')) {
            $query->where('brans_id', $request->integer('brans_id'));
        }

        if ($request->filled('merkez_id')) {
            $query->where('merkez_id', $request->integer('merkez_id'));
        }

        if ($request->filled('kurum_id')) {
            $kurumId = $request->integer('kurum_id');
            $query->whereHas('kurumlar', fn ($q) => $q->where('kurumlar.id', $kurumId));
        }

        if ($request->filled('ogretmen_id')) {
            $ogretmenId = $request->integer('ogretmen_id');
            $query->whereHas('ogretmenler', fn ($q) => $q->where('users.id', $ogretmenId));
        }

        if ($request->filled('kurs_tipi_id')) {
            $query->where('kurs_tipi_id', $request->integer('kurs_tipi_id'));
        }

        $this->applyDurumFilter($query, $request);
        $this->applyBasvuruDurumuFilter($query, $request);

        $filename = 'kurslar-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Kurs No', 'Alan', 'Branş', 'Merkez', 'Kurum', 'Başlama', 'Bitiş',
                'Başvuru', 'Kayıt', 'İptal', 'Kontenjan', 'Yedek', 'Eğitmen', 'Belge Türü', 'Durum', 'Başvuru Durumu',
            ], ';');

            $query->chunk(200, function ($kurslar) use ($handle) {
                foreach ($kurslar as $kurs) {
                    fputcsv($handle, [
                        $kurs->kurs_no,
                        $kurs->alan?->ad,
                        $kurs->brans?->ad,
                        $kurs->merkez?->ad,
                        $kurs->kurumlar->pluck('ad')->implode(', '),
                        $kurs->kurs_baslama_tarihi?->format('d.m.Y'),
                        $kurs->kurs_bitis_tarihi?->format('d.m.Y'),
                        $kurs->basvuru_sayisi,
                        $kurs->kayit_sayisi,
                        $kurs->iptal_sayisi,
                        $kurs->kontenjan,
                        $kurs->yedek_kontenjan,
                        $kurs->ogretmenAdlari(),
                        $kurs->kursTipi?->ad,
                        $kurs->durum?->label(),
                        $kurs->basvuruDurumuLabel(),
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function applyKursNoFilter($query, Request $request): void
    {
        if (! $request->filled('kurs_no')) {
            return;
        }

        $value = (string) $request->string('kurs_no');
        $mode = (string) $request->input('kurs_no_mode', 'exact');

        match ($mode) {
            'starts' => $query->where('kurs_no', 'like', $value.'%'),
            'ends' => $query->where('kurs_no', 'like', '%'.$value),
            'exact' => $query->where('kurs_no', $value),
            default => $query->where('kurs_no', 'like', '%'.$value.'%'),
        };
    }

    private function applyDurumFilter($query, Request $request): void
    {
        $durum = (string) $request->input('durum', '');

        if ($durum === '' || $durum === 'tumu') {
            return;
        }

        $query->where('durum', $durum);
    }

    private function applyBasvuruDurumuFilter($query, Request $request): void
    {
        $basvuruDurumu = (string) $request->input('basvuru_durumu', 'tumu');

        if ($basvuruDurumu === '' || $basvuruDurumu === 'tumu') {
            return;
        }

        $query->basvuruDurumu($basvuruDurumu);
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function searchKurslar(Request $request): array
    {
        $query = Kurs::query()
            ->with(['merkez', 'alan', 'brans', 'kursTipi', 'ogretmenler', 'gunler', 'kurumlar'])
            ->select('kurslar.*');

        $user = $request->user();
        if ($user && $user->sadeceAtananKurslariGorur()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('ogretmenler', fn ($oq) => $oq->where('users.id', $user->id))
                    ->orWhere('kurslar.ogretmen_id', $user->id);
            });
        }

        if ($user && $user->sadeceYetkiliMerkezleriGorur()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('kurslar.merkez_id', $merkezIds);
            }
        }

        $query->kullaniciKurumKapsami($user);

        $this->applyKursNoFilter($query, $request);

        if ($request->filled('alan_id')) {
            $query->where('alan_id', $request->integer('alan_id'));
        }

        if ($request->filled('brans_id')) {
            $query->where('brans_id', $request->integer('brans_id'));
        }

        if ($request->filled('merkez_id')) {
            $query->where('merkez_id', $request->integer('merkez_id'));
        }

        if ($request->filled('kurum_id')) {
            $kurumId = $request->integer('kurum_id');
            $query->whereHas('kurumlar', fn ($q) => $q->where('kurumlar.id', $kurumId));
        }

        if ($request->filled('ogretmen_id')) {
            $ogretmenId = $request->integer('ogretmen_id');
            $query->whereHas('ogretmenler', fn ($q) => $q->where('users.id', $ogretmenId));
        }

        if ($request->filled('kurs_tipi_id')) {
            $query->where('kurs_tipi_id', $request->integer('kurs_tipi_id'));
        }

        $this->applyDurumFilter($query, $request);
        $this->applyBasvuruDurumuFilter($query, $request);

        if ($request->filled('gunler')) {
            $gunler = array_values(array_filter((array) $request->input('gunler')));
            if ($gunler !== []) {
                $query->whereHas('gunler', fn ($q) => $q->whereIn('gun', $gunler));
            }
        }

        if ($request->filled('ilk_kayit')) {
            $query->whereDate('created_at', '>=', $request->string('ilk_kayit'));
        }

        if ($request->filled('son_kayit')) {
            $query->whereDate('created_at', '<=', $request->string('son_kayit'));
        }

        if ($request->filled('kurs_baslama_ilk')) {
            $query->whereDate('kurs_baslama_tarihi', '>=', $request->string('kurs_baslama_ilk'));
        }

        if ($request->filled('kurs_baslama_son')) {
            $query->whereDate('kurs_baslama_tarihi', '<=', $request->string('kurs_baslama_son'));
        }

        if ($request->filled('kurs_bitis_ilk')) {
            $query->whereDate('kurs_bitis_tarihi', '>=', $request->string('kurs_bitis_ilk'));
        }

        if ($request->filled('kurs_bitis_son')) {
            $query->whereDate('kurs_bitis_tarihi', '<=', $request->string('kurs_bitis_son'));
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';
        $sortable = [
            'no' => 'kurslar.kurs_no',
            'alan' => 'alanlar.ad',
            'brans' => 'branslar.ad',
            'merkez' => 'merkezler.ad',
            'baslama' => 'kurslar.kurs_baslama_tarihi',
            'bitis' => 'kurslar.kurs_bitis_tarihi',
            'basvuru' => 'kurslar.basvuru_sayisi',
            'kayit' => 'kurslar.kayit_sayisi',
            'iptal' => 'kurslar.iptal_sayisi',
            'kontenjan' => 'kurslar.kontenjan',
            'yedek' => 'kurslar.yedek_kontenjan',
            'egitmen' => 'users.ad',
            'belge' => 'kurs_tipleri.ad',
            'durum' => 'kurslar.durum',
            'tarih' => 'kurslar.created_at',
        ];

        if ($sort === 'basvuru_durumu') {
            $now = now()->toDateTimeString();
            $directionSql = $direction === 'asc' ? 'ASC' : 'DESC';
            $query->orderByRaw(
                'CASE
                    WHEN kurslar.onlinede_yayinlansin = 0 OR kurslar.basvuru_baslama_tarihi IS NULL OR kurslar.basvuru_bitis_tarihi IS NULL THEN 3
                    WHEN kurslar.basvuru_baslama_tarihi > ? THEN 1
                    WHEN kurslar.basvuru_bitis_tarihi < ? THEN 2
                    ELSE 0
                END '.$directionSql,
                [$now, $now]
            );
        } elseif (isset($sortable[$sort])) {
            if (in_array($sort, ['alan', 'brans', 'merkez', 'egitmen', 'belge'], true)) {
                $query
                    ->leftJoin('alanlar', 'alanlar.id', '=', 'kurslar.alan_id')
                    ->leftJoin('branslar', 'branslar.id', '=', 'kurslar.brans_id')
                    ->leftJoin('merkezler', 'merkezler.id', '=', 'kurslar.merkez_id')
                    ->leftJoin('users', 'users.id', '=', 'kurslar.ogretmen_id')
                    ->leftJoin('kurs_tipleri', 'kurs_tipleri.id', '=', 'kurslar.kurs_tipi_id');
            }

            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->latest('kurslar.created_at');
            $sort = '';
            $direction = 'desc';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $kurslar = $query->paginate($perPage)->withQueryString();

        $filters = $request->only([
            'kurs_no', 'kurs_no_mode', 'alan_id', 'brans_id', 'merkez_id', 'kurum_id', 'ogretmen_id',
            'kurs_tipi_id', 'durum', 'basvuru_durumu', 'ilk_kayit', 'son_kayit',
            'kurs_baslama_ilk', 'kurs_baslama_son', 'kurs_bitis_ilk', 'kurs_bitis_son',
            'gunler', 'per_page', 'sort', 'direction',
        ]);

        return [$kurslar, $sort, $direction, $filters];
    }
}
