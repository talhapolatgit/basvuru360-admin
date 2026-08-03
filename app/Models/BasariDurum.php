<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BasariDurum extends Model
{
    protected $table = 'basari_durumlari';

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
     * @return HasMany<KursBasvuru, $this>
     */
    public function basvurular(): HasMany
    {
        return $this->hasMany(KursBasvuru::class, 'basari_durumu_id');
    }

    public function statusClass(): string
    {
        return $this->status_sinifi ?: 'status-hazirlik';
    }
}
