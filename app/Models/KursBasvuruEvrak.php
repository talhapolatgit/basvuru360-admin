<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class KursBasvuruEvrak extends Model
{
    use SoftDeletes;

    protected $table = 'kurs_basvuru_evraklari';

    protected $fillable = [
        'kurs_basvuru_id',
        'evrak_tipi_id',
        'dosya_yolu',
        'orijinal_ad',
        'mime',
        'boyut',
        'olusturan_id',
        'silen_id',
    ];

    protected function casts(): array
    {
        return [
            'boyut' => 'integer',
        ];
    }

    public function basvuru(): BelongsTo
    {
        return $this->belongsTo(KursBasvuru::class, 'kurs_basvuru_id');
    }

    public function evrakTipi(): BelongsTo
    {
        return $this->belongsTo(EvrakTipi::class, 'evrak_tipi_id');
    }

    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    public function silen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'silen_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->dosya_yolu) {
            return null;
        }

        return Storage::disk('public')->url($this->dosya_yolu);
    }
}
