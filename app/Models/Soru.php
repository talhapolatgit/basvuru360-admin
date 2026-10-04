<?php

namespace App\Models;

use App\Enums\SoruTipi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Soru extends Model
{
    protected $table = 'sorular';

    protected $fillable = [
        'form_id',
        'tip',
        'baslik',
        'aciklama',
        'zorunlu',
        'min_deger',
        'max_deger',
        'tam_sayi',
        'kosul_soru_id',
        'kosul_secenek_ids',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'tip' => SoruTipi::class,
            'zorunlu' => 'boolean',
            'tam_sayi' => 'boolean',
            'min_deger' => 'float',
            'max_deger' => 'float',
            'kosul_soru_id' => 'integer',
            'kosul_secenek_ids' => 'array',
            'sira' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SoruFormu, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(SoruFormu::class, 'form_id');
    }

    /**
     * @return HasMany<SoruSecenek, $this>
     */
    public function secenekler(): HasMany
    {
        return $this->hasMany(SoruSecenek::class, 'soru_id')->orderBy('sira')->orderBy('id');
    }

    /**
     * @return BelongsTo<Soru, $this>
     */
    public function kosulSoru(): BelongsTo
    {
        return $this->belongsTo(Soru::class, 'kosul_soru_id');
    }

    public function kosulluMu(): bool
    {
        return $this->kosul_soru_id !== null && $this->kosulSecenekIdleri() !== [];
    }

    /**
     * @return list<int>
     */
    public function kosulSecenekIdleri(): array
    {
        return array_values(array_map('intval', $this->kosul_secenek_ids ?? []));
    }

    /**
     * Editör kartında gösterilen koşul açıklaması, ör. "Kemanınız var mı? = Evet".
     *
     * @param  iterable<Soru>  $formSorulari
     */
    public function kosulOzeti(iterable $formSorulari): ?string
    {
        if (! $this->kosulluMu()) {
            return null;
        }

        foreach ($formSorulari as $soru) {
            if ($soru->id !== $this->kosul_soru_id) {
                continue;
            }
            $etiketler = $soru->secenekler
                ->whereIn('id', $this->kosulSecenekIdleri())
                ->pluck('etiket')
                ->implode(' veya ');

            return $soru->baslik.' = '.($etiketler !== '' ? $etiketler : '—');
        }

        return null;
    }

    public function sayiOzeti(): ?string
    {
        if ($this->tip === SoruTipi::Checkbox) {
            $parcalar = [];
            if ($this->min_deger !== null) {
                $parcalar[] = 'En az '.$this->sayiMetni($this->min_deger).' seçim';
            }
            if ($this->max_deger !== null) {
                $parcalar[] = 'En fazla '.$this->sayiMetni($this->max_deger).' seçim';
            }

            return $parcalar === [] ? null : implode(' · ', $parcalar);
        }

        if ($this->tip !== SoruTipi::Sayi) {
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

    /**
     * Portal API'sinde kullanılan soru gösterimi.
     *
     * @return array<string, mixed>
     */
    public function apiVerisi(): array
    {
        return [
            'id' => $this->id,
            'tip' => $this->tip->value,
            'baslik' => $this->baslik,
            'aciklama' => $this->aciklama,
            'zorunlu' => (bool) $this->zorunlu,
            'placeholder' => $this->tip->placeholder() ?: null,
            'min_deger' => $this->min_deger,
            'max_deger' => $this->max_deger,
            'tam_sayi' => (bool) $this->tam_sayi,
            'kosul_soru_id' => $this->kosulluMu() ? $this->kosul_soru_id : null,
            'kosul_secenek_ids' => $this->kosulluMu() ? $this->kosulSecenekIdleri() : [],
            'secenekler' => $this->secenekler->map(fn (SoruSecenek $secenek) => [
                'id' => $secenek->id,
                'etiket' => $secenek->etiket,
            ])->values()->all(),
        ];
    }

    private function sayiMetni(float $deger): string
    {
        if (fmod($deger, 1.0) === 0.0) {
            return (string) (int) $deger;
        }

        return rtrim(rtrim(number_format($deger, 4, ',', ''), '0'), ',');
    }
}
