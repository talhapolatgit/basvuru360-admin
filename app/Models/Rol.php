<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $table = 'roller';

    protected $fillable = [
        'kod',
        'ad',
        'aciklama',
        'tum_yetkiler',
        'sistem',
        'sira',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'tum_yetkiler' => 'boolean',
            'sistem' => 'boolean',
            'aktif' => 'boolean',
            'sira' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Yetki, $this>
     */
    public function yetkiler(): BelongsToMany
    {
        return $this->belongsToMany(Yetki::class, 'rol_yetki', 'rol_id', 'yetki_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function kullanicilar(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kullanici_rol', 'rol_id', 'user_id')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->tum_yetkiler || $this->kod === 'admin';
    }

    public function silinebilirMi(): bool
    {
        return ! $this->sistem;
    }
}
