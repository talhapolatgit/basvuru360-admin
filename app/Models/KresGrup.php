<?php

namespace App\Models;

use App\Enums\Cinsiyet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KresGrup extends Model
{
    protected $table = 'kres_gruplar';

    protected $fillable = [
        'okul_id',
        'donem_id',
        'ad',
        'min_yas',
        'max_yas',
        'kontenjan',
        'yedek_kontenjan',
        'cinsiyet_sarti',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'min_yas' => 'integer',
            'max_yas' => 'integer',
            'kontenjan' => 'integer',
            'yedek_kontenjan' => 'integer',
            'cinsiyet_sarti' => Cinsiyet::class,
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KresOkul, $this>
     */
    public function okul(): BelongsTo
    {
        return $this->belongsTo(KresOkul::class, 'okul_id');
    }

    /**
     * @return BelongsTo<KresDonem, $this>
     */
    public function donem(): BelongsTo
    {
        return $this->belongsTo(KresDonem::class, 'donem_id');
    }

    /**
     * @return HasMany<KresBasvuru, $this>
     */
    public function basvurular(): HasMany
    {
        return $this->hasMany(KresBasvuru::class, 'grup_id');
    }

    public function yasAraligiLabel(): string
    {
        if ($this->min_yas === null && $this->max_yas === null) {
            return '—';
        }

        if ($this->min_yas !== null && $this->max_yas !== null) {
            if ((int) $this->min_yas === (int) $this->max_yas) {
                return $this->min_yas.' yaş';
            }

            return $this->min_yas.'–'.$this->max_yas.' yaş';
        }

        if ($this->min_yas !== null) {
            return $this->min_yas.'+ yaş';
        }

        return '≤ '.$this->max_yas.' yaş';
    }

    public function cinsiyetSartiLabel(): string
    {
        return $this->cinsiyet_sarti?->label() ?? 'Farketmez';
    }

    public function ogrenciUygunMu(?int $yas, ?Cinsiyet $cinsiyet = null): bool
    {
        if (! $this->aktif) {
            return false;
        }

        if ($this->min_yas !== null && ($yas === null || $yas < (int) $this->min_yas)) {
            return false;
        }

        if ($this->max_yas !== null && ($yas === null || $yas > (int) $this->max_yas)) {
            return false;
        }

        if ($this->cinsiyet_sarti instanceof Cinsiyet && $cinsiyet instanceof Cinsiyet) {
            return $this->cinsiyet_sarti === $cinsiyet;
        }

        return true;
    }
}
