<?php

namespace App\Services;

use App\Models\Alan;
use App\Models\Brans;
use App\Models\Etkinlik;
use App\Models\EtkinlikTipi;
use App\Models\Kurs;
use App\Models\Merkez;
use App\Models\PortalSayfa;
use App\Models\PortalSayfaKurali;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PortalSayfaServisi
{
    public const ARKAPLAN_MOD_KAPLA = 'kapla';

    public const ARKAPLAN_MOD_SIGDIR = 'sigdir';

    /** @var list<string> */
    public const ARKAPLAN_MODLARI = [self::ARKAPLAN_MOD_KAPLA, self::ARKAPLAN_MOD_SIGDIR];

    /** @var list<string> */
    public const REZERVE_SLUGS = [
        'kurslar',
        'etkinlikler',
        'giris',
        'kayit',
        'basvurularim',
        'profil',
        'sayfa',
        'api',
        'hizli-arama',
    ];

    /** İçerik kuralı olmayan sistem sayfaları (yalnızca meta / menü / görseller). */
    /** @var list<string> */
    public const AUTH_SAYFA_KODLARI = [
        'basvurularim',
        'profil',
    ];

    public function icerikKurallariDestekler(?PortalSayfa $sayfa): bool
    {
        if (! $sayfa) {
            return true;
        }

        return ! in_array((string) $sayfa->kod, self::AUTH_SAYFA_KODLARI, true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PortalSayfa>
     */
    public function liste()
    {
        return PortalSayfa::query()
            ->with('kurallar')
            ->orderBy('sira')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array{baslik: string, aciklama?: string|null, slug?: string|null, menude_goster?: bool, kurallar?: list<array{kaynak: string, secim_tipi: string, hedef_id?: int|null, hedef_nos?: string|null}>, anasayfa_logo?: UploadedFile|null, anasayfa_logo_kaldir?: bool, sidebar_ikon?: UploadedFile|null, sidebar_ikon_kaldir?: bool}  $data
     */
    public function olustur(array $data): PortalSayfa
    {
        return DB::transaction(function () use ($data) {
            $slug = $this->normalizeSlug($data['slug'] ?? null, $data['baslik']);
            $this->assertSlugAvailable($slug);

            $maxSira = (int) PortalSayfa::query()->max('sira');

            $sayfa = PortalSayfa::query()->create([
                'kod' => null,
                'baslik' => trim($data['baslik']),
                'aciklama' => $this->normalizeAciklama($data['aciklama'] ?? null),
                'menu_aciklama' => $this->normalizeAciklama($data['menu_aciklama'] ?? null),
                'anasayfa_logo' => $this->guncelleYukleme(
                    null,
                    $data['anasayfa_logo'] ?? null,
                    false,
                    'anasayfa',
                ),
                'anasayfa_menu_arkaplan' => $this->guncelleYukleme(
                    null,
                    $data['anasayfa_menu_arkaplan'] ?? null,
                    false,
                    'menu-bg',
                ),
                'anasayfa_menu_arkaplan_mod' => $this->normalizeArkaplanMod($data['anasayfa_menu_arkaplan_mod'] ?? null),
                'sidebar_ikon' => $this->guncelleYukleme(
                    null,
                    $data['sidebar_ikon'] ?? null,
                    false,
                    'sidebar',
                ),
                'slug' => $slug,
                'sistem' => false,
                'menude_goster' => (bool) ($data['menude_goster'] ?? true),
                'sadece_giris' => (bool) ($data['sadece_giris'] ?? false),
                'sira' => $maxSira + 1,
            ]);

            $this->senkronKurallar($sayfa, $data['kurallar'] ?? []);

            return $sayfa->fresh(['kurallar']);
        });
    }

    /**
     * @param  array{baslik: string, aciklama?: string|null, slug?: string|null, menude_goster?: bool, kurallar?: list<array{kaynak: string, secim_tipi: string, hedef_id?: int|null, hedef_nos?: string|null}>, anasayfa_logo?: UploadedFile|null, anasayfa_logo_kaldir?: bool, sidebar_ikon?: UploadedFile|null, sidebar_ikon_kaldir?: bool}  $data
     */
    public function guncelle(PortalSayfa $sayfa, array $data): PortalSayfa
    {
        return DB::transaction(function () use ($sayfa, $data) {
            $sayfa->baslik = trim($data['baslik']);
            $sayfa->aciklama = $this->normalizeAciklama($data['aciklama'] ?? null);
            $sayfa->menu_aciklama = $this->normalizeAciklama($data['menu_aciklama'] ?? null);
            $sayfa->menude_goster = (bool) ($data['menude_goster'] ?? $sayfa->menude_goster);
            $sayfa->sadece_giris = (bool) ($data['sadece_giris'] ?? $sayfa->sadece_giris);
            $sayfa->anasayfa_logo = $this->guncelleYukleme(
                $sayfa->anasayfa_logo,
                $data['anasayfa_logo'] ?? null,
                (bool) ($data['anasayfa_logo_kaldir'] ?? false),
                'anasayfa',
            );
            $sayfa->anasayfa_menu_arkaplan = $this->guncelleYukleme(
                $sayfa->anasayfa_menu_arkaplan,
                $data['anasayfa_menu_arkaplan'] ?? null,
                (bool) ($data['anasayfa_menu_arkaplan_kaldir'] ?? false),
                'menu-bg',
            );
            $sayfa->anasayfa_menu_arkaplan_mod = $this->normalizeArkaplanMod(
                $data['anasayfa_menu_arkaplan_mod'] ?? $sayfa->anasayfa_menu_arkaplan_mod,
            );
            $sayfa->sidebar_ikon = $this->guncelleYukleme(
                $sayfa->sidebar_ikon,
                $data['sidebar_ikon'] ?? null,
                (bool) ($data['sidebar_ikon_kaldir'] ?? false),
                'sidebar',
            );

            if (! $sayfa->sistem) {
                $slug = $this->normalizeSlug($data['slug'] ?? null, $data['baslik']);
                $this->assertSlugAvailable($slug, $sayfa->id);
                $sayfa->slug = $slug;
            }

            $sayfa->save();

            if ($this->icerikKurallariDestekler($sayfa)) {
                $this->senkronKurallar($sayfa, $data['kurallar'] ?? []);
            }

            return $sayfa->fresh(['kurallar']);
        });
    }

    public function sil(PortalSayfa $sayfa): void
    {
        if ($sayfa->sistem) {
            throw ValidationException::withMessages([
                'sayfa' => 'Sistem sayfaları silinemez.',
            ]);
        }

        $this->silDosya($sayfa->anasayfa_logo);
        $this->silDosya($sayfa->anasayfa_menu_arkaplan);
        $this->silDosya($sayfa->sidebar_ikon);
        $sayfa->delete();
    }

    public function anasayfaLogoUrl(?PortalSayfa $sayfa): ?string
    {
        if (! $sayfa) {
            return null;
        }

        return $this->adminGorselUrl($sayfa->anasayfa_logo);
    }

    public function anasayfaMenuArkaplanUrl(?PortalSayfa $sayfa): ?string
    {
        if (! $sayfa) {
            return null;
        }

        return $this->adminGorselUrl($sayfa->anasayfa_menu_arkaplan);
    }

    public function sidebarIkonUrl(?PortalSayfa $sayfa): ?string
    {
        if (! $sayfa) {
            return null;
        }

        return $this->adminGorselUrl($sayfa->sidebar_ikon);
    }

    public function apiAnasayfaLogoUrl(PortalSayfa $sayfa): ?string
    {
        return $this->apiGorselUrl(
            $sayfa->anasayfa_logo,
            '/api/v1/portal-sayfalar/'.$sayfa->slug.'/anasayfa-logo',
        );
    }

    public function apiAnasayfaMenuArkaplanUrl(PortalSayfa $sayfa): ?string
    {
        return $this->apiGorselUrl(
            $sayfa->anasayfa_menu_arkaplan,
            '/api/v1/portal-sayfalar/'.$sayfa->slug.'/anasayfa-menu-arkaplan',
        );
    }

    public function apiSidebarIkonUrl(PortalSayfa $sayfa): ?string
    {
        return $this->apiGorselUrl(
            $sayfa->sidebar_ikon,
            '/api/v1/portal-sayfalar/'.$sayfa->slug.'/sidebar-ikon',
        );
    }

    public function anasayfaLogoDosyaYolu(PortalSayfa $sayfa): ?string
    {
        return $this->dosyaYolu($sayfa->anasayfa_logo);
    }

    public function anasayfaMenuArkaplanDosyaYolu(PortalSayfa $sayfa): ?string
    {
        return $this->dosyaYolu($sayfa->anasayfa_menu_arkaplan);
    }

    public function sidebarIkonDosyaYolu(PortalSayfa $sayfa): ?string
    {
        return $this->dosyaYolu($sayfa->sidebar_ikon);
    }

    /**
     * @param  list<int>  $siraliIdler
     */
    public function sirala(array $siraliIdler): void
    {
        DB::transaction(function () use ($siraliIdler) {
            foreach (array_values($siraliIdler) as $index => $id) {
                PortalSayfa::query()
                    ->where('id', (int) $id)
                    ->update(['sira' => $index + 1]);
            }
        });
    }

    public function menudeGosterToggle(PortalSayfa $sayfa, bool $goster): PortalSayfa
    {
        $sayfa->menude_goster = $goster;
        $sayfa->save();

        return $sayfa;
    }

    /**
     * @param  list<array{kaynak: string, secim_tipi: string, hedef_id?: int|null, hedef_nos?: string|null}>  $kurallar
     */
    public function senkronKurallar(PortalSayfa $sayfa, array $kurallar): void
    {
        $normalized = [];
        foreach (array_values($kurallar) as $kural) {
            $kaynak = (string) ($kural['kaynak'] ?? '');
            $secimTipi = (string) ($kural['secim_tipi'] ?? '');
            $hedefId = $kural['hedef_id'] ?? null;
            $hedefId = $hedefId !== null && $hedefId !== '' ? (int) $hedefId : null;
            $hedefNos = isset($kural['hedef_nos']) ? trim((string) $kural['hedef_nos']) : '';

            if (! in_array($kaynak, [PortalSayfaKurali::KAYNAK_KURS, PortalSayfaKurali::KAYNAK_ETKINLIK], true)) {
                throw ValidationException::withMessages([
                    'kurallar' => 'Geçersiz kural kaynağı.',
                ]);
            }

            $izinli = $kaynak === PortalSayfaKurali::KAYNAK_KURS
                ? PortalSayfaKurali::KURS_SECIM_TIPLERI
                : PortalSayfaKurali::ETKINLIK_SECIM_TIPLERI;

            if (! in_array($secimTipi, $izinli, true)) {
                throw ValidationException::withMessages([
                    'kurallar' => 'Geçersiz seçim tipi.',
                ]);
            }

            if ($secimTipi === 'tum') {
                $normalized[] = [
                    'kaynak' => $kaynak,
                    'secim_tipi' => $secimTipi,
                    'hedef_id' => null,
                ];

                continue;
            }

            if (in_array($secimTipi, ['kurs', 'etkinlik'], true)) {
                foreach ($this->resolveNosToIds($kaynak, $secimTipi, $hedefNos) as $id) {
                    $normalized[] = [
                        'kaynak' => $kaynak,
                        'secim_tipi' => $secimTipi,
                        'hedef_id' => $id,
                    ];
                }

                continue;
            }

            if (! $hedefId) {
                throw ValidationException::withMessages([
                    'kurallar' => 'Seçim tipi için hedef zorunludur.',
                ]);
            }

            $this->assertHedefExists($kaynak, $secimTipi, $hedefId);

            $normalized[] = [
                'kaynak' => $kaynak,
                'secim_tipi' => $secimTipi,
                'hedef_id' => $hedefId,
            ];
        }

        $sayfa->kurallar()->delete();

        foreach (array_values($normalized) as $index => $row) {
            $sayfa->kurallar()->create($row + ['sira' => $index + 1]);
        }
    }

    /**
     * Form satırları için kuralları hazırlar; kurs/etkinlik bazlı ardışık kuralları virgüllü no alanına birleştirir.
     *
     * @param  iterable<int, PortalSayfaKurali|array{kaynak: string, secim_tipi: string, hedef_id?: int|null, hedef_nos?: string|null}>  $kurallar
     * @return list<array{kaynak: string, secim_tipi: string, hedef_id: int|null, hedef_nos: string|null}>
     */
    public function formKurallari(iterable $kurallar): array
    {
        $out = [];

        foreach ($kurallar as $kural) {
            $kaynak = is_array($kural) ? (string) ($kural['kaynak'] ?? '') : (string) $kural->kaynak;
            $secimTipi = is_array($kural) ? (string) ($kural['secim_tipi'] ?? '') : (string) $kural->secim_tipi;
            $hedefId = is_array($kural) ? ($kural['hedef_id'] ?? null) : $kural->hedef_id;
            $hedefId = $hedefId !== null && $hedefId !== '' ? (int) $hedefId : null;
            $hedefNos = is_array($kural) ? ($kural['hedef_nos'] ?? null) : null;

            if (in_array($secimTipi, ['kurs', 'etkinlik'], true)) {
                if ($hedefNos === null || $hedefNos === '') {
                    $hedefNos = $hedefId
                        ? $this->noFromId($kaynak, $secimTipi, $hedefId)
                        : '';
                }

                $last = $out[array_key_last($out)] ?? null;
                if (
                    $last
                    && $last['kaynak'] === $kaynak
                    && $last['secim_tipi'] === $secimTipi
                ) {
                    $parts = array_filter(array_map('trim', explode(',', (string) $last['hedef_nos'])));
                    if ($hedefNos !== '' && $hedefNos !== null) {
                        foreach (array_filter(array_map('trim', explode(',', (string) $hedefNos))) as $no) {
                            $parts[] = $no;
                        }
                    }
                    $out[array_key_last($out)]['hedef_nos'] = implode(', ', array_values(array_unique($parts)));

                    continue;
                }

                $out[] = [
                    'kaynak' => $kaynak,
                    'secim_tipi' => $secimTipi,
                    'hedef_id' => null,
                    'hedef_nos' => $hedefNos !== '' ? (string) $hedefNos : null,
                ];

                continue;
            }

            $out[] = [
                'kaynak' => $kaynak,
                'secim_tipi' => $secimTipi,
                'hedef_id' => $hedefId,
                'hedef_nos' => null,
            ];
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    public function resolveNosToIds(string $kaynak, string $secimTipi, string $hedefNos): array
    {
        $nos = array_values(array_unique(array_filter(array_map(
            static fn (string $no) => trim($no),
            explode(',', $hedefNos),
        ), static fn (string $no) => $no !== '')));

        if ($nos === []) {
            $label = $secimTipi === 'kurs' ? 'kurs numarası' : 'etkinlik numarası';
            throw ValidationException::withMessages([
                'kurallar' => "En az bir {$label} girin (virgülle ayırabilirsiniz).",
            ]);
        }

        if ($secimTipi === 'kurs' && $kaynak === 'kurs') {
            $found = Kurs::query()
                ->whereIn('kurs_no', $nos)
                ->get(['id', 'kurs_no']);
            $map = $found->pluck('id', 'kurs_no');
        } elseif ($secimTipi === 'etkinlik' && $kaynak === 'etkinlik') {
            $found = Etkinlik::query()
                ->whereIn('etkinlik_no', $nos)
                ->get(['id', 'etkinlik_no']);
            $map = $found->pluck('id', 'etkinlik_no');
        } else {
            throw ValidationException::withMessages([
                'kurallar' => 'Geçersiz numara hedefi.',
            ]);
        }

        $missing = [];
        $ids = [];
        foreach ($nos as $no) {
            if (! $map->has($no)) {
                $missing[] = $no;
                continue;
            }
            $ids[] = (int) $map->get($no);
        }

        if ($missing !== []) {
            $label = $secimTipi === 'kurs' ? 'Kurs' : 'Etkinlik';
            throw ValidationException::withMessages([
                'kurallar' => $label.' numarası bulunamadı: '.implode(', ', $missing),
            ]);
        }

        return array_values(array_unique($ids));
    }

    public function noFromId(string $kaynak, string $secimTipi, int $hedefId): string
    {
        if ($kaynak === 'kurs' && $secimTipi === 'kurs') {
            return (string) (Kurs::query()->whereKey($hedefId)->value('kurs_no') ?? '');
        }

        if ($kaynak === 'etkinlik' && $secimTipi === 'etkinlik') {
            return (string) (Etkinlik::query()->whereKey($hedefId)->value('etkinlik_no') ?? '');
        }

        return '';
    }

    public function normalizeSlug(?string $slug, string $baslik): string
    {
        $base = $slug !== null && trim($slug) !== '' ? $slug : $baslik;
        $normalized = Str::slug(Str::ascii($base), '-');
        if ($normalized === '') {
            $normalized = 'sayfa';
        }

        return Str::limit($normalized, 120, '');
    }

    public function normalizeAciklama(?string $aciklama): ?string
    {
        $value = trim((string) ($aciklama ?? ''));

        return $value === '' ? null : Str::limit($value, 500, '');
    }

    public function normalizeArkaplanMod(?string $mod): string
    {
        $value = strtolower(trim((string) ($mod ?? '')));

        return in_array($value, self::ARKAPLAN_MODLARI, true)
            ? $value
            : self::ARKAPLAN_MOD_KAPLA;
    }

    public function assertSlugAvailable(string $slug, ?int $ignoreId = null): void
    {
        $sayfa = $ignoreId !== null ? PortalSayfa::query()->find($ignoreId) : null;
        $sistemSlug = $sayfa?->sistem === true;

        if (! $sistemSlug && in_array($slug, self::REZERVE_SLUGS, true)) {
            throw ValidationException::withMessages([
                'slug' => 'Bu URL adresi sistem tarafından rezerve edilmiştir.',
            ]);
        }

        $query = PortalSayfa::query()->where('slug', $slug);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'Bu URL adresi zaten kullanılıyor.',
            ]);
        }
    }

    public function assertHedefExists(string $kaynak, string $secimTipi, int $hedefId): void
    {
        $exists = match (true) {
            $kaynak === 'kurs' && $secimTipi === 'kurs' => Kurs::query()->whereKey($hedefId)->exists(),
            $kaynak === 'kurs' && $secimTipi === 'brans' => Brans::query()->whereKey($hedefId)->exists(),
            $kaynak === 'kurs' && $secimTipi === 'alan' => Alan::query()->whereKey($hedefId)->exists(),
            $kaynak === 'kurs' && $secimTipi === 'merkez' => Merkez::query()->whereKey($hedefId)->exists(),
            $kaynak === 'etkinlik' && $secimTipi === 'etkinlik' => Etkinlik::query()->whereKey($hedefId)->exists(),
            $kaynak === 'etkinlik' && $secimTipi === 'tur' => EtkinlikTipi::query()->whereKey($hedefId)->exists(),
            $kaynak === 'etkinlik' && $secimTipi === 'merkez' => Merkez::query()->whereKey($hedefId)->exists(),
            default => false,
        };

        if (! $exists) {
            throw ValidationException::withMessages([
                'kurallar' => 'Seçilen hedef bulunamadı.',
            ]);
        }
    }

    /**
     * @param  Builder<Kurs>  $query
     * @return Builder<Kurs>
     */
    public function uygulaKursKurallari(Builder $query, PortalSayfa $sayfa): Builder
    {
        $kurallar = $sayfa->kurallar->where('kaynak', 'kurs')->values();
        if ($kurallar->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        if ($kurallar->contains(fn (PortalSayfaKurali $k) => $k->secim_tipi === 'tum')) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($kurallar) {
            foreach ($kurallar as $kural) {
                $builder->orWhere(function (Builder $inner) use ($kural) {
                    match ($kural->secim_tipi) {
                        'kurs' => $inner->where('id', $kural->hedef_id),
                        'brans' => $inner->where('brans_id', $kural->hedef_id),
                        'alan' => $inner->where('alan_id', $kural->hedef_id),
                        'merkez' => $inner->where('merkez_id', $kural->hedef_id),
                        default => $inner->whereRaw('1 = 0'),
                    };
                });
            }
        });
    }

    /**
     * @param  Builder<Etkinlik>  $query
     * @return Builder<Etkinlik>
     */
    public function uygulaEtkinlikKurallari(Builder $query, PortalSayfa $sayfa): Builder
    {
        $kurallar = $sayfa->kurallar->where('kaynak', 'etkinlik')->values();
        if ($kurallar->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        if ($kurallar->contains(fn (PortalSayfaKurali $k) => $k->secim_tipi === 'tum')) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($kurallar) {
            foreach ($kurallar as $kural) {
                $builder->orWhere(function (Builder $inner) use ($kural) {
                    match ($kural->secim_tipi) {
                        'etkinlik' => $inner->where('id', $kural->hedef_id),
                        'tur' => $inner->where('etkinlik_tipi_id', $kural->hedef_id),
                        'merkez' => $inner->where('merkez_id', $kural->hedef_id),
                        default => $inner->whereRaw('1 = 0'),
                    };
                });
            }
        });
    }

    public function findBySlug(string $slug): ?PortalSayfa
    {
        return PortalSayfa::query()
            ->with('kurallar')
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @return array{merkezler: list<array{value:int,label:string}>, alanlar: list<array{value:int,label:string}>, branslar: list<array{value:int,label:string}>, kurslar: list<array{value:int,label:string}>, etkinlikler: list<array{value:int,label:string}>, etkinlik_tipleri: list<array{value:int,label:string}>}
     */
    public function hedefSecenekleri(): array
    {
        return [
            'merkezler' => Merkez::query()->orderBy('ad')->get(['id', 'ad'])
                ->map(fn (Merkez $m) => ['value' => $m->id, 'label' => $m->ad])->all(),
            'alanlar' => Alan::query()->orderBy('ad')->get(['id', 'ad'])
                ->map(fn (Alan $a) => ['value' => $a->id, 'label' => $a->ad])->all(),
            'branslar' => Brans::query()->with('alan:id,ad')->orderBy('ad')->get(['id', 'ad', 'alan_id'])
                ->map(fn (Brans $b) => [
                    'value' => $b->id,
                    'label' => $b->alan?->ad ? "{$b->ad} ({$b->alan->ad})" : $b->ad,
                ])->all(),
            'kurslar' => Kurs::query()
                ->with(['brans:id,ad', 'alan:id,ad'])
                ->orderByDesc('id')
                ->limit(500)
                ->get(['id', 'kurs_no', 'brans_id', 'alan_id'])
                ->map(fn (Kurs $k) => [
                    'value' => $k->id,
                    'label' => trim(($k->kurs_no ?: '#'.$k->id).' — '.($k->brans?->ad ?? $k->alan?->ad ?? 'Kurs')),
                ])->all(),
            'etkinlikler' => Etkinlik::query()
                ->orderByDesc('id')
                ->limit(500)
                ->get(['id', 'etkinlik_no', 'ad'])
                ->map(fn (Etkinlik $e) => [
                    'value' => $e->id,
                    'label' => trim(($e->etkinlik_no ?: '#'.$e->id).' — '.($e->ad ?: 'Etkinlik')),
                ])->all(),
            'etkinlik_tipleri' => EtkinlikTipi::query()->orderBy('ad')->get(['id', 'ad'])
                ->map(fn (EtkinlikTipi $t) => ['value' => $t->id, 'label' => $t->ad])->all(),
        ];
    }

    public function kuralEtiketi(PortalSayfaKurali $kural): string
    {
        if ($kural->secim_tipi === 'tum') {
            return $kural->kaynak === 'kurs' ? 'Tüm kurslar' : 'Tüm etkinlikler';
        }

        $label = match (true) {
            $kural->kaynak === 'kurs' && $kural->secim_tipi === 'kurs' => Kurs::query()->with('brans')->find($kural->hedef_id)?->kurs_no,
            $kural->kaynak === 'kurs' && $kural->secim_tipi === 'brans' => Brans::query()->find($kural->hedef_id)?->ad,
            $kural->kaynak === 'kurs' && $kural->secim_tipi === 'alan' => Alan::query()->find($kural->hedef_id)?->ad,
            $kural->kaynak === 'kurs' && $kural->secim_tipi === 'merkez' => Merkez::query()->find($kural->hedef_id)?->ad,
            $kural->kaynak === 'etkinlik' && $kural->secim_tipi === 'etkinlik' => Etkinlik::query()->find($kural->hedef_id)?->ad,
            $kural->kaynak === 'etkinlik' && $kural->secim_tipi === 'tur' => EtkinlikTipi::query()->find($kural->hedef_id)?->ad,
            $kural->kaynak === 'etkinlik' && $kural->secim_tipi === 'merkez' => Merkez::query()->find($kural->hedef_id)?->ad,
            default => null,
        };

        $tipLabel = match ($kural->secim_tipi) {
            'kurs' => 'Kurs',
            'brans' => 'Branş',
            'alan' => 'Alan',
            'merkez' => 'Merkez',
            'etkinlik' => 'Etkinlik',
            'tur' => 'Tür',
            default => $kural->secim_tipi,
        };

        return $tipLabel.': '.($label ?: ('#'.$kural->hedef_id));
    }

    private function guncelleYukleme(
        ?string $mevcut,
        ?UploadedFile $file,
        bool $kaldir,
        string $prefix,
    ): ?string {
        $path = $mevcut;

        if ($kaldir && $path) {
            $this->silDosya($path);
            $path = null;
        }

        if ($file !== null) {
            if ($path) {
                $this->silDosya($path);
            }
            $path = $this->kaydetDosya($file, $prefix);
        }

        return $path;
    }

    private function kaydetDosya(UploadedFile $file, string $prefix): string
    {
        $klasor = public_path('uploads/portal-sayfalar');
        if (! is_dir($klasor)) {
            mkdir($klasor, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'], true) ? $ext : 'png';
        $ad = $prefix.'-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$ext;
        $file->move($klasor, $ad);

        return 'uploads/portal-sayfalar/'.$ad;
    }

    private function silDosya(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'uploads/portal-sayfalar/')) {
            return;
        }

        $mutlak = public_path($path);
        if (is_file($mutlak)) {
            @unlink($mutlak);
        }
    }

    private function adminGorselUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || ! str_starts_with($path, 'uploads/portal-sayfalar/')) {
            return null;
        }

        if (! is_file(public_path($path))) {
            return null;
        }

        return asset($path);
    }

    private function apiGorselUrl(?string $path, string $endpoint): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || ! str_starts_with($path, 'uploads/portal-sayfalar/')) {
            return null;
        }

        if (! is_file(public_path($path))) {
            return null;
        }

        return url($endpoint).'?v='.substr(hash('sha256', $path), 0, 12);
    }

    private function dosyaYolu(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || ! str_starts_with($path, 'uploads/portal-sayfalar/')) {
            return null;
        }

        $full = public_path($path);
        if (! is_file($full)) {
            return null;
        }

        return $full;
    }
}
