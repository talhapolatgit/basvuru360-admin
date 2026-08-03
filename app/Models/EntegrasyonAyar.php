<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntegrasyonAyar extends Model
{
    protected $table = 'entegrasyon_ayarlari';

    protected $fillable = [
        'tur',
        'aktif_saglayici',
        'aktif',
        'ayarlar',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'ayarlar' => 'array',
        ];
    }
}
