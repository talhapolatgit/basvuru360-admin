<?php

namespace App\Services;

use App\Models\Kisi;
use App\Services\Email\EmailSender;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Portalda üyelik oluşturan kişiye aktif e-posta entegrasyonu ile hoş geldiniz e-postası gönderir.
 */
class HosgeldinEpostaServisi
{
    private const LOGO_CID = 'kurum-logo';

    private const RENK = '#15408f';

    private const RENK_ACIK = '#2f6fd6';

    private const SAAT_DILIMI = 'Europe/Istanbul';

    public function __construct(
        private readonly GenelAyarServisi $ayarlar,
    ) {}

    /**
     * Genel ayarlarda açıksa ve kişinin e-posta adresi varsa gönderir; hata kaydı engellemez.
     */
    public function kayitSonrasiGonder(Kisi $kisi): void
    {
        if (! $this->ayarlar->hosgeldinEpostaAktif() || blank($kisi->email)) {
            return;
        }

        try {
            $sonuc = $this->gonder($kisi);
            if (! ($sonuc['ok'] ?? false)) {
                Log::warning('Hoş geldiniz e-postası gönderilemedi', ['kisi_id' => $kisi->id, 'hata' => $sonuc['message'] ?? null]);
            }
        } catch (Throwable $e) {
            Log::warning('Hoş geldiniz e-postası gönderilemedi', ['kisi_id' => $kisi->id, 'hata' => $e->getMessage()]);
        }
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function gonder(Kisi $kisi, ?string $email = null): array
    {
        $veri = $this->sablonVerisi($kisi);
        $logo = $this->logoDosyasi();

        return app(EmailSender::class)->send(
            $email ?? (string) $kisi->email,
            $veri['konu'],
            view('emails.hosgeldin-metin', $veri)->render(),
            [
                'html' => view('emails.hosgeldin', [...$veri, 'logoCid' => $logo ? self::LOGO_CID : null])->render(),
                'gomulu_gorseller' => $logo ? [self::LOGO_CID => $logo] : [],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function sablonVerisi(Kisi $kisi): array
    {
        $form = $this->ayarlar->formVerisi();
        $kurum = $form['kurum_adi'] !== '' ? $form['kurum_adi'] : (string) config('app.name');
        $portalUrl = rtrim((string) config('app.portal_url'), '/');

        return [
            'konu' => "{$kurum} Başvuru Portalı'na Hoş Geldiniz",
            'kurum' => $kurum,
            'ad' => $this->ozelAd((string) $kisi->ad),
            'tamAd' => $this->ozelAd(trim($kisi->ad.' '.$kisi->soyad)),
            'tc' => $this->tcMaskele((string) $kisi->tc_kimlik_no),
            'email' => (string) $kisi->email,
            'kayitTarihi' => ($kisi->portal_hesap_at ?? $kisi->created_at ?? now())->copy()->setTimezone(self::SAAT_DILIMI)->format('d.m.Y H:i'),
            'portalUrl' => $portalUrl !== '' ? $portalUrl.'/giris' : null,
            'renk' => self::RENK,
            'renkAcik' => self::RENK_ACIK,
            'telefon' => $this->telefonBicimle($form['telefon']),
            'kurumEposta' => $form['eposta'],
            'webSitesi' => $form['web_sitesi'],
            'adres' => trim(implode(' ', array_filter([
                $form['adres'],
                trim(implode('/', array_filter([$form['ilce'], $form['il']]))),
            ]))),
            'yil' => now()->year,
        ];
    }

    private function logoDosyasi(): ?string
    {
        foreach ([$this->ayarlar->logoDosyaYolu(), $this->ayarlar->headerLogoDosyaYolu()] as $yol) {
            // SVG çoğu e-posta istemcisinde gösterilmez.
            if ($yol && in_array(strtolower(pathinfo($yol, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif'], true)) {
                return $yol;
            }
        }

        return null;
    }

    private function ozelAd(string $deger): string
    {
        $kucuk = mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], trim($deger)), 'UTF-8');

        return preg_replace_callback(
            '/(^|\s)(\S)/u',
            fn (array $m) => $m[1].mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], $m[2]), 'UTF-8'),
            $kucuk,
        ) ?? $deger;
    }

    private function tcMaskele(string $tc): string
    {
        return strlen($tc) === 11 ? substr($tc, 0, 3).'******'.substr($tc, -2) : $tc;
    }

    private function telefonBicimle(string $telefon): string
    {
        $rakam = preg_replace('/\D/', '', $telefon) ?? '';

        return strlen($rakam) === 11
            ? sprintf('%s %s %s %s', substr($rakam, 0, 4), substr($rakam, 4, 3), substr($rakam, 7, 2), substr($rakam, 9, 2))
            : $telefon;
    }
}
