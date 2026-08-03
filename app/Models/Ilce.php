<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ilce extends Model
{
    protected $table = 'ilceler';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'il_id',
        'ad',
    ];

    /**
     * @return BelongsTo<Il, $this>
     */
    public function il(): BelongsTo
    {
        return $this->belongsTo(Il::class, 'il_id');
    }
}