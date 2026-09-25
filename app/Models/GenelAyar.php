<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GenelAyar extends Model
{
    protected $table = 'genel_ayarlar';

    protected $fillable = [
        'anahtar',
        'deger',
    ];

    /** @var list<string> */
    public const ANAHTARLAR = [
        'kurum_adi',
        'telefon',
        'eposta',
        'il',
        'ilce',
        'adres',
        'logo',
        'web_sitesi',
        'kisi_giris_yontemi',
        'yakin_icin_basvuru_aktif',
        'manuel_yakin_ekleme_aktif',
        'sidebar_logo',
        'sidebar_baslik',
        'sidebar_alt_baslik',
        'sidebar_logo_arkaplan',
        'sidebar_arkaplan',
        'sidebar_arkaplan_tip',
        'header_logo',
        'favicon',
        'site_aciklama',
    ];

    public static function deger(string $anahtar, ?string $varsayilan = null): ?string
    {
        $kayit = static::query()->where('anahtar', $anahtar)->first();

        if (! $kayit || $kayit->deger === null || $kayit->deger === '') {
            return $varsayilan;
        }

        return (string) $kayit->deger;
    }

    /**
     * @param  list<string>|null  $anahtarlar
     * @return array<string, string|null>
     */
    public static function harita(?array $anahtarlar = null): array
    {
        $anahtarlar ??= self::ANAHTARLAR;

        /** @var Collection<string, string|null> $kayitlar */
        $kayitlar = static::query()
            ->whereIn('anahtar', $anahtarlar)
            ->pluck('deger', 'anahtar');

        $sonuc = [];
        foreach ($anahtarlar as $anahtar) {
            $deger = $kayitlar->get($anahtar);
            $sonuc[$anahtar] = ($deger === null || $deger === '') ? null : (string) $deger;
        }

        return $sonuc;
    }

    /**
     * @param  array<string, string|null>  $degerler
     */
    public static function kaydetCoklu(array $degerler): void
    {
        foreach ($degerler as $anahtar => $deger) {
            static::query()->updateOrCreate(
                ['anahtar' => $anahtar],
                ['deger' => $deger]
            );
        }
    }
}
