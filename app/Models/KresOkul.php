<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KresOkul extends Model
{
    protected $table = 'kres_okullar';

    protected $fillable = [
        'ad',
        'adres',
        'telefon',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<KresGrup, $this>
     */
    public function gruplar(): HasMany
    {
        return $this->hasMany(KresGrup::class, 'okul_id');
    }
}
