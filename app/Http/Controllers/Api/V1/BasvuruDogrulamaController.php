<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Kisi;
use App\Services\BasvuruDogrulamaServisi;
use App\Services\GenelAyarServisi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BasvuruDogrulamaController extends ApiController
{
    public function gonder(Request $request, GenelAyarServisi $ayarlar, BasvuruDogrulamaServisi $otp): JsonResponse
    {
        $kanallar = $ayarlar->basvuruDogrulamaKanallari();
        if ($kanallar === []) {
            return $this->error('Başvurularda doğrulama aktif değil.', 422);
        }

        /** @var Kisi $kisi */
        $kisi = $request->user();

        try {
            $sonuc = $otp->baslat($kisi, $kanallar);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($sonuc, 'Doğrulama kodu gönderildi.');
    }

    public function yenile(Request $request, BasvuruDogrulamaServisi $otp): JsonResponse
    {
        $validated = $request->validate([
            'dogrulama_token' => ['required', 'string'],
        ], [
            'dogrulama_token.required' => 'Doğrulama oturumu bulunamadı. Lütfen yeni bir doğrulama kodu isteyin.',
        ]);

        /** @var Kisi $kisi */
        $kisi = $request->user();

        try {
            $sonuc = $otp->yenidenGonder($validated['dogrulama_token'], $kisi);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success($sonuc, 'Yeni doğrulama kodu gönderildi.');
    }
}
