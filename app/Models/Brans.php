<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brans extends Model
{
    protected $table = 'branslar';

    protected $fillable = [
        'alan_id',
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
     * @return BelongsTo<Alan, $this>
     */
    public function alan(): BelongsTo
    {
        return $this->belongsTo(Alan::class, 'alan_id');
    }

    /**
     * @return HasMany<Kurs, $this>
     */
    public function kurslar(): HasMany
    {
        return $this->hasMany(Kurs::class, 'brans_id');
    }
}
