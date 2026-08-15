<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KresSoruSecenek extends Model
{
    protected $table = 'kres_soru_secenekler';

    protected $fillable = [
        'soru_id',
        'etiket',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'sira' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<KresSoru, $this>
     */
    public function soru(): BelongsTo
    {
        return $this->belongsTo(KresSoru::class, 'soru_id');
    }
}
