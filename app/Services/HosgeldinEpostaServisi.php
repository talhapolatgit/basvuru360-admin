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
    public function __construct(
        private readonly GenelAyarServisi $ayarlar,
        private readonly EpostaTasarimServisi $tasarim,
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

        return app(EmailSender::class)->send(
            $email ?? (string) $kisi->email,
            $veri['konu'],
            view('emails.hosgeldin-metin', $veri)->render(),
            $this->tasarim->htmlBaglami('emails.hosgeldin', $veri),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function sablonVerisi(Kisi $kisi): array
    {
        $ortak = $this->tasarim->ortakVeri();

        return [
            ...$ortak,
            'konu' => "{$ortak['kurum']} Başvuru Portalı'na Hoş Geldiniz",
            'ad' => $this->tasarim->ozelAd((string) $kisi->ad),
            'tamAd' => $this->tasarim->ozelAd(trim($kisi->ad.' '.$kisi->soyad)),
            'tc' => $this->tcMaskele((string) $kisi->tc_kimlik_no),
            'email' => (string) $kisi->email,
            'kayitTarihi' => ($kisi->portal_hesap_at ?? $kisi->created_at ?? now())->copy()->setTimezone(EpostaTasarimServisi::SAAT_DILIMI)->format('d.m.Y H:i'),
            'portalUrl' => $this->tasarim->portalUrl('giris'),
        ];
    }

    private function tcMaskele(string $tc): string
    {
        return strlen($tc) === 11 ? substr($tc, 0, 3).'******'.substr($tc, -2) : $tc;
    }
}
