<?php

namespace App\Models;

use App\Enums\Cinsiyet;
use App\Enums\IkametSarti;
use App\Enums\KursDurum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kurs extends Model
{
    use SoftDeletes;

    protected $table = 'kurslar';

    protected $fillable = [
        'kurs_no',
        'merkez_id',
        'alan_id',
        'brans_id',
        'kurs_tipi_id',
        'ogretmen_id',
        'kontenjan',
        'yedek_kontenjan',
        'ikamet_disi_kontenjan',
        'meb_numarasi',
        'kurs_baslama_tarihi',
        'kurs_bitis_tarihi',
        'basvuru_baslama_tarihi',
        'basvuru_bitis_tarihi',
        'toplam_kurs_saati',
        'cinsiyet_sarti',
        'minimum_yas',
        'maksimum_yas',
        'ikamet_sarti',
        'ogrenci_olma_sarti',
        'engelli_olma_sarti',
        'egitim_durumu_id',
        'mezun_olma_sarti',
        'evrak_zorunlu',
        'onlinede_yayinlansin',
        'aciklama',
        'takvim_rengi',
        'basvuru_sayisi',
        'kayit_sayisi',
        'iptal_sayisi',
        'durum',
        'olusturan_id',
        'guncelleyen_id',
    ];

    protected function casts(): array
    {
        return [
            'kurs_baslama_tarihi' => 'date',
            'kurs_bitis_tarihi' => 'date',
            'basvuru_baslama_tarihi' => 'datetime',
            'basvuru_bitis_tarihi' => 'datetime',
            'kontenjan' => 'integer',
            'yedek_kontenjan' => 'integer',
            'ikamet_disi_kontenjan' => 'integer',
            'toplam_kurs_saati' => 'integer',
            'minimum_yas' => 'integer',
            'maksimum_yas' => 'integer',
            'basvuru_sayisi' => 'integer',
            'kayit_sayisi' => 'integer',
            'iptal_sayisi' => 'integer',
            'durum' => KursDurum::class,
            'cinsiyet_sarti' => Cinsiyet::class,
            'ikamet_sarti' => IkametSarti::class,
            'ogrenci_olma_sarti' => 'boolean',
            'engelli_olma_sarti' => 'boolean',
            'mezun_olma_sarti' => 'boolean',
            'evrak_zorunlu' => 'boolean',
            'onlinede_yayinlansin' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Merkez, $this>
     */
    public function merkez(): BelongsTo
    {
        return $this->belongsTo(Merkez::class, 'merkez_id');
    }

    /**
     * @return BelongsTo<Alan, $this>
     */
    public function alan(): BelongsTo
    {
        return $this->belongsTo(Alan::class, 'alan_id');
    }

    /**
     * @return BelongsTo<Brans, $this>
     */
    public function brans(): BelongsTo
    {
        return $this->belongsTo(Brans::class, 'brans_id');
    }

    /**
     * @return BelongsTo<KursTipi, $this>
     */
    public function kursTipi(): BelongsTo
    {
        return $this->belongsTo(KursTipi::class, 'kurs_tipi_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function ogretmen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ogretmen_id');
    }

    /**
     * Kursa atanan öğretmenler (çoklu).
     *
     * @return BelongsToMany<User, $this>
     */
    public function ogretmenler(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kurs_ogretmenleri', 'kurs_id', 'user_id')
            ->withTimestamps();
    }

    public function ogretmenAdlari(): string
    {
        $adlar = $this->relationLoaded('ogretmenler')
            ? $this->ogretmenler->sortBy('id')->values()
            : $this->ogretmenler()->orderBy('users.id')->get();

        if ($adlar->isEmpty()) {
            return '—';
        }

        return $adlar->map(fn (User $user) => $user->tam_adi)->implode(', ');
    }

    /**
     * Pivot’taki öğretmenlere göre ogretmen_id kolonunu senkronlar (ilk atanana set eder).
     */
    public function syncOgretmenIdFromPivot(): void
    {
        $firstId = $this->ogretmenler()->orderBy('users.id')->value('users.id');

        if ((int) ($this->ogretmen_id ?? 0) === (int) ($firstId ?? 0)) {
            return;
        }

        $this->forceFill(['ogretmen_id' => $firstId])->saveQuietly();
    }

    /**
     * @param  list<int>  $ogretmenIds
     */
    public function syncOgretmenler(array $ogretmenIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $ogretmenIds)));
        $this->ogretmenler()->sync($ids);
        $this->syncOgretmenIdFromPivot();
        $this->unsetRelation('ogretmenler');
        $this->unsetRelation('ogretmen');
    }

    /**
     * @return BelongsTo<EgitimDurumu, $this>
     */
    public function egitimDurumu(): BelongsTo
    {
        return $this->belongsTo(EgitimDurumu::class, 'egitim_durumu_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guncelleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guncelleyen_id');
    }

    /**
     * @return BelongsToMany<EvrakTipi, $this>
     */
    public function evrakTipleri(): BelongsToMany
    {
        return $this->belongsToMany(EvrakTipi::class, 'kurs_evraklari', 'kurs_id', 'evrak_tipi_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Kurum, $this>
     */
    public function kurumlar(): BelongsToMany
    {
        return $this->belongsToMany(Kurum::class, 'kurs_kurum', 'kurs_id', 'kurum_id')
            ->withTimestamps()
            ->orderBy('kurumlar.sira')
            ->orderBy('kurumlar.ad');
    }

    /**
     * @param  list<int|string>  $kurumIds
     */
    public function syncKurumlar(array $kurumIds): void
    {
        $this->kurumlar()->sync(array_values(array_unique(array_map('intval', $kurumIds))));
        $this->unsetRelation('kurumlar');
    }

    /**
     * Kursun haftalık ders günleri ve saatleri.
     *
     * @return HasMany<KursGun, $this>
     */
    public function gunler(): HasMany
    {
        return $this->hasMany(KursGun::class, 'kurs_id');
    }

    /**
     * Kursa yapılan başvurular.
     *
     * @return HasMany<KursBasvuru, $this>
     */
    public function basvurular(): HasMany
    {
        return $this->hasMany(KursBasvuru::class, 'kurs_id');
    }

    /**
     * @return HasMany<KursSmsGonderim, $this>
     */
    public function smsGonderimleri(): HasMany
    {
        return $this->hasMany(KursSmsGonderim::class, 'kurs_id');
    }

    /**
     * @return HasMany<SmsLog, $this>
     */
    public function smsLoglari(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'kurs_id');
    }

    /**
     * @return HasMany<KursEpostaGonderim, $this>
     */
    public function epostaGonderimleri(): HasMany
    {
        return $this->hasMany(KursEpostaGonderim::class, 'kurs_id');
    }

    /**
     * @return HasMany<EpostaLog, $this>
     */
    public function epostaLoglari(): HasMany
    {
        return $this->hasMany(EpostaLog::class, 'kurs_id');
    }

    /**
     * Kurs tarih aralığındaki ders oturumları.
     *
     * @return HasMany<KursDers, $this>
     */
    public function dersler(): HasMany
    {
        return $this->hasMany(KursDers::class, 'kurs_id');
    }

    public function gunlerOzeti(): string
    {
        $gunler = $this->gunler
            ->sortBy(fn (KursGun $gun) => $gun->gun?->sira() ?? 99)
            ->map(fn (KursGun $gun) => $gun->ozet())
            ->values();

        return $gunler->isEmpty() ? '—' : $gunler->implode(', ');
    }

    /**
     * Kullanıcının görebileceği kurslar: atanan + yetkili merkez + kurum kapsamı.
     * Kurs listesi / başvuru listesi aynı filtreyi kullanmalı.
     *
     * @param  Builder<Kurs>  $query
     * @return Builder<Kurs>
     */
    public function scopeKullaniciKapsami(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query;
        }

        if ($user->sadeceAtananKurslariGorur()) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereHas('ogretmenler', fn (Builder $oq) => $oq->where('users.id', $user->id))
                    ->orWhere('kurslar.ogretmen_id', $user->id);
            });
        }

        if ($user->sadeceYetkiliMerkezleriGorur()) {
            $merkezIds = $user->yetkiliMerkezIdleri();
            if ($merkezIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('merkez_id', $merkezIds);
            }
        }

        return $query->kullaniciKurumKapsami($user);
    }

    /**
     * Yalnızca kendi kurumunu gören kullanıcılar için:
     * - Kurumu tanımlı kurslar → kullanıcının kurumlarıyla kesişmeli
     * - Kurumu tanımsız kurslar → kısıta takılmaz
     *
     * @param  Builder<Kurs>  $query
     * @return Builder<Kurs>
     */
    public function scopeKullaniciKurumKapsami(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->sadeceKendiKurumlariniGorur()) {
            return $query;
        }

        $kurumIds = $user->kendiKurumIdleri();

        return $query->where(function (Builder $q) use ($kurumIds) {
            $q->whereDoesntHave('kurumlar');

            if ($kurumIds !== []) {
                $q->orWhereHas('kurumlar', fn (Builder $kq) => $kq->whereIn('kurumlar.id', $kurumIds));
            }
        });
    }

    public function basvuruDonemindeMi(): bool
    {
        if (! $this->basvuru_baslama_tarihi || ! $this->basvuru_bitis_tarihi) {
            return false;
        }

        return now()->between($this->basvuru_baslama_tarihi, $this->basvuru_bitis_tarihi);
    }

    /**
     * @return 'acik'|'yakinda'|'kapandi'|'kapali'
     */
    public function basvuruDurumuKod(): string
    {
        if (! $this->onlinede_yayinlansin) {
            return 'kapali';
        }

        if (! $this->basvuru_baslama_tarihi || ! $this->basvuru_bitis_tarihi) {
            return 'kapali';
        }

        $now = now();

        if ($now->lt($this->basvuru_baslama_tarihi)) {
            return 'yakinda';
        }

        if ($now->gt($this->basvuru_bitis_tarihi)) {
            return 'kapandi';
        }

        return 'acik';
    }

    public function basvuruDurumuLabel(): string
    {
        return match ($this->basvuruDurumuKod()) {
            'acik' => 'Açık',
            'yakinda' => 'Yakında',
            'kapandi' => 'Kapandı',
            default => 'Kapalı',
        };
    }

    public function basvuruDurumuStatusClass(): string
    {
        return match ($this->basvuruDurumuKod()) {
            'acik' => 'status-aktif',
            'yakinda' => 'status-yedek',
            'kapandi' => 'status-tamamlanan',
            default => 'status-hazirlik',
        };
    }

    /**
     * Vatandaş portalında listelenen aktif kurslar:
     * online yayınlanmış ve durum = aktif.
     *
     * @param  Builder<Kurs>  $query
     * @return Builder<Kurs>
     */
    public function scopePortaldeAktif(Builder $query): Builder
    {
        return $query
            ->where('onlinede_yayinlansin', true)
            ->where('durum', KursDurum::Aktif);
    }

    /**
     * @param  Builder<Kurs>  $query
     * @return Builder<Kurs>
     */
    public function scopeBasvuruDurumu(Builder $query, string $kod): Builder
    {
        $now = now();

        return match ($kod) {
            'kapali' => $query->where(function (Builder $q) {
                $q->where('onlinede_yayinlansin', false)
                    ->orWhereNull('basvuru_baslama_tarihi')
                    ->orWhereNull('basvuru_bitis_tarihi');
            }),
            'acik' => $query
                ->where('onlinede_yayinlansin', true)
                ->whereNotNull('basvuru_baslama_tarihi')
                ->whereNotNull('basvuru_bitis_tarihi')
                ->where('basvuru_baslama_tarihi', '<=', $now)
                ->where('basvuru_bitis_tarihi', '>=', $now),
            'yakinda' => $query
                ->where('onlinede_yayinlansin', true)
                ->whereNotNull('basvuru_baslama_tarihi')
                ->where('basvuru_baslama_tarihi', '>', $now),
            'kapandi' => $query
                ->where('onlinede_yayinlansin', true)
                ->whereNotNull('basvuru_bitis_tarihi')
                ->where('basvuru_bitis_tarihi', '<', $now),
            default => $query,
        };
    }
}
