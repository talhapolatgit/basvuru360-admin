<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtkinlikSmsGonderim extends Model
{
    protected $table = 'etkinlik_sms_gonderimleri';

    protected $fillable = [
        'etkinlik_id',
        'gonderen_id',
        'mesaj',
        'kapsam',
        'basvuru_durum_kod',
        'toplam',
        'gonderilen',
        'atlanan',
        'detay',
    ];

    protected function casts(): array
    {
        return [
            'detay' => 'array',
            'toplam' => 'integer',
            'gonderilen' => 'integer',
            'atlanan' => 'integer',
        ];
    }

    public function etkinlik(): BelongsTo
    {
        return $this->belongsTo(Etkinlik::class);
    }

    public function gonderen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gonderen_id');
    }
}
