<?php

namespace App\Http\Controllers;

use App\Services\Entegrasyon\EntegrasyonAyarServisi;
use App\Services\Entegrasyon\EntegrasyonCozumleyici;
use App\Services\LogKaydedici;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class EntegrasyonController extends Controller
{
    public function index(EntegrasyonAyarServisi $servis): View
    {
        return view('entegrasyonlar.index', [
            'turler' => $servis->ekranVerisi(),
        ]);
    }

    public function update(Request $request, EntegrasyonAyarServisi $servis): RedirectResponse|JsonResponse
    {
        $turKodlari = array_keys($servis->turler());
        $rules = [
            'tur_aktif' => ['required', 'array'],
            'aktif' => ['nullable', 'array'],
            'ayarlar' => ['nullable', 'array'],
        ];

        foreach ($turKodlari as $tur) {
            $izinli = array_keys($servis->saglayicilar($tur));
            $rules["tur_aktif.{$tur}"] = ['required', 'boolean'];
            $rules["aktif.{$tur}"] = [
                Rule::requiredIf(fn () => $request->boolean("tur_aktif.{$tur}")),
                'nullable',
                'string',
                Rule::in($izinli),
            ];

            foreach ($servis->saglayicilar($tur) as $saglayiciKod => $saglayici) {
                foreach ((array) ($saglayici['alanlar'] ?? []) as $alan => $tanim) {
                    $tip = (string) ($tanim['tip'] ?? 'text');
                    $alanKurallari = ['nullable'];

                    $alanKurallari[] = match ($tip) {
                        'email' => 'email',
                        'number' => 'numeric',
                        'select' => Rule::in(array_map('strval', array_keys((array) ($tanim['secenekler'] ?? [])))),
                        default => 'string',
                    };

                    if ($tip !== 'number' && $tip !== 'select') {
                        $alanKurallari[] = 'max:500';
                    }

                    $rules["ayarlar.{$tur}.{$saglayiciKod}.{$alan}"] = $alanKurallari;
                }
            }
        }

        $validated = $request->validate($rules, [
            'tur_aktif.required' => 'Entegrasyon durumları zorunludur.',
            'tur_aktif.*.required' => 'Her entegrasyon türü için aktif/pasif durumu seçilmelidir.',
            'aktif.*.required' => 'Aktif entegrasyonlar için bir sağlayıcı seçilmelidir.',
            'aktif.*.in' => 'Seçilen sağlayıcı bu tür için geçerli değil.',
            'ayarlar.*.*.*.email' => 'Geçerli bir e-posta adresi girin.',
        ]);

        $turAktiflik = [];
        $secimler = [];
        foreach ($turKodlari as $tur) {
            $turAktiflik[$tur] = (bool) ($validated['tur_aktif'][$tur] ?? false);
            if (isset($validated['aktif'][$tur]) && $validated['aktif'][$tur] !== '') {
                $secimler[$tur] = $validated['aktif'][$tur];
            }
        }

        $ayarlar = is_array($validated['ayarlar'] ?? null) ? $validated['ayarlar'] : [];

        $onceki = [];
        $yeni = [];
        foreach ($turKodlari as $tur) {
            $onceki[$tur] = [
                'aktif' => $servis->turAktifMi($tur),
                'saglayici' => $servis->aktifSaglayiciKod($tur),
            ];
        }

        try {
            $servis->kaydet($secimler, $turAktiflik, $ayarlar);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'aktif' => $e->getMessage(),
            ]);
        }

        foreach ($turKodlari as $tur) {
            $yeni[$tur] = [
                'aktif' => $servis->turAktifMi($tur),
                'saglayici' => $servis->aktifSaglayiciKod($tur),
            ];
        }

        LogKaydedici::kaydet(
            islem: 'entegrasyon.guncellendi',
            aciklama: 'Entegrasyon ayarları güncellendi.',
            eski: $onceki,
            yeni: $yeni,
            konuAdi: 'Entegrasyonlar',
        );

        $message = 'Entegrasyon ayarları kaydedildi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'turler' => $servis->ekranVerisi(),
            ]);
        }

        return redirect()
            ->route('entegrasyonlar.index')
            ->with('success', $message);
    }

    public function updateAyarlar(
        Request $request,
        EntegrasyonAyarServisi $servis,
        string $tur,
        string $saglayici,
    ): JsonResponse {
        $izinli = $servis->saglayicilar($tur);

        if (! array_key_exists($tur, $servis->turler()) || ! array_key_exists($saglayici, $izinli)) {
            abort(404);
        }

        $alanTanimlari = (array) ($izinli[$saglayici]['alanlar'] ?? []);

        if ($alanTanimlari === []) {
            abort(404);
        }

        $validated = $request->validate([
            'ayarlar' => ['required', 'array'],
            ...$this->ayarKurallari($alanTanimlari),
        ], [
            'ayarlar.required' => 'Ayar alanları zorunludur.',
            'ayarlar.*.email' => 'Geçerli bir e-posta adresi girin.',
        ]);

        try {
            $servis->kaydetSaglayiciAyarlari($tur, $saglayici, $validated['ayarlar']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'ayarlar' => $e->getMessage(),
            ]);
        }

        $saglayiciAd = (string) ($izinli[$saglayici]['ad'] ?? $saglayici);

        LogKaydedici::kaydet(
            islem: 'entegrasyon.ayar_guncellendi',
            aciklama: "{$saglayiciAd} sağlayıcı ayarları güncellendi.",
            konuAdi: 'Entegrasyonlar',
            ekstra: [
                'tur' => $tur,
                'saglayici' => $saglayici,
            ],
        );

        $message = $saglayiciAd.' ayarları kaydedildi.';

        return response()->json([
            'message' => $message,
            'turler' => $servis->ekranVerisi(),
        ]);
    }

    /**
     * Ayar penceresindeki (henüz kaydedilmemiş olabilecek) değerlerle sağlayıcıyı dener.
     * Boş bırakılan gizli alanlar için kayıtlı değer kullanılır.
     */
    public function test(
        Request $request,
        EntegrasyonAyarServisi $servis,
        EntegrasyonCozumleyici $cozumleyici,
        string $tur,
        string $saglayici,
    ): JsonResponse {
        $izinli = $servis->saglayicilar($tur);

        if (! array_key_exists($tur, $servis->turler()) || ! array_key_exists($saglayici, $izinli)) {
            abort(404);
        }

        $alanTanimlari = (array) ($izinli[$saglayici]['alanlar'] ?? []);

        $rules = [
            'ayarlar' => ['nullable', 'array'],
            ...$this->ayarKurallari($alanTanimlari),
        ];
        $rules += match ($tur) {
            'kimlik_sorgulama', 'adres_sorgulama', 'yakin_sorgulama' => [
                'test.tc_kimlik_no' => ['required', 'digits:11'],
                'test.dogum_tarihi' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            ],
            'sms' => ['test.telefon' => ['required', 'string', 'regex:/^05\d{9}$/']],
            'eposta' => ['test.email' => ['required', 'email', 'max:150']],
            default => [],
        };

        $validated = $request->validate($rules, [
            'test.tc_kimlik_no.required' => 'Test için T.C. kimlik numarası girin.',
            'test.tc_kimlik_no.digits' => 'T.C. kimlik numarası 11 haneli olmalıdır.',
            'test.dogum_tarihi.required' => 'Test için doğum tarihi girin.',
            'test.dogum_tarihi.*' => 'Geçerli bir doğum tarihi girin.',
            'test.telefon.required' => 'Test için telefon numarası girin.',
            'test.telefon.regex' => 'Telefon 05XXXXXXXXX biçiminde olmalıdır.',
            'test.email.required' => 'Test için e-posta adresi girin.',
            'test.email.email' => 'Geçerli bir e-posta adresi girin.',
            'ayarlar.*.email' => 'Geçerli bir e-posta adresi girin.',
        ]);

        $kayitli = $servis->saglayiciAyarlari($tur, $saglayici);
        $gelen = (array) ($validated['ayarlar'] ?? []);
        $ayarlar = [];
        foreach ($alanTanimlari as $alan => $tanim) {
            $gizli = (bool) ($tanim['gizli'] ?? (($tanim['tip'] ?? '') === 'password'));
            $deger = $gelen[$alan] ?? null;
            $ayarlar[$alan] = ($deger === null || $deger === '') && $gizli ? ($kayitli[$alan] ?? null) : $deger;
        }

        $test = (array) ($validated['test'] ?? []);
        $saglayiciAd = (string) ($izinli[$saglayici]['ad'] ?? $saglayici);
        $baslangic = microtime(true);

        try {
            $ornek = $cozumleyici->ornek($tur, $saglayici, $ayarlar);
            $sonuc = match ($tur) {
                'kimlik_sorgulama', 'adres_sorgulama', 'yakin_sorgulama' => $ornek->sorgula($test['tc_kimlik_no'], $test['dogum_tarihi']),
                'sms' => $ornek->send(
                    $test['telefon'],
                    'Başvuru360 entegrasyon testi: '.$saglayiciAd.' SMS gönderimi başarılı.',
                    ['gonderen_id' => auth()->id(), 'kapsam' => 'entegrasyon_test'],
                ),
                'eposta' => $ornek->send(
                    $test['email'],
                    'Entegrasyon Testi',
                    "Bu e-posta Başvuru360 entegrasyon ekranından {$saglayiciAd} sağlayıcısını test etmek için gönderildi.",
                    ['gonderen_id' => auth()->id(), 'kapsam' => 'entegrasyon_test'],
                ),
                default => throw new RuntimeException('Bu entegrasyon türü için test desteklenmiyor.'),
            };
        } catch (RuntimeException $e) {
            $sonuc = ['ok' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            report($e);
            $sonuc = ['ok' => false, 'message' => 'Test sırasında beklenmeyen bir hata oluştu: '.$e->getMessage()];
        }

        $ok = (bool) ($sonuc['ok'] ?? false);
        $sureMs = (int) round((microtime(true) - $baslangic) * 1000);

        LogKaydedici::kaydet(
            islem: 'entegrasyon.test_edildi',
            aciklama: "{$saglayiciAd} sağlayıcısı test edildi: ".($ok ? 'başarılı' : 'başarısız').'.',
            konuAdi: 'Entegrasyonlar',
            ekstra: ['tur' => $tur, 'saglayici' => $saglayici, 'basarili' => $ok, 'sure_ms' => $sureMs],
        );

        $veri = $sonuc;
        unset($veri['ok'], $veri['message']);

        return response()->json([
            'ok' => $ok,
            'message' => (string) ($sonuc['message'] ?? ($ok ? 'Test başarılı.' : 'Test başarısız.')),
            'sure_ms' => $sureMs,
            'veri' => $ok ? $veri : [],
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $alanTanimlari
     * @return array<string, list<mixed>>
     */
    private function ayarKurallari(array $alanTanimlari): array
    {
        $rules = [];

        foreach ($alanTanimlari as $alan => $tanim) {
            $tip = (string) ($tanim['tip'] ?? 'text');
            $alanKurallari = ['nullable'];

            $alanKurallari[] = match ($tip) {
                'email' => 'email',
                'number' => 'numeric',
                'select' => Rule::in(array_map('strval', array_keys((array) ($tanim['secenekler'] ?? [])))),
                default => 'string',
            };

            if ($tip !== 'number' && $tip !== 'select') {
                $alanKurallari[] = 'max:500';
            }

            $rules["ayarlar.{$alan}"] = $alanKurallari;
        }

        return $rules;
    }
}
