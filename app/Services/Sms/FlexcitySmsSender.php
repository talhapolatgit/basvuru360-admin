<?php

namespace App\Services\Sms;

use App\Services\Flexcity\FlexcityIstemcisi;
use RuntimeException;
use Throwable;

/**
 * Flexcity SMS servisi — hizliGonder ile tekil SMS gönderir.
 *
 * İstek gövdesi (yalnızca gsmList ve content değişir):
 * muhatapIdList=[]&hizliGonder=true&gsmList=[5307075030]&content=...
 */
class FlexcitySmsSender implements SmsSender
{
    private readonly FlexcityIstemcisi $istemci;

    /**
     * @param  array<string, mixed>  $ayarlar
     */
    public function __construct(array $ayarlar)
    {
        $this->istemci = new FlexcityIstemcisi($ayarlar, 'SMS');
    }

    public function send(string $telefon, string $mesaj, array $context = []): array
    {
        $gsm = $this->gsmNumarasi($telefon);
        if ($gsm === null) {
            return ['ok' => false, 'message' => 'Geçersiz telefon numarası.'];
        }

        $icerik = trim($mesaj);
        if ($icerik === '') {
            return ['ok' => false, 'message' => 'SMS metni boş olamaz.'];
        }

        try {
            $json = $this->istemci->postHam(
                'muhatapIdList=[]&hizliGonder=true&gsmList=['.$gsm.']&content='.$icerik
            );
        } catch (RuntimeException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage() ?: 'SMS gönderilemedi.'];
        }

        if (($json['success'] ?? false) === true) {
            return ['ok' => true, 'message' => FlexcityIstemcisi::servisMesaji($json) ?? 'flexcity'];
        }

        return [
            'ok' => false,
            'message' => FlexcityIstemcisi::servisMesaji($json) ?? 'SMS servisi gönderimi reddetti.',
        ];
    }

    /**
     * Flexcity gsmList formatı: 5xxxxxxxxx (ülke kodu / baştaki 0 olmadan).
     */
    private function gsmNumarasi(string $telefon): ?string
    {
        $digits = preg_replace('/\D+/', '', $telefon) ?? '';

        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return $digits;
        }

        return null;
    }
}
