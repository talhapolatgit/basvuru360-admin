<?php

namespace App\Models;

use App\Enums\Cinsiyet;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Kisi extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'kisiler';

    protected $fillable = [
        'ad',
        'soyad',
        'tc_kimlik_no',
        'dogum_tarihi',
        'telefon',
        'email',
        'password',
        'cinsiyet',
        'dogum_yeri',
        'medeni_durum',
        'uyruk',
        'anne_adi',
        'baba_adi',
        'il',
        'ilce',
        'adres',
        'diger_adres',
        'profil_foto',
        'aktif',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'dogum_tarihi' => 'date',
            'cinsiyet' => Cinsiyet::class,
            'aktif' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getTamAdiAttribute(): string
    {
        return trim("{$this->ad} {$this->soyad}");
    }

    public function getProfilFotoUrlAttribute(): ?string
    {
        if (! $this->profil_foto) {
            return null;
        }

        return asset('uploads/avatars/'.$this->profil_foto);
    }

    public function getBasHarflerAttribute(): string
    {
        return collect([$this->ad, $this->soyad])
            ->filter()
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');
    }

    /**
     * Portal başvurusunda gönderilen iletişim/adres bilgilerini profile yazar.
     *
     * @param  array<string, mixed>  $validated
     */
    public function basvuruIleProfilGuncelle(array $validated): void
    {
        $guncelle = [];

        foreach (['telefon', 'email', 'il', 'ilce', 'adres', 'cinsiyet'] as $alan) {
            if (! array_key_exists($alan, $validated)) {
                continue;
            }

            $deger = $validated[$alan];
            if ($deger instanceof \BackedEnum) {
                $deger = $deger->value;
            }

            if ($deger === null || $deger === '') {
                continue;
            }

            $guncelle[$alan] = $deger;
        }

        if ($guncelle === []) {
            return;
        }

        $this->fill($guncelle)->save();
    }

    /**
     * Kişi adına yapılan kurs başvuruları (katılımcı olarak).
     *
     * @return HasMany<KursBasvuru, $this>
     */
    public function kursBasvurulari(): HasMany
    {
        return $this->hasMany(KursBasvuru::class, 'kisi_id');
    }

    /**
     * Kişi adına yapılan etkinlik başvuruları (katılımcı olarak).
     *
     * @return HasMany<EtkinlikBasvuru, $this>
     */
    public function etkinlikBasvurulari(): HasMany
    {
        return $this->hasMany(EtkinlikBasvuru::class, 'kisi_id');
    }

    /**
     * Bu kişinin başvuran olduğu kayıtlar.
     *
     * @return HasMany<KursBasvuru, $this>
     */
    public function yaptigiBasvurular(): HasMany
    {
        return $this->hasMany(KursBasvuru::class, 'basvuran_id');
    }

    /**
     * Bu kişinin veli olarak yaptığı başvurular.
     *
     * @return HasMany<KursBasvuru, $this>
     */
    public function veliBasvurulari(): HasMany
    {
        return $this->hasMany(KursBasvuru::class, 'veli_id');
    }

    /**
     * @return HasMany<KursYoklama, $this>
     */
    public function yoklamalar(): HasMany
    {
        return $this->hasMany(KursYoklama::class, 'kisi_id');
    }
}
