<?php

namespace App\Models;

use Database\Factories\UserFactory;
use App\Enums\Cinsiyet;
use App\Enums\IkiAsamaliGuvenlik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var list<string>|null */
    private ?array $yetkiKodlariCache = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ad',
        'soyad',
        'tc_kimlik_no',
        'dogum_tarihi',
        'cinsiyet',
        'dogum_yeri',
        'medeni_durum',
        'uyruk',
        'anne_adi',
        'baba_adi',
        'telefon',
        'il',
        'ilce',
        'adres',
        'profil_foto',
        'aktif',
        'iki_asamali_guvenlik',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'dogum_tarihi' => 'date',
            'cinsiyet' => Cinsiyet::class,
            'password' => 'hashed',
            'aktif' => 'boolean',
            'iki_asamali_guvenlik' => IkiAsamaliGuvenlik::class,
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
     * @return BelongsToMany<Rol, $this>
     */
    public function roller(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'kullanici_rol', 'user_id', 'rol_id')
            ->withTimestamps()
            ->orderBy('roller.sira');
    }

    /**
     * @return BelongsToMany<Kurum, $this>
     */
    public function kurumlar(): BelongsToMany
    {
        return $this->belongsToMany(Kurum::class, 'kullanici_kurum', 'user_id', 'kurum_id')
            ->withTimestamps()
            ->orderBy('kurumlar.sira')
            ->orderBy('kurumlar.ad');
    }

    /**
     * @param  list<int|string>  $kurumIds
     */
    public function syncKurumlar(array $kurumIds): void
    {
        $this->kurumlar()->sync(array_values(array_unique(array_map('intval', $kurumIds))));
        $this->unsetRelation('kurumlar');
    }

    /**
     * @return BelongsToMany<Merkez, $this>
     */
    public function atananMerkezler(): BelongsToMany
    {
        return $this->belongsToMany(Merkez::class, 'kullanici_merkez', 'user_id', 'merkez_id')
            ->withTimestamps()
            ->orderBy('merkezler.ad');
    }

    /**
     * @param  list<int|string>  $merkezIds
     */
    public function syncMerkezler(array $merkezIds): void
    {
        $this->atananMerkezler()->sync(array_values(array_unique(array_map('intval', $merkezIds))));
        $this->unsetRelation('atananMerkezler');
    }

    public function isAdmin(): bool
    {
        if ($this->relationLoaded('roller')) {
            return $this->roller->contains(fn (Rol $rol) => $rol->isAdmin() && $rol->aktif);
        }

        return $this->roller()
            ->where('aktif', true)
            ->where(function ($q) {
                $q->where('tum_yetkiler', true)->orWhere('kod', 'admin');
            })
            ->exists();
    }

    public function isPersonel(): bool
    {
        return $this->hasRolKodu('personel');
    }

    public function isOgretmen(): bool
    {
        return $this->hasRolKodu('ogretmen');
    }

    /**
     * Kurs listesi/detayında yalnızca kendisine eğitmen olarak
     * atanmış kursları görebilir (Admin hariç).
     */
    public function sadeceAtananKurslariGorur(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        return $this->hasYetki('kurs.sadece_atanan');
    }

    /**
     * Yalnızca yetkilendirildiği merkezleri (ve o merkezlerin kurslarını) görür.
     * "Tüm merkezleri görüntüle" yetkisi varsa kısıt uygulanmaz.
     */
    public function sadeceYetkiliMerkezleriGorur(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->hasYetki('kurs.tum_merkezler')) {
            return false;
        }

        return $this->hasYetki('kurs.sadece_yetkili_merkez');
    }

    /**
     * @return list<int>
     */
    public function yetkiliMerkezIdleri(): array
    {
        if ($this->relationLoaded('atananMerkezler')) {
            return $this->atananMerkezler->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->atananMerkezler()->pluck('merkezler.id')->map(fn ($id) => (int) $id)->all();
    }

    public function atanmisMerkezMu(Merkez|int $merkez): bool
    {
        $merkezId = $merkez instanceof Merkez ? (int) $merkez->id : (int) $merkez;

        if ($this->relationLoaded('atananMerkezler')) {
            return $this->atananMerkezler->contains(fn (Merkez $m) => (int) $m->id === $merkezId);
        }

        return $this->atananMerkezler()->where('merkezler.id', $merkezId)->exists();
    }

    /**
     * Yalnızca kendi kurumlarına bağlı kursları görür.
     * Kurumu tanımlanmamış kurslar bu kısıta takılmaz.
     * "Tüm kurumları görüntüle" yetkisi varsa kısıt uygulanmaz.
     */
    public function sadeceKendiKurumlariniGorur(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->hasYetki('kurs.tum_kurumlar')) {
            return false;
        }

        return $this->hasYetki('kurs.sadece_kendi_kurum');
    }

    /**
     * @return list<int>
     */
    public function kendiKurumIdleri(): array
    {
        if ($this->relationLoaded('kurumlar')) {
            return $this->kurumlar->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return $this->kurumlar()->pluck('kurumlar.id')->map(fn ($id) => (int) $id)->all();
    }

    public function kursKendiKurumKapsamindaMi(Kurs $kurs): bool
    {
        if (! $this->sadeceKendiKurumlariniGorur()) {
            return true;
        }

        $kurs->loadMissing('kurumlar');

        if ($kurs->kurumlar->isEmpty()) {
            return true;
        }

        $kurumIds = $this->kendiKurumIdleri();
        if ($kurumIds === []) {
            return false;
        }

        return $kurs->kurumlar->contains(fn (Kurum $kurum) => in_array((int) $kurum->id, $kurumIds, true));
    }

    public function sadeceAtananEtkinlikleriGorur(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        return $this->hasYetki('etkinlik.sadece_atanan');
    }

    public function sadeceYetkiliMerkezleriGorurEtkinlik(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->hasYetki('etkinlik.tum_merkezler')) {
            return false;
        }

        return $this->hasYetki('etkinlik.sadece_yetkili_merkez');
    }

    public function sadeceKendiKurumlariniGorurEtkinlik(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->hasYetki('etkinlik.tum_kurumlar')) {
            return false;
        }

        return $this->hasYetki('etkinlik.sadece_kendi_kurum');
    }

    public function etkinlikKendiKurumKapsamindaMi(Etkinlik $etkinlik): bool
    {
        if (! $this->sadeceKendiKurumlariniGorurEtkinlik()) {
            return true;
        }

        $etkinlik->loadMissing('kurumlar');

        if ($etkinlik->kurumlar->isEmpty()) {
            return true;
        }

        $kurumIds = $this->kendiKurumIdleri();
        if ($kurumIds === []) {
            return false;
        }

        return $etkinlik->kurumlar->contains(fn (Kurum $kurum) => in_array((int) $kurum->id, $kurumIds, true));
    }

    public function atanmisEtkinlikMu(Etkinlik|int $etkinlik): bool
    {
        $etkinlikId = $etkinlik instanceof Etkinlik ? (int) $etkinlik->id : (int) $etkinlik;

        return $this->atananEtkinlikler()->where('etkinlikler.id', $etkinlikId)->exists();
    }

    /**
     * Kullanıcı bu kursa eğitmen olarak atanmış mı?
     */
    public function atanmisKursMu(Kurs|int $kurs): bool
    {
        $kursId = $kurs instanceof Kurs ? (int) $kurs->id : (int) $kurs;

        if ($kurs instanceof Kurs && (int) ($kurs->ogretmen_id ?? 0) === (int) $this->id) {
            return true;
        }

        return $this->atananKurslar()->where('kurslar.id', $kursId)->exists()
            || Kurs::query()
                ->whereKey($kursId)
                ->where('ogretmen_id', $this->id)
                ->exists();
    }

    public function hasRolKodu(string $kod): bool
    {
        if ($this->relationLoaded('roller')) {
            return $this->roller->contains(fn (Rol $rol) => $rol->kod === $kod && $rol->aktif);
        }

        return $this->roller()->where('aktif', true)->where('kod', $kod)->exists();
    }

    /**
     * @return list<string>
     */
    public function getYetkiKodlari(): array
    {
        if ($this->yetkiKodlariCache !== null) {
            return $this->yetkiKodlariCache;
        }

        if ($this->isAdmin()) {
            return $this->yetkiKodlariCache = ['*'];
        }

        if ($this->relationLoaded('roller')) {
            $roller = $this->roller->where('aktif', true);
            if ($roller->isNotEmpty() && ! $roller->first()->relationLoaded('yetkiler')) {
                $this->load('roller.yetkiler');
                $roller = $this->roller->where('aktif', true);
            }
        } else {
            $roller = $this->roller()->where('aktif', true)->with('yetkiler')->get();
        }

        $kodlar = $roller
            ->flatMap(fn (Rol $rol) => $rol->yetkiler->pluck('kod'))
            ->unique()
            ->values()
            ->all();

        return $this->yetkiKodlariCache = $kodlar;
    }

    public function forgetYetkiCache(): void
    {
        $this->yetkiKodlariCache = null;
    }

    public function hasYetki(string|array $kod): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $kodlar = is_array($kod) ? $kod : [$kod];
        $sahip = $this->getYetkiKodlari();

        foreach ($kodlar as $k) {
            if (! in_array($k, $sahip, true)) {
                return false;
            }
        }

        return $kodlar !== [];
    }

    public function hasAnyYetki(string|array $kodlar): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $kodlar = is_array($kodlar) ? $kodlar : [$kodlar];
        $sahip = $this->getYetkiKodlari();

        foreach ($kodlar as $k) {
            if (in_array($k, $sahip, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllYetkiler(array $kodlar): bool
    {
        return $this->hasYetki($kodlar);
    }

    /**
     * @param  list<int|string>  $rolIds
     */
    public function syncRoller(array $rolIds): void
    {
        $this->roller()->sync(array_values(array_unique(array_map('intval', $rolIds))));
        $this->forgetYetkiCache();
        $this->unsetRelation('roller');
    }

    /**
     * Atanan rollerin adlarını birleştirir (UI gösterimi).
     */
    public function getRollerOzetiAttribute(): string
    {
        $roller = $this->relationLoaded('roller')
            ? $this->roller
            : $this->roller()->get();

        return $roller->pluck('ad')->filter()->implode(', ') ?: '—';
    }

    /**
     * Rolü öğretmen olan veya en az bir kursa öğretmen olarak atanmış kullanıcılar.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeEgitmen(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereHas('roller', fn ($r) => $r->where('kod', 'ogretmen')->where('aktif', true))
                ->orWhereHas('atananKurslar');
        });
    }

    /**
     * @return HasMany<Kurs, $this>
     */
    public function verdigiKurslar(): HasMany
    {
        return $this->hasMany(Kurs::class, 'ogretmen_id');
    }

    /**
     * @return BelongsToMany<Kurs, $this>
     */
    public function atananKurslar(): BelongsToMany
    {
        return $this->belongsToMany(Kurs::class, 'kurs_ogretmenleri', 'user_id', 'kurs_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Etkinlik, $this>
     */
    public function atananEtkinlikler(): BelongsToMany
    {
        return $this->belongsToMany(Etkinlik::class, 'etkinlik_sorumlulari', 'user_id', 'etkinlik_id')
            ->withTimestamps();
    }
}
