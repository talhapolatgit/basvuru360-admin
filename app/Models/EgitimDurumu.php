<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EgitimDurumu extends Model
{
    protected $table = 'egitim_durumlari';

    protected $fillable = [
        'ad',
        'seviye',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'seviye' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Kurs, $this>
     */
    public function kurslar(): HasMany
    {
        return $this->hasMany(Kurs::class, 'egitim_durumu_id');
    }
}
