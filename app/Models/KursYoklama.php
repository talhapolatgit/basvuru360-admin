<?php

namespace App\Models;

use App\Enums\YoklamaDurum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KursYoklama extends Model
{
    use SoftDeletes;

    protected $table = 'kurs_yoklamalari';

    protected $fillable = [
        'kurs_ders_id',
        'kurs_basvuru_id',
        'kisi_id',
        'durum',
        'saatlik_durumlar',
        'aciklama',
        'olusturan_id',
        'guncelleyen_id',
    ];

    protected function casts(): array
    {
        return [
            'durum' => YoklamaDurum::class,
            'saatlik_durumlar' => 'array',
        ];
    }

    /**
     * @return BelongsTo<KursDers, $this>
     */
    public function ders(): BelongsTo
    {
        return $this->belongsTo(KursDers::class, 'kurs_ders_id');
    }

    /**
     * @return BelongsTo<KursBasvuru, $this>
     */
    public function basvuru(): BelongsTo
    {
        return $this->belongsTo(KursBasvuru::class, 'kurs_basvuru_id');
    }

    /**
     * @return BelongsTo<Kisi, $this>
     */
    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function olusturan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function guncelleyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guncelleyen_id');
    }

    /**
     * @return list<string>
     */
    public function normalizeSaatlikDurumlar(int $adet): array
    {
        $adet = max(1, $adet);
        $current = array_values(is_array($this->saatlik_durumlar) ? $this->saatlik_durumlar : []);
        $allowed = YoklamaDurum::values();
        $out = [];

        for ($i = 0; $i < $adet; $i++) {
            $val = (string) ($current[$i] ?? YoklamaDurum::Yok->value);
            if ($val === 'gec') {
                $val = YoklamaDurum::Var->value;
            }
            if (! in_array($val, $allowed, true)) {
                $val = YoklamaDurum::Yok->value;
            }
            $out[] = $val;
        }

        return $out;
    }

    /**
     * @param  list<string>  $saatlik
     */
    public function syncDurumFromSaatlik(array $saatlik): YoklamaDurum
    {
        if ($saatlik === []) {
            return YoklamaDurum::Yok;
        }

        if (in_array(YoklamaDurum::Var->value, $saatlik, true)) {
            return YoklamaDurum::Var;
        }

        if (in_array(YoklamaDurum::Izinli->value, $saatlik, true)) {
            return YoklamaDurum::Izinli;
        }

        return YoklamaDurum::Yok;
    }

    public function varSaatSayisi(): int
    {
        $saatlik = is_array($this->saatlik_durumlar) ? $this->saatlik_durumlar : [];

        return count(array_filter(
            $saatlik,
            fn ($durum) => $durum === YoklamaDurum::Var->value
        ));
    }

    public function herhangiBirSaatteVarMi(): bool
    {
        return $this->varSaatSayisi() > 0;
    }
}
