<?php

namespace App\Http\Controllers;

use App\Enums\Cinsiyet;
use App\Http\Controllers\Concerns\ResolvesKresDonem;
use App\Models\Kisi;
use App\Models\KresBasvuru;
use App\Models\KresBasvuruDurum;
use App\Models\KresGrup;
use App\Models\KresOkul;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KresBasvuruController extends Controller
{
    use ResolvesKresDonem;

    public function store(Request $request, KresOkul $kresOkul, KresGrup $kresGrup): Response
    {
        abort_unless($kresGrup->okul_id === $kresOkul->id, 404);

        $validated = $request->validate([
            'kisi_id' => [
                'required',
                'integer',
                'exists:kisiler,id',
                Rule::unique('kres_basvurulari', 'kisi_id')
                    ->where(fn ($q) => $q->where('grup_id', $kresGrup->id)->whereNull('deleted_at')),
            ],
            'basvuran_id' => ['nullable', 'integer', 'exists:kisiler,id', 'different:kisi_id'],
            'durum_id' => [
                'required',
                'integer',
                Rule::exists('kres_basvuru_durumlari', 'id')->where(fn ($q) => $q->where('aktif', true)),
            ],
            'yedek_sira' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'notlar' => ['nullable', 'string', 'max:2000'],
        ], [
            'kisi_id.required' => 'Öğrenci (kişi) seçimi zorunludur.',
            'kisi_id.unique' => 'Bu öğrenci bu gruba zaten kayıtlı.',
            'durum_id.required' => 'Durum seçimi zorunludur.',
            'basvuran_id.different' => 'Başvuran ile öğrenci aynı kişi olamaz.',
        ]);

        $kisi = Kisi::query()->find($validated['kisi_id']);
        if ($kresGrup->cinsiyet_sarti instanceof Cinsiyet) {
            if (! $kisi?->cinsiyet) {
                throw ValidationException::withMessages([
                    'kisi_id' => 'Bu grup için öğrencinin cinsiyeti kayıtlı olmalıdır.',
                ]);
            }

            if ($kisi->cinsiyet !== $kresGrup->cinsiyet_sarti) {
                throw ValidationException::withMessages([
                    'kisi_id' => 'Bu grup yalnızca '.$kresGrup->cinsiyet_sarti->label().' öğrenciler içindir.',
                ]);
            }
        }

        $this->kayitKriteriniKontrolEt($kresGrup, $kisi);

        $durumKod = KresBasvuruDurum::query()->whereKey($validated['durum_id'])->value('kod');
        if ($durumKod !== 'yedek') {
            $validated['yedek_sira'] = null;
        }

        KresBasvuru::query()->create([
            ...$validated,
            'grup_id' => $kresGrup->id,
            'olusturan_id' => $request->user()?->id,
        ]);

        return $this->islemYaniti($request, $kresOkul, $kresGrup, 'Başvuru kaydı oluşturuldu.');
    }

    public function updateDurum(
        Request $request,
        KresOkul $kresOkul,
        KresGrup $kresGrup,
        KresBasvuru $kresBasvuru,
    ): Response {
        abort_unless($kresGrup->okul_id === $kresOkul->id, 404);
        abort_unless($kresBasvuru->grup_id === $kresGrup->id, 404);

        $validated = $request->validate([
            'durum_id' => [
                'required',
                'integer',
                Rule::exists('kres_basvuru_durumlari', 'id')->where(fn ($q) => $q->where('aktif', true)),
            ],
            'yedek_sira' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $durumKod = KresBasvuruDurum::query()->whereKey($validated['durum_id'])->value('kod');
        $kresBasvuru->update([
            'durum_id' => $validated['durum_id'],
            'yedek_sira' => $durumKod === 'yedek' ? ($validated['yedek_sira'] ?? $kresBasvuru->yedek_sira) : null,
        ]);

        return $this->islemYaniti($request, $kresOkul, $kresGrup, 'Başvuru durumu güncellendi.');
    }

    public function destroy(Request $request, KresOkul $kresOkul, KresGrup $kresGrup, KresBasvuru $kresBasvuru): Response
    {
        abort_unless($kresGrup->okul_id === $kresOkul->id, 404);
        abort_unless($kresBasvuru->grup_id === $kresGrup->id, 404);

        $ad = $kresBasvuru->kisi?->tam_adi ?: 'Başvuru';
        $kresBasvuru->delete();

        return $this->islemYaniti($request, $kresOkul, $kresGrup, $ad.' başvurusu listeden kaldırıldı.');
    }

    private function islemYaniti(Request $request, KresOkul $okul, KresGrup $grup, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->route('kres.gruplar.show', [$okul, $grup])
            ->with('success', $message);
    }

    private function kayitKriteriniKontrolEt(KresGrup $grup, ?Kisi $kisi): void
    {
        if ($grup->dogumAraligiKullaniliyorMu()) {
            $this->dogumTarihiKriteriniKontrolEt($grup, $kisi);

            return;
        }

        $this->yasKriteriniKontrolEt($grup, $kisi);
    }

    private function dogumTarihiKriteriniKontrolEt(KresGrup $grup, ?Kisi $kisi): void
    {
        if ($grup->dogumTarihiAraliginaUygunMu($kisi?->dogum_tarihi)) {
            return;
        }

        $etiket = $grup->kriterEtiketi();
        if ($kisi?->dogum_tarihi === null) {
            throw ValidationException::withMessages([
                'kisi_id' => 'Seçilen kişinin doğum tarihi kayıtlı değil. Bu grup '.$etiket.' içindir.',
            ]);
        }

        throw ValidationException::withMessages([
            'kisi_id' => 'Seçilen kişinin doğum tarihi '.$kisi->dogum_tarihi->format('d.m.Y').'. Bu grup '.$etiket.' içindir.',
        ]);
    }

    private function yasKriteriniKontrolEt(KresGrup $grup, ?Kisi $kisi): void
    {
        $yas = $kisi?->dogum_tarihi !== null ? (int) $kisi->dogum_tarihi->age : null;

        if ($grup->yasKriterineUygunMu($yas)) {
            return;
        }

        $etiket = $grup->kriterEtiketi();
        if ($yas === null) {
            throw ValidationException::withMessages([
                'kisi_id' => 'Seçilen kişinin doğum tarihi kayıtlı değil. Bu grup '.$etiket.' içindir.',
            ]);
        }

        throw ValidationException::withMessages([
            'kisi_id' => 'Seçilen kişi '.$yas.' yaşında. Bu grup '.$etiket.' içindir.',
        ]);
    }

    public function kisiAra(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $items = Kisi::query()
            ->when(
                preg_match('/^\d+$/', $q),
                fn ($query) => $query->where('tc_kimlik_no', 'like', $q.'%'),
                fn ($query) => $query->where(function ($inner) use ($q) {
                    $inner->where('ad', 'like', '%'.$q.'%')
                        ->orWhere('soyad', 'like', '%'.$q.'%')
                        ->orWhereRaw("CONCAT(ad, ' ', soyad) like ?", ['%'.$q.'%']);
                }),
            )
            ->orderBy('ad')
            ->orderBy('soyad')
            ->limit(15)
            ->get(['id', 'ad', 'soyad', 'tc_kimlik_no', 'dogum_tarihi']);

        return response()->json([
            'items' => $items->map(fn (Kisi $k) => [
                'id' => $k->id,
                'ad' => $k->ad,
                'soyad' => $k->soyad,
                'tam_adi' => $k->tam_adi,
                'tc_kimlik_no' => $k->tc_kimlik_no,
                'dogum_tarihi' => $k->dogum_tarihi?->format('Y-m-d'),
                'label' => $k->tam_adi.($k->tc_kimlik_no ? ' · '.$k->tc_kimlik_no : ''),
            ])->values(),
        ]);
    }
}
