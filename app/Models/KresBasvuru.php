<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    protected static function booted(): void
    {
        static::saving(function (KresBasvuru $basvuru): void {
            $basvuru->aktif_kayit_anahtari = $basvuru->deleted_at === null
                ? $basvuru->grup_id.'-'.$basvuru->kisi_id
                : null;
        });

        static::deleted(function (KresBasvuru $basvuru): void {
            if ($basvuru->isForceDeleting()) {
                return;
            }

            static::withTrashed()->whereKey($basvuru->getKey())->update([
                'aktif_kayit_anahtari' => null,
            ]);
        });
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
     * @return MorphMany<BasvuruCevap, $this>
     */
    public function cevaplar(): MorphMany
    {
        return $this->morphMany(BasvuruCevap::class, 'basvuru');
    }
}
