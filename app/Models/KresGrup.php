<?php

namespace App\Models;

use App\Enums\Cinsiyet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Throwable;

class KresGrup extends Model
{
    protected $table = 'kres_gruplar';

    protected $fillable = [
        'okul_id',
        'donem_id',
        'ad',
        'min_yas',
        'max_yas',
        'dogum_baslangic',
        'dogum_bitis',
        'kontenjan',
        'yedek_kontenjan',
        'cinsiyet_sarti',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'min_yas' => 'integer',
            'max_yas' => 'integer',
            'dogum_baslangic' => 'date',
            'dogum_bitis' => 'date',
            'kontenjan' => 'integer',
            'yedek_kontenjan' => 'integer',
            'cinsiyet_sarti' => Cinsiyet::class,
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KresOkul, $this>
     */
    public function okul(): BelongsTo
    {
        return $this->belongsTo(KresOkul::class, 'okul_id');
    }

    /**
     * @return BelongsTo<KresDonem, $this>
     */
    public function donem(): BelongsTo
    {
        return $this->belongsTo(KresDonem::class, 'donem_id');
    }

    /**
     * @return HasMany<KresBasvuru, $this>
     */
    public function basvurular(): HasMany
    {
        return $this->hasMany(KresBasvuru::class, 'grup_id');
    }

    public function dogumAraligiKullaniliyorMu(): bool
    {
        return $this->dogum_baslangic !== null || $this->dogum_bitis !== null;
    }

    public function kriterEtiketi(): string
    {
        if ($this->dogumAraligiKullaniliyorMu()) {
            return $this->dogumAraligiLabel();
        }

        return $this->yasAraligiLabel();
    }

    public function dogumAraligiLabel(): string
    {
        $baslangic = $this->dogum_baslangic?->format('d.m.Y');
        $bitis = $this->dogum_bitis?->format('d.m.Y');

        if ($baslangic && $bitis) {
            return $baslangic.' – '.$bitis;
        }

        if ($baslangic) {
            return $baslangic.' ve sonrası';
        }

        if ($bitis) {
            return $bitis.' ve öncesi';
        }

        return '—';
    }

    public function yasAraligiLabel(): string
    {
        if ($this->min_yas === null && $this->max_yas === null) {
            return '—';
        }

        if ($this->min_yas !== null && $this->max_yas !== null) {
            if ((int) $this->min_yas === (int) $this->max_yas) {
                return $this->min_yas.' yaş';
            }

            return $this->min_yas.'–'.$this->max_yas.' yaş';
        }

        if ($this->min_yas !== null) {
            return $this->min_yas.'+ yaş';
        }

        return '≤ '.$this->max_yas.' yaş';
    }

    public function cinsiyetSartiLabel(): string
    {
        return $this->cinsiyet_sarti?->label() ?? 'Farketmez';
    }

    public function yasKriterineUygunMu(?int $yas): bool
    {
        if ($this->min_yas !== null && ($yas === null || $yas < (int) $this->min_yas)) {
            return false;
        }

        if ($this->max_yas !== null && ($yas === null || $yas > (int) $this->max_yas)) {
            return false;
        }

        return true;
    }

    public function dogumTarihiAraliginaUygunMu(mixed $dogum): bool
    {
        $tarih = $this->tarihMetni($dogum);
        if ($tarih === null) {
            return false;
        }

        $baslangic = $this->dogum_baslangic?->toDateString();
        $bitis = $this->dogum_bitis?->toDateString();

        if ($baslangic !== null && $tarih < $baslangic) {
            return false;
        }

        if ($bitis !== null && $tarih > $bitis) {
            return false;
        }

        return true;
    }

    public function ogrenciUygunMu(?int $yas, ?Cinsiyet $cinsiyet = null, mixed $dogumTarihi = null): bool
    {
        if (! $this->aktif) {
            return false;
        }

        if ($this->dogumAraligiKullaniliyorMu()) {
            if (! $this->dogumTarihiAraliginaUygunMu($dogumTarihi)) {
                return false;
            }
        } elseif (! $this->yasKriterineUygunMu($yas)) {
            return false;
        }

        if ($this->cinsiyet_sarti instanceof Cinsiyet && $cinsiyet instanceof Cinsiyet) {
            return $this->cinsiyet_sarti === $cinsiyet;
        }

        return true;
    }

    private function tarihMetni(mixed $dogum): ?string
    {
        if ($dogum === null || $dogum === '') {
            return null;
        }

        try {
            return Carbon::parse($dogum)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
