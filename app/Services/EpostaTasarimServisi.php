<?php

namespace App\Services;

use App\Models\Kurs;
use App\Models\KursBasvuru;
use App\Models\PortalSayfa;

/**
 * Kurum logolu, ortak tasarımlı HTML e-postaları (emails.layout) için veri ve gönderim bağlamı üretir.
 */
class EpostaTasarimServisi
{
    public const LOGO_CID = 'kurum-logo';

    private const RENK = '#15408f';

    private const RENK_ACIK = '#2f6fd6';

    public const SAAT_DILIMI = 'Europe/Istanbul';

    public function __construct(
        private readonly GenelAyarServisi $ayarlar,
    ) {}

    /**
     * Şablonu ortak verilerle render eder; EmailSender::send() context'ine eklenecek html ve gömülü logo döner.
     *
     * @param  array<string, mixed>  $veri
     * @return array{html: string, gomulu_gorseller: array<string, string>}
     */
    public function htmlBaglami(string $view, array $veri): array
    {
        $logo = $this->logoDosyasi();

        return [
            'html' => view($view, [...$this->ortakVeri(), ...$veri, 'logoCid' => $logo ? self::LOGO_CID : null])->render(),
            'gomulu_gorseller' => $logo ? [self::LOGO_CID => $logo] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ortakVeri(): array
    {
        $form = $this->ayarlar->formVerisi();

        return [
            'kurum' => $form['kurum_adi'] !== '' ? $form['kurum_adi'] : (string) config('app.name'),
            'renk' => self::RENK,
            'renkAcik' => self::RENK_ACIK,
            'telefon' => $this->telefonBicimle($form['telefon']),
            'kurumEposta' => $form['eposta'],
            'webSitesi' => $form['web_sitesi'],
            'adres' => trim(implode(' ', array_filter([
                $form['adres'],
                trim(implode('/', array_filter([$form['ilce'], $form['il']]))),
            ]))),
            'yil' => now()->year,
        ];
    }

    public function portalUrl(string $yol = ''): ?string
    {
        $taban = rtrim((string) config('app.portal_url'), '/');

        return $taban !== '' ? $taban.($yol !== '' ? '/'.ltrim($yol, '/') : '') : null;
    }

    /**
     * Kurs başvurusu onaylandığında (kesin kayıt) gönderilen e-postanın HTML bağlamı.
     * $mesajSablon yöneticinin ayarlardan düzenlediği düz metindir; {ad_soyad} burada doldurulur.
     *
     * @return array{html: string, gomulu_gorseller: array<string, string>}
     */
    public function kursBasvuruOnayBaglami(Kurs $kurs, KursBasvuru $basvuru, string $konu, string $mesajSablon): array
    {
        $kurs->loadMissing(['brans', 'merkez', 'gunler']);
        $katilimci = $this->ozelAd((string) ($basvuru->kisi?->tam_adi ?? $basvuru->basvuran?->tam_adi ?? ''));
        $mesaj = str_ireplace('{ad_soyad}', $katilimci, $mesajSablon);
        $basvurularSlug = PortalSayfa::query()->where('kod', 'basvurularim')->value('slug') ?: 'basvurular';

        $tarih = collect([$kurs->kurs_baslama_tarihi, $kurs->kurs_bitis_tarihi])
            ->filter()
            ->map(fn ($t) => $t->format('d.m.Y'))
            ->unique()
            ->implode(' – ');
        $gunler = $kurs->gunlerOzeti();

        return $this->htmlBaglami('emails.kurs-basvuru-onay', [
            'konu' => $konu,
            'mesaj' => $mesaj,
            'kursAdi' => $kurs->brans?->ad ?? 'Kurs #'.$kurs->kurs_no,
            'bilgiler' => array_filter([
                'Katılımcı' => $katilimci,
                'Kurs No' => (string) $kurs->kurs_no,
                'Merkez' => (string) $kurs->merkez?->ad,
                'Kurs Tarihleri' => $tarih,
                'Gün ve Saatler' => $gunler !== '—' ? $gunler : '',
                'Toplam Süre' => $kurs->toplam_kurs_saati ? $kurs->toplam_kurs_saati.' saat' : '',
            ]),
            'portalUrl' => $this->portalUrl($basvurularSlug),
        ]);
    }

    public function logoDosyasi(): ?string
    {
        foreach ([$this->ayarlar->logoDosyaYolu(), $this->ayarlar->headerLogoDosyaYolu()] as $yol) {
            // SVG çoğu e-posta istemcisinde gösterilmez.
            if ($yol && in_array(strtolower(pathinfo($yol, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif'], true)) {
                return $yol;
            }
        }

        return null;
    }

    public function ozelAd(string $deger): string
    {
        $kucuk = mb_strtolower(str_replace(['I', 'İ'], ['ı', 'i'], trim($deger)), 'UTF-8');

        return preg_replace_callback(
            '/(^|\s)(\S)/u',
            fn (array $m) => $m[1].mb_strtoupper(str_replace(['i', 'ı'], ['İ', 'I'], $m[2]), 'UTF-8'),
            $kucuk,
        ) ?? $deger;
    }

    private function telefonBicimle(string $telefon): string
    {
        $rakam = preg_replace('/\D/', '', $telefon) ?? '';

        return strlen($rakam) === 11
            ? sprintf('%s %s %s %s', substr($rakam, 0, 4), substr($rakam, 4, 3), substr($rakam, 7, 2), substr($rakam, 9, 2))
            : $telefon;
    }
}
