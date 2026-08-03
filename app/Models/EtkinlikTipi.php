<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtkinlikTipi extends Model
{
    protected $table = 'etkinlik_tipleri';

    protected $fillable = [
        'ad',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Etkinlik, $this>
     */
    public function etkinlikler(): HasMany
    {
        return $this->hasMany(Etkinlik::class, 'etkinlik_tipi_id');
    }
}
