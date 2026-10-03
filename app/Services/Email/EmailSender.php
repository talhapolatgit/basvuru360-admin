<?php

namespace App\Services\Email;

interface EmailSender
{
    /**
     * $mesaj düz metin gövdedir; context['html'] verilirse HTML gövde olarak gönderilir
     * ($mesaj düz metin alternatifi olarak eklenir). context['gomulu_gorseller'] (cid => dosya yolu)
     * HTML içinde <img src="cid:..."> ile kullanılacak görselleri ekler.
     *
     * @param  array{kurs_id?: int|null, basvuru_id?: int|null, etkinlik_id?: int|null, etkinlik_basvuru_id?: int|null, gonderen_id?: int|null, html?: string|null, gomulu_gorseller?: array<string, string>}  $context
     * @return array{ok: bool, message?: string}
     */
    public function send(string $email, string $konu, string $mesaj, array $context = []): array;
}
