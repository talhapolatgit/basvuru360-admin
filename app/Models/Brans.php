<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brans extends Model
{
    protected $table = 'branslar';

    protected $fillable = [
        'alan_id',
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
     * @return BelongsTo<Alan, $this>
     */
    public function alan(): BelongsTo
    {
        return $this->belongsTo(Alan::class, 'alan_id');
    }

    /**
     * @return HasMany<Kurs, $this>
     */
    public function kurslar(): HasMany
    {
        return $this->hasMany(Kurs::class, 'brans_id');
    }

    /**
     * Silmeyi engelleyen bağlı kayıtlar, ör. ["3 kurs"].
     *
     * @return list<string>
     */
    public function silmeEngelleri(): array
    {
        return collect([
            'kurs' => $this->kurslar()->count(),
            'portal sayfası kuralı' => PortalSayfaKurali::query()
                ->where('secim_tipi', 'brans')
                ->where('hedef_id', $this->id)
                ->count(),
        ])->filter()->map(fn (int $adet, string $etiket) => "{$adet} {$etiket}")->values()->all();
    }
}
