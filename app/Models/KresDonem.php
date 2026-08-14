<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KresDonem extends Model
{
    protected $table = 'kres_donemler';

    protected $fillable = [
        'ad',
        'baslangic',
        'bitis',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'baslangic' => 'date',
            'bitis' => 'date',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<KresGrup, $this>
     */
    public function gruplar(): HasMany
    {
        return $this->hasMany(KresGrup::class, 'donem_id');
    }
}
