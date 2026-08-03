<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalSayfa extends Model
{
    protected $table = 'portal_sayfalar';

    protected $fillable = [
        'kod',
        'baslik',
        'aciklama',
        'menu_aciklama',
        'anasayfa_logo',
        'anasayfa_menu_arkaplan',
        'anasayfa_menu_arkaplan_mod',
        'sidebar_ikon',
        'slug',
        'sistem',
        'menude_goster',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'sistem' => 'boolean',
            'menude_goster' => 'boolean',
            'sira' => 'integer',
        ];
    }

    /**
     * @return HasMany<PortalSayfaKurali, $this>
     */
    public function kurallar(): HasMany
    {
        return $this->hasMany(PortalSayfaKurali::class, 'portal_sayfa_id')->orderBy('sira')->orderBy('id');
    }

    public function hasKurs(): bool
    {
        return $this->kurallar->contains(fn (PortalSayfaKurali $k) => $k->kaynak === 'kurs');
    }

    public function hasEtkinlik(): bool
    {
        return $this->kurallar->contains(fn (PortalSayfaKurali $k) => $k->kaynak === 'etkinlik');
    }

    public function isAuthSayfa(): bool
    {
        return in_array((string) $this->kod, ['basvurularim', 'profil'], true);
    }
}
