<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EvrakTipi extends Model
{
    protected $table = 'evrak_tipleri';

    protected $fillable = [
        'ad',
        'aciklama',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Kurs, $this>
     */
    public function kurslar(): BelongsToMany
    {
        return $this->belongsToMany(Kurs::class, 'kurs_evraklari', 'evrak_tipi_id', 'kurs_id')
            ->withTimestamps();
    }
}
