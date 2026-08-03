<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $table = 'sms_loglari';

    protected $fillable = [
        'telefon',
        'mesaj',
        'durum',
        'hata_mesaji',
        'kurs_id',
        'basvuru_id',
        'etkinlik_id',
        'etkinlik_basvuru_id',
        'gonderen_id',
    ];

    /**
     * @return BelongsTo<Kurs, $this>
     */
    public function kurs(): BelongsTo
    {
        return $this->belongsTo(Kurs::class);
    }

    /**
     * @return BelongsTo<KursBasvuru, $this>
     */
    public function basvuru(): BelongsTo
    {
        return $this->belongsTo(KursBasvuru::class, 'basvuru_id');
    }

    public function etkinlik(): BelongsTo
    {
        return $this->belongsTo(Etkinlik::class);
    }

    public function etkinlikBasvuru(): BelongsTo
    {
        return $this->belongsTo(EtkinlikBasvuru::class, 'etkinlik_basvuru_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function gonderen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gonderen_id');
    }
}
