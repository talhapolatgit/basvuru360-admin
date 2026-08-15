<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KresBasvuruCevap extends Model
{
    protected $table = 'kres_basvuru_cevaplari';

    protected $fillable = [
        'basvuru_id',
        'soru_id',
        'deger',
        'dosya_yolu',
        'orijinal_ad',
        'mime',
        'boyut',
    ];

    /**
     * @return BelongsTo<KresBasvuru, $this>
     */
    public function basvuru(): BelongsTo
    {
        return $this->belongsTo(KresBasvuru::class, 'basvuru_id');
    }

    /**
     * @return BelongsTo<KresSoru, $this>
     */
    public function soru(): BelongsTo
    {
        return $this->belongsTo(KresSoru::class, 'soru_id');
    }
}
