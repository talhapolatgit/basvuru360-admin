<?php

namespace App\Services\Entegrasyon;

use App\Models\EntegrasyonAyar;
use InvalidArgumentException;

class EntegrasyonAyarServisi
{
    /**
     * @return array<string, array{ad: string, aciklama: string}>
     */
    public function turler(): array
    {
        return (array) config('entegrasyonlar.turler', []);
    }

    /**
     * @return array<string, array{tur: string, ad: string, aciklama: string, alanlar?: array<string, array<string, mixed>>}>
     */
    public function saglayicilar(?string $tur = null): array
    {
        $hepsi = (array) config('entegrasyonlar.saglayicilar', []);

        if ($tur === null) {
            return $hepsi;
        }

        return array_filter(
            $hepsi,
            fn (array $s) => ($s['tur'] ?? null) === $tur
        );
    }

    public function turAktifMi(string $tur): bool
    {
        $kayit = EntegrasyonAyar::query()->where('tur', $tur)->first();

        if ($kayit === null) {
            return true;
        }

        return (bool) $kayit->aktif;
    }

    public function aktifSaglayiciKod(string $tur): string
    {
        $kayit = EntegrasyonAyar::query()->where('tur', $tur)->first();
        if ($kayit?->aktif_saglayici) {
            $kod = $kayit->aktif_saglayici;
            if (array_key_exists($kod, $this->saglayicilar($tur))) {
                return $kod;
            }
        }

        return (string) (config("entegrasyonlar.varsayilanlar.{$tur}") ?? '');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function turAyarlari(string $tur): array
    {
        $kayit = EntegrasyonAyar::query()->where('tur', $tur)->first();
        $ayarlar = $kayit?->ayarlar;

        return is_array($ayarlar) ? $ayarlar : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function saglayiciAyarlari(string $tur, ?string $saglayiciKod = null): array
    {
        $kod = $saglayiciKod ?: $this->aktifSaglayiciKod($tur);
        $hepsi = $this->turAyarlari($tur);
        $ayarlar = $hepsi[$kod] ?? [];

        return is_array($ayarlar) ? $ayarlar : [];
    }

    /**
     * Ekran için tür + sağlayıcı + aktif kod + ayar alanları özeti.
     *
     * @return list<array{
     *     tur: string,
     *     ad: string,
     *     aciklama: string,
     *     tur_aktif: bool,
     *     aktif: string,
     *     saglayicilar: list<array{
     *         kod: string,
     *         ad: string,
     *         aciklama: string,
     *         aktif: bool,
     *         alanlar: list<array<string, mixed>>,
     *         degerler: array<string, mixed>
     *     }>
     * }>
     */
    public function ekranVerisi(): array
    {
        $liste = [];

        foreach ($this->turler() as $tur => $meta) {
            $aktifKod = $this->aktifSaglayiciKod($tur);
            $turAktif = $this->turAktifMi($tur);
            $kayitliAyarlar = $this->turAyarlari($tur);
            $saglayicilar = [];

            foreach ($this->saglayicilar($tur) as $kod => $saglayici) {
                $alanTanimlari = (array) ($saglayici['alanlar'] ?? []);
                $kayitli = is_array($kayitliAyarlar[$kod] ?? null) ? $kayitliAyarlar[$kod] : [];
                $alanlar = [];
                $degerler = [];

                foreach ($alanTanimlari as $alan => $tanim) {
                    $gizli = (bool) ($tanim['gizli'] ?? (($tanim['tip'] ?? '') === 'password'));
                    $kayitliDeger = $kayitli[$alan] ?? null;
                    $varsayilan = $tanim['varsayilan'] ?? '';
                    $deger = $kayitliDeger !== null && $kayitliDeger !== ''
                        ? (string) $kayitliDeger
                        : (string) $varsayilan;

                    if ($gizli) {
                        // Gizli alanlar forma boş döner; dolu olup olmadığı ayrı tutulur.
                        $degerler[$alan] = '';
                    } else {
                        $degerler[$alan] = $deger;
                    }

                    $alanlar[] = [
                        'kod' => $alan,
                        'etiket' => (string) ($tanim['etiket'] ?? $alan),
                        'tip' => (string) ($tanim['tip'] ?? 'text'),
                        'zorunlu' => (bool) ($tanim['zorunlu'] ?? false),
                        'gizli' => $gizli,
                        'placeholder' => (string) ($tanim['placeholder'] ?? ''),
                        'secenekler' => (array) ($tanim['secenekler'] ?? []),
                        'dolu' => $gizli && filled($kayitliDeger),
                        'deger' => $gizli ? '' : $deger,
                    ];
                }

                $saglayicilar[] = [
                    'kod' => $kod,
                    'ad' => (string) ($saglayici['ad'] ?? $kod),
                    'aciklama' => (string) ($saglayici['aciklama'] ?? ''),
                    'aktif' => $turAktif && $kod === $aktifKod,
                    'alanlar' => $alanlar,
                    'degerler' => $degerler,
                ];
            }

            $liste[] = [
                'tur' => $tur,
                'ad' => (string) ($meta['ad'] ?? $tur),
                'aciklama' => (string) ($meta['aciklama'] ?? ''),
                'tur_aktif' => $turAktif,
                'aktif' => $aktifKod,
                'saglayicilar' => $saglayicilar,
            ];
        }

        return $liste;
    }

    /**
     * @param  array<string, string>  $secimler  tur => saglayici_kod
     * @param  array<string, bool>  $turAktiflik  tur => aktif mi
     * @param  array<string, array<string, array<string, mixed>>>  $ayarlar  tur => saglayici => alan => deger
     */
    public function kaydet(array $secimler, array $turAktiflik = [], array $ayarlar = []): void
    {
        foreach ($this->turler() as $tur => $meta) {
            $turAktif = array_key_exists($tur, $turAktiflik)
                ? (bool) $turAktiflik[$tur]
                : $this->turAktifMi($tur);

            $kod = array_key_exists($tur, $secimler)
                ? trim((string) $secimler[$tur])
                : $this->aktifSaglayiciKod($tur);

            $izinli = $this->saglayicilar($tur);

            if ($kod === '' || ! array_key_exists($kod, $izinli)) {
                if ($turAktif) {
                    throw new InvalidArgumentException("Geçersiz sağlayıcı: {$tur} / {$kod}");
                }

                $kod = $this->aktifSaglayiciKod($tur);
                if ($kod === '' || ! array_key_exists($kod, $izinli)) {
                    $kod = (string) array_key_first($izinli);
                }
            }

            if ($kod === '') {
                throw new InvalidArgumentException("Bu tür için sağlayıcı bulunamadı: {$tur}");
            }

            $mevcutAyarlar = $this->turAyarlari($tur);
            $gelenAyarlar = is_array($ayarlar[$tur] ?? null) ? $ayarlar[$tur] : [];
            $birlesikAyarlar = $this->ayarlarBirlesik($izinli, $mevcutAyarlar, $gelenAyarlar);

            if ($turAktif) {
                $this->assertZorunluAyarlar($kod, $izinli[$kod] ?? [], $birlesikAyarlar[$kod] ?? []);
            }

            EntegrasyonAyar::query()->updateOrCreate(
                ['tur' => $tur],
                [
                    'aktif_saglayici' => $kod,
                    'aktif' => $turAktif,
                    'ayarlar' => $birlesikAyarlar === [] ? null : $birlesikAyarlar,
                ]
            );
        }
    }

    /**
     * Tek bir sağlayıcının ayar alanlarını günceller.
     *
     * @param  array<string, mixed>  $degerler
     */
    public function kaydetSaglayiciAyarlari(string $tur, string $saglayiciKod, array $degerler): void
    {
        if (! array_key_exists($tur, $this->turler())) {
            throw new InvalidArgumentException("Geçersiz entegrasyon türü: {$tur}");
        }

        $izinli = $this->saglayicilar($tur);

        if (! array_key_exists($saglayiciKod, $izinli)) {
            throw new InvalidArgumentException("Geçersiz sağlayıcı: {$tur} / {$saglayiciKod}");
        }

        if ((array) ($izinli[$saglayiciKod]['alanlar'] ?? []) === []) {
            throw new InvalidArgumentException('Bu sağlayıcı için ayar alanı tanımlı değil.');
        }

        $mevcutAyarlar = $this->turAyarlari($tur);
        $birlesikAyarlar = $this->ayarlarBirlesik($izinli, $mevcutAyarlar, [
            $saglayiciKod => $degerler,
        ]);

        $this->assertZorunluAyarlar($saglayiciKod, $izinli[$saglayiciKod], $birlesikAyarlar[$saglayiciKod] ?? []);

        EntegrasyonAyar::query()->updateOrCreate(
            ['tur' => $tur],
            [
                'aktif_saglayici' => $this->aktifSaglayiciKod($tur) ?: $saglayiciKod,
                'aktif' => $this->turAktifMi($tur),
                'ayarlar' => $birlesikAyarlar === [] ? null : $birlesikAyarlar,
            ]
        );
    }

    /**
     * @param  array<string, array{alanlar?: array<string, array<string, mixed>>}>  $izinli
     * @param  array<string, array<string, mixed>>  $mevcut
     * @param  array<string, array<string, mixed>>  $gelen
     * @return array<string, array<string, mixed>>
     */
    private function ayarlarBirlesik(array $izinli, array $mevcut, array $gelen): array
    {
        $sonuc = [];

        foreach ($izinli as $saglayiciKod => $saglayici) {
            $alanTanimlari = (array) ($saglayici['alanlar'] ?? []);
            if ($alanTanimlari === []) {
                continue;
            }

            $onceki = is_array($mevcut[$saglayiciKod] ?? null) ? $mevcut[$saglayiciKod] : [];
            $yeni = is_array($gelen[$saglayiciKod] ?? null) ? $gelen[$saglayiciKod] : [];
            $birlesik = [];

            foreach ($alanTanimlari as $alan => $tanim) {
                $gizli = (bool) ($tanim['gizli'] ?? (($tanim['tip'] ?? '') === 'password'));
                $gelenDeger = array_key_exists($alan, $yeni) ? $yeni[$alan] : null;

                if (is_string($gelenDeger)) {
                    $gelenDeger = trim($gelenDeger);
                }

                if ($gizli && ($gelenDeger === null || $gelenDeger === '')) {
                    if (array_key_exists($alan, $onceki) && $onceki[$alan] !== null && $onceki[$alan] !== '') {
                        $birlesik[$alan] = $onceki[$alan];
                    }

                    continue;
                }

                if ($gelenDeger === null) {
                    if (array_key_exists($alan, $onceki)) {
                        $birlesik[$alan] = $onceki[$alan];
                    }

                    continue;
                }

                $birlesik[$alan] = $gelenDeger;
            }

            if ($birlesik !== []) {
                $sonuc[$saglayiciKod] = $birlesik;
            }
        }

        return $sonuc;
    }

    /**
     * @param  array{alanlar?: array<string, array<string, mixed>>}  $saglayici
     * @param  array<string, mixed>  $degerler
     */
    private function assertZorunluAyarlar(string $kod, array $saglayici, array $degerler): void
    {
        foreach ((array) ($saglayici['alanlar'] ?? []) as $alan => $tanim) {
            if (! ($tanim['zorunlu'] ?? false)) {
                continue;
            }

            $deger = $degerler[$alan] ?? null;
            if ($deger === null || (is_string($deger) && trim($deger) === '')) {
                $etiket = (string) ($tanim['etiket'] ?? $alan);
                throw new InvalidArgumentException("{$kod} için zorunlu alan eksik: {$etiket}");
            }
        }
    }
}
