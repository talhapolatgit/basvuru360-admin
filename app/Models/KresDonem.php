<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KresDonem extends Model
{
    protected $table = 'kres_donemler';

    protected $fillable = [
        'ad',
        'baslangic',
        'bitis',
        'aktif',
        'yayinla',
    ];

    protected function casts(): array
    {
        return [
            'baslangic' => 'date',
            'bitis' => 'date',
            'aktif' => 'boolean',
            'yayinla' => 'boolean',
        ];
    }

    public function portaldaYayinda(): bool
    {
        return $this->aktif && $this->yayinla;
    }

    /**
     * @return HasMany<KresGrup, $this>
     */
    public function gruplar(): HasMany
    {
        return $this->hasMany(KresGrup::class, 'donem_id');
    }

    /**
     * @return HasOne<KresSoruFormu, $this>
     */
    public function soruFormu(): HasOne
    {
        return $this->hasOne(KresSoruFormu::class, 'donem_id');
    }
}
