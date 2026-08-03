<?php

namespace App\Http\Controllers;

use App\Services\LogKaydedici;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profil.edit', [
            'kullanici' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $kullanici = $request->user();

        $validated = $request->validate([
            'ad' => ['required', 'string', 'max:100'],
            'soyad' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($kullanici->id),
            ],
            'telefon' => ['nullable', 'string', 'max:20'],
            'tc_kimlik_no' => [
                'nullable', 'digits:11',
                Rule::unique('users', 'tc_kimlik_no')->ignore($kullanici->id),
            ],
            'dogum_tarihi' => ['nullable', 'date'],
            'il' => ['nullable', 'string', 'max:100'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'adres' => ['nullable', 'string', 'max:500'],
        ], [
            'ad.required' => 'Ad alanı zorunludur.',
            'soyad.required' => 'Soyad alanı zorunludur.',
            'email.required' => 'E-posta alanı zorunludur.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'tc_kimlik_no.digits' => 'TC Kimlik No 11 haneli olmalıdır.',
            'tc_kimlik_no.unique' => 'Bu TC Kimlik No zaten kayıtlı.',
        ]);

        if (($validated['tc_kimlik_no'] ?? null) === '') {
            $validated['tc_kimlik_no'] = null;
        }

        $onceki = $this->snapshot($kullanici);
        $kullanici->update($validated);
        $yeni = $this->snapshot($kullanici);

        if ($onceki !== $yeni) {
            LogKaydedici::kaydet(
                islem: 'profil.guncellendi',
                aciklama: ($kullanici->tam_adi).' profil bilgilerini güncelledi.',
                konu: $kullanici,
                eski: $onceki,
                yeni: $yeni,
                konuAdi: $kullanici->tam_adi,
                userId: $kullanici->id,
            );
        }

        return redirect()->route('profil.edit')->with('success', 'Profil bilgileriniz güncellendi.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $kullanici = $request->user();

        $validated = $request->validate([
            'mevcut_sifre' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'mevcut_sifre.required' => 'Mevcut şifrenizi girmelisiniz.',
            'password.required' => 'Yeni şifre zorunludur.',
            'password.min' => 'Yeni şifre en az 6 karakter olmalıdır.',
            'password.confirmed' => 'Yeni şifre tekrarı eşleşmiyor.',
        ]);

        if (! Hash::check($validated['mevcut_sifre'], $kullanici->password)) {
            return back()
                ->withErrors(['mevcut_sifre' => 'Mevcut şifreniz hatalı.'])
                ->with('sifre_hata', true);
        }

        $kullanici->update(['password' => $validated['password']]);

        LogKaydedici::kaydet(
            islem: 'profil.sifre_degistirildi',
            aciklama: ($kullanici->tam_adi).' şifresini değiştirdi.',
            konu: $kullanici,
            konuAdi: $kullanici->tam_adi,
            userId: $kullanici->id,
        );

        return redirect()->route('profil.edit')->with('success', 'Şifreniz başarıyla güncellendi.');
    }

    public function updateFoto(Request $request): JsonResponse
    {
        $kullanici = $request->user();

        $validated = $request->validate([
            'profil_foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'profil_foto.required' => 'Bir resim seçmelisiniz.',
            'profil_foto.image' => 'Yüklenen dosya bir resim olmalıdır.',
            'profil_foto.mimes' => 'Profil resmi JPG, PNG veya WEBP olmalıdır.',
            'profil_foto.max' => 'Profil resmi en fazla 2 MB olabilir.',
        ]);

        $dosya = $this->fotoKaydet($validated['profil_foto'], $kullanici->profil_foto);
        $kullanici->update(['profil_foto' => $dosya]);

        LogKaydedici::kaydet(
            islem: 'profil.guncellendi',
            aciklama: ($kullanici->tam_adi).' profil fotoğrafını güncelledi.',
            konu: $kullanici,
            konuAdi: $kullanici->tam_adi,
            userId: $kullanici->id,
        );

        return response()->json([
            'message' => 'Profil fotoğrafı güncellendi.',
            'url' => $kullanici->profil_foto_url,
        ]);
    }

    public function deleteFoto(Request $request): JsonResponse
    {
        $kullanici = $request->user();

        if (! $kullanici->profil_foto) {
            return response()->json([
                'message' => 'Kaldırılacak bir profil fotoğrafı bulunamadı.',
            ], 422);
        }

        $this->fotoSil($kullanici->profil_foto);
        $kullanici->update(['profil_foto' => null]);

        LogKaydedici::kaydet(
            islem: 'profil.guncellendi',
            aciklama: ($kullanici->tam_adi).' profil fotoğrafını kaldırdı.',
            konu: $kullanici,
            konuAdi: $kullanici->tam_adi,
            userId: $kullanici->id,
        );

        return response()->json([
            'message' => 'Profil fotoğrafı kaldırıldı.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot($kullanici): array
    {
        return [
            'ad' => $kullanici->ad,
            'soyad' => $kullanici->soyad,
            'email' => $kullanici->email,
            'telefon' => $kullanici->telefon,
            'tc_kimlik_no' => $kullanici->tc_kimlik_no,
            'dogum_tarihi' => $kullanici->dogum_tarihi?->toDateString(),
            'il' => $kullanici->il,
            'ilce' => $kullanici->ilce,
            'adres' => $kullanici->adres,
            'profil_foto' => $kullanici->profil_foto,
        ];
    }

    private function fotoKaydet(UploadedFile $foto, ?string $eski): string
    {
        $klasor = public_path('uploads/avatars');
        if (! is_dir($klasor)) {
            @mkdir($klasor, 0755, true);
        }

        $ad = Str::uuid()->toString().'.'.strtolower($foto->getClientOriginalExtension() ?: 'jpg');
        $foto->move($klasor, $ad);

        $this->fotoSil($eski);

        return $ad;
    }

    private function fotoSil(?string $dosya): void
    {
        if (! $dosya) {
            return;
        }

        $yol = public_path('uploads/avatars/'.$dosya);
        if (is_file($yol)) {
            @unlink($yol);
        }
    }
}
