<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alan extends Model
{
    protected $table = 'alanlar';

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
     * @return HasMany<Brans, $this>
     */
    public function branslar(): HasMany
    {
        return $this->hasMany(Brans::class, 'alan_id');
    }

    /**
     * @return HasMany<Kurs, $this>
     */
    public function kurslar(): HasMany
    {
        return $this->hasMany(Kurs::class, 'alan_id');
    }
}
