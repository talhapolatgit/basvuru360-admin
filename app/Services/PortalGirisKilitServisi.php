<?php

namespace App\Services;

use App\Models\Kisi;
use Illuminate\Support\Facades\DB;

/**
 * Portal girişinde hesap bazlı geçici kilit:
 * PENCERE_DAKIKA içinde MAKS_HATALI_DENEME hatalı deneme → KILIT_DAKIKA boyunca giriş kapalı.
 */
class PortalGirisKilitServisi
{
    public const MAKS_HATALI_DENEME = 10;

    public const PENCERE_DAKIKA = 30;

    public const KILIT_DAKIKA = 120;

    public const GOSTERIM_SAAT_DILIMI = 'Europe/Istanbul';

    /**
     * Hatalı denemeyi kaydeder; bu deneme hesabı kilitlediyse true döner.
     */
    public function hataliDenemeKaydet(Kisi $kisi): bool
    {
        return DB::transaction(function () use ($kisi) {
            /** @var Kisi $kayit */
            $kayit = Kisi::query()->lockForUpdate()->findOrFail($kisi->id);
            $simdi = now();

            $pencereDoldu = $kayit->ilk_hatali_giris_at === null
                || $kayit->ilk_hatali_giris_at->lt($simdi->copy()->subMinutes(self::PENCERE_DAKIKA));

            if ($pencereDoldu) {
                $kayit->hatali_giris_sayisi = 1;
                $kayit->ilk_hatali_giris_at = $simdi;
            } else {
                $kayit->hatali_giris_sayisi++;
            }

            $kilitlendi = $kayit->hatali_giris_sayisi >= self::MAKS_HATALI_DENEME;

            if ($kilitlendi) {
                $kayit->giris_kilit_bitis = $simdi->copy()->addMinutes(self::KILIT_DAKIKA);
                $kayit->hatali_giris_sayisi = 0;
                $kayit->ilk_hatali_giris_at = null;
            }

            $kayit->save();
            $kisi->setRawAttributes($kayit->getAttributes(), true);

            return $kilitlendi;
        });
    }

    public function sifirla(Kisi $kisi): void
    {
        if ($kisi->hatali_giris_sayisi === 0 && $kisi->ilk_hatali_giris_at === null && $kisi->giris_kilit_bitis === null) {
            return;
        }

        $kisi->forceFill([
            'hatali_giris_sayisi' => 0,
            'ilk_hatali_giris_at' => null,
            'giris_kilit_bitis' => null,
        ])->save();
    }

    public function kilitBitisMetni(Kisi $kisi): string
    {
        return $kisi->giris_kilit_bitis
            ?->copy()
            ->timezone(self::GOSTERIM_SAAT_DILIMI)
            ->format('d.m.Y H:i') ?? '—';
    }

    public function kilitMesaji(Kisi $kisi): string
    {
        return 'Çok sayıda hatalı giriş denemesi nedeniyle hesabınız '
            .$this->kilitBitisMetni($kisi)
            .' saatine kadar geçici olarak kilitlendi.';
    }
}
