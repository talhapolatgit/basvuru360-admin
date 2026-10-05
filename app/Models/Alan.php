<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alan extends Model
{
    protected $table = 'alanlar';

    protected $fillable = [
        'ad',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Brans, $this>
     */
    public function branslar(): HasMany
    {
        return $this->hasMany(Brans::class, 'alan_id');
    }

    /**
     * @return HasMany<Kurs, $this>
     */
    public function kurslar(): HasMany
    {
        return $this->hasMany(Kurs::class, 'alan_id');
    }

    /**
     * Silmeyi engelleyen bağlı kayıtlar, ör. ["3 kurs", "2 branş"].
     *
     * @return list<string>
     */
    public function silmeEngelleri(): array
    {
        return collect([
            'kurs' => $this->kurslar()->count(),
            'branş' => $this->branslar()->count(),
            'portal sayfası kuralı' => PortalSayfaKurali::query()
                ->where('secim_tipi', 'alan')
                ->where('hedef_id', $this->id)
                ->count(),
        ])->filter()->map(fn (int $adet, string $etiket) => "{$adet} {$etiket}")->values()->all();
    }
}
