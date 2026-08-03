<?php

namespace App\Services;

use App\Enums\SmsGonderimSecenegi;
use App\Models\EtkinlikAyar;
use App\Support\HtmlMetin;

class EtkinlikAyarServisi
{
    public const DEFAULT_KVKK_BASLIK = 'KVKK Metni';

    public const DEFAULT_AYDINLATMA_BASLIK = 'Aydınlatma Metni';

    /**
     * @return array<string, mixed>
     */
    public function formVerisi(): array
    {
        $ayarlar = EtkinlikAyar::harita();

        return [
            'sms_basvuru_onay' => $this->secenekDeger($ayarlar, 'sms_basvuru_onay'),
            'sms_basvuru_iptal' => $this->secenekDeger($ayarlar, 'sms_basvuru_iptal'),
            'sms_basvuru_yedek' => $this->secenekDeger($ayarlar, 'sms_basvuru_yedek'),
            'sms_metin_onay' => $this->metinVeyaVarsayilan($ayarlar['sms_metin_onay'] ?? null, EtkinlikAyar::DEFAULT_SMS_METIN_ONAY),
            'sms_metin_iptal' => $this->metinVeyaVarsayilan($ayarlar['sms_metin_iptal'] ?? null, EtkinlikAyar::DEFAULT_SMS_METIN_IPTAL),
            'sms_metin_yedek' => $this->metinVeyaVarsayilan($ayarlar['sms_metin_yedek'] ?? null, EtkinlikAyar::DEFAULT_SMS_METIN_YEDEK),
            'eposta_basvuru_onay' => $this->secenekDeger($ayarlar, 'eposta_basvuru_onay'),
            'eposta_basvuru_iptal' => $this->secenekDeger($ayarlar, 'eposta_basvuru_iptal'),
            'eposta_basvuru_yedek' => $this->secenekDeger($ayarlar, 'eposta_basvuru_yedek'),
            'eposta_konu_onay' => $this->metinVeyaVarsayilan($ayarlar['eposta_konu_onay'] ?? null, EtkinlikAyar::DEFAULT_EPOSTA_KONU_ONAY),
            'eposta_konu_iptal' => $this->metinVeyaVarsayilan($ayarlar['eposta_konu_iptal'] ?? null, EtkinlikAyar::DEFAULT_EPOSTA_KONU_IPTAL),
            'eposta_konu_yedek' => $this->metinVeyaVarsayilan($ayarlar['eposta_konu_yedek'] ?? null, EtkinlikAyar::DEFAULT_EPOSTA_KONU_YEDEK),
            'eposta_metin_onay' => $this->metinVeyaVarsayilan($ayarlar['eposta_metin_onay'] ?? null, EtkinlikAyar::DEFAULT_EPOSTA_METIN_ONAY),
            'eposta_metin_iptal' => $this->metinVeyaVarsayilan($ayarlar['eposta_metin_iptal'] ?? null, EtkinlikAyar::DEFAULT_EPOSTA_METIN_IPTAL),
            'eposta_metin_yedek' => $this->metinVeyaVarsayilan($ayarlar['eposta_metin_yedek'] ?? null, EtkinlikAyar::DEFAULT_EPOSTA_METIN_YEDEK),
            'kvkk_baslik' => (string) ($ayarlar['kvkk_baslik'] ?? ''),
            'kvkk_metni' => (string) ($ayarlar['kvkk_metni'] ?? ''),
            'aydinlatma_baslik' => (string) ($ayarlar['aydinlatma_baslik'] ?? ''),
            'aydinlatma_metni' => (string) ($ayarlar['aydinlatma_metni'] ?? ''),
            'onaylanmis_basvuru_kisi_iptal' => $this->evetHayirDeger($ayarlar, 'onaylanmis_basvuru_kisi_iptal'),
            'secenekler' => array_map(
                fn (SmsGonderimSecenegi $s) => [
                    'value' => $s->value,
                    'label' => $s->label(),
                ],
                SmsGonderimSecenegi::cases()
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string|null>
     */
    public function kaydet(array $payload): array
    {
        EtkinlikAyar::kaydetCoklu([
            'sms_basvuru_onay' => (string) $payload['sms_basvuru_onay'],
            'sms_basvuru_iptal' => (string) $payload['sms_basvuru_iptal'],
            'sms_basvuru_yedek' => (string) $payload['sms_basvuru_yedek'],
            'sms_metin_onay' => trim((string) ($payload['sms_metin_onay'] ?? '')),
            'sms_metin_iptal' => trim((string) ($payload['sms_metin_iptal'] ?? '')),
            'sms_metin_yedek' => trim((string) ($payload['sms_metin_yedek'] ?? '')),
            'eposta_basvuru_onay' => (string) $payload['eposta_basvuru_onay'],
            'eposta_basvuru_iptal' => (string) $payload['eposta_basvuru_iptal'],
            'eposta_basvuru_yedek' => (string) $payload['eposta_basvuru_yedek'],
            'eposta_konu_onay' => trim((string) ($payload['eposta_konu_onay'] ?? '')),
            'eposta_konu_iptal' => trim((string) ($payload['eposta_konu_iptal'] ?? '')),
            'eposta_konu_yedek' => trim((string) ($payload['eposta_konu_yedek'] ?? '')),
            'eposta_metin_onay' => trim((string) ($payload['eposta_metin_onay'] ?? '')),
            'eposta_metin_iptal' => trim((string) ($payload['eposta_metin_iptal'] ?? '')),
            'eposta_metin_yedek' => trim((string) ($payload['eposta_metin_yedek'] ?? '')),
            'kvkk_baslik' => $this->normalizeBaslik($payload['kvkk_baslik'] ?? null),
            'kvkk_metni' => HtmlMetin::temizle($payload['kvkk_metni'] ?? null),
            'aydinlatma_baslik' => $this->normalizeBaslik($payload['aydinlatma_baslik'] ?? null),
            'aydinlatma_metni' => HtmlMetin::temizle($payload['aydinlatma_metni'] ?? null),
            'onaylanmis_basvuru_kisi_iptal' => $this->normalizeEvetHayir($payload['onaylanmis_basvuru_kisi_iptal'] ?? null),
        ]);

        return EtkinlikAyar::harita();
    }

    /** Onaylanmış (kesin kayıt) başvuruları kişiler portal üzerinden iptal edebilir mi? */
    public function kisiOnaylanmisBasvuruIptalEdebilir(): bool
    {
        return $this->evetHayirDeger(EtkinlikAyar::harita(['onaylanmis_basvuru_kisi_iptal']), 'onaylanmis_basvuru_kisi_iptal') === 'evet';
    }

    /**
     * @return list<array{kod: string, baslik: string, metin: string}>
     */
    public function basvuruOnaylari(): array
    {
        $sonuc = [];

        $kvkk = EtkinlikAyar::deger('kvkk_metni');
        if (! HtmlMetin::bosMu($kvkk)) {
            $sonuc[] = [
                'kod' => 'kvkk',
                'baslik' => HtmlMetin::baslik(EtkinlikAyar::deger('kvkk_baslik'), self::DEFAULT_KVKK_BASLIK),
                'metin' => (string) $kvkk,
            ];
        }

        $aydinlatma = EtkinlikAyar::deger('aydinlatma_metni');
        if (! HtmlMetin::bosMu($aydinlatma)) {
            $sonuc[] = [
                'kod' => 'aydinlatma',
                'baslik' => HtmlMetin::baslik(EtkinlikAyar::deger('aydinlatma_baslik'), self::DEFAULT_AYDINLATMA_BASLIK),
                'metin' => (string) $aydinlatma,
            ];
        }

        return $sonuc;
    }

    public function smsBasvuruOnay(): SmsGonderimSecenegi
    {
        return EtkinlikAyar::secenek('sms_basvuru_onay');
    }

    public function smsBasvuruIptal(): SmsGonderimSecenegi
    {
        return EtkinlikAyar::secenek('sms_basvuru_iptal');
    }

    public function smsBasvuruYedek(): SmsGonderimSecenegi
    {
        return EtkinlikAyar::secenek('sms_basvuru_yedek');
    }

    public function smsMetinOnay(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('sms_metin_onay'), EtkinlikAyar::DEFAULT_SMS_METIN_ONAY);
    }

    public function smsMetinIptal(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('sms_metin_iptal'), EtkinlikAyar::DEFAULT_SMS_METIN_IPTAL);
    }

    public function smsMetinYedek(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('sms_metin_yedek'), EtkinlikAyar::DEFAULT_SMS_METIN_YEDEK);
    }

    public function epostaBasvuruOnay(): SmsGonderimSecenegi
    {
        return EtkinlikAyar::secenek('eposta_basvuru_onay');
    }

    public function epostaBasvuruIptal(): SmsGonderimSecenegi
    {
        return EtkinlikAyar::secenek('eposta_basvuru_iptal');
    }

    public function epostaBasvuruYedek(): SmsGonderimSecenegi
    {
        return EtkinlikAyar::secenek('eposta_basvuru_yedek');
    }

    public function epostaKonuOnay(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('eposta_konu_onay'), EtkinlikAyar::DEFAULT_EPOSTA_KONU_ONAY);
    }

    public function epostaKonuIptal(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('eposta_konu_iptal'), EtkinlikAyar::DEFAULT_EPOSTA_KONU_IPTAL);
    }

    public function epostaKonuYedek(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('eposta_konu_yedek'), EtkinlikAyar::DEFAULT_EPOSTA_KONU_YEDEK);
    }

    public function epostaMetinOnay(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('eposta_metin_onay'), EtkinlikAyar::DEFAULT_EPOSTA_METIN_ONAY);
    }

    public function epostaMetinIptal(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('eposta_metin_iptal'), EtkinlikAyar::DEFAULT_EPOSTA_METIN_IPTAL);
    }

    public function epostaMetinYedek(): string
    {
        return $this->metinVeyaVarsayilan(EtkinlikAyar::deger('eposta_metin_yedek'), EtkinlikAyar::DEFAULT_EPOSTA_METIN_YEDEK);
    }

    /**
     * @param  array<string, string|null>  $ayarlar
     */
    private function secenekDeger(array $ayarlar, string $anahtar): string
    {
        return SmsGonderimSecenegi::tryFrom((string) ($ayarlar[$anahtar] ?? ''))?->value
            ?? SmsGonderimSecenegi::IstegeBagli->value;
    }

    /**
     * @param  array<string, string|null>  $ayarlar
     */
    private function evetHayirDeger(array $ayarlar, string $anahtar): string
    {
        $deger = (string) ($ayarlar[$anahtar] ?? '');

        return in_array($deger, ['evet', 'hayir'], true) ? $deger : 'evet';
    }

    private function normalizeEvetHayir(mixed $value): string
    {
        $deger = (string) $value;

        return in_array($deger, ['evet', 'hayir'], true) ? $deger : 'evet';
    }

    private function metinVeyaVarsayilan(?string $metin, string $varsayilan): string
    {
        $metin = trim((string) $metin);

        return $metin !== '' ? $metin : $varsayilan;
    }

    private function normalizeBaslik(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
