<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KresBasvuru extends Model
{
    use SoftDeletes;

    protected $table = 'kres_basvurulari';

    protected $fillable = [
        'grup_id',
        'kisi_id',
        'basvuran_id',
        'durum_id',
        'yedek_sira',
        'notlar',
        'olusturan_id',
    ];

    protected function casts(): array
    {
        return [
            'yedek_sira' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<KresGrup, $this>
     */
    public function grup(): BelongsTo
    {
        return $this->belongsTo(KresGrup::class, 'grup_id');
    }

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
    public function basvuran(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'basvuran_id');
    }

    /**
     * @return BelongsTo<KresBasvuruDurum, $this>
     */
    public function durum(): BelongsTo
    {
        return $this->belongsTo(KresBasvuruDurum::class, 'durum_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    /**
     * @return HasMany<KresBasvuruCevap, $this>
     */
    public function cevaplar(): HasMany
    {
        return $this->hasMany(KresBasvuruCevap::class, 'basvuru_id');
    }
}
