<?php

namespace App\Http\Controllers;

use App\Enums\SmsGonderimSecenegi;
use App\Models\BasariDurum;
use App\Models\BasvuruDurum;
use App\Models\EtkinlikBasvuruDurum;
use App\Models\EtkinlikTipi;
use App\Models\EvrakTipi;
use App\Models\IptalGerekce;
use App\Models\KursTipi;
use App\Models\Kurum;
use App\Services\EtkinlikAyarServisi;
use App\Services\KursAyarServisi;
use App\Services\LogKaydedici;
use App\Services\SertifikaAyarServisi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SabitTanimController extends Controller
{
    public const MODULE_KURS = 'kurs';

    public const MODULE_ETKINLIK = 'etkinlik';

    public const KURS_TABS = [
        'kurs-tipleri' => 'Kurs Tipleri',
        'evrak-tipleri' => 'Evrak Tipleri',
        'basvuru-durumlari' => 'Başvuru Durumları',
        'basari-durumlari' => 'Başarı Durumları',
        'iptal-gerekceleri' => 'İptal Gerekçeleri',
        'kurumlar' => 'Kurumlar',
        'sertifika-ayarlari' => 'Sertifika Ayarları',
        'diger-ayarlar' => 'Diğer Ayarlar',
    ];

    /** Liste yerine form gösteren sekmeler. */
    public const FORM_TABS = [
        'sertifika-ayarlari',
        'diger-ayarlar',
    ];

    public const ETKINLIK_TABS = [
        'etkinlik-tipleri' => 'Etkinlik Tipleri',
        'etkinlik-basvuru-durumlari' => 'Etkinlik Başvuru Durumları',
        'diger-ayarlar' => 'Diğer Ayarlar',
    ];

    /**
     * Geçici olarak gizlenen sekmeler. Geri açmak için ilgili kodu listeden çıkarın.
     *
     * @var list<string>
     */
    public const HIDDEN_TABS = [
        'kurs-tipleri',
        'basvuru-durumlari',
        'basari-durumlari',
        'etkinlik-basvuru-durumlari',
    ];

    public const STATUS_SINIFLARI = [
        'status-aktif' => 'Aktif (yeşil/mavi)',
        'status-hazirlik' => 'Hazırlık (gri)',
        'status-tamamlanan' => 'Tamamlanan (yeşil)',
        'status-iptal' => 'İptal (kırmızı)',
        'status-yedek' => 'Yedek (sarı)',
    ];

    public function index(Request $request): View
    {
        return $this->renderIndex($request, self::MODULE_KURS);
    }

    public function etkinlikIndex(Request $request): View
    {
        return $this->renderIndex($request, self::MODULE_ETKINLIK);
    }

    private function renderIndex(Request $request, string $module): View
    {
        $visibleTabs = $this->visibleTabs($module);
        $activeTab = $this->resolveTab($request, $module);

        if ($request->ajax() || $request->boolean('ajax')) {
            abort_unless(array_key_exists($activeTab, $visibleTabs), 404);

            if (in_array($activeTab, self::FORM_TABS, true)) {
                return view('sabit-tanimlar.partials.'.$activeTab, [
                    'form' => $this->formTabVerisi($activeTab, $module),
                    'module' => $module,
                ]);
            }

            return view('sabit-tanimlar.partials.'.$activeTab.'-results', $this->tabViewData($request, $activeTab));
        }

        $tabsData = [];
        foreach (array_keys($visibleTabs) as $tab) {
            if (in_array($tab, self::FORM_TABS, true)) {
                $tabsData[$tab] = ['form' => true];
                continue;
            }
            $tabsData[$tab] = $this->tabViewData($request, $tab, $tab === $activeTab);
        }

        $meta = $this->moduleMeta($module);

        return view('sabit-tanimlar.index', [
            'activeTab' => $activeTab,
            'tabs' => $visibleTabs,
            'tabsData' => $tabsData,
            'statusSiniflari' => self::STATUS_SINIFLARI,
            'counts' => array_intersect_key($this->tabCounts(), $visibleTabs),
            'indexRoute' => $meta['index_route'],
            'pageEyebrow' => $meta['eyebrow'],
            'pageTitle' => $meta['title'],
            'pageSubtitle' => $meta['subtitle'],
            'module' => $module,
            'formTabForms' => collect(array_intersect(self::FORM_TABS, array_keys($visibleTabs)))
                ->mapWithKeys(fn (string $tab) => [$tab => $this->formTabVerisi($tab, $module)])
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formTabVerisi(string $tab, string $module = self::MODULE_KURS): array
    {
        return match ($tab) {
            'sertifika-ayarlari' => app(SertifikaAyarServisi::class)->formVerisi(),
            'diger-ayarlar' => $module === self::MODULE_ETKINLIK
                ? app(EtkinlikAyarServisi::class)->formVerisi()
                : app(KursAyarServisi::class)->formVerisi(),
            default => [],
        };
    }

    /**
     * @return array{index_route: string, eyebrow: string, title: string, subtitle: string}
     */
    private function moduleMeta(string $module): array
    {
        if ($module === self::MODULE_ETKINLIK) {
            return [
                'index_route' => 'etkinlik-sabit-tanimlar.index',
                'eyebrow' => 'Etkinlik Yönetimi',
                'title' => 'Sabit Tanımlar',
                'subtitle' => 'Etkinlik tipi, başvuru durumu ve diğer ayarları tek ekrandan yönetin.',
            ];
        }

        return [
            'index_route' => 'sabit-tanimlar.index',
            'eyebrow' => 'Kurs Yönetimi',
            'title' => 'Sabit Tanımlar',
            'subtitle' => 'Evrak tipi, iptal gerekçesi, kurum ve diğer ayarları tek ekrandan yönetin.',
        ];
    }

    public function storeKursTipi(Request $request): RedirectResponse|JsonResponse
    {
        return $this->storeEntity($request, KursTipi::class, 'kurs_tipleri', 'Kurs tipi', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('kurs_tipleri', 'ad')],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aktif' => $r->boolean('aktif'),
        ]);
    }

    public function updateKursTipi(Request $request, KursTipi $kursTipi): RedirectResponse|JsonResponse
    {
        return $this->updateEntity($request, $kursTipi, 'Kurs tipi', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('kurs_tipleri', 'ad')->ignore($kursTipi->id)],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aktif' => $r->boolean('aktif'),
        ], ['ad', 'aktif']);
    }

    public function storeEtkinlikTipi(Request $request): RedirectResponse|JsonResponse
    {
        return $this->storeEntity($request, EtkinlikTipi::class, 'etkinlik_tipleri', 'Etkinlik tipi', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('etkinlik_tipleri', 'ad')],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aktif' => $r->boolean('aktif'),
        ]);
    }

    public function updateEtkinlikTipi(Request $request, EtkinlikTipi $etkinlikTipi): RedirectResponse|JsonResponse
    {
        return $this->updateEntity($request, $etkinlikTipi, 'Etkinlik tipi', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('etkinlik_tipleri', 'ad')->ignore($etkinlikTipi->id)],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aktif' => $r->boolean('aktif'),
        ], ['ad', 'aktif']);
    }

    public function storeEvrakTipi(Request $request): RedirectResponse|JsonResponse
    {
        return $this->storeEntity($request, EvrakTipi::class, 'evrak_tipleri', 'Evrak tipi', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('evrak_tipleri', 'ad')],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'aktif' => $r->boolean('aktif'),
        ]);
    }

    public function updateEvrakTipi(Request $request, EvrakTipi $evrakTipi): RedirectResponse|JsonResponse
    {
        return $this->updateEntity($request, $evrakTipi, 'Evrak tipi', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('evrak_tipleri', 'ad')->ignore($evrakTipi->id)],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'aktif' => $r->boolean('aktif'),
        ], ['ad', 'aciklama', 'aktif']);
    }

    public function storeBasvuruDurum(Request $request): RedirectResponse|JsonResponse
    {
        return $this->storeEntity($request, BasvuruDurum::class, 'basvuru_durumlari', 'Başvuru durumu', [
            'kod' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('basvuru_durumlari', 'kod')],
            'ad' => ['required', 'string', 'max:150', Rule::unique('basvuru_durumlari', 'ad')],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'status_sinifi' => ['required', 'string', Rule::in(array_keys(self::STATUS_SINIFLARI))],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'kod' => $v['kod'],
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'status_sinifi' => $v['status_sinifi'],
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ], messages: [
            'kod.regex' => 'Kod yalnızca küçük harf, rakam ve alt çizgi içerebilir.',
        ]);
    }

    public function updateBasvuruDurum(Request $request, BasvuruDurum $basvuruDurum): RedirectResponse|JsonResponse
    {
        return $this->updateEntity($request, $basvuruDurum, 'Başvuru durumu', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('basvuru_durumlari', 'ad')->ignore($basvuruDurum->id)],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'status_sinifi' => ['required', 'string', Rule::in(array_keys(self::STATUS_SINIFLARI))],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'status_sinifi' => $v['status_sinifi'],
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ], ['kod', 'ad', 'aciklama', 'status_sinifi', 'sira', 'aktif']);
    }

    public function storeEtkinlikBasvuruDurum(Request $request): RedirectResponse|JsonResponse
    {
        return $this->storeEntity($request, EtkinlikBasvuruDurum::class, 'etkinlik_basvuru_durumlari', 'Etkinlik başvuru durumu', [
            'kod' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('etkinlik_basvuru_durumlari', 'kod')],
            'ad' => ['required', 'string', 'max:150', Rule::unique('etkinlik_basvuru_durumlari', 'ad')],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'status_sinifi' => ['required', 'string', Rule::in(array_keys(self::STATUS_SINIFLARI))],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'kod' => $v['kod'],
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'status_sinifi' => $v['status_sinifi'],
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ], messages: [
            'kod.regex' => 'Kod yalnızca küçük harf, rakam ve alt çizgi içerebilir.',
        ]);
    }

    public function updateEtkinlikBasvuruDurum(Request $request, EtkinlikBasvuruDurum $etkinlikBasvuruDurum): RedirectResponse|JsonResponse
    {
        return $this->updateEntity($request, $etkinlikBasvuruDurum, 'Etkinlik başvuru durumu', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('etkinlik_basvuru_durumlari', 'ad')->ignore($etkinlikBasvuruDurum->id)],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'status_sinifi' => ['required', 'string', Rule::in(array_keys(self::STATUS_SINIFLARI))],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'status_sinifi' => $v['status_sinifi'],
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ], ['kod', 'ad', 'aciklama', 'status_sinifi', 'sira', 'aktif']);
    }

    public function storeBasariDurum(Request $request): RedirectResponse|JsonResponse
    {
        return $this->storeEntity($request, BasariDurum::class, 'basari_durumlari', 'Başarı durumu', [
            'kod' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('basari_durumlari', 'kod')],
            'ad' => ['required', 'string', 'max:150', Rule::unique('basari_durumlari', 'ad')],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'status_sinifi' => ['required', 'string', Rule::in(array_keys(self::STATUS_SINIFLARI))],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'kod' => $v['kod'],
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'status_sinifi' => $v['status_sinifi'],
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ], messages: [
            'kod.regex' => 'Kod yalnızca küçük harf, rakam ve alt çizgi içerebilir.',
        ]);
    }

    public function updateBasariDurum(Request $request, BasariDurum $basariDurum): RedirectResponse|JsonResponse
    {
        return $this->updateEntity($request, $basariDurum, 'Başarı durumu', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('basari_durumlari', 'ad')->ignore($basariDurum->id)],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'status_sinifi' => ['required', 'string', Rule::in(array_keys(self::STATUS_SINIFLARI))],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'status_sinifi' => $v['status_sinifi'],
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ], ['kod', 'ad', 'aciklama', 'status_sinifi', 'sira', 'aktif']);
    }

    public function storeIptalGerekce(Request $request): RedirectResponse|JsonResponse
    {
        return $this->storeEntity($request, IptalGerekce::class, 'iptal_gerekceleri', 'İptal gerekçesi', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('iptal_gerekceleri', 'ad')],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ]);
    }

    public function updateIptalGerekce(Request $request, IptalGerekce $iptalGerekce): RedirectResponse|JsonResponse
    {
        return $this->updateEntity($request, $iptalGerekce, 'İptal gerekçesi', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('iptal_gerekceleri', 'ad')->ignore($iptalGerekce->id)],
            'aciklama' => ['nullable', 'string', 'max:500'],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'aciklama' => $v['aciklama'] ?? null,
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ], ['ad', 'aciklama', 'sira', 'aktif']);
    }

    public function storeKurum(Request $request): RedirectResponse|JsonResponse
    {
        return $this->storeEntity($request, Kurum::class, 'kurumlar', 'Kurum', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('kurumlar', 'ad')],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ]);
    }

    public function updateKurum(Request $request, Kurum $kurum): RedirectResponse|JsonResponse
    {
        return $this->updateEntity($request, $kurum, 'Kurum', [
            'ad' => ['required', 'string', 'max:150', Rule::unique('kurumlar', 'ad')->ignore($kurum->id)],
            'sira' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'aktif' => ['nullable', 'boolean'],
        ], fn (array $v, Request $r) => [
            'ad' => $v['ad'],
            'sira' => (int) ($v['sira'] ?? 0),
            'aktif' => $r->boolean('aktif'),
        ], ['ad', 'sira', 'aktif']);
    }

    public function updateSertifikaAyarlari(Request $request, SertifikaAyarServisi $servis): RedirectResponse|JsonResponse
    {
        $sablonKodlari = array_keys((array) config('sertifika.sablonlar', []));

        $validated = $request->validate([
            'kurum_adi' => ['nullable', 'string', 'max:200'],
            'sablonlar' => ['required', 'array'],
            'sablonlar.*.kod' => ['required', 'string', 'max:20'],
            'sablonlar.*.baslik' => ['nullable', 'string', 'max:120'],
            'sablonlar.*.alt_baslik' => ['nullable', 'string', 'max:120'],
            'sablonlar.*.metin' => ['required', 'string', 'max:1000'],
            'sablonlar.*.alt_metin' => ['nullable', 'string', 'max:1000'],
            'sablonlar.*.kenarlik_olcusu' => ['required', 'numeric', 'min:0', 'max:40'],
            'sablonlar.*.egitmen_imzasi' => ['nullable', 'boolean'],
            'sablonlar.*.diger_imzaci' => ['nullable', 'boolean'],
            'sablonlar.*.diger_imzaci_unvan' => ['nullable', 'string', 'max:120'],
            'sablonlar.*.diger_imzaci_ad_soyad' => ['nullable', 'string', 'max:150'],
            'sablonlar.*.belge_no_yazdir' => ['nullable', 'boolean'],
            'sablonlar.*.tarih_yazdir' => ['nullable', 'boolean'],
            'sablonlar.*.renk' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sablonlar.*.vurgu' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sablonlar.*.arka_plan' => ['nullable', 'file', 'max:5120', 'extensions:png,jpg,jpeg,svg,webp'],
            'arka_plan_kaldir' => ['nullable', 'array'],
            'arka_plan_kaldir.*' => ['string', Rule::in($sablonKodlari)],
        ], [
            'sablonlar.*.kod.required' => 'Belge kodu zorunludur.',
            'sablonlar.*.metin.required' => 'Belge metni zorunludur.',
            'sablonlar.*.kenarlik_olcusu.required' => 'Kenarlık ölçüsü zorunludur.',
            'sablonlar.*.kenarlik_olcusu.min' => 'Kenarlık ölçüsü 0 mm’den küçük olamaz.',
            'sablonlar.*.kenarlik_olcusu.max' => 'Kenarlık ölçüsü en fazla 40 mm olabilir.',
            'sablonlar.*.renk.regex' => 'Ana renk geçerli bir hex renk olmalıdır.',
            'sablonlar.*.vurgu.regex' => 'Vurgu rengi geçerli bir hex renk olmalıdır.',
            'sablonlar.*.arka_plan.extensions' => 'Şablon yalnızca PNG, JPG, SVG veya WEBP olabilir.',
            'sablonlar.*.arka_plan.max' => 'Şablon dosyası en fazla 5 MB olabilir.',
        ]);

        $digerImzaHatalari = [];
        foreach ($sablonKodlari as $kod) {
            $validated['sablonlar'][$kod]['egitmen_imzasi'] = $request->boolean("sablonlar.{$kod}.egitmen_imzasi");
            $validated['sablonlar'][$kod]['diger_imzaci'] = $request->boolean("sablonlar.{$kod}.diger_imzaci");
            $validated['sablonlar'][$kod]['belge_no_yazdir'] = $request->boolean("sablonlar.{$kod}.belge_no_yazdir");
            $validated['sablonlar'][$kod]['tarih_yazdir'] = $request->boolean("sablonlar.{$kod}.tarih_yazdir");

            if ($validated['sablonlar'][$kod]['diger_imzaci']) {
                if (trim((string) ($validated['sablonlar'][$kod]['diger_imzaci_unvan'] ?? '')) === '') {
                    $digerImzaHatalari["sablonlar.{$kod}.diger_imzaci_unvan"] = 'Diğer imzacı için unvan zorunludur.';
                }
                if (trim((string) ($validated['sablonlar'][$kod]['diger_imzaci_ad_soyad'] ?? '')) === '') {
                    $digerImzaHatalari["sablonlar.{$kod}.diger_imzaci_ad_soyad"] = 'Diğer imzacı için ad soyad zorunludur.';
                }
            }
        }

        if ($digerImzaHatalari !== []) {
            throw ValidationException::withMessages($digerImzaHatalari);
        }

        $dosyalar = [];
        foreach ($sablonKodlari as $kod) {
            $file = $request->file("sablonlar.{$kod}.arka_plan");
            if ($file) {
                $dosyalar[$kod] = $file;
            }
        }

        $onceki = $servis->formVerisi();
        $servis->kaydet(
            $validated,
            $dosyalar,
            array_values((array) ($validated['arka_plan_kaldir'] ?? [])),
        );

        LogKaydedici::kaydet(
            islem: 'sertifika_ayarlari.guncellendi',
            aciklama: 'Sertifika ayarları güncellendi.',
            eski: [
                'kurum_adi' => $onceki['kurum_adi'] ?? null,
            ],
            yeni: [
                'kurum_adi' => $validated['kurum_adi'] ?? null,
            ],
            konuAdi: 'Sertifika Ayarları',
        );

        $message = 'Sertifika ayarları kaydedildi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'form' => $servis->formVerisi(),
            ]);
        }

        return redirect()
            ->route('sabit-tanimlar.index', ['tab' => 'sertifika-ayarlari'])
            ->with('success', $message);
    }

    public function updateDigerAyarlar(Request $request, KursAyarServisi $servis): RedirectResponse|JsonResponse
    {
        $secenekler = SmsGonderimSecenegi::values();

        $validated = $request->validate([
            'sms_basvuru_onay' => ['required', Rule::in($secenekler)],
            'sms_basvuru_iptal' => ['required', Rule::in($secenekler)],
            'sms_basvuru_yedek' => ['required', Rule::in($secenekler)],
            'sms_metin_onay' => ['required', 'string', 'min:1', 'max:480'],
            'sms_metin_iptal' => ['required', 'string', 'min:1', 'max:480'],
            'sms_metin_yedek' => ['required', 'string', 'min:1', 'max:480'],
            'eposta_basvuru_onay' => ['required', Rule::in($secenekler)],
            'eposta_basvuru_iptal' => ['required', Rule::in($secenekler)],
            'eposta_basvuru_yedek' => ['required', Rule::in($secenekler)],
            'eposta_konu_onay' => ['required', 'string', 'min:1', 'max:200'],
            'eposta_konu_iptal' => ['required', 'string', 'min:1', 'max:200'],
            'eposta_konu_yedek' => ['required', 'string', 'min:1', 'max:200'],
            'eposta_metin_onay' => ['required', 'string', 'min:1', 'max:5000'],
            'eposta_metin_iptal' => ['required', 'string', 'min:1', 'max:5000'],
            'eposta_metin_yedek' => ['required', 'string', 'min:1', 'max:5000'],
            'onaylanmis_basvuru_kisi_iptal' => ['required', Rule::in(['evet', 'hayir'])],
            'kvkk_baslik' => ['nullable', 'string', 'max:200'],
            'kvkk_metni' => ['nullable', 'string', 'max:50000'],
            'aydinlatma_baslik' => ['nullable', 'string', 'max:200'],
            'aydinlatma_metni' => ['nullable', 'string', 'max:50000'],
        ], [
            'sms_basvuru_onay.required' => 'Onay SMS ayarı zorunludur.',
            'sms_basvuru_iptal.required' => 'İptal SMS ayarı zorunludur.',
            'sms_basvuru_yedek.required' => 'Yedek SMS ayarı zorunludur.',
            'sms_metin_onay.required' => 'Onay SMS metni zorunludur.',
            'sms_metin_iptal.required' => 'İptal SMS metni zorunludur.',
            'sms_metin_yedek.required' => 'Yedek SMS metni zorunludur.',
            'sms_metin_onay.max' => 'Onay SMS metni en fazla 480 karakter olabilir.',
            'sms_metin_iptal.max' => 'İptal SMS metni en fazla 480 karakter olabilir.',
            'sms_metin_yedek.max' => 'Yedek SMS metni en fazla 480 karakter olabilir.',
            'eposta_basvuru_onay.required' => 'Onay e-posta ayarı zorunludur.',
            'eposta_basvuru_iptal.required' => 'İptal e-posta ayarı zorunludur.',
            'eposta_basvuru_yedek.required' => 'Yedek e-posta ayarı zorunludur.',
            'eposta_konu_onay.required' => 'Onay e-posta konusu zorunludur.',
            'eposta_konu_iptal.required' => 'İptal e-posta konusu zorunludur.',
            'eposta_konu_yedek.required' => 'Yedek e-posta konusu zorunludur.',
            'eposta_metin_onay.required' => 'Onay e-posta metni zorunludur.',
            'eposta_metin_iptal.required' => 'İptal e-posta metni zorunludur.',
            'eposta_metin_yedek.required' => 'Yedek e-posta metni zorunludur.',
            'eposta_konu_onay.max' => 'Onay e-posta konusu en fazla 200 karakter olabilir.',
            'eposta_konu_iptal.max' => 'İptal e-posta konusu en fazla 200 karakter olabilir.',
            'eposta_konu_yedek.max' => 'Yedek e-posta konusu en fazla 200 karakter olabilir.',
            'eposta_metin_onay.max' => 'Onay e-posta metni en fazla 5000 karakter olabilir.',
            'eposta_metin_iptal.max' => 'İptal e-posta metni en fazla 5000 karakter olabilir.',
            'eposta_metin_yedek.max' => 'Yedek e-posta metni en fazla 5000 karakter olabilir.',
            'onaylanmis_basvuru_kisi_iptal.required' => 'Onaylanmış başvuru iptal ayarı zorunludur.',
            'onaylanmis_basvuru_kisi_iptal.in' => 'Onaylanmış başvuru iptal ayarı Evet veya Hayır olmalıdır.',
        ]);

        $onceki = $servis->formVerisi();
        unset($onceki['secenekler']);
        $servis->kaydet($validated);

        LogKaydedici::kaydet(
            islem: 'kurs_ayarlari.guncellendi',
            aciklama: 'Kurs diğer ayarları güncellendi.',
            eski: $onceki,
            yeni: $validated,
            konuAdi: 'Diğer Ayarlar',
        );

        $message = 'Diğer ayarlar kaydedildi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'form' => $servis->formVerisi(),
            ]);
        }

        return redirect()
            ->route('sabit-tanimlar.index', ['tab' => 'diger-ayarlar'])
            ->with('success', $message);
    }

    public function updateEtkinlikDigerAyarlar(Request $request, EtkinlikAyarServisi $servis): RedirectResponse|JsonResponse
    {
        $secenekler = SmsGonderimSecenegi::values();

        $validated = $request->validate([
            'sms_basvuru_onay' => ['required', Rule::in($secenekler)],
            'sms_basvuru_iptal' => ['required', Rule::in($secenekler)],
            'sms_basvuru_yedek' => ['required', Rule::in($secenekler)],
            'sms_metin_onay' => ['required', 'string', 'min:1', 'max:480'],
            'sms_metin_iptal' => ['required', 'string', 'min:1', 'max:480'],
            'sms_metin_yedek' => ['required', 'string', 'min:1', 'max:480'],
            'eposta_basvuru_onay' => ['required', Rule::in($secenekler)],
            'eposta_basvuru_iptal' => ['required', Rule::in($secenekler)],
            'eposta_basvuru_yedek' => ['required', Rule::in($secenekler)],
            'eposta_konu_onay' => ['required', 'string', 'min:1', 'max:200'],
            'eposta_konu_iptal' => ['required', 'string', 'min:1', 'max:200'],
            'eposta_konu_yedek' => ['required', 'string', 'min:1', 'max:200'],
            'eposta_metin_onay' => ['required', 'string', 'min:1', 'max:5000'],
            'eposta_metin_iptal' => ['required', 'string', 'min:1', 'max:5000'],
            'eposta_metin_yedek' => ['required', 'string', 'min:1', 'max:5000'],
            'onaylanmis_basvuru_kisi_iptal' => ['required', Rule::in(['evet', 'hayir'])],
            'kvkk_baslik' => ['nullable', 'string', 'max:200'],
            'kvkk_metni' => ['nullable', 'string', 'max:50000'],
            'aydinlatma_baslik' => ['nullable', 'string', 'max:200'],
            'aydinlatma_metni' => ['nullable', 'string', 'max:50000'],
        ], [
            'sms_basvuru_onay.required' => 'Onay SMS ayarı zorunludur.',
            'sms_basvuru_iptal.required' => 'İptal SMS ayarı zorunludur.',
            'sms_basvuru_yedek.required' => 'Yedek SMS ayarı zorunludur.',
            'sms_metin_onay.required' => 'Onay SMS metni zorunludur.',
            'sms_metin_iptal.required' => 'İptal SMS metni zorunludur.',
            'sms_metin_yedek.required' => 'Yedek SMS metni zorunludur.',
            'sms_metin_onay.max' => 'Onay SMS metni en fazla 480 karakter olabilir.',
            'sms_metin_iptal.max' => 'İptal SMS metni en fazla 480 karakter olabilir.',
            'sms_metin_yedek.max' => 'Yedek SMS metni en fazla 480 karakter olabilir.',
            'eposta_basvuru_onay.required' => 'Onay e-posta ayarı zorunludur.',
            'eposta_basvuru_iptal.required' => 'İptal e-posta ayarı zorunludur.',
            'eposta_basvuru_yedek.required' => 'Yedek e-posta ayarı zorunludur.',
            'eposta_konu_onay.required' => 'Onay e-posta konusu zorunludur.',
            'eposta_konu_iptal.required' => 'İptal e-posta konusu zorunludur.',
            'eposta_konu_yedek.required' => 'Yedek e-posta konusu zorunludur.',
            'eposta_metin_onay.required' => 'Onay e-posta metni zorunludur.',
            'eposta_metin_iptal.required' => 'İptal e-posta metni zorunludur.',
            'eposta_metin_yedek.required' => 'Yedek e-posta metni zorunludur.',
            'eposta_konu_onay.max' => 'Onay e-posta konusu en fazla 200 karakter olabilir.',
            'eposta_konu_iptal.max' => 'İptal e-posta konusu en fazla 200 karakter olabilir.',
            'eposta_konu_yedek.max' => 'Yedek e-posta konusu en fazla 200 karakter olabilir.',
            'eposta_metin_onay.max' => 'Onay e-posta metni en fazla 5000 karakter olabilir.',
            'eposta_metin_iptal.max' => 'İptal e-posta metni en fazla 5000 karakter olabilir.',
            'eposta_metin_yedek.max' => 'Yedek e-posta metni en fazla 5000 karakter olabilir.',
            'onaylanmis_basvuru_kisi_iptal.required' => 'Onaylanmış başvuru iptal ayarı zorunludur.',
            'onaylanmis_basvuru_kisi_iptal.in' => 'Onaylanmış başvuru iptal ayarı Evet veya Hayır olmalıdır.',
        ]);

        $onceki = $servis->formVerisi();
        unset($onceki['secenekler']);
        $servis->kaydet($validated);

        LogKaydedici::kaydet(
            islem: 'etkinlik_ayarlari.guncellendi',
            aciklama: 'Etkinlik diğer ayarları güncellendi.',
            eski: $onceki,
            yeni: $validated,
            konuAdi: 'Diğer Ayarlar',
        );

        $message = 'Diğer ayarlar kaydedildi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'form' => $servis->formVerisi(),
            ]);
        }

        return redirect()
            ->route('etkinlik-sabit-tanimlar.index', ['tab' => 'diger-ayarlar'])
            ->with('success', $message);
    }

    /**
     * @return array<string, string>
     */
    public static function visibleTabs(?string $module = self::MODULE_KURS): array
    {
        $tabs = $module === self::MODULE_ETKINLIK ? self::ETKINLIK_TABS : self::KURS_TABS;

        return array_diff_key($tabs, array_flip(self::HIDDEN_TABS));
    }

    private function resolveTab(Request $request, string $module = self::MODULE_KURS): string
    {
        $visible = self::visibleTabs($module);
        $default = array_key_first($visible) ?: ($module === self::MODULE_ETKINLIK ? 'etkinlik-tipleri' : 'evrak-tipleri');
        $tab = (string) $request->input('tab', $default);

        return array_key_exists($tab, $visible) ? $tab : $default;
    }

    /**
     * @return array<string, int>
     */
    private function tabCounts(): array
    {
        return [
            'kurs-tipleri' => KursTipi::query()->count(),
            'etkinlik-tipleri' => EtkinlikTipi::query()->count(),
            'evrak-tipleri' => EvrakTipi::query()->count(),
            'basvuru-durumlari' => BasvuruDurum::query()->count(),
            'etkinlik-basvuru-durumlari' => EtkinlikBasvuruDurum::query()->count(),
            'basari-durumlari' => BasariDurum::query()->count(),
            'iptal-gerekceleri' => IptalGerekce::query()->count(),
            'kurumlar' => Kurum::query()->count(),
            'sertifika-ayarlari' => count((array) config('sertifika.sablonlar', [])),
            'diger-ayarlar' => 6,
        ];
    }

    /**
     * @return array{items: \Illuminate\Support\Collection, sort: string, direction: string, filters: array<string, mixed>}
     */
    private function tabViewData(Request $request, string $tab, bool $useRequestFilters = true): array
    {
        $filtersSource = $useRequestFilters ? $request : new Request(['durum' => 'tumu']);

        return match ($tab) {
            'kurs-tipleri' => $this->searchSimple($filtersSource, KursTipi::query(), ['ad']),
            'etkinlik-tipleri' => $this->searchSimple($filtersSource, EtkinlikTipi::query(), ['ad']),
            'evrak-tipleri' => $this->searchSimple($filtersSource, EvrakTipi::query(), ['ad', 'aciklama']),
            'basvuru-durumlari' => $this->searchOrdered($filtersSource, BasvuruDurum::query(), ['ad', 'kod', 'aciklama']),
            'etkinlik-basvuru-durumlari' => $this->searchOrdered($filtersSource, EtkinlikBasvuruDurum::query(), ['ad', 'kod', 'aciklama']),
            'basari-durumlari' => $this->searchOrdered($filtersSource, BasariDurum::query(), ['ad', 'kod', 'aciklama']),
            'iptal-gerekceleri' => $this->searchOrdered($filtersSource, IptalGerekce::query(), ['ad', 'aciklama']),
            'kurumlar' => $this->searchOrdered($filtersSource, Kurum::query(), ['ad']),
            default => $this->searchSimple($filtersSource, KursTipi::query(), ['ad']),
        };
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $searchColumns
     * @return array{items: \Illuminate\Support\Collection, sort: string, direction: string, filters: array<string, mixed>}
     */
    private function searchSimple(Request $request, Builder $query, array $searchColumns): array
    {
        $this->applyCommonFilters($query, $request, $searchColumns);

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $sortable = ['ad' => 'ad', 'olusturma' => 'created_at'];

        if (isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderBy('ad', 'asc');
            $sort = '';
            $direction = 'asc';
        }

        $filters = $request->only(['q', 'durum', 'sort', 'direction']);
        $filters['durum'] = $this->normalizedDurum($request);

        return [
            'items' => $query->get(),
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $searchColumns
     * @return array{items: \Illuminate\Support\Collection, sort: string, direction: string, filters: array<string, mixed>}
     */
    private function searchOrdered(Request $request, Builder $query, array $searchColumns): array
    {
        $this->applyCommonFilters($query, $request, $searchColumns);

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $sortable = [
            'ad' => 'ad',
            'kod' => 'kod',
            'sira' => 'sira',
            'olusturma' => 'created_at',
        ];

        if (isset($sortable[$sort]) && ($sort !== 'kod' || $query->getModel()->isFillable('kod'))) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderBy('sira')->orderBy('ad');
            $sort = '';
            $direction = 'asc';
        }

        $filters = $request->only(['q', 'durum', 'sort', 'direction']);
        $filters['durum'] = $this->normalizedDurum($request);

        return [
            'items' => $query->get(),
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $searchColumns
     */
    private function applyCommonFilters(Builder $query, Request $request, array $searchColumns): void
    {
        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            if ($q !== '') {
                $query->where(function (Builder $outer) use ($q, $searchColumns) {
                    foreach ($searchColumns as $i => $column) {
                        if ($i === 0) {
                            $outer->where($column, 'like', "%{$q}%");
                        } else {
                            $outer->orWhere($column, 'like', "%{$q}%");
                        }
                    }
                });
            }
        }

        $durum = $this->normalizedDurum($request);
        if ($durum === 'aktif') {
            $query->where('aktif', true);
        } elseif ($durum === 'pasif') {
            $query->where('aktif', false);
        }
    }

    private function normalizedDurum(Request $request): string
    {
        $durum = (string) $request->input('durum', 'tumu');

        return in_array($durum, ['aktif', 'pasif', 'tumu'], true) ? $durum : 'tumu';
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $rules
     * @param  callable(array<string, mixed>, Request): array<string, mixed>  $attributes
     * @param  array<string, string>  $messages
     */
    private function storeEntity(
        Request $request,
        string $modelClass,
        string $logPrefix,
        string $label,
        array $rules,
        callable $attributes,
        array $messages = [],
    ): RedirectResponse|JsonResponse {
        $validated = $request->validate($rules, $messages + [
            'ad.required' => $label.' adı zorunludur.',
            'ad.unique' => 'Bu isimde bir kayıt zaten var.',
            'kod.required' => 'Kod zorunludur.',
            'kod.unique' => 'Bu kod zaten kullanılıyor.',
        ]);

        $payload = $attributes($validated, $request);
        /** @var Model $entity */
        $entity = $modelClass::query()->create($payload);

        LogKaydedici::kaydet(
            islem: $logPrefix.'.olusturuldu',
            aciklama: '"'.($entity->ad ?? $entity->getKey()).'" '.$label.' oluşturuldu.',
            konu: $entity,
            yeni: $payload,
            konuAdi: (string) ($entity->ad ?? $entity->getKey()),
        );

        $message = '"'.($entity->ad ?? $label).'" başarıyla oluşturuldu.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->route($this->indexRouteForModel($entity), ['tab' => $this->tabForModel($entity)])
            ->with('success', $message);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  callable(array<string, mixed>, Request): array<string, mixed>  $attributes
     * @param  list<string>  $snapshotKeys
     */
    private function updateEntity(
        Request $request,
        Model $entity,
        string $label,
        array $rules,
        callable $attributes,
        array $snapshotKeys,
    ): RedirectResponse|JsonResponse {
        $onceki = [];
        foreach ($snapshotKeys as $key) {
            $onceki[$key] = $entity->{$key};
            if (is_bool($onceki[$key]) || in_array($key, ['aktif'], true)) {
                $onceki[$key] = (bool) $entity->{$key};
            }
        }

        $validated = $request->validate($rules, [
            'ad.required' => $label.' adı zorunludur.',
            'ad.unique' => 'Bu isimde bir kayıt zaten var.',
        ]);

        $payload = $attributes($validated, $request);
        $entity->update($payload);

        $yeni = [];
        foreach ($snapshotKeys as $key) {
            $yeni[$key] = $entity->{$key};
            if (is_bool($yeni[$key]) || in_array($key, ['aktif'], true)) {
                $yeni[$key] = (bool) $entity->{$key};
            }
        }

        if ($onceki !== $yeni) {
            $table = $entity->getTable();
            LogKaydedici::kaydet(
                islem: $table.'.guncellendi',
                aciklama: '"'.($entity->ad ?? $entity->getKey()).'" '.$label.' güncellendi.',
                konu: $entity,
                eski: $onceki,
                yeni: $yeni,
                konuAdi: (string) ($entity->ad ?? $entity->getKey()),
            );
        }

        $message = '"'.($entity->ad ?? $label).'" başarıyla güncellendi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->route($this->indexRouteForModel($entity), ['tab' => $this->tabForModel($entity)])
            ->with('success', $message);
    }

    private function tabForModel(Model $entity): string
    {
        return match ($entity::class) {
            KursTipi::class => 'kurs-tipleri',
            EtkinlikTipi::class => 'etkinlik-tipleri',
            EvrakTipi::class => 'evrak-tipleri',
            BasvuruDurum::class => 'basvuru-durumlari',
            EtkinlikBasvuruDurum::class => 'etkinlik-basvuru-durumlari',
            BasariDurum::class => 'basari-durumlari',
            IptalGerekce::class => 'iptal-gerekceleri',
            Kurum::class => 'kurumlar',
            default => 'evrak-tipleri',
        };
    }

    private function indexRouteForModel(Model $entity): string
    {
        return match ($entity::class) {
            EtkinlikTipi::class, EtkinlikBasvuruDurum::class => 'etkinlik-sabit-tanimlar.index',
            default => 'sabit-tanimlar.index',
        };
    }
}
