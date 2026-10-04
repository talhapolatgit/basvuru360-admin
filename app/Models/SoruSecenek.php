<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoruSecenek extends Model
{
    protected $table = 'soru_secenekleri';

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
     * @return BelongsTo<Soru, $this>
     */
    public function soru(): BelongsTo
    {
        return $this->belongsTo(Soru::class, 'soru_id');
    }
}
