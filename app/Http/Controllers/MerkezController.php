<?php

namespace App\Http\Controllers;

use App\Enums\EtkinlikDurum;
use App\Enums\KursDurum;
use App\Models\BasvuruDurum;
use App\Models\Il;
use App\Models\KursBasvuru;
use App\Models\KursEpostaGonderim;
use App\Models\KursSmsGonderim;
use App\Models\Merkez;
use App\Services\Email\EmailSender;
use App\Services\LogKaydedici;
use App\Services\Sms\PhoneNormalizer;
use App\Services\Sms\SmsSender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MerkezController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$merkezler, $sort, $direction, $filters] = $this->search($request);

        $viewData = [
            'merkezler' => $merkezler,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];

        if ($request->ajax() || $request->boolean('ajax')) {
            return view('merkezler._results', $viewData);
        }

        return view('merkezler.index', $viewData + $this->formLookups() + [
            'ozet' => $this->ozet(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validated($request);
        $merkez = Merkez::query()->create($validated);

        LogKaydedici::kaydet(
            islem: 'merkez.olusturuldu',
            aciklama: '"'.$merkez->ad.'" merkezi oluşturuldu.',
            konu: $merkez,
            yeni: ['ad' => $merkez->ad, 'aktif' => (bool) $merkez->aktif],
            konuAdi: $merkez->ad,
        );

        $message = "\"{$merkez->ad}\" merkezi başarıyla oluşturuldu.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('merkezler.index')->with('success', $message);
    }

    public function update(Request $request, Merkez $merkez): RedirectResponse|JsonResponse
    {
        $onceki = ['ad' => $merkez->ad, 'aktif' => (bool) $merkez->aktif];

        $validated = $this->validated($request, $merkez);
        $merkez->update($validated);

        $yeni = ['ad' => $merkez->ad, 'aktif' => (bool) $merkez->aktif];
        if ($onceki !== $yeni) {
            LogKaydedici::kaydet(
                islem: 'merkez.guncellendi',
                aciklama: '"'.$merkez->ad.'" merkezi güncellendi.',
                konu: $merkez,
                eski: $onceki,
                yeni: $yeni,
                konuAdi: $merkez->ad,
            );
        }

        $message = "\"{$merkez->ad}\" merkezi başarıyla güncellendi.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('merkezler.index')->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        if (! $request->filled('durum')) {
            $request->merge(['durum' => 'tumu']);
        }

        [$merkezler] = $this->search($request, paginate: false);

        $filename = 'merkezler-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($merkezler) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Ad', 'Aktif Kurs Sayısı', 'Durum', 'Oluşturma Tarihi'], ';');

            $merkezler->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $merkez) {
                    fputcsv($handle, [
                        $merkez->ad,
                        $merkez->aktif_kurs_sayisi,
                        $merkez->aktif ? 'Aktif' : 'Pasif',
                        $merkez->created_at?->format('d.m.Y H:i') ?? '',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Merkez $merkez): View
    {
        $kurslar = $merkez->kurslar()
            ->with(['alan', 'brans'])
            ->where('durum', KursDurum::Aktif)
            ->orderByDesc('id')
            ->get();

        $etkinlikler = $merkez->etkinlikler()
            ->with(['etkinlikTipi'])
            ->where('durum', EtkinlikDurum::Aktif)
            ->orderByDesc('id')
            ->get();

        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        $aktifOgrenciSayisi = ($kesinKayitId && $kurslar->isNotEmpty())
            ? KursBasvuru::query()
                ->whereIn('kurs_id', $kurslar->pluck('id'))
                ->where('durum_id', $kesinKayitId)
                ->count()
            : 0;

        return view('merkezler.show', $this->formLookups() + [
            'merkez' => $merkez,
            'kurslar' => $kurslar,
            'etkinlikler' => $etkinlikler,
            'aktifKursSayisi' => $kurslar->count(),
            'aktifEtkinlikSayisi' => $etkinlikler->count(),
            'toplamKursSayisi' => $merkez->kurslar()->count(),
            'aktifOgrenciSayisi' => $aktifOgrenciSayisi,
        ]);
    }

    public function sendSms(Request $request, Merkez $merkez, SmsSender $smsSender): JsonResponse
    {
        $validated = $request->validate([
            'mesaj' => ['required', 'string', 'min:1', 'max:480'],
        ], [
            'mesaj.required' => 'SMS metni zorunludur.',
            'mesaj.max' => 'SMS metni en fazla 480 karakter olabilir.',
        ]);

        $basvurular = $this->kesinKayitliBasvurular($merkez);
        if ($basvurular->isEmpty()) {
            throw ValidationException::withMessages([
                'mesaj' => 'Bu merkezin aktif kurslarında kesin kayıtlı öğrenci bulunamadı.',
            ]);
        }

        $gonderilen = 0;
        $atlanan = 0;

        foreach ($basvurular->groupBy('kurs_id') as $kursId => $grup) {
            $detay = [];
            $kGonderilen = 0;
            $kAtlanan = 0;

            foreach ($grup as $basvuru) {
                $ad = $this->basvuruAd($basvuru);
                $telefon = $this->basvuruTelefon($basvuru);

                if (! $telefon) {
                    $kAtlanan++;
                    $detay[] = ['basvuru_id' => $basvuru->id, 'ad' => $ad, 'telefon' => null, 'durum' => 'atlandi', 'hata' => 'Telefon yok'];
                    continue;
                }

                $kisisel = $this->mesajKisisellestir($validated['mesaj'], $ad);
                if (mb_strlen($kisisel) > 480) {
                    $kAtlanan++;
                    $detay[] = ['basvuru_id' => $basvuru->id, 'ad' => $ad, 'telefon' => $telefon, 'durum' => 'atlandi', 'hata' => 'Kişiselleştirilmiş mesaj 480 karakteri aşıyor', 'mesaj' => $kisisel];
                    continue;
                }

                $sonuc = $smsSender->send($telefon, $kisisel, [
                    'kurs_id' => (int) $kursId,
                    'basvuru_id' => $basvuru->id,
                    'gonderen_id' => $request->user()?->id,
                ]);

                if ($sonuc['ok'] ?? false) {
                    $kGonderilen++;
                    $detay[] = ['basvuru_id' => $basvuru->id, 'ad' => $ad, 'telefon' => $telefon, 'durum' => 'gonderildi', 'mesaj' => $kisisel];
                } else {
                    $kAtlanan++;
                    $detay[] = ['basvuru_id' => $basvuru->id, 'ad' => $ad, 'telefon' => $telefon, 'durum' => 'atlandi', 'hata' => $sonuc['message'] ?? 'Gönderilemedi', 'mesaj' => $kisisel];
                }
            }

            KursSmsGonderim::query()->create([
                'kurs_id' => (int) $kursId,
                'gonderen_id' => $request->user()?->id,
                'mesaj' => $validated['mesaj'],
                'kapsam' => 'secilen',
                'basvuru_durum_kod' => 'kesin_kayit',
                'toplam' => $grup->count(),
                'gonderilen' => $kGonderilen,
                'atlanan' => $kAtlanan,
                'detay' => $detay,
            ]);

            $gonderilen += $kGonderilen;
            $atlanan += $kAtlanan;
        }

        $mesaj = "{$gonderilen} SMS gönderildi";
        if ($atlanan > 0) {
            $mesaj .= ", {$atlanan} kayıt atlandı";
        }
        $mesaj .= '.';

        return response()->json(['message' => $mesaj]);
    }

    public function sendEposta(Request $request, Merkez $merkez, EmailSender $emailSender): JsonResponse
    {
        $validated = $request->validate([
            'konu' => ['required', 'string', 'min:1', 'max:200'],
            'mesaj' => ['required', 'string', 'min:1', 'max:5000'],
        ], [
            'konu.required' => 'E-posta konusu zorunludur.',
            'konu.max' => 'E-posta konusu en fazla 200 karakter olabilir.',
            'mesaj.required' => 'E-posta metni zorunludur.',
            'mesaj.max' => 'E-posta metni en fazla 5000 karakter olabilir.',
        ]);

        $basvurular = $this->kesinKayitliBasvurular($merkez);
        if ($basvurular->isEmpty()) {
            throw ValidationException::withMessages([
                'mesaj' => 'Bu merkezin aktif kurslarında kesin kayıtlı öğrenci bulunamadı.',
            ]);
        }

        $gonderilen = 0;
        $atlanan = 0;

        foreach ($basvurular->groupBy('kurs_id') as $kursId => $grup) {
            $detay = [];
            $kGonderilen = 0;
            $kAtlanan = 0;

            foreach ($grup as $basvuru) {
                $ad = $this->basvuruAd($basvuru);
                $email = $this->basvuruEmail($basvuru);

                if (! $email) {
                    $kAtlanan++;
                    $detay[] = ['basvuru_id' => $basvuru->id, 'ad' => $ad, 'email' => null, 'durum' => 'atlandi', 'hata' => 'E-posta yok'];
                    continue;
                }

                $kisiselKonu = $this->mesajKisisellestir($validated['konu'], $ad);
                $kisiselMesaj = $this->mesajKisisellestir($validated['mesaj'], $ad);

                if (mb_strlen($kisiselKonu) > 200 || mb_strlen($kisiselMesaj) > 5000) {
                    $kAtlanan++;
                    $detay[] = ['basvuru_id' => $basvuru->id, 'ad' => $ad, 'email' => $email, 'durum' => 'atlandi', 'hata' => 'Kişiselleştirilmiş içerik karakter sınırını aşıyor', 'konu' => $kisiselKonu, 'mesaj' => $kisiselMesaj];
                    continue;
                }

                $sonuc = $emailSender->send($email, $kisiselKonu, $kisiselMesaj, [
                    'kurs_id' => (int) $kursId,
                    'basvuru_id' => $basvuru->id,
                    'gonderen_id' => $request->user()?->id,
                ]);

                if ($sonuc['ok'] ?? false) {
                    $kGonderilen++;
                    $detay[] = ['basvuru_id' => $basvuru->id, 'ad' => $ad, 'email' => $email, 'durum' => 'gonderildi', 'konu' => $kisiselKonu, 'mesaj' => $kisiselMesaj];
                } else {
                    $kAtlanan++;
                    $detay[] = ['basvuru_id' => $basvuru->id, 'ad' => $ad, 'email' => $email, 'durum' => 'atlandi', 'hata' => $sonuc['message'] ?? 'Gönderilemedi', 'konu' => $kisiselKonu, 'mesaj' => $kisiselMesaj];
                }
            }

            KursEpostaGonderim::query()->create([
                'kurs_id' => (int) $kursId,
                'gonderen_id' => $request->user()?->id,
                'konu' => $validated['konu'],
                'mesaj' => $validated['mesaj'],
                'kapsam' => 'secilen',
                'basvuru_durum_kod' => 'kesin_kayit',
                'toplam' => $grup->count(),
                'gonderilen' => $kGonderilen,
                'atlanan' => $kAtlanan,
                'detay' => $detay,
            ]);

            $gonderilen += $kGonderilen;
            $atlanan += $kAtlanan;
        }

        $mesaj = "{$gonderilen} e-posta gönderildi";
        if ($atlanan > 0) {
            $mesaj .= ", {$atlanan} kayıt atlandı";
        }
        $mesaj .= '.';

        return response()->json(['message' => $mesaj]);
    }

    /**
     * Merkezin aktif kurslarındaki kesin kayıtlı başvurular.
     *
     * @return Collection<int, KursBasvuru>
     */
    private function kesinKayitliBasvurular(Merkez $merkez): Collection
    {
        $kesinKayitId = BasvuruDurum::idByKod('kesin_kayit');
        if (! $kesinKayitId) {
            return collect();
        }

        $aktifKursIds = $merkez->kurslar()
            ->where('durum', KursDurum::Aktif)
            ->pluck('id');

        if ($aktifKursIds->isEmpty()) {
            return collect();
        }

        return KursBasvuru::query()
            ->with(['kisi', 'basvuran', 'veli'])
            ->whereIn('kurs_id', $aktifKursIds)
            ->where('durum_id', $kesinKayitId)
            ->get();
    }

    private function basvuruAd(KursBasvuru $basvuru): string
    {
        return $basvuru->kisi?->tam_adi
            ?? $basvuru->basvuran?->tam_adi
            ?? ('Başvuru #'.$basvuru->id);
    }

    private function basvuruTelefon(KursBasvuru $basvuru): ?string
    {
        return PhoneNormalizer::normalize(
            $basvuru->basvuran?->telefon
                ?: $basvuru->kisi?->telefon
                ?: $basvuru->veli?->telefon
        );
    }

    private function basvuruEmail(KursBasvuru $basvuru): ?string
    {
        $email = $basvuru->basvuran?->email
            ?: $basvuru->kisi?->email
            ?: $basvuru->veli?->email;

        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function mesajKisisellestir(string $sablon, string $adSoyad): string
    {
        return str_ireplace('{ad_soyad}', $adSoyad, $sablon);
    }

    /**
     * @return array{0: LengthAwarePaginator|Builder, 1: string, 2: string, 3: array<string, mixed>}
     */
    private function search(Request $request, bool $paginate = true): array
    {
        $query = Merkez::query()
            ->kullaniciKapsami($request->user())
            ->withCount([
                'kurslar as aktif_kurs_sayisi' => fn (Builder $q) => $q->where('durum', KursDurum::Aktif),
            ]);

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            if ($q !== '') {
                $mode = (string) $request->input('q_mode', 'contains');
                match ($mode) {
                    'starts' => $query->where('ad', 'like', $q.'%'),
                    'ends' => $query->where('ad', 'like', '%'.$q),
                    'exact' => $query->where('ad', $q),
                    default => $query->where('ad', 'like', '%'.$q.'%'),
                };
            }
        }

        $durum = (string) $request->input('durum', 'tumu');
        if ($durum === 'aktif') {
            $query->where('aktif', true);
        } elseif ($durum === 'pasif') {
            $query->where('aktif', false);
        } else {
            $durum = 'tumu';
        }

        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'ad' => 'ad',
            'aktif_kurs_sayisi' => 'aktif_kurs_sayisi',
            'olusturma' => 'created_at',
        ];

        if (isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->orderBy('ad', 'asc');
            $sort = '';
        }

        $perPage = in_array((int) $request->input('per_page'), [20, 50, 100], true)
            ? (int) $request->input('per_page')
            : 20;

        $filters = $request->only(['q', 'q_mode', 'durum', 'per_page', 'sort', 'direction']);
        $filters['durum'] = $durum;

        if ($paginate) {
            return [$query->paginate($perPage)->withQueryString(), $sort, $direction, $filters];
        }

        return [$query, $sort, $direction, $filters];
    }

    /**
     * @return array<string, int>
     */
    private function ozet(): array
    {
        $base = Merkez::query()->kullaniciKapsami(request()->user());

        return [
            'toplam' => (clone $base)->count(),
            'aktif' => (clone $base)->where('aktif', true)->count(),
            'pasif' => (clone $base)->where('aktif', false)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Merkez $merkez = null): array
    {
        $validated = $request->validate([
            'ad' => [
                'required', 'string', 'max:150',
                Rule::unique('merkezler', 'ad')->ignore($merkez?->id),
            ],
            'il' => ['nullable', 'string', 'max:100'],
            'ilce' => ['nullable', 'string', 'max:100'],
            'aktif' => ['nullable', 'boolean'],
        ], [
            'ad.required' => 'Merkez adı zorunludur.',
            'ad.unique' => 'Bu isimde bir merkez zaten kayıtlı.',
        ]);

        $validated['il'] = filled($validated['il'] ?? null) ? trim((string) $validated['il']) : null;
        $validated['ilce'] = filled($validated['ilce'] ?? null) ? trim((string) $validated['ilce']) : null;
        $validated['aktif'] = $request->boolean('aktif');

        return $validated;
    }

    /**
     * @return array{iller: \Illuminate\Support\Collection<int, Il>, ilcelerByIl: array<string, list<string>>}
     */
    private function formLookups(): array
    {
        $iller = Il::query()
            ->with(['ilceler' => fn ($q) => $q->orderBy('ad')])
            ->orderBy('ad')
            ->get(['id', 'ad']);

        $ilcelerByIl = [];
        foreach ($iller as $il) {
            $ilcelerByIl[$il->ad] = $il->ilceler->pluck('ad')->values()->all();
        }

        return [
            'iller' => $iller,
            'ilcelerByIl' => $ilcelerByIl,
        ];
    }
}
