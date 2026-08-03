<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IptalGerekce extends Model
{
    protected $table = 'iptal_gerekceleri';

    protected $fillable = [
        'ad',
        'aciklama',
        'sira',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'sira' => 'integer',
        ];
    }

    /**
     * @return HasMany<KursBasvuru, $this>
     */
    public function basvurular(): HasMany
    {
        return $this->hasMany(KursBasvuru::class, 'iptal_gerekce_id');
    }
}
