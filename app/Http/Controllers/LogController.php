<?php

namespace App\Http\Controllers;

use App\Models\LogKayit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $perPage = $this->perPage($request);

        $loglar = $this->query($filters)
            ->paginate($perPage)
            ->withQueryString();

        return view('loglar.index', [
            'loglar' => $loglar,
            'filters' => $filters,
            'kullanicilar' => $this->kullaniciSecenekleri(),
            'islemler' => LogKayit::ISLEM_ETIKETLERI,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);

        $baslik = [
            'Tarih', 'İşlem', 'Konu Türü', 'Konu', 'Kullanıcı', 'Açıklama',
            'IP Adresi', 'Tarayıcı', 'Platform', 'Cihaz', 'HTTP', 'URL',
        ];

        $dosyaAdi = 'log-kayitlari-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($filters, $baslik) {
            $cikti = fopen('php://output', 'w');
            fwrite($cikti, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel uyumu)
            fputcsv($cikti, $baslik, ';');

            $this->query($filters)->chunk(500, function ($kayitlar) use ($cikti) {
                foreach ($kayitlar as $log) {
                    fputcsv($cikti, [
                        $log->created_at?->format('d.m.Y H:i:s'),
                        $log->islem_adi,
                        $log->konu_turu ?? '-',
                        $log->kurs ? ('Kurs #'.$log->kurs->kurs_no) : ($log->konu_adi ?? '-'),
                        $log->user?->tam_adi ?? 'Sistem',
                        $log->aciklama,
                        $log->ip_adresi,
                        $log->tarayici,
                        $log->platform,
                        $log->cihaz_tipi,
                        $log->http_metodu,
                        $log->url,
                    ], ';');
                }
            });

            fclose($cikti);
        }, $dosyaAdi, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<LogKayit>
     */
    private function query(array $filters): Builder
    {
        return LogKayit::query()
            ->with(['kurs:id,kurs_no', 'user:id,ad,soyad'])
            ->when($filters['q'] !== null, function (Builder $q) use ($filters) {
                $terim = '%'.$filters['q'].'%';
                $q->where(function (Builder $sub) use ($terim) {
                    $sub->where('aciklama', 'like', $terim)
                        ->orWhere('konu_adi', 'like', $terim)
                        ->orWhereHas('kurs', fn (Builder $k) => $k->where('kurs_no', 'like', $terim));
                });
            })
            ->when($filters['kurs_no'] !== null, fn (Builder $q) => $q->whereHas('kurs', fn (Builder $k) => $k->where('kurs_no', $filters['kurs_no'])))
            ->when($filters['islem'] !== null, fn (Builder $q) => $q->where('islem', $filters['islem']))
            ->when($filters['user_id'] !== null, fn (Builder $q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['baslangic'] !== null, fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['baslangic']))
            ->when($filters['bitis'] !== null, fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['bitis']))
            ->latest();
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $temizle = static fn (?string $deger) => ($deger !== null && trim($deger) !== '') ? trim($deger) : null;

        return [
            'q' => $temizle($request->query('q')),
            'kurs_no' => $temizle($request->query('kurs_no')),
            'islem' => array_key_exists((string) $request->query('islem'), LogKayit::ISLEM_ETIKETLERI) ? $request->query('islem') : null,
            'user_id' => $request->filled('user_id') ? (int) $request->query('user_id') : null,
            'baslangic' => $temizle($request->query('baslangic')),
            'bitis' => $temizle($request->query('bitis')),
            'per_page' => (int) $request->query('per_page', 20),
        ];
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        return in_array($perPage, [20, 50, 100], true) ? $perPage : 20;
    }

    /**
     * İşlem yapmış olan kullanıcıların filtre için listesi.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function kullaniciSecenekleri()
    {
        return User::query()
            ->whereIn('id', LogKayit::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('ad')
            ->orderBy('soyad')
            ->get(['id', 'ad', 'soyad']);
    }
}
