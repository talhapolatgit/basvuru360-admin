<?php

namespace App\Http\Controllers;

use App\Models\Merkez;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MerkezYetkiController extends Controller
{
    public function index(Request $request): View
    {
        [$kullanicilar, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'kullanicilar' => $kullanicilar,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
            'merkezler' => Merkez::query()->orderBy('ad')->get(),
            'roller' => Rol::query()->where('aktif', true)->orderBy('sira')->orderBy('ad')->get(),
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('merkez-yetkileri._results', $viewData);
        }

        return view('merkez-yetkileri.index', $viewData);
    }

    public function export(Request $request): StreamedResponse
    {
        [$kullanicilar] = $this->search($request, paginate: false);

        $filename = 'merkez-yetkileri-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($kullanicilar) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Ad Soyad', 'E-posta', 'Roller', 'Merkezler', 'Merkez Adedi', 'Durum'], ';');

            $kullanicilar->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $kullanici) {
                    fputcsv($handle, [
                        $kullanici->tam_adi,
                        $kullanici->email,
                        $kullanici->roller->pluck('ad')->implode(', '),
                        $kullanici->atananMerkezler->pluck('ad')->implode(', '),
                        $kullanici->merkez_sayisi,
                        $kullanici->aktif ? 'Aktif' : 'Pasif',
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
    private function search(Request $request, bool $paginate = true): array
    {
        $query = User::query()
            ->with(['roller', 'atananMerkezler'])
            ->withCount('atananMerkezler as merkez_sayisi');

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            if ($q !== '') {
                $query->where(function (Builder $outer) use ($q) {
                    $outer->where('ad', 'like', "%{$q}%")
                        ->orWhere('soyad', 'like', "%{$q}%")
                        ->orWhereRaw("CONCAT(ad, ' ', soyad) like ?", ["%{$q}%"])
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('tc_kimlik_no', 'like', "%{$q}%");
                });
            }
        }

        if ($request->filled('rol_id')) {
            $rolId = $request->integer('rol_id');
            $query->whereHas('roller', fn (Builder $r) => $r->where('roller.id', $rolId));
        }

        if ($request->filled('merkez_id')) {
            $merkezId = $request->integer('merkez_id');
            $query->whereHas('atananMerkezler', fn (Builder $m) => $m->where('merkezler.id', $merkezId));
        }

        $durum = (string) $request->input('durum', 'tumu');
        if ($durum === 'yetkili') {
            $query->has('atananMerkezler');
        } elseif ($durum === 'yetkisiz') {
            $query->doesntHave('atananMerkezler');
        } else {
            $durum = 'tumu';
        }

        $aktif = (string) $request->input('aktif', 'tumu');
        if ($aktif === 'aktif') {
            $query->where('aktif', true);
        } elseif ($aktif === 'pasif') {
            $query->where('aktif', false);
        } else {
            $aktif = 'tumu';
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'ad' => ['ad', 'soyad'],
            'email' => ['email'],
            'merkez_sayisi' => ['merkez_sayisi'],
        ];

        if (isset($sortable[$sort])) {
            foreach ($sortable[$sort] as $column) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->orderBy('ad', 'asc')->orderBy('soyad', 'asc');
            $sort = '';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only(['q', 'rol_id', 'merkez_id', 'durum', 'aktif', 'per_page', 'sort', 'direction']);
        $filters['durum'] = $durum;
        $filters['aktif'] = $aktif;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }
}
