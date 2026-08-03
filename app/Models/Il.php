<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Il extends Model
{
    protected $table = 'iller';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'ad',
    ];

    /**
     * @return HasMany<Ilce, $this>
     */
    public function ilceler(): HasMany
    {
        return $this->hasMany(Ilce::class, 'il_id');
    }
}