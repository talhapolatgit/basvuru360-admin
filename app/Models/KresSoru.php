<?php

namespace App\Models;

use App\Enums\KresSoruTipi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KresSoru extends Model
{
    protected $table = 'kres_sorular';

    protected $fillable = [
        'form_id',
        'tip',
        'baslik',
        'aciklama',
        'zorunlu',
        'min_deger',
        'max_deger',
        'tam_sayi',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'tip' => KresSoruTipi::class,
            'zorunlu' => 'boolean',
            'tam_sayi' => 'boolean',
            'min_deger' => 'float',
            'max_deger' => 'float',
            'sira' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<KresSoruFormu, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(KresSoruFormu::class, 'form_id');
    }

    /**
     * @return HasMany<KresSoruSecenek, $this>
     */
    public function secenekler(): HasMany
    {
        return $this->hasMany(KresSoruSecenek::class, 'soru_id')->orderBy('sira')->orderBy('id');
    }

    public function sayiOzeti(): ?string
    {
        if ($this->tip === KresSoruTipi::Checkbox) {
            $parcalar = [];
            if ($this->min_deger !== null) {
                $parcalar[] = 'En az '.$this->sayiMetni($this->min_deger).' seçim';
            }
            if ($this->max_deger !== null) {
                $parcalar[] = 'En fazla '.$this->sayiMetni($this->max_deger).' seçim';
            }

            return $parcalar === [] ? null : implode(' · ', $parcalar);
        }

        if ($this->tip !== KresSoruTipi::Sayi) {
            return null;
        }

        $parcalar = [];
        if ($this->min_deger !== null) {
            $parcalar[] = 'Min '.$this->sayiMetni($this->min_deger);
        }
        if ($this->max_deger !== null) {
            $parcalar[] = 'Maks '.$this->sayiMetni($this->max_deger);
        }
        if ($this->tam_sayi) {
            $parcalar[] = 'Tam sayı';
        }

        return $parcalar === [] ? null : implode(' · ', $parcalar);
    }

    private function sayiMetni(float $deger): string
    {
        if (fmod($deger, 1.0) === 0.0) {
            return (string) (int) $deger;
        }

        return rtrim(rtrim(number_format($deger, 4, ',', ''), '0'), ',');
    }
}
