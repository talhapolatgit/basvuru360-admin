<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoruFormu extends Model
{
    protected $table = 'soru_formlari';

    protected $fillable = [
        'ad',
        'aciklama',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Soru, $this>
     */
    public function sorular(): HasMany
    {
        return $this->hasMany(Soru::class, 'form_id')->orderBy('sira')->orderBy('id');
    }

    /**
     * @return HasMany<Kurs, $this>
     */
    public function kurslar(): HasMany
    {
        return $this->hasMany(Kurs::class, 'soru_formu_id');
    }

    /**
     * @return HasMany<Etkinlik, $this>
     */
    public function etkinlikler(): HasMany
    {
        return $this->hasMany(Etkinlik::class, 'soru_formu_id');
    }

    /**
     * @return HasMany<KresDonem, $this>
     */
    public function kresDonemleri(): HasMany
    {
        return $this->hasMany(KresDonem::class, 'soru_formu_id');
    }

    /**
     * Formu kullanan kurs, etkinlik ve kreş dönemi sayısı (withCount ile yüklenmişse onu kullanır).
     */
    public function kullanimSayisi(): int
    {
        return (int) ($this->kurslar_count ?? $this->kurslar()->count())
            + (int) ($this->etkinlikler_count ?? $this->etkinlikler()->count())
            + (int) ($this->kres_donemleri_count ?? $this->kresDonemleri()->count());
    }

    /**
     * Portal başvuru formunda gösterilecek veri. Pasif form veya sorusu olmayan form için null döner.
     *
     * @return array{id: int, ad: string, aciklama: ?string, sorular: list<array<string, mixed>>}|null
     */
    public function apiVerisi(): ?array
    {
        if (! $this->aktif) {
            return null;
        }

        $this->loadMissing('sorular.secenekler');
        if ($this->sorular->isEmpty()) {
            return null;
        }

        return [
            'id' => $this->id,
            'ad' => $this->ad,
            'aciklama' => $this->aciklama,
            'sorular' => $this->sorular->map(fn (Soru $soru) => $soru->apiVerisi())->values()->all(),
        ];
    }
}
