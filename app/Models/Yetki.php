<?php

namespace App\Models;

use App\Support\YetkiKatalogu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Yetki extends Model
{
    protected $table = 'yetkiler';

    protected $fillable = [
        'kod',
        'ad',
        'modul',
        'aciklama',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'sira' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Rol, $this>
     */
    public function roller(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_yetki', 'yetki_id', 'rol_id');
    }

    public function getModulAdiAttribute(): string
    {
        return YetkiKatalogu::MODULLER[$this->modul] ?? $this->modul;
    }
}
