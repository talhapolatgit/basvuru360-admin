<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merkez extends Model
{
    protected $table = 'merkezler';

    protected $fillable = [
        'ad',
        'il',
        'ilce',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Kurs, $this>
     */
    public function kurslar(): HasMany
    {
        return $this->hasMany(Kurs::class, 'merkez_id');
    }

    /**
     * @return HasMany<Etkinlik, $this>
     */
    public function etkinlikler(): HasMany
    {
        return $this->hasMany(Etkinlik::class, 'merkez_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function kullanicilar(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kullanici_merkez', 'merkez_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Silmeyi engelleyen bağlı kayıtlar, ör. ["3 kurs", "1 portal sayfası kuralı"].
     *
     * @return list<string>
     */
    public function silmeEngelleri(): array
    {
        return collect([
            'kurs' => $this->kurslar()->count(),
            'etkinlik' => $this->etkinlikler()->count(),
            'kullanıcı merkez yetkisi' => $this->kullanicilar()->count(),
            'portal sayfası kuralı' => PortalSayfaKurali::query()
                ->where('secim_tipi', 'merkez')
                ->where('hedef_id', $this->id)
                ->count(),
        ])->filter()->map(fn (int $adet, string $etiket) => "{$adet} {$etiket}")->values()->all();
    }

    /**
     * @param  Builder<Merkez>  $query
     * @return Builder<Merkez>
     */
    public function scopeKullaniciKapsami(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->sadeceYetkiliMerkezleriGorur()) {
            return $query;
        }

        $ids = $user->yetkiliMerkezIdleri();

        if ($ids === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn($query->getModel()->getTable().'.id', $ids);
    }
}
