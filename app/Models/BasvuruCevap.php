<?php

namespace App\Models;

use App\Enums\SoruTipi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Kurs, etkinlik veya kreş başvurusunda bir soruya verilen cevap.
 * Soru metni ve seçenek etiketleri cevapla birlikte saklanır; form sonradan değişse de cevap okunur kalır.
 */
class BasvuruCevap extends Model
{
    protected $table = 'basvuru_cevaplari';

    protected $fillable = [
        'basvuru_type',
        'basvuru_id',
        'soru_id',
        'soru_baslik',
        'soru_tip',
        'deger',
        'deger_metin',
        'dosya_yolu',
        'orijinal_ad',
        'mime',
        'boyut',
    ];

    protected function casts(): array
    {
        return [
            'soru_id' => 'integer',
            'boyut' => 'integer',
        ];
    }

    public function basvuru(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Soru, $this>
     */
    public function soru(): BelongsTo
    {
        return $this->belongsTo(Soru::class, 'soru_id');
    }

    public function tip(): ?SoruTipi
    {
        return SoruTipi::tryFrom((string) $this->soru_tip);
    }

    public function dosyaMi(): bool
    {
        return $this->dosya_yolu !== null && $this->dosya_yolu !== '';
    }

    public function dosyaUrl(): ?string
    {
        return $this->dosyaMi() ? Storage::disk('public')->url($this->dosya_yolu) : null;
    }

    /**
     * Liste, detay ve Excel'de gösterilecek okunur değer.
     */
    public function gorunenDeger(): string
    {
        if ($this->dosyaMi()) {
            return (string) ($this->orijinal_ad ?: basename((string) $this->dosya_yolu));
        }

        if ($this->deger_metin !== null && $this->deger_metin !== '') {
            return $this->deger_metin;
        }

        $deger = (string) ($this->deger ?? '');

        if ($this->tip() === SoruTipi::Tarih && $deger !== '' && ($zaman = strtotime($deger))) {
            return date('d.m.Y', $zaman);
        }

        return $deger;
    }
}
