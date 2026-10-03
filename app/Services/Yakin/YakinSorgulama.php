<?php

namespace App\Services\Yakin;

interface YakinSorgulama
{
    /**
     * Kişinin 1. derece yakınlarını (eşi, çocukları, anne, baba) döner; kişinin kendisi listede yer almaz.
     *
     * @return array{
     *     ok: bool,
     *     yakinlar?: list<array{
     *         yakinlik_kodu: string|null,
     *         yakinlik: string|null,
     *         tc_kimlik_no: string|null,
     *         ad: string|null,
     *         soyad: string|null,
     *         cinsiyet: string|null,
     *         dogum_tarihi: string|null,
     *         dogum_yeri: string|null,
     *         medeni_durum: string|null,
     *         anne_adi: string|null,
     *         baba_adi: string|null,
     *         olum_tarihi: string|null,
     *     }>,
     *     message?: string
     * }
     */
    public function sorgula(string $tcKimlikNo, ?string $dogumTarihi = null): array;
}
