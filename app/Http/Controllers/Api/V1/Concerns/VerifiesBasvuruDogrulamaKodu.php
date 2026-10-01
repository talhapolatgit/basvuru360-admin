<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Kisi;
use App\Services\BasvuruDogrulamaServisi;
use App\Services\GenelAyarServisi;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

trait VerifiesBasvuruDogrulamaKodu
{
    /**
     * Başvurularda doğrulama açıksa istekteki kodu kontrol eder. Kod burada tüketilmez;
     * başvuru kaydedildikten sonra {@see basvuruDogrulamaKoduTamamla()} çağrılmalıdır.
     *
     * @return string|null Doğrulama token'ı; ayar kapalıysa null.
     *
     * @throws ValidationException
     */
    protected function basvuruDogrulamaKoduKontrol(Request $request, Kisi $basvuran): ?string
    {
        if (app(GenelAyarServisi::class)->basvuruDogrulamaKanallari() === []) {
            return null;
        }

        $token = trim((string) $request->input('dogrulama_token', ''));
        $kod = trim((string) $request->input('dogrulama_kodu', ''));

        if ($token === '' || $kod === '') {
            throw ValidationException::withMessages([
                'dogrulama_kodu' => 'Başvuruyu tamamlamak için doğrulama kodu gereklidir.',
            ]);
        }

        try {
            app(BasvuruDogrulamaServisi::class)->kontrol($token, $kod, $basvuran);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['dogrulama_kodu' => $e->getMessage()]);
        }

        return $token;
    }

    protected function basvuruDogrulamaKoduTamamla(?string $token): void
    {
        if ($token !== null) {
            app(BasvuruDogrulamaServisi::class)->tamamla($token);
        }
    }
}
