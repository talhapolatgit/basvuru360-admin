<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KursDers extends Model
{
    use SoftDeletes;

    protected $table = 'kurs_dersleri';

    protected $fillable = [
        'kurs_id',
        'kurs_gun_id',
        'tarih',
        'baslangic_saati',
        'bitis_saati',
        'ders_saati',
        'sinif',
        'iptal_edildi',
        'iptal_gerekcesi',
        'iptal_eden_id',
        'iptal_tarihi',
        'orijinal_tarih',
        'tarih_degistiren_id',
        'tarih_degisiklik_tarihi',
        'yoklama_alindi',
        'yoklama_alan_id',
        'olusturan_id',
        'guncelleyen_id',
    ];

    protected function casts(): array
    {
        return [
            'tarih' => 'date',
            'ders_saati' => 'float',
            'iptal_edildi' => 'boolean',
            'iptal_tarihi' => 'datetime',
            'orijinal_tarih' => 'date',
            'tarih_degisiklik_tarihi' => 'datetime',
            'yoklama_alindi' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Kurs, $this>
     */
    public function kurs(): BelongsTo
    {
        return $this->belongsTo(Kurs::class, 'kurs_id');
    }

    /**
     * @return BelongsTo<KursGun, $this>
     */
    public function kursGun(): BelongsTo
    {
        return $this->belongsTo(KursGun::class, 'kurs_gun_id');
    }

    /**
     * @return HasMany<KursYoklama, $this>
     */
    public function yoklamalar(): HasMany
    {
        return $this->hasMany(KursYoklama::class, 'kurs_ders_id');
    }

    /**
     * Yoklamayı kaydeden kullanıcı.
     *
     * @return BelongsTo<User, $this>
     */
    public function yoklamaAlan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'yoklama_alan_id');
    }

    /**
     * Dersi iptal eden kullanıcı.
     *
     * @return BelongsTo<User, $this>
     */
    public function iptalEden(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iptal_eden_id');
    }

    /**
     * Ders tarihini değiştiren kullanıcı.
     *
     * @return BelongsTo<User, $this>
     */
    public function tarihDegistiren(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tarih_degistiren_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guncelleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guncelleyen_id');
    }

    public function ozet(): string
    {
        return sprintf(
            '%s %s-%s',
            $this->tarih?->format('d.m.Y') ?? '',
            substr((string) $this->baslangic_saati, 0, 5),
            substr((string) $this->bitis_saati, 0, 5)
        );
    }

    public function saatAdedi(): int
    {
        return max(1, (int) ceil((float) $this->ders_saati));
    }
}
