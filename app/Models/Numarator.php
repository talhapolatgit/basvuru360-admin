<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Numarator extends Model
{
    protected $table = 'numarator';

    protected $fillable = [
        'kod',
        'son_numara',
    ];

    protected function casts(): array
    {
        return [
            'son_numara' => 'integer',
        ];
    }
}
