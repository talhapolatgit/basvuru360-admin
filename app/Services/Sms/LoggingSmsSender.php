<?php

namespace App\Services\Sms;

use App\Models\SmsLog;
use Throwable;

class LoggingSmsSender implements SmsSender
{
    public function __construct(
        private readonly SmsSender $inner,
    ) {}

    public function send(string $telefon, string $mesaj, array $context = []): array
    {
        $sonuc = $this->inner->send($telefon, $mesaj, $context);
        $ok = (bool) ($sonuc['ok'] ?? false);

        try {
            SmsLog::query()->create([
                'telefon' => $telefon,
                'mesaj' => $mesaj,
                'durum' => $ok ? 'gonderildi' : 'basarisiz',
                'hata_mesaji' => $ok ? null : ($sonuc['message'] ?? 'Gönderilemedi'),
                'kurs_id' => isset($context['kurs_id']) ? (int) $context['kurs_id'] : null,
                'basvuru_id' => isset($context['basvuru_id']) ? (int) $context['basvuru_id'] : null,
                'etkinlik_id' => isset($context['etkinlik_id']) ? (int) $context['etkinlik_id'] : null,
                'etkinlik_basvuru_id' => isset($context['etkinlik_basvuru_id']) ? (int) $context['etkinlik_basvuru_id'] : null,
                'gonderen_id' => isset($context['gonderen_id']) ? (int) $context['gonderen_id'] : null,
            ]);
        } catch (Throwable) {
            // SMS gönderimi log yazım hatası yüzünden bozulmasın.
        }

        return $sonuc;
    }
}
