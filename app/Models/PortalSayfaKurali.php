<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalSayfaKurali extends Model
{
    public const KAYNAK_KURS = 'kurs';

    public const KAYNAK_ETKINLIK = 'etkinlik';

    public const KURS_SECIM_TIPLERI = ['tum', 'kurs', 'brans', 'alan', 'merkez'];

    public const ETKINLIK_SECIM_TIPLERI = ['tum', 'etkinlik', 'tur', 'merkez'];

    protected $table = 'portal_sayfa_kurallari';

    protected $fillable = [
        'portal_sayfa_id',
        'kaynak',
        'secim_tipi',
        'hedef_id',
        'sira',
    ];

    protected function casts(): array
    {
        return [
            'hedef_id' => 'integer',
            'sira' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PortalSayfa, $this>
     */
    public function sayfa(): BelongsTo
    {
        return $this->belongsTo(PortalSayfa::class, 'portal_sayfa_id');
    }
}
