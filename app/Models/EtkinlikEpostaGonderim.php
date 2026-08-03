<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtkinlikEpostaGonderim extends Model
{
    protected $table = 'etkinlik_eposta_gonderimleri';

    protected $fillable = [
        'etkinlik_id',
        'gonderen_id',
        'konu',
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
