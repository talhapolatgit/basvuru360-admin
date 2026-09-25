<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YakinlikDerecesi extends Model
{
    protected $table = 'yakinlik_dereceleri';

    protected $fillable = [
        'kod',
        'ad',
        'sira',
    ];

    /**
     * @return HasMany<KisiYakin, $this>
     */
    public function kisiYakinlar(): HasMany
    {
        return $this->hasMany(KisiYakin::class, 'yakinlik_derecesi_id');
    }

    public static function cocuktan(?string $cinsiyet): ?self
    {
        $kod = match ($cinsiyet) {
            'erkek' => 'OGLU',
            'kadin' => 'KIZI',
            default => null,
        };

        if (! $kod) {
            return null;
        }

        return static::query()->where('kod', $kod)->first();
    }
}
