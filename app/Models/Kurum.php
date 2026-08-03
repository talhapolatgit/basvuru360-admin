<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kurum extends Model
{
    protected $table = 'kurumlar';

    protected $fillable = [
        'ad',
        'aktif',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'sira' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function kullanicilar(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kullanici_kurum', 'kurum_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Kurs, $this>
     */
    public function kurslar(): BelongsToMany
    {
        return $this->belongsToMany(Kurs::class, 'kurs_kurum', 'kurum_id', 'kurs_id')
            ->withTimestamps();
    }
}
