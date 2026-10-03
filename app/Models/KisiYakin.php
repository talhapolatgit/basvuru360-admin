<?php

namespace App\Models;

use App\Enums\Cinsiyet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Kişinin yakını. Yakın bilgileri bu kayıtta tutulur; yakin_kisi_id yalnızca
 * aynı T.C. ile bir kişi kaydı varsa doludur.
 */
class KisiYakin extends Model
{
    protected $table = 'kisi_yakinlar';

    protected $fillable = [
        'kisi_id',
        'yakin_kisi_id',
        'ad',
        'soyad',
        'tc_kimlik_no',
        'dogum_tarihi',
        'cinsiyet',
        'yakinlik_derecesi_id',
        'son_sorgu_at',
        'kaydeden_type',
        'kaydeden_id',
    ];

    protected function casts(): array
    {
        return [
            'dogum_tarihi' => 'date',
            'cinsiyet' => Cinsiyet::class,
            'son_sorgu_at' => 'datetime',
        ];
    }

    public function getTamAdiAttribute(): string
    {
        return trim("{$this->ad} {$this->soyad}");
    }

    /**
     * Kaydı ekleyen yönetim paneli kullanıcısı (User) veya portal kişisi (Kisi).
     */
    public function kaydeden(): MorphTo
    {
        return $this->morphTo();
    }

    public function kaydedenAdi(): ?string
    {
        return match (true) {
            $this->kaydeden instanceof User => $this->kaydeden->tam_adi,
            $this->kaydeden instanceof Kisi => $this->kaydeden->tam_adi.' (portal)',
            default => null,
        };
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
    public function yakin(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'yakin_kisi_id');
    }

    /**
     * @return BelongsTo<YakinlikDerecesi, $this>
     */
    public function yakinlikDerecesi(): BelongsTo
    {
        return $this->belongsTo(YakinlikDerecesi::class, 'yakinlik_derecesi_id');
    }
}
