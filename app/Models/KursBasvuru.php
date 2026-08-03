<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KursBasvuru extends Model
{
    use SoftDeletes;

    protected $table = 'kurs_basvurulari';

    protected $fillable = [
        'kisi_id',
        'basvuran_id',
        'veli_id',
        'kurs_id',
        'durum_id',
        'yedek_sira',
        'basari_durumu_id',
        'kursa_baslama_tarihi',
        'onay_tarihi',
        'onaylayan_id',
        'iptal_tarihi',
        'iptal_gerekce_id',
        'iptal_eden_id',
        'olusturan_id',
        'guncelleyen_id',
        'silen_id',
    ];

    protected function casts(): array
    {
        return [
            'yedek_sira' => 'integer',
            'kursa_baslama_tarihi' => 'date',
            'onay_tarihi' => 'datetime',
            'iptal_tarihi' => 'datetime',
        ];
    }

    public function yedekteMi(): bool
    {
        return $this->durum?->kod === 'yedek' || $this->yedek_sira !== null;
    }

    /**
     * Kursa katılacak kişi (öğrenci / çocuk).
     *
     * @return BelongsTo<Kisi, $this>
     */
    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    /**
     * Başvuruyu yapan kişi (kendisi veya veli).
     *
     * @return BelongsTo<Kisi, $this>
     */
    public function basvuran(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'basvuran_id');
    }

    /**
     * Çocuk adına başvuran veli (varsa).
     *
     * @return BelongsTo<Kisi, $this>
     */
    public function veli(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'veli_id');
    }

    public function veliBasvurusuMu(): bool
    {
        return $this->veli_id !== null;
    }

    /**
     * @return BelongsTo<Kurs, $this>
     */
    public function kurs(): BelongsTo
    {
        return $this->belongsTo(Kurs::class, 'kurs_id');
    }

    /**
     * @return BelongsTo<BasvuruDurum, $this>
     */
    public function durum(): BelongsTo
    {
        return $this->belongsTo(BasvuruDurum::class, 'durum_id');
    }

    /**
     * @return BelongsTo<BasariDurum, $this>
     */
    public function basariDurum(): BelongsTo
    {
        return $this->belongsTo(BasariDurum::class, 'basari_durumu_id');
    }

    /**
     * @return BelongsTo<IptalGerekce, $this>
     */
    public function iptalGerekce(): BelongsTo
    {
        return $this->belongsTo(IptalGerekce::class, 'iptal_gerekce_id');
    }

    /**
     * @return HasMany<KursYoklama, $this>
     */
    public function yoklamalar(): HasMany
    {
        return $this->hasMany(KursYoklama::class, 'kurs_basvuru_id');
    }

    /**
     * @return HasMany<KursBasvuruEvrak, $this>
     */
    public function evraklar(): HasMany
    {
        return $this->hasMany(KursBasvuruEvrak::class, 'kurs_basvuru_id');
    }

    /**
     * Alınan yoklamalara göre, kurs saati bazında "var" oranını (yüzde) döndürür.
     * İptal edilen derslerin yoklamaları hesaba katılmaz.
     * Yoklama kaydı yoksa null döner.
     */
    public function yoklamaVarOrani(): ?int
    {
        $yoklamalar = $this->relationLoaded('yoklamalar')
            ? $this->yoklamalar
            : $this->yoklamalar()->with('ders:id,iptal_edildi')->get();

        $toplamSaat = 0;
        $varSaat = 0;

        foreach ($yoklamalar as $yoklama) {
            // İptal edilen derslerin yoklamalarını dikkate alma.
            if ($yoklama->ders?->iptal_edildi) {
                continue;
            }

            $saatlik = is_array($yoklama->saatlik_durumlar) ? $yoklama->saatlik_durumlar : [];

            foreach ($saatlik as $durum) {
                $toplamSaat++;
                if ($durum === \App\Enums\YoklamaDurum::Var->value || $durum === 'gec') {
                    $varSaat++;
                }
            }
        }

        if ($toplamSaat === 0) {
            return null;
        }

        return (int) round($varSaat / $toplamSaat * 100);
    }

    /**
     * @return HasMany<SmsLog, $this>
     */
    public function smsLoglari(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'basvuru_id');
    }

    /**
     * @return HasMany<EpostaLog, $this>
     */
    public function epostaLoglari(): HasMany
    {
        return $this->hasMany(EpostaLog::class, 'basvuru_id');
    }

    /**
     * Panel kullanıcısı (başvuruyu oluşturan personel).
     *
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
     * Başvuruyu onaylayan kullanıcı.
     *
     * @return BelongsTo<User, $this>
     */
    public function onaylayan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'onaylayan_id');
    }

    /**
     * Başvuruyu iptal eden kullanıcı.
     *
     * @return BelongsTo<User, $this>
     */
    public function iptalEden(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iptal_eden_id');
    }

    /**
     * Başvuruyu silen kullanıcı.
     *
     * @return BelongsTo<User, $this>
     */
    public function silen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'silen_id');
    }

    /**
     * Öğrenci, belirtilen ders tarihinde yoklamaya dahil mi?
     */
    public function yoklamayaDahilMi(\DateTimeInterface|string $dersTarihi): bool
    {
        $baslama = $this->kursa_baslama_tarihi?->toDateString();

        if ($baslama === null) {
            $this->loadMissing('kurs');
            $baslama = $this->kurs?->kurs_baslama_tarihi?->toDateString();
        }

        if ($baslama === null) {
            return true;
        }

        $ders = $dersTarihi instanceof \DateTimeInterface
            ? $dersTarihi->format('Y-m-d')
            : (string) $dersTarihi;

        return $baslama <= $ders;
    }
}
