<?php

namespace App\Services;

use App\Enums\Cinsiyet;
use App\Enums\IkametSarti;
use App\Models\BasvuruDurum;
use App\Models\Etkinlik;
use App\Models\EtkinlikBasvuru;
use App\Models\EtkinlikBasvuruDurum;
use App\Models\Kurs;
use App\Models\KursBasvuru;
use App\Models\Merkez;
use Illuminate\Validation\ValidationException;

class BasvuruKosulDogrulayici
{
    /**
     * @param  array{dogum_tarihi?: mixed, cinsiyet?: mixed, il?: mixed, ilce?: mixed}  $katilimci
     */
    public function dogrula(Kurs|Etkinlik $kaynak, array $katilimci, ?int $yas): void
    {
        $errors = $this->hatalar($kaynak, $katilimci, $yas);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array{dogum_tarihi?: mixed, cinsiyet?: mixed, il?: mixed, ilce?: mixed}  $katilimci
     * @return array<string, string>
     */
    public function hatalar(Kurs|Etkinlik $kaynak, array $katilimci, ?int $yas): array
    {
        $errors = [];

        if ($kaynak->minimum_yas !== null || $kaynak->maksimum_yas !== null) {
            $min = $kaynak->minimum_yas !== null ? (int) $kaynak->minimum_yas : null;
            $max = $kaynak->maksimum_yas !== null ? (int) $kaynak->maksimum_yas : null;

            if ($yas === null) {
                $errors['dogum_tarihi'] = 'Yaş koşulu için doğum tarihi zorunludur.';
            } elseif ($min !== null && $yas < $min) {
                $errors['dogum_tarihi'] = $max !== null
                    ? 'Bu kursa başvurmak için '.$min.'-'.$max.' yaş aralığında olmalısınız.'
                    : 'Bu kursa başvurmak için en az '.$min.' yaşında olmalısınız.';
            } elseif ($max !== null && $yas > $max) {
                $errors['dogum_tarihi'] = $min !== null
                    ? 'Bu kursa başvurmak için '.$min.'-'.$max.' yaş aralığında olmalısınız.'
                    : 'Bu kursa başvurmak için en fazla '.$max.' yaşında olmalısınız.';
            }
        }

        if ($kaynak->cinsiyet_sarti instanceof Cinsiyet) {
            $cinsiyet = $this->cinsiyetCoz($katilimci['cinsiyet'] ?? null);
            if ($cinsiyet === null) {
                $errors['cinsiyet'] = 'Bu kurs için cinsiyet seçimi zorunludur.';
            } elseif ($cinsiyet !== $kaynak->cinsiyet_sarti) {
                $errors['cinsiyet'] = 'Bu kurs yalnızca '.$kaynak->cinsiyet_sarti->label().' katılımcılara açıktır.';
            }
        }

        $ikametHatasi = $this->ikametHatasi($kaynak, $katilimci);
        if ($ikametHatasi !== null) {
            $errors['ilce'] = $ikametHatasi;
        }

        // Etkinlik mesajlarında "kurs" yerine "etkinlik" kullan
        if ($kaynak instanceof Etkinlik) {
            foreach ($errors as $key => $message) {
                $errors[$key] = str_replace('Bu kursa', 'Bu etkinliğe', $message);
                $errors[$key] = str_replace('Bu kurs için', 'Bu etkinlik için', $errors[$key]);
                $errors[$key] = str_replace('Bu kurs yalnızca', 'Bu etkinlik yalnızca', $errors[$key]);
            }
        }

        return $errors;
    }

    /**
     * @param  array{il?: mixed, ilce?: mixed}  $katilimci
     */
    private function ikametHatasi(Kurs|Etkinlik $kaynak, array $katilimci): ?string
    {
        $sarti = $kaynak->ikamet_sarti;
        if (! $sarti instanceof IkametSarti || $sarti === IkametSarti::Hayir) {
            return null;
        }

        $hizmetIlcesi = $this->hizmetIlcesi($kaynak->merkez);
        if ($hizmetIlcesi === null || $hizmetIlcesi === '') {
            return null;
        }

        $katilimciIlce = trim((string) ($katilimci['ilce'] ?? ''));
        if ($katilimciIlce === '') {
            return 'İkamet koşulu için ilçe bilgisi zorunludur.';
        }

        $yerel = $this->yerelIkametMi(
            (string) ($katilimci['il'] ?? ''),
            $katilimciIlce,
            $hizmetIlcesi,
            $hizmetIli = $this->hizmetIli($kaynak->merkez)
        );

        if ($sarti === IkametSarti::Evet) {
            return $yerel
                ? null
                : 'Bu başvuru yalnızca '.$hizmetIlcesi.' ilçesinde ikamet edenlere açıktır.';
        }

        // Kismen: ilçe dışı kontenjan
        if ($yerel) {
            return null;
        }

        $limit = (int) ($kaynak->ikamet_disi_kontenjan ?? 0);
        if ($limit <= 0) {
            return 'İkamet dışı kontenjan dolmuştur; yalnızca '.$hizmetIlcesi.' ilçesinde ikamet edenler başvurabilir.';
        }

        $mevcutDisi = $this->ikametDisiBasvuruSayisi($kaynak, $hizmetIlcesi, $hizmetIli);
        if ($mevcutDisi >= $limit) {
            return 'İkamet dışı kontenjan ('.$limit.') dolmuştur.';
        }

        return null;
    }

    private function ikametDisiBasvuruSayisi(Kurs|Etkinlik $kaynak, string $hizmetIlcesi, ?string $hizmetIli): int
    {
        $iptalId = $kaynak instanceof Kurs
            ? BasvuruDurum::idByKod('iptal')
            : EtkinlikBasvuruDurum::idByKod('iptal');

        $query = $kaynak instanceof Kurs
            ? KursBasvuru::query()->where('kurs_id', $kaynak->id)
            : EtkinlikBasvuru::query()->where('etkinlik_id', $kaynak->id);

        if ($iptalId) {
            $query->where('durum_id', '!=', $iptalId);
        }

        return $query->with('kisi:id,il,ilce')
            ->get()
            ->filter(fn ($basvuru) => ! $this->yerelIkametMi(
                (string) ($basvuru->kisi?->il ?? ''),
                (string) ($basvuru->kisi?->ilce ?? ''),
                $hizmetIlcesi,
                $hizmetIli
            ))
            ->count();
    }

    private function yerelIkametMi(string $il, string $ilce, string $hizmetIlcesi, ?string $hizmetIli): bool
    {
        if ($this->normalizeYer($ilce) !== $this->normalizeYer($hizmetIlcesi)) {
            return false;
        }

        if ($hizmetIli && $this->normalizeYer($il) !== '' && $this->normalizeYer($il) !== $this->normalizeYer($hizmetIli)) {
            return false;
        }

        return true;
    }

    private function hizmetIlcesi(?Merkez $merkez): ?string
    {
        $fromMerkez = trim((string) ($merkez?->ilce ?? ''));
        if ($fromMerkez !== '') {
            return $fromMerkez;
        }

        $fromConfig = trim((string) config('basvuru.hizmet_ilcesi', ''));

        return $fromConfig !== '' ? $fromConfig : null;
    }

    private function hizmetIli(?Merkez $merkez): ?string
    {
        $fromMerkez = trim((string) ($merkez?->il ?? ''));
        if ($fromMerkez !== '') {
            return $fromMerkez;
        }

        $fromConfig = trim((string) config('basvuru.hizmet_ili', ''));

        return $fromConfig !== '' ? $fromConfig : null;
    }

    private function cinsiyetCoz(mixed $value): ?Cinsiyet
    {
        if ($value instanceof Cinsiyet) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Cinsiyet::tryFrom(trim($value));
    }

    private function normalizeYer(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(['I', 'İ'], ['ı', 'i'], $value);

        return mb_strtolower($value, 'UTF-8');
    }
}
