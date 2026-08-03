<?php

namespace App\Http\Controllers;

use App\Enums\EtkinlikDurum;
use App\Enums\KursDurum;
use App\Models\Etkinlik;
use App\Models\KursDers;
use App\Models\Merkez;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TakvimController extends Controller
{
    private const ETKINLIK_RENK = '#f1416c';

    /**
     * Takvim sayfası — yalnızca görünür ayın verisini yükler.
     */
    public function index(Request $request): View
    {
        $egitmen = $this->resolveEgitmen($request);
        $merkez = $this->resolveMerkez($request);
        $etkinlikGorebilir = $request->user()?->hasYetki('etkinlik.goruntule') && ! $egitmen;
        $ay = $this->resolveAy($request);
        $payload = $this->monthPayload($ay, $egitmen, $merkez, $etkinlikGorebilir);

        return view('takvim.index', [
            'takvimJson' => $payload['items'],
            'ay' => $ay,
            'etkinlikGorebilir' => $etkinlikGorebilir,
            'egitmen' => $egitmen,
            'merkez' => $merkez,
        ]);
    }

    /**
     * Seçilen aya ait takvim kayıtlarını JSON olarak döndürür.
     */
    public function data(Request $request): JsonResponse
    {
        $egitmen = $this->resolveEgitmen($request);
        $merkez = $this->resolveMerkez($request);
        $etkinlikGorebilir = $request->user()?->hasYetki('etkinlik.goruntule') && ! $egitmen;
        $ay = $this->resolveAy($request);
        $payload = $this->monthPayload($ay, $egitmen, $merkez, $etkinlikGorebilir);

        return response()->json($payload);
    }

    /**
     * Seçilen aya ait takvimi PDF olarak indirir.
     */
    public function exportPdf(Request $request): Response
    {
        $egitmen = $this->resolveEgitmen($request);
        $merkez = $this->resolveMerkez($request);
        $etkinlikGorebilir = $request->user()?->hasYetki('etkinlik.goruntule') && ! $egitmen;
        $ay = $this->resolveAy($request);
        $payload = $this->monthPayload($ay, $egitmen, $merkez, $etkinlikGorebilir);

        [$yil, $ayNo] = array_map('intval', explode('-', $ay));
        $ayBaslangic = Carbon::create($yil, $ayNo, 1)->startOfDay();
        $ayBitis = $ayBaslangic->copy()->endOfMonth();

        $items = collect($payload['items']);
        $byDate = $items->groupBy('tarih');

        $ekId = $egitmen ? '-o'.$egitmen->id : ($merkez ? '-m'.$merkez->id : '');
        $filename = 'takvim-'.$ay.$ekId.'.pdf';

        $pdf = Pdf::loadView('takvim.takvim_pdf', [
            'ayBaslangic' => $ayBaslangic,
            'ayBitis' => $ayBitis,
            'byDate' => $byDate,
            'items' => $items,
            'etkinlikGorebilir' => $etkinlikGorebilir,
            'egitmen' => $egitmen,
            'merkez' => $merkez,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * @return array{ay: string, items: list<array<string, mixed>>, meta: array{kurs: int, etkinlik: int, kayit: int}}
     */
    private function monthPayload(string $ay, ?User $egitmen, ?Merkez $merkez, bool $etkinlikGorebilir): array
    {
        [$yil, $ayNo] = array_map('intval', explode('-', $ay));
        $ayBaslangic = Carbon::create($yil, $ayNo, 1)->startOfDay();
        $ayBitis = $ayBaslangic->copy()->endOfMonth();
        $bas = $ayBaslangic->toDateString();
        $bit = $ayBitis->toDateString();

        $dersler = $this->derslerQuery($egitmen, $merkez)
            ->whereBetween('tarih', [$bas, $bit])
            ->orderBy('tarih')
            ->orderBy('baslangic_saati')
            ->get();

        $items = $this->mapKursItems($dersler);

        if ($etkinlikGorebilir) {
            $etkinlikler = $this->etkinliklerQuery($merkez)
                ->where('baslangic_tarihi', '<=', $bit)
                ->where('bitis_tarihi', '>=', $bas)
                ->get();

            $items = $items->concat($this->mapEtkinlikItems($etkinlikler, $bas, $bit));
        }

        $items = $items
            ->sortBy([
                ['tarih', 'asc'],
                ['baslangic', 'asc'],
                ['ad', 'asc'],
            ])
            ->values();

        return [
            'ay' => $ay,
            'items' => $items->all(),
            'meta' => [
                'kurs' => $items->where('tip', 'kurs')->pluck('kurs_id')->unique()->filter()->count(),
                'etkinlik' => $items->where('tip', 'etkinlik')->pluck('etkinlik_id')->unique()->filter()->count(),
                'kayit' => $items->count(),
            ],
        ];
    }

    private function resolveAy(Request $request): string
    {
        $ay = (string) $request->input('ay', now()->format('Y-m'));

        if (! preg_match('/^\d{4}-\d{2}$/', $ay)) {
            return now()->format('Y-m');
        }

        [$yil, $ayNo] = array_map('intval', explode('-', $ay));
        if ($ayNo < 1 || $ayNo > 12 || $yil < 2000 || $yil > 2100) {
            return now()->format('Y-m');
        }

        return sprintf('%04d-%02d', $yil, $ayNo);
    }

    /**
     * @param  Collection<int, KursDers>  $dersler
     * @return Collection<int, array<string, mixed>>
     */
    private function mapKursItems(Collection $dersler): Collection
    {
        return $dersler->map(function (KursDers $ders) {
            $kurs = $ders->kurs;
            $kursAdi = $kurs?->brans?->ad ?? ('Kurs #'.$kurs?->kurs_no);

            return [
                'tip' => 'kurs',
                'id' => 'kurs-'.$ders->id,
                'tarih' => $ders->tarih?->format('Y-m-d'),
                'gun' => $ders->tarih?->locale('tr')->isoFormat('dddd'),
                'baslangic' => substr((string) $ders->baslangic_saati, 0, 5),
                'bitis' => substr((string) $ders->bitis_saati, 0, 5),
                'ders_saati' => rtrim(rtrim(number_format((float) $ders->ders_saati, 1, '.', ''), '0'), '.'),
                'sinif' => $ders->sinif ?: '',
                'kurs_id' => $kurs?->id,
                'kurs_no' => $kurs?->kurs_no,
                'ad' => $kursAdi,
                'kurs_adi' => $kursAdi,
                'merkez' => $kurs?->merkez?->ad ?? '',
                'renk' => $kurs?->takvim_rengi ?: '#3699ff',
                'yoklama_alindi' => (bool) $ders->yoklama_alindi,
                'iptal_edildi' => (bool) $ders->iptal_edildi,
                'url' => $kurs ? route('kurslar.show', $kurs) : null,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, Etkinlik>  $etkinlikler
     * @return Collection<int, array<string, mixed>>
     */
    private function mapEtkinlikItems(Collection $etkinlikler, ?string $minDate = null, ?string $maxDate = null): Collection
    {
        $items = collect();

        foreach ($etkinlikler as $etkinlik) {
            if (! $etkinlik->baslangic_tarihi || ! $etkinlik->bitis_tarihi) {
                continue;
            }

            $start = $etkinlik->baslangic_tarihi->copy()->startOfDay();
            $end = $etkinlik->bitis_tarihi->copy()->startOfDay();

            if ($minDate) {
                $min = Carbon::parse($minDate)->startOfDay();
                if ($start->lt($min)) {
                    $start = $min;
                }
            }
            if ($maxDate) {
                $max = Carbon::parse($maxDate)->startOfDay();
                if ($end->gt($max)) {
                    $end = $max;
                }
            }

            if ($start->gt($end)) {
                continue;
            }

            $renk = $etkinlik->takvim_rengi ?: self::ETKINLIK_RENK;
            $ad = $etkinlik->ad ?: ('Etkinlik #'.$etkinlik->etkinlik_no);
            $url = route('etkinlikler.show', $etkinlik);
            $merkezAd = $etkinlik->merkez?->ad ?? '';
            $tipAdi = $etkinlik->etkinlikTipi?->ad ?? '';
            $iptal = $etkinlik->durum === EtkinlikDurum::Iptal;

            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                $items->push([
                    'tip' => 'etkinlik',
                    'id' => 'etkinlik-'.$etkinlik->id.'-'.$day->format('Y-m-d'),
                    'etkinlik_id' => $etkinlik->id,
                    'tarih' => $day->format('Y-m-d'),
                    'gun' => $day->locale('tr')->isoFormat('dddd'),
                    'baslangic' => '',
                    'bitis' => '',
                    'ders_saati' => '',
                    'sinif' => $tipAdi,
                    'ad' => $ad,
                    'kurs_adi' => $ad,
                    'kurs_no' => $etkinlik->etkinlik_no,
                    'merkez' => $merkezAd,
                    'renk' => $renk,
                    'yoklama_alindi' => false,
                    'iptal_edildi' => $iptal,
                    'url' => $url,
                ]);
            }
        }

        return $items->values();
    }

    /**
     * Aktif kurs derslerini opsiyonel öğretmen ve merkez filtresiyle döndürür.
     */
    private function derslerQuery(?User $egitmen, ?Merkez $merkez = null): Builder
    {
        $user = request()->user();
        if ($user && $user->sadeceAtananKurslariGorur()) {
            $egitmen = $user;
        }

        return KursDers::query()
            ->select([
                'kurs_dersleri.id',
                'kurs_dersleri.kurs_id',
                'kurs_dersleri.tarih',
                'kurs_dersleri.baslangic_saati',
                'kurs_dersleri.bitis_saati',
                'kurs_dersleri.ders_saati',
                'kurs_dersleri.sinif',
                'kurs_dersleri.yoklama_alindi',
                'kurs_dersleri.iptal_edildi',
            ])
            ->whereHas('kurs', function (Builder $q) use ($egitmen, $merkez, $user) {
                $q->where('durum', KursDurum::Aktif);

                if ($egitmen) {
                    $q->where(function (Builder $kq) use ($egitmen) {
                        $kq->whereHas('ogretmenler', fn (Builder $o) => $o->whereKey($egitmen->id))
                            ->orWhere('kurslar.ogretmen_id', $egitmen->id);
                    });
                }

                if ($merkez) {
                    $q->where('merkez_id', $merkez->id);
                }

                if ($user && $user->sadeceYetkiliMerkezleriGorur()) {
                    $merkezIds = $user->yetkiliMerkezIdleri();
                    if ($merkezIds === []) {
                        $q->whereRaw('0 = 1');
                    } else {
                        $q->whereIn('merkez_id', $merkezIds);
                    }
                }

                $q->kullaniciKurumKapsami($user);
            })
            ->with([
                'kurs:id,kurs_no,brans_id,merkez_id,takvim_rengi',
                'kurs.brans:id,ad',
                'kurs.merkez:id,ad',
            ]);
    }

    /**
     * Aktif etkinlikleri yetki kapsamı ve opsiyonel merkez filtresiyle döndürür.
     */
    private function etkinliklerQuery(?Merkez $merkez = null): Builder
    {
        $user = request()->user();

        $query = Etkinlik::query()
            ->select([
                'etkinlikler.id',
                'etkinlikler.etkinlik_no',
                'etkinlikler.ad',
                'etkinlikler.merkez_id',
                'etkinlikler.etkinlik_tipi_id',
                'etkinlikler.baslangic_tarihi',
                'etkinlikler.bitis_tarihi',
                'etkinlikler.takvim_rengi',
                'etkinlikler.durum',
            ])
            ->where('durum', EtkinlikDurum::Aktif)
            ->whereNotNull('baslangic_tarihi')
            ->whereNotNull('bitis_tarihi')
            ->with([
                'merkez:id,ad',
                'etkinlikTipi:id,ad',
            ]);

        if ($merkez) {
            $query->where('merkez_id', $merkez->id);
        }

        if ($user && $user->sadeceAtananEtkinlikleriGorur()) {
            $query->whereHas('sorumlular', fn (Builder $q) => $q->where('users.id', $user->id));
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

        return $query->orderBy('baslangic_tarihi')->orderBy('ad');
    }

    private function resolveEgitmen(Request $request): ?User
    {
        $user = $request->user();
        if ($user && $user->sadeceAtananKurslariGorur()) {
            return $user;
        }

        $ogretmenId = $request->integer('ogretmen') ?: null;

        return $ogretmenId ? User::find($ogretmenId) : null;
    }

    private function resolveMerkez(Request $request): ?Merkez
    {
        $merkezId = $request->integer('merkez') ?: null;
        if (! $merkezId) {
            return null;
        }

        return Merkez::query()->kullaniciKapsami($request->user())->find($merkezId);
    }
}
