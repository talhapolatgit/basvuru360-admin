<?php

namespace App\Models;

use App\Services\GuvenilirIpServisi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuvenilirIpAdresi extends Model
{
    protected $table = 'guvenilir_ip_adresleri';

    protected $fillable = [
        'ip_adresi',
        'aciklama',
        'olusturan_id',
        'guncelleyen_id',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => GuvenilirIpServisi::onbellegiTemizle());
        static::deleted(fn () => GuvenilirIpServisi::onbellegiTemizle());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guncelleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guncelleyen_id');
    }
}
