<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KresSoruFormu extends Model
{
    protected $table = 'kres_soru_formlari';

    protected $fillable = [
        'donem_id',
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
     * @return BelongsTo<KresDonem, $this>
     */
    public function donem(): BelongsTo
    {
        return $this->belongsTo(KresDonem::class, 'donem_id');
    }

    /**
     * @return HasMany<KresSoru, $this>
     */
    public function sorular(): HasMany
    {
        return $this->hasMany(KresSoru::class, 'form_id')->orderBy('sira')->orderBy('id');
    }
}
