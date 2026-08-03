<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtkinlikBasvuruDurum extends Model
{
    protected $table = 'etkinlik_basvuru_durumlari';

    protected $fillable = [
        'kod',
        'ad',
        'aciklama',
        'status_sinifi',
        'sira',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'sira' => 'integer',
        ];
    }

    /**
     * @return HasMany<EtkinlikBasvuru, $this>
     */
    public function basvurular(): HasMany
    {
        return $this->hasMany(EtkinlikBasvuru::class, 'durum_id');
    }

    public function statusClass(): string
    {
        return $this->status_sinifi ?: 'status-hazirlik';
    }

    public static function idByKod(string $kod): ?int
    {
        return static::query()->where('kod', $kod)->value('id');
    }
}
