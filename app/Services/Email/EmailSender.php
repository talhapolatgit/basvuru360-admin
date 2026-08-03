<?php

namespace App\Services\Email;

interface EmailSender
{
    /**
     * @param  array{kurs_id?: int|null, basvuru_id?: int|null, etkinlik_id?: int|null, etkinlik_basvuru_id?: int|null, gonderen_id?: int|null}  $context
     * @return array{ok: bool, message?: string}
     */
    public function send(string $email, string $konu, string $mesaj, array $context = []): array;
}
