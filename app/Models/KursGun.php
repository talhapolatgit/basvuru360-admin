<?php

namespace App\Models;

use App\Enums\HaftaGunu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KursGun extends Model
{
    protected $table = 'kurs_gunleri';

    protected $fillable = [
        'kurs_id',
        'gun',
        'baslangic_saati',
        'bitis_saati',
        'ders_saati',
        'sinif',
    ];

    protected function casts(): array
    {
        return [
            'gun' => HaftaGunu::class,
            'ders_saati' => 'float',
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
     * @return HasMany<KursDers, $this>
     */
    public function dersler(): HasMany
    {
        return $this->hasMany(KursDers::class, 'kurs_gun_id');
    }

    public function ozet(): string
    {
        $baslangic = substr((string) $this->baslangic_saati, 0, 5);
        $bitis = substr((string) $this->bitis_saati, 0, 5);
        $saat = rtrim(rtrim(number_format((float) $this->ders_saati, 1, '.', ''), '0'), '.');

        return sprintf(
            '%s %s-%s (%ss)',
            $this->gun?->kisaLabel() ?? '',
            $baslangic,
            $bitis,
            $saat
        );
    }
}
