<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SertifikaAyar extends Model
{
    protected $table = 'sertifika_ayarlari';

    protected $fillable = [
        'kurum_adi',
        'sablonlar',
    ];

    protected function casts(): array
    {
        return [
            'sablonlar' => 'array',
        ];
    }

    public static function current(): self
    {
        $ayar = static::query()->first();

        if ($ayar) {
            return $ayar;
        }

        return static::query()->create([
            'kurum_adi' => null,
            'sablonlar' => [],
        ]);
    }
}
