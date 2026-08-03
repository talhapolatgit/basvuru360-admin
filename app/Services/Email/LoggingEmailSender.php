<?php

namespace App\Services\Email;

use App\Models\EpostaLog;
use Throwable;

class LoggingEmailSender implements EmailSender
{
    public function __construct(
        private readonly EmailSender $inner,
    ) {}

    public function send(string $email, string $konu, string $mesaj, array $context = []): array
    {
        $sonuc = $this->inner->send($email, $konu, $mesaj, $context);
        $ok = (bool) ($sonuc['ok'] ?? false);

        try {
            EpostaLog::query()->create([
                'email' => $email,
                'konu' => $konu,
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
            // E-posta gönderimi log yazım hatası yüzünden bozulmasın.
        }

        return $sonuc;
    }
}
