<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KursEpostaGonderim extends Model
{
    protected $table = 'kurs_eposta_gonderimleri';

    protected $fillable = [
        'kurs_id',
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

    /**
     * @return BelongsTo<Kurs, $this>
     */
    public function kurs(): BelongsTo
    {
        return $this->belongsTo(Kurs::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function gonderen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gonderen_id');
    }
}
