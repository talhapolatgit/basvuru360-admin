<?php

namespace App\Models;

use App\Enums\KatilimDurumu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtkinlikBasvuru extends Model
{
    use SoftDeletes;

    protected $table = 'etkinlik_basvurulari';

    protected $fillable = [
        'kisi_id',
        'basvuran_id',
        'veli_id',
        'etkinlik_id',
        'durum_id',
        'yedek_sira',
        'katilim_durumu',
        'onay_tarihi',
        'onaylayan_id',
        'iptal_tarihi',
        'iptal_gerekce_id',
        'iptal_eden_id',
        'olusturan_id',
        'guncelleyen_id',
        'silen_id',
    ];

    protected function casts(): array
    {
        return [
            'katilim_durumu' => KatilimDurumu::class,
            'yedek_sira' => 'integer',
            'onay_tarihi' => 'datetime',
            'iptal_tarihi' => 'datetime',
        ];
    }

    public function yedekteMi(): bool
    {
        return $this->durum?->kod === 'yedek' || $this->yedek_sira !== null;
    }

    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    public function basvuran(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'basvuran_id');
    }

    public function veli(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'veli_id');
    }

    public function veliBasvurusuMu(): bool
    {
        return $this->veli_id !== null;
    }

    public function etkinlik(): BelongsTo
    {
        return $this->belongsTo(Etkinlik::class, 'etkinlik_id');
    }

    public function durum(): BelongsTo
    {
        return $this->belongsTo(EtkinlikBasvuruDurum::class, 'durum_id');
    }

    public function iptalGerekce(): BelongsTo
    {
        return $this->belongsTo(IptalGerekce::class, 'iptal_gerekce_id');
    }

    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    public function guncelleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guncelleyen_id');
    }

    public function onaylayan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'onaylayan_id');
    }

    public function iptalEden(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iptal_eden_id');
    }

    public function silen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'silen_id');
    }

    public function evraklar(): HasMany
    {
        return $this->hasMany(EtkinlikBasvuruEvrak::class, 'etkinlik_basvuru_id');
    }
}
