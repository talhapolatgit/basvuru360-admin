<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KresDonem extends Model
{
    protected $table = 'kres_donemler';

    protected $fillable = [
        'ad',
        'baslangic',
        'bitis',
        'aktif',
        'yayinla',
        'soru_formu_id',
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
     * @return BelongsTo<SoruFormu, $this>
     */
    public function soruFormu(): BelongsTo
    {
        return $this->belongsTo(SoruFormu::class, 'soru_formu_id');
    }
}
