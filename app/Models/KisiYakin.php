<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KisiYakin extends Model
{
    protected $table = 'kisi_yakinlar';

    protected $fillable = [
        'kisi_id',
        'yakin_kisi_id',
        'yakinlik_derecesi_id',
    ];

    /**
     * @return BelongsTo<Kisi, $this>
     */
    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    /**
     * @return BelongsTo<Kisi, $this>
     */
    public function yakin(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'yakin_kisi_id');
    }

    /**
     * @return BelongsTo<YakinlikDerecesi, $this>
     */
    public function yakinlikDerecesi(): BelongsTo
    {
        return $this->belongsTo(YakinlikDerecesi::class, 'yakinlik_derecesi_id');
    }
}
