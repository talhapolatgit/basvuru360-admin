<?php

namespace App\Http\Controllers;

use App\Enums\EtkinlikDurum;
use App\Enums\KursDurum;
use App\Models\Alan;
use App\Models\BasvuruDurum;
use App\Models\Brans;
use App\Models\Etkinlik;
use App\Models\EtkinlikBasvuru;
use App\Models\EtkinlikBasvuruDurum;
use App\Models\EtkinlikTipi;
use App\Models\KursBasvuru;
use App\Models\KursDers;
use App\Models\Kurs;
use App\Models\User;
use App\Models\Merkez;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function anasayfa(): View
    {
        $bugun = now()->toDateString();
        $haftaSonu = now()->addDays(7)->toDateString();
        $user = request()->user();
        $sadeceAtanan = $user && $user->sadeceAtananKurslariGorur();
        $sadeceYetkiliMerkez = $user && $user->sadeceYetkiliMerkezleriGorur();
        $yetkiliMerkezIds = $sadeceYetkiliMerkez ? $user->yetkiliMerkezIdleri() : null;
        $sadeceKendiKurum = $user && $user->sadeceKendiKurumlariniGorur();

        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        $onayBekliyorId = BasvuruDurum::idByKod('onay_bekliyor');
        $etkinlikKesinKayitId = EtkinlikBasvuruDurum::idByKod('kesin_kayit');
        $etkinlikOnayBekliyorId = EtkinlikBasvuruDurum::idByKod('onay_bekliyor');

        $atananKursKapsami = function (Builder $q) use ($user) {
            $q->whereHas('ogretmenler', fn (Builder $o) => $o->whereKey($user->id))
                ->orWhere('kurslar.ogretmen_id', $user->id);
        };

        $merkezKapsami = function (Builder $q) use ($yetkiliMerkezIds) {
            if ($yetkiliMerkezIds === []) {
                $q->whereRaw('0 = 1');
            } else {
                $q->whereIn('merkez_id', $yetkiliMerkezIds);
            }
        };

        $kursKapsamiUygula = function (Builder $q) use ($sadeceAtanan, $atananKursKapsami, $sadeceYetkiliMerkez, $merkezKapsami, $sadeceKendiKurum, $user) {
            if ($sadeceAtanan) {
                $q->where($atananKursKapsami);
            }
            if ($sadeceYetkiliMerkez) {
                $q->where($merkezKapsami);
            }
            if ($sadeceKendiKurum) {
                $q->kullaniciKurumKapsami($user);
            }
        };

        $etkinlikKapsamiUygula = function (Builder $q) use ($user) {
            $this->applyEtkinlikScope($q, $user);
        };

        $yaklasanDersler = KursDers::query()
            ->whereDate('tarih', '>=', $bugun)
            ->whereDate('tarih', '<=', $haftaSonu)
            ->where('iptal_edildi', false)
            ->whereHas('kurs', function (Builder $q) use ($kursKapsamiUygula) {
                $q->where('durum', KursDurum::Aktif);
                $kursKapsamiUygula($q);
            })
            ->with(['kurs.brans', 'kurs.merkez'])
            ->orderBy('tarih')
            ->orderBy('baslangic_saati')
            ->limit(12)
            ->get();

        $yaklasanEtkinliklerQuery = Etkinlik::query()
            ->where('durum', EtkinlikDurum::Aktif)
            ->whereDate('baslangic_tarihi', '>=', $bugun)
            ->whereDate('baslangic_tarihi', '<=', $haftaSonu)
            ->with(['merkez', 'etkinlikTipi']);
        $etkinlikKapsamiUygula($yaklasanEtkinliklerQuery);
        $yaklasanEtkinlikler = $yaklasanEtkinliklerQuery
            ->orderBy('baslangic_tarihi')
            ->limit(12)
            ->get();

        $sonBasvurularQuery = KursBasvuru::query()
            ->with(['kisi', 'basvuran', 'durum', 'kurs.brans', 'kurs.merkez']);

        if ($sadeceAtanan || $sadeceYetkiliMerkez || $sadeceKendiKurum) {
            $sonBasvurularQuery->whereHas('kurs', $kursKapsamiUygula);
        }

        $sonBasvurular = $sonBasvurularQuery
            ->latest('kurs_basvurulari.created_at')
            ->limit(8)
            ->get();

        $sonEtkinlikBasvurulariQuery = EtkinlikBasvuru::query()
            ->with(['kisi', 'basvuran', 'durum', 'etkinlik.merkez', 'etkinlik.etkinlikTipi'])
            ->whereHas('etkinlik', $etkinlikKapsamiUygula);

        $sonEtkinlikBasvurulari = $sonEtkinlikBasvurulariQuery
            ->latest('etkinlik_basvurulari.created_at')
            ->limit(8)
            ->get();

        $aktifKursQuery = Kurs::query()->where('durum', KursDurum::Aktif);
        $kursKapsamiUygula($aktifKursQuery);

        $bugunkuDersQuery = KursDers::query()
            ->whereDate('tarih', $bugun)
            ->where('iptal_edildi', false)
            ->whereHas('kurs', function (Builder $q) use ($kursKapsamiUygula) {
                $q->where('durum', KursDurum::Aktif);
                $kursKapsamiUygula($q);
            });

        $onayBekleyenQuery = $onayBekliyorId
            ? KursBasvuru::query()->where('durum_id', $onayBekliyorId)
            : null;
        $kesinKayitQuery = $kesinKayitId
            ? KursBasvuru::query()->where('durum_id', $kesinKayitId)
            : null;

        if ($sadeceAtanan || $sadeceYetkiliMerkez || $sadeceKendiKurum) {
            $onayBekleyenQuery?->whereHas('kurs', $kursKapsamiUygula);
            $kesinKayitQuery?->whereHas('kurs', $kursKapsamiUygula);
        }

        $aktifEtkinlikQuery = Etkinlik::query()->where('durum', EtkinlikDurum::Aktif);
        $etkinlikKapsamiUygula($aktifEtkinlikQuery);

        $basvuruyaAcikEtkinlikQuery = Etkinlik::query()->basvuruDurumu('acik');
        $etkinlikKapsamiUygula($basvuruyaAcikEtkinlikQuery);

        $etkinlikOnayBekleyenQuery = $etkinlikOnayBekliyorId
            ? EtkinlikBasvuru::query()->where('durum_id', $etkinlikOnayBekliyorId)
            : null;
        $etkinlikKesinKayitQuery = $etkinlikKesinKayitId
            ? EtkinlikBasvuru::query()->where('durum_id', $etkinlikKesinKayitId)
            : null;

        $etkinlikOnayBekleyenQuery?->whereHas('etkinlik', $etkinlikKapsamiUygula);
        $etkinlikKesinKayitQuery?->whereHas('etkinlik', $etkinlikKapsamiUygula);

        return view('dashboard.anasayfa', [
            'aktifKursSayisi' => $aktifKursQuery->count(),
            'bugunkuDersSayisi' => $bugunkuDersQuery->count(),
            'onayBekleyenSayisi' => $onayBekleyenQuery?->count() ?? 0,
            'kesinKayitSayisi' => $kesinKayitQuery?->count() ?? 0,
            'yaklasanDersler' => $yaklasanDersler,
            'sonBasvurular' => $sonBasvurular,
            'aktifEtkinlikSayisi' => $aktifEtkinlikQuery->count(),
            'basvuruyaAcikEtkinlikSayisi' => $basvuruyaAcikEtkinlikQuery->count(),
            'etkinlikOnayBekleyenSayisi' => $etkinlikOnayBekleyenQuery?->count() ?? 0,
            'etkinlikKesinKayitSayisi' => $etkinlikKesinKayitQuery?->count() ?? 0,
            'yaklasanEtkinlikler' => $yaklasanEtkinlikler,
            'sonEtkinlikBasvurulari' => $sonEtkinlikBasvurulari,
        ]);
    }

    public function dashboard(): View
    {
        $kursDurumDagilim = collect(KursDurum::cases())->map(fn (KursDurum $durum) => [
            'kod' => $durum->value,
            'label' => $durum->label(),
            'count' => Kurs::query()->where('durum', $durum)->count(),
        ])->values();

        $etkinlikDurumDagilim = collect(EtkinlikDurum::cases())->map(fn (EtkinlikDurum $durum) => [
            'kod' => $durum->value,
            'label' => $durum->label(),
            'count' => Etkinlik::query()->where('durum', $durum)->count(),
        ])->values();

        $basvuruDurumDagilim = BasvuruDurum::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->orderBy('ad')
            ->withCount('basvurular')
            ->get()
            ->map(fn (BasvuruDurum $durum) => [
                'label' => $durum->ad,
                'sinif' => $durum->statusClass(),
                'count' => $durum->basvurular_count,
            ]);

        $etkinlikBasvuruDurumDagilim = EtkinlikBasvuruDurum::query()
            ->where('aktif', true)
            ->orderBy('sira')
            ->orderBy('ad')
            ->withCount('basvurular')
            ->get()
            ->map(fn (EtkinlikBasvuruDurum $durum) => [
                'label' => $durum->ad,
                'sinif' => $durum->statusClass(),
                'count' => $durum->basvurular_count,
            ]);

        $aylikBasvuru = collect(range(5, 0))->map(function (int $geri) {
            $ay = now()->startOfMonth()->subMonths($geri);
            $son = (clone $ay)->endOfMonth();

            return [
                'label' => $ay->locale('tr')->isoFormat('MMM YY'),
                'kurs' => KursBasvuru::query()
                    ->whereBetween('kurs_basvurulari.created_at', [$ay, $son])
                    ->count(),
                'etkinlik' => EtkinlikBasvuru::query()
                    ->whereBetween('etkinlik_basvurulari.created_at', [$ay, $son])
                    ->count(),
            ];
        });

        $merkezDagilim = Merkez::query()
            ->withCount([
                'kurslar as aktif_kurs_sayisi' => fn (Builder $q) => $q->where('durum', KursDurum::Aktif),
            ])
            ->orderByDesc('aktif_kurs_sayisi')
            ->limit(6)
            ->get()
            ->map(fn (Merkez $merkez) => [
                'label' => $merkez->ad,
                'count' => $merkez->aktif_kurs_sayisi,
            ]);

        $merkezEtkinlikDagilim = Merkez::query()
            ->withCount([
                'etkinlikler as aktif_etkinlik_sayisi' => fn (Builder $q) => $q->where('durum', EtkinlikDurum::Aktif),
            ])
            ->orderByDesc('aktif_etkinlik_sayisi')
            ->limit(6)
            ->get()
            ->map(fn (Merkez $merkez) => [
                'label' => $merkez->ad,
                'count' => $merkez->aktif_etkinlik_sayisi,
            ]);

        $bransDagilim = Brans::query()
            ->withCount([
                'kurslar as kurs_sayisi',
            ])
            ->orderByDesc('kurs_sayisi')
            ->limit(6)
            ->get()
            ->map(fn (Brans $brans) => [
                'label' => $brans->ad,
                'count' => $brans->kurs_sayisi,
            ]);

        $etkinlikTipiDagilim = EtkinlikTipi::query()
            ->withCount('etkinlikler')
            ->orderByDesc('etkinlikler_count')
            ->limit(6)
            ->get()
            ->map(fn (EtkinlikTipi $tip) => [
                'label' => $tip->ad,
                'count' => $tip->etkinlikler_count,
            ]);

        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        $etkinlikKesinKayitId = EtkinlikBasvuruDurum::idByKod('kesin_kayit');

        return view('dashboard.dashboard', [
            'toplamKurs' => Kurs::query()->count(),
            'aktifKurs' => Kurs::query()->where('durum', KursDurum::Aktif)->count(),
            'toplamBasvuru' => KursBasvuru::query()->count(),
            'kesinKayit' => $kesinKayitId
                ? KursBasvuru::query()->where('durum_id', $kesinKayitId)->count()
                : 0,
            'toplamEtkinlik' => Etkinlik::query()->count(),
            'aktifEtkinlik' => Etkinlik::query()->where('durum', EtkinlikDurum::Aktif)->count(),
            'toplamEtkinlikBasvuru' => EtkinlikBasvuru::query()->count(),
            'etkinlikKesinKayit' => $etkinlikKesinKayitId
                ? EtkinlikBasvuru::query()->where('durum_id', $etkinlikKesinKayitId)->count()
                : 0,
            'toplamKullanici' => User::query()->count(),
            'ogretmenSayisi' => User::query()->whereHas('roller', fn ($q) => $q->where('kod', 'ogretmen'))->count(),
            'personelSayisi' => User::query()->whereHas('roller', fn ($q) => $q->where('kod', 'personel'))->count(),
            'merkezSayisi' => Merkez::query()->count(),
            'alanSayisi' => Alan::query()->count(),
            'bransSayisi' => Brans::query()->count(),
            'etkinlikTipiSayisi' => EtkinlikTipi::query()->count(),
            'kursDurumDagilim' => $kursDurumDagilim,
            'etkinlikDurumDagilim' => $etkinlikDurumDagilim,
            'basvuruDurumDagilim' => $basvuruDurumDagilim,
            'etkinlikBasvuruDurumDagilim' => $etkinlikBasvuruDurumDagilim,
            'aylikBasvuru' => $aylikBasvuru,
            'merkezDagilim' => $merkezDagilim,
            'merkezEtkinlikDagilim' => $merkezEtkinlikDagilim,
            'bransDagilim' => $bransDagilim,
            'etkinlikTipiDagilim' => $etkinlikTipiDagilim,
        ]);
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
}
