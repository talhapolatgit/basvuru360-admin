<?php

namespace App\Models;

use App\Enums\Cinsiyet;
use App\Enums\EtkinlikDurum;
use App\Enums\IkametSarti;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etkinlik extends Model
{
    use SoftDeletes;

    protected $table = 'etkinlikler';

    protected $fillable = [
        'etkinlik_no',
        'ad',
        'aciklama',
        'merkez_id',
        'etkinlik_tipi_id',
        'kontenjan',
        'yedek_kontenjan',
        'ikamet_disi_kontenjan',
        'baslangic_tarihi',
        'bitis_tarihi',
        'basvuru_baslama_tarihi',
        'basvuru_bitis_tarihi',
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
            'baslangic_tarihi' => 'date',
            'bitis_tarihi' => 'date',
            'basvuru_baslama_tarihi' => 'datetime',
            'basvuru_bitis_tarihi' => 'datetime',
            'kontenjan' => 'integer',
            'yedek_kontenjan' => 'integer',
            'ikamet_disi_kontenjan' => 'integer',
            'minimum_yas' => 'integer',
            'maksimum_yas' => 'integer',
            'basvuru_sayisi' => 'integer',
            'kayit_sayisi' => 'integer',
            'iptal_sayisi' => 'integer',
            'durum' => EtkinlikDurum::class,
            'cinsiyet_sarti' => Cinsiyet::class,
            'ikamet_sarti' => IkametSarti::class,
            'ogrenci_olma_sarti' => 'boolean',
            'engelli_olma_sarti' => 'boolean',
            'mezun_olma_sarti' => 'boolean',
            'evrak_zorunlu' => 'boolean',
            'onlinede_yayinlansin' => 'boolean',
        ];
    }

    public function merkez(): BelongsTo
    {
        return $this->belongsTo(Merkez::class, 'merkez_id');
    }

    public function etkinlikTipi(): BelongsTo
    {
        return $this->belongsTo(EtkinlikTipi::class, 'etkinlik_tipi_id');
    }

    public function egitimDurumu(): BelongsTo
    {
        return $this->belongsTo(EgitimDurumu::class, 'egitim_durumu_id');
    }

    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    public function guncelleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guncelleyen_id');
    }

    public function sorumlular(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'etkinlik_sorumlulari', 'etkinlik_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * @param  list<int>  $userIds
     */
    public function syncSorumlular(array $userIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        $this->sorumlular()->sync($ids);
        $this->unsetRelation('sorumlular');
    }

    public function sorumluAdlari(): string
    {
        $adlar = $this->relationLoaded('sorumlular')
            ? $this->sorumlular->sortBy('id')->values()
            : $this->sorumlular()->orderBy('users.id')->get();

        if ($adlar->isEmpty()) {
            return '—';
        }

        return $adlar->map(fn (User $user) => $user->tam_adi)->implode(', ');
    }

    public function evrakTipleri(): BelongsToMany
    {
        return $this->belongsToMany(EvrakTipi::class, 'etkinlik_evraklari', 'etkinlik_id', 'evrak_tipi_id')
            ->withTimestamps();
    }

    public function kurumlar(): BelongsToMany
    {
        return $this->belongsToMany(Kurum::class, 'etkinlik_kurum', 'etkinlik_id', 'kurum_id')
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

    public function basvurular(): HasMany
    {
        return $this->hasMany(EtkinlikBasvuru::class, 'etkinlik_id');
    }

    public function smsGonderimleri(): HasMany
    {
        return $this->hasMany(EtkinlikSmsGonderim::class, 'etkinlik_id');
    }

    public function epostaGonderimleri(): HasMany
    {
        return $this->hasMany(EtkinlikEpostaGonderim::class, 'etkinlik_id');
    }

    public function smsLoglari(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'etkinlik_id');
    }

    public function epostaLoglari(): HasMany
    {
        return $this->hasMany(EpostaLog::class, 'etkinlik_id');
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
     * Vatandaş portalında listelenen aktif etkinlikler:
     * online yayınlanmış ve durum = aktif.
     *
     * @param  Builder<Etkinlik>  $query
     * @return Builder<Etkinlik>
     */
    public function scopePortaldeAktif(Builder $query): Builder
    {
        return $query
            ->where('onlinede_yayinlansin', true)
            ->where('durum', EtkinlikDurum::Aktif);
    }

    /**
     * @param  Builder<Etkinlik>  $query
     * @return Builder<Etkinlik>
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

    /**
     * @param  Builder<Etkinlik>  $query
     * @return Builder<Etkinlik>
     */
    public function scopeKullaniciKurumKapsami(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->sadeceKendiKurumlariniGorurEtkinlik()) {
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
}
