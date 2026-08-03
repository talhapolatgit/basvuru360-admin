<?php

namespace App\Http\Controllers;

use App\Services\Entegrasyon\EntegrasyonAyarServisi;
use App\Services\LogKaydedici;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

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

        $rules = [
            'ayarlar' => ['required', 'array'],
        ];

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

        $validated = $request->validate($rules, [
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
}
