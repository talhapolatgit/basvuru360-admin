<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\HaftaGunu;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\V1\EtkinlikResource;
use App\Http\Resources\Api\V1\KursResource;
use App\Models\Alan;
use App\Models\Brans;
use App\Models\Etkinlik;
use App\Models\EtkinlikTipi;
use App\Models\Kurs;
use App\Models\KursGun;
use App\Models\KursTipi;
use App\Models\Merkez;
use App\Models\PortalSayfa;
use App\Services\Jwt\JwtTokenServisi;
use App\Services\PortalSayfaServisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnexpectedValueException;

class PortalSayfaController extends ApiController
{
    public function index(PortalSayfaServisi $servis): JsonResponse
    {
        $items = $servis->liste()
            ->where('menude_goster', true)
            ->values()
            ->map(fn (PortalSayfa $sayfa) => $this->serializeSayfa($sayfa, $servis));

        return $this->success(['items' => $items]);
    }

    public function show(string $slug, PortalSayfaServisi $servis): JsonResponse
    {
        $sayfa = $servis->findBySlug($slug);
        if (! $sayfa) {
            return $this->error('Sayfa bulunamadı.', 404);
        }

        return $this->success($this->serializeSayfa($sayfa, $servis));
    }

    public function anasayfaLogo(string $slug, PortalSayfaServisi $servis): BinaryFileResponse|Response
    {
        $sayfa = $servis->findBySlug($slug);
        if (! $sayfa) {
            return response('Logo bulunamadı.', 404);
        }

        return $this->gorselYaniti($servis->anasayfaLogoDosyaYolu($sayfa));
    }

    public function anasayfaMenuArkaplan(string $slug, PortalSayfaServisi $servis): BinaryFileResponse|Response
    {
        $sayfa = $servis->findBySlug($slug);
        if (! $sayfa) {
            return response('Arkaplan bulunamadı.', 404);
        }

        return $this->gorselYaniti($servis->anasayfaMenuArkaplanDosyaYolu($sayfa));
    }

    public function sidebarIkon(string $slug, PortalSayfaServisi $servis): BinaryFileResponse|Response
    {
        $sayfa = $servis->findBySlug($slug);
        if (! $sayfa) {
            return response('İkon bulunamadı.', 404);
        }

        return $this->gorselYaniti($servis->sidebarIkonDosyaYolu($sayfa));
    }

    /**
     * Sayfa kapsamındaki kayıtlarla dolu filtre seçenekleri.
     * Branş listesi isteğe bağlı alan_id ile daraltılır.
     */
    public function filtreler(string $slug, Request $request, PortalSayfaServisi $servis, JwtTokenServisi $jwt): JsonResponse
    {
        $sayfa = $servis->findBySlug($slug);
        if (! $sayfa) {
            return $this->error('Sayfa bulunamadı.', 404);
        }

        if ($denied = $this->sadeceGirisEngeli($sayfa, $request, $jwt)) {
            return $denied;
        }

        $validated = $request->validate([
            'alan_id' => ['nullable', 'integer', 'exists:alanlar,id'],
        ]);

        $merkezIds = collect();
        $alanIds = collect();
        $bransIds = collect();
        $kursTipiIds = collect();
        $etkinlikTipiIds = collect();
        $gunler = collect();

        if ($sayfa->hasKurs()) {
            $kursQuery = Kurs::query()->portaldeAktif();
            $servis->uygulaKursKurallari($kursQuery, $sayfa);

            $merkezIds = $merkezIds->merge(
                $kursQuery->clone()->whereNotNull('merkez_id')->distinct()->pluck('merkez_id')
            );
            $alanIds = $kursQuery->clone()->whereNotNull('alan_id')->distinct()->pluck('alan_id');
            $kursTipiIds = $kursQuery->clone()->whereNotNull('kurs_tipi_id')->distinct()->pluck('kurs_tipi_id');

            $bransQuery = $kursQuery->clone()->whereNotNull('brans_id');
            if (! empty($validated['alan_id'])) {
                $bransQuery->where('alan_id', (int) $validated['alan_id']);
            }
            $bransIds = $bransQuery->distinct()->pluck('brans_id');

            $gunler = KursGun::query()
                ->whereIn('kurs_id', $kursQuery->clone()->select('kurslar.id'))
                ->distinct()
                ->pluck('gun')
                ->map(function ($gun) {
                    if ($gun instanceof HaftaGunu) {
                        return $gun->carbonIso();
                    }

                    $enum = HaftaGunu::tryFrom((string) $gun);

                    return $enum?->carbonIso();
                })
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        if ($sayfa->hasEtkinlik()) {
            $etkinlikQuery = Etkinlik::query()->portaldeAktif();
            $servis->uygulaEtkinlikKurallari($etkinlikQuery, $sayfa);

            $merkezIds = $merkezIds->merge(
                $etkinlikQuery->clone()->whereNotNull('merkez_id')->distinct()->pluck('merkez_id')
            );
            $etkinlikTipiIds = $etkinlikQuery->clone()
                ->whereNotNull('etkinlik_tipi_id')
                ->distinct()
                ->pluck('etkinlik_tipi_id');
        }

        return $this->success([
            'merkezler' => $this->lookupItems(Merkez::class, $merkezIds),
            'alanlar' => $this->lookupItems(Alan::class, $alanIds),
            'branslar' => $this->bransItems($bransIds),
            'kurs_tipleri' => $this->lookupItems(KursTipi::class, $kursTipiIds),
            'etkinlik_tipleri' => $this->lookupItems(EtkinlikTipi::class, $etkinlikTipiIds),
            'gunler' => $gunler->values()->all(),
        ]);
    }

    public function kurslar(string $slug, Request $request, PortalSayfaServisi $servis, JwtTokenServisi $jwt): JsonResponse
    {
        $sayfa = $servis->findBySlug($slug);
        if (! $sayfa) {
            return $this->error('Sayfa bulunamadı.', 404);
        }
        if ($denied = $this->sadeceGirisEngeli($sayfa, $request, $jwt)) {
            return $denied;
        }
        if ($sayfa->isAuthSayfa() || ! $sayfa->hasKurs()) {
            return $this->error('Bu sayfada kurs içeriği yok.', 404);
        }

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'merkez_id' => ['nullable', 'integer', 'exists:merkezler,id'],
            'alan_id' => ['nullable', 'integer', 'exists:alanlar,id'],
            'brans_id' => ['nullable', 'integer', 'exists:branslar,id'],
            'kurs_tipi_id' => ['nullable', 'integer', 'exists:kurs_tipleri,id'],
            'basvuru_durumu' => ['nullable', 'string', 'in:acik,yakinda,kapandi,kapali'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = Kurs::query()
            ->portaldeAktif()
            ->with([
                'merkez',
                'alan',
                'brans',
                'kursTipi',
                'gunler',
                'evrakTipleri' => fn ($q) => $q->where('aktif', true)->orderBy('ad'),
            ]);

        $servis->uygulaKursKurallari($query, $sayfa);

        if (! empty($validated['merkez_id'])) {
            $query->where('merkez_id', (int) $validated['merkez_id']);
        }
        if (! empty($validated['alan_id'])) {
            $query->where('alan_id', (int) $validated['alan_id']);
        }
        if (! empty($validated['brans_id'])) {
            $query->where('brans_id', (int) $validated['brans_id']);
        }
        if (! empty($validated['kurs_tipi_id'])) {
            $query->where('kurs_tipi_id', (int) $validated['kurs_tipi_id']);
        }
        if (! empty($validated['basvuru_durumu'])) {
            $query->basvuruDurumu($validated['basvuru_durumu']);
        }
        if (! empty($validated['q'])) {
            $q = trim((string) $validated['q']);
            $query->where(function ($builder) use ($q) {
                $builder
                    ->where('kurs_no', 'like', "%{$q}%")
                    ->orWhereHas('brans', fn ($b) => $b->where('ad', 'like', "%{$q}%"))
                    ->orWhereHas('alan', fn ($b) => $b->where('ad', 'like', "%{$q}%"))
                    ->orWhereHas('merkez', fn ($b) => $b->where('ad', 'like', "%{$q}%"));
            });
        }

        $query
            ->orderByRaw("CASE
                WHEN kurslar.basvuru_baslama_tarihi IS NOT NULL
                     AND kurslar.basvuru_bitis_tarihi IS NOT NULL
                     AND kurslar.basvuru_baslama_tarihi <= ?
                     AND kurslar.basvuru_bitis_tarihi >= ? THEN 0
                WHEN kurslar.basvuru_baslama_tarihi IS NOT NULL
                     AND kurslar.basvuru_baslama_tarihi > ? THEN 1
                ELSE 2
            END", [now(), now(), now()])
            ->orderBy('basvuru_bitis_tarihi')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return $this->success([
            'items' => KursResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function etkinlikler(string $slug, Request $request, PortalSayfaServisi $servis, JwtTokenServisi $jwt): JsonResponse
    {
        $sayfa = $servis->findBySlug($slug);
        if (! $sayfa) {
            return $this->error('Sayfa bulunamadı.', 404);
        }
        if ($denied = $this->sadeceGirisEngeli($sayfa, $request, $jwt)) {
            return $denied;
        }
        if ($sayfa->isAuthSayfa() || ! $sayfa->hasEtkinlik()) {
            return $this->error('Bu sayfada etkinlik içeriği yok.', 404);
        }

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'merkez_id' => ['nullable', 'integer', 'exists:merkezler,id'],
            'etkinlik_tipi_id' => ['nullable', 'integer', 'exists:etkinlik_tipleri,id'],
            'basvuru_durumu' => ['nullable', 'string', 'in:acik,yakinda,kapandi,kapali'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = Etkinlik::query()
            ->portaldeAktif()
            ->with([
                'merkez',
                'etkinlikTipi',
                'evrakTipleri' => fn ($q) => $q->where('aktif', true)->orderBy('ad'),
            ]);

        $servis->uygulaEtkinlikKurallari($query, $sayfa);

        if (! empty($validated['merkez_id'])) {
            $query->where('merkez_id', (int) $validated['merkez_id']);
        }
        if (! empty($validated['etkinlik_tipi_id'])) {
            $query->where('etkinlik_tipi_id', (int) $validated['etkinlik_tipi_id']);
        }
        if (! empty($validated['basvuru_durumu'])) {
            $query->basvuruDurumu($validated['basvuru_durumu']);
        }
        if (! empty($validated['q'])) {
            $q = trim((string) $validated['q']);
            $query->where(function ($builder) use ($q) {
                $builder
                    ->where('etkinlik_no', 'like', "%{$q}%")
                    ->orWhere('ad', 'like', "%{$q}%")
                    ->orWhereHas('etkinlikTipi', fn ($b) => $b->where('ad', 'like', "%{$q}%"))
                    ->orWhereHas('merkez', fn ($b) => $b->where('ad', 'like', "%{$q}%"));
            });
        }

        $query
            ->orderByRaw("CASE
                WHEN etkinlikler.basvuru_baslama_tarihi IS NOT NULL
                     AND etkinlikler.basvuru_bitis_tarihi IS NOT NULL
                     AND etkinlikler.basvuru_baslama_tarihi <= ?
                     AND etkinlikler.basvuru_bitis_tarihi >= ? THEN 0
                WHEN etkinlikler.basvuru_baslama_tarihi IS NOT NULL
                     AND etkinlikler.basvuru_baslama_tarihi > ? THEN 1
                ELSE 2
            END", [now(), now(), now()])
            ->orderBy('basvuru_bitis_tarihi')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return $this->success([
            'items' => EtkinlikResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * @return array{id: int, kod: string|null, baslik: string, aciklama: string|null, anasayfa_logo_url: string|null, sidebar_ikon_url: string|null, slug: string, path: string, sistem: bool, sadece_giris: bool, has_kurs: bool, has_etkinlik: bool}
     */
    private function serializeSayfa(PortalSayfa $sayfa, PortalSayfaServisi $servis): array
    {
        if (! $sayfa->relationLoaded('kurallar')) {
            $sayfa->load('kurallar');
        }

        return [
            'id' => $sayfa->id,
            'kod' => $sayfa->kod,
            'baslik' => $sayfa->baslik,
            'aciklama' => $sayfa->aciklama,
            'menu_aciklama' => $sayfa->menu_aciklama,
            'anasayfa_logo_url' => $servis->apiAnasayfaLogoUrl($sayfa),
            'anasayfa_menu_arkaplan_url' => $servis->apiAnasayfaMenuArkaplanUrl($sayfa),
            'anasayfa_menu_arkaplan_mod' => $servis->normalizeArkaplanMod($sayfa->anasayfa_menu_arkaplan_mod),
            'sidebar_ikon_url' => $servis->apiSidebarIkonUrl($sayfa),
            'slug' => $sayfa->slug,
            'path' => $sayfa->sistem ? '/'.$sayfa->slug : '/sayfa/'.$sayfa->slug,
            'sistem' => (bool) $sayfa->sistem,
            'sadece_giris' => (bool) $sayfa->sadece_giris,
            'has_kurs' => $sayfa->hasKurs(),
            'has_etkinlik' => $sayfa->hasEtkinlik(),
        ];
    }

    private function sadeceGirisEngeli(PortalSayfa $sayfa, Request $request, JwtTokenServisi $jwt): ?JsonResponse
    {
        if (! $sayfa->sadece_giris) {
            return null;
        }

        $header = $request->header('Authorization', '');
        if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $matches) !== 1) {
            return $this->error('Bu sayfaya erişmek için giriş yapmalısınız.', 401);
        }

        try {
            $kisi = $jwt->kisi($matches[1], JwtTokenServisi::TIP_ACCESS);
            auth('api')->setUser($kisi);
            $request->setUserResolver(static fn () => $kisi);

            return null;
        } catch (UnexpectedValueException $e) {
            return $this->error($e->getMessage() ?: 'Bu sayfaya erişmek için giriş yapmalısınız.', 401);
        } catch (\Throwable) {
            return $this->error('Bu sayfaya erişmek için giriş yapmalısınız.', 401);
        }
    }

    private function gorselYaniti(?string $path): BinaryFileResponse|Response
    {
        if ($path === null) {
            return response('Görsel bulunamadı.', 404);
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * @param  class-string<Alan|EtkinlikTipi|KursTipi|Merkez>  $model
     * @param  Collection<int, mixed>  $ids
     * @return list<array{id: int, ad: string}>
     */
    private function lookupItems(string $model, Collection $ids): array
    {
        $idList = $ids->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($idList->isEmpty()) {
            return [];
        }

        return $model::query()
            ->whereIn('id', $idList)
            ->orderBy('ad')
            ->get(['id', 'ad'])
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'ad' => (string) $item->ad,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $ids
     * @return list<array{id: int, ad: string, alan_id: int|null}>
     */
    private function bransItems(Collection $ids): array
    {
        $idList = $ids->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($idList->isEmpty()) {
            return [];
        }

        return Brans::query()
            ->whereIn('id', $idList)
            ->orderBy('ad')
            ->get(['id', 'ad', 'alan_id'])
            ->map(fn (Brans $b) => [
                'id' => (int) $b->id,
                'ad' => (string) $b->ad,
                'alan_id' => $b->alan_id !== null ? (int) $b->alan_id : null,
            ])
            ->all();
    }
}
