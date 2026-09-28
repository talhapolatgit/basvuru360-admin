<?php

namespace App\Http\Controllers;

use App\Models\GuvenilirIpAdresi;
use App\Services\GuvenilirIpServisi;
use App\Services\LogKaydedici;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuvenilirIpAdresiController extends Controller
{
    public function index(Request $request): View
    {
        $adresler = GuvenilirIpAdresi::query()
            ->with(['olusturan:id,ad,soyad', 'guncelleyen:id,ad,soyad'])
            ->orderBy('ip_adresi')
            ->get();

        return view('guvenilir-ip-adresleri.index', [
            'adresler' => $adresler,
            'mevcutIp' => $request->ip(),
            'mevcutIpGuvenilir' => app(GuvenilirIpServisi::class)->guvenilirMi($request->ip()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $veri = $this->dogrula($request, null, 'olustur');

        $adres = GuvenilirIpAdresi::query()->create([
            ...$veri,
            'olusturan_id' => $request->user()?->id,
            'guncelleyen_id' => $request->user()?->id,
        ]);

        LogKaydedici::kaydet(
            islem: 'guvenilir_ip.eklendi',
            aciklama: $adres->ip_adresi.' güvenilir IP adreslerine eklendi.',
            konu: $adres,
            konuAdi: $adres->ip_adresi,
            yeni: $veri,
        );

        return redirect()
            ->route('guvenilir-ip-adresleri.index')
            ->with('success', 'Güvenilir IP adresi eklendi.');
    }

    public function update(Request $request, GuvenilirIpAdresi $guvenilirIpAdresi): RedirectResponse
    {
        $veri = $this->dogrula($request, $guvenilirIpAdresi, 'duzenle');
        $eski = $guvenilirIpAdresi->only(['ip_adresi', 'aciklama']);

        $guvenilirIpAdresi->update([
            ...$veri,
            'guncelleyen_id' => $request->user()?->id,
        ]);

        LogKaydedici::kaydet(
            islem: 'guvenilir_ip.guncellendi',
            aciklama: $guvenilirIpAdresi->ip_adresi.' güvenilir IP kaydı güncellendi.',
            konu: $guvenilirIpAdresi,
            konuAdi: $guvenilirIpAdresi->ip_adresi,
            eski: $eski,
            yeni: $veri,
        );

        return redirect()
            ->route('guvenilir-ip-adresleri.index')
            ->with('success', 'Güvenilir IP adresi güncellendi.');
    }

    public function destroy(GuvenilirIpAdresi $guvenilirIpAdresi): RedirectResponse
    {
        $eski = $guvenilirIpAdresi->only(['ip_adresi', 'aciklama']);
        $guvenilirIpAdresi->delete();

        LogKaydedici::kaydet(
            islem: 'guvenilir_ip.silindi',
            aciklama: $eski['ip_adresi'].' güvenilir IP adreslerinden kaldırıldı.',
            konuAdi: $eski['ip_adresi'],
            eski: $eski,
        );

        return redirect()
            ->route('guvenilir-ip-adresleri.index')
            ->with('success', 'Güvenilir IP adresi silindi.');
    }

    /**
     * @return array{ip_adresi: string, aciklama: ?string}
     */
    private function dogrula(Request $request, ?GuvenilirIpAdresi $mevcut, string $errorBag): array
    {
        $request->merge([
            'ip_adresi' => trim((string) $request->input('ip_adresi')),
            'aciklama' => trim((string) $request->input('aciklama')) ?: null,
        ]);

        $veri = $request->validateWithBag($errorBag, [
            'ip_adresi' => [
                'required',
                'string',
                'max:64',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($hata = GuvenilirIpServisi::dogrula((string) $value)) {
                        $fail($hata);
                    }
                },
                Rule::unique('guvenilir_ip_adresleri', 'ip_adresi')->ignore($mevcut?->id),
            ],
            'aciklama' => ['nullable', 'string', 'max:255'],
        ], [
            'ip_adresi.required' => 'IP adresi zorunludur.',
            'ip_adresi.max' => 'IP adresi en fazla 64 karakter olabilir.',
            'ip_adresi.unique' => 'Bu IP adresi zaten tanımlı.',
            'aciklama.max' => 'Açıklama en fazla 255 karakter olabilir.',
        ]);

        return [
            'ip_adresi' => $veri['ip_adresi'],
            'aciklama' => $veri['aciklama'] ?? null,
        ];
    }
}
