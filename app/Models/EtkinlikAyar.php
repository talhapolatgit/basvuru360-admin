<?php

namespace App\Models;

use App\Enums\SmsGonderimSecenegi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EtkinlikAyar extends Model
{
    protected $table = 'etkinlik_ayarlari';

    public const DEFAULT_SMS_METIN_ONAY = 'Merhaba {ad_soyad}, etkinlik başvurunuz onaylanmıştır.';

    public const DEFAULT_SMS_METIN_IPTAL = 'Merhaba {ad_soyad}, etkinlik başvurunuz iptal edilmiştir.';

    public const DEFAULT_SMS_METIN_YEDEK = 'Merhaba {ad_soyad}, etkinlik başvurunuz yedek listeye alınmıştır.';

    public const DEFAULT_EPOSTA_KONU_ONAY = 'Etkinlik Başvuru Onayı';

    public const DEFAULT_EPOSTA_KONU_IPTAL = 'Etkinlik Başvuru İptali';

    public const DEFAULT_EPOSTA_KONU_YEDEK = 'Etkinlik Başvuru Yedek Bildirimi';

    public const DEFAULT_EPOSTA_METIN_ONAY = 'Merhaba {ad_soyad}, etkinlik başvurunuz onaylanmıştır.';

    public const DEFAULT_EPOSTA_METIN_IPTAL = 'Merhaba {ad_soyad}, etkinlik başvurunuz iptal edilmiştir.';

    public const DEFAULT_EPOSTA_METIN_YEDEK = 'Merhaba {ad_soyad}, etkinlik başvurunuz yedek listeye alınmıştır.';

    /** @var list<string> */
    public const ANAHTARLAR = [
        'sms_basvuru_onay',
        'sms_basvuru_iptal',
        'sms_basvuru_yedek',
        'sms_metin_onay',
        'sms_metin_iptal',
        'sms_metin_yedek',
        'eposta_basvuru_onay',
        'eposta_basvuru_iptal',
        'eposta_basvuru_yedek',
        'eposta_konu_onay',
        'eposta_konu_iptal',
        'eposta_konu_yedek',
        'eposta_metin_onay',
        'eposta_metin_iptal',
        'eposta_metin_yedek',
        'kvkk_baslik',
        'kvkk_metni',
        'aydinlatma_baslik',
        'aydinlatma_metni',
        'onaylanmis_basvuru_kisi_iptal',
    ];

    protected $fillable = [
        'anahtar',
        'deger',
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

    public static function secenek(string $anahtar): SmsGonderimSecenegi
    {
        return SmsGonderimSecenegi::tryFrom((string) (static::deger($anahtar) ?? ''))
            ?? SmsGonderimSecenegi::IstegeBagli;
    }
}
