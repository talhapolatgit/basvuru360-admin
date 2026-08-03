<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Sertifikalar — Kurs #{{ $kurs->kurs_no }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1a1a1a;
        }

        .page {
            position: relative;
            width: 297mm;
            height: 210mm;
            page-break-after: always;
            overflow: hidden;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 297mm;
            height: 210mm;
            z-index: 0;
        }

        .bg img {
            width: 297mm;
            height: 210mm;
            display: block;
        }

        .fallback-frame {
            position: absolute;
            z-index: 0;
            border: 2.5pt solid #1e3a5f;
        }

        .fallback-frame-inner {
            position: absolute;
            top: 3mm;
            left: 3mm;
            right: 3mm;
            bottom: 3mm;
            border: 1.2pt solid #b8860b;
        }

        /*
         * Yazı alanı: kenarlık ölçüsü kadar içeriden başlar.
         * Başlık / imza alanları şablon kenarlığının üzerine binmez.
         */
        .content {
            position: absolute;
            z-index: 2;
            overflow: hidden;
        }

        .field {
            position: absolute;
            line-height: 1.35;
            white-space: pre-wrap;
        }

        .field-kisi {
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.15);
            padding-bottom: 2mm;
        }

        .field-baslik {
            letter-spacing: 3px;
        }

        .field-alt {
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.85;
        }

        .field-imza {
            border-top: 1px solid rgba(0, 0, 0, 0.35);
            padding-top: 2mm;
        }
    </style>
</head>
<body>
@foreach ($belgeler as $belge)
    @php
        $alanlar = $belge['alanlar'];
        $renk = $belge['renk'];
        $vurgu = $belge['vurgu'];
        $kenarlik = max(0, min(40, (float) ($belge['kenarlik_olcusu'] ?? 12)));
        $alanDegerleri = [
            'kurum' => $belge['kurum_adi'],
            'baslik' => $belge['baslik'],
            'alt_baslik' => $belge['alt_baslik'],
            'kisi' => $belge['kisi_adi'],
            'metin' => $belge['metin'],
            'detay' => $belge['detay'],
            'belge_no' => $belge['belge_no'],
            'tarih' => $belge['tarih'],
            'imza_sol' => $belge['imza_sol'],
            'imza_sag' => $belge['imza_sag'],
        ];
    @endphp
    <div class="page">
        @if (! empty($belge['arka_plan']))
            <div class="bg">
                <img src="{{ $belge['arka_plan'] }}" alt="">
            </div>
        @else
            <div
                class="fallback-frame"
                style="
                    top: {{ $kenarlik }}mm;
                    left: {{ $kenarlik }}mm;
                    right: {{ $kenarlik }}mm;
                    bottom: {{ $kenarlik }}mm;
                    border-color: {{ $renk }};
                "
            >
                <div class="fallback-frame-inner" style="border-color: {{ $vurgu }};"></div>
            </div>
        @endif

        <div
            class="content"
            style="
                top: {{ $kenarlik }}mm;
                left: {{ $kenarlik }}mm;
                width: {{ 297 - (2 * $kenarlik) }}mm;
                height: {{ 210 - (2 * $kenarlik) }}mm;
            "
        >
            @foreach ($alanDegerleri as $alan => $deger)
                @if (is_array($alanlar[$alan] ?? null) && filled($deger))
                    @php
                        $stil = $alanlar[$alan];
                        $extraClass = match ($alan) {
                            'kisi' => 'field-kisi',
                            'baslik' => 'field-baslik',
                            'alt_baslik' => 'field-alt',
                            'imza_sol', 'imza_sag' => 'field-imza',
                            default => '',
                        };
                        $color = in_array($alan, ['baslik', 'kurum'], true)
                            ? $renk
                            : (in_array($alan, ['alt_baslik'], true) ? $vurgu : '#1a1a1a');
                    @endphp
                    <div
                        class="field {{ $extraClass }}"
                        style="
                            top: {{ $stil['top'] ?? '0' }};
                            left: {{ $stil['left'] ?? '0' }};
                            width: {{ $stil['width'] ?? '100%' }};
                            text-align: {{ $stil['align'] ?? 'center' }};
                            font-size: {{ $stil['size'] ?? '11pt' }};
                            font-weight: {{ $stil['weight'] ?? 'normal' }};
                            color: {{ $color }};
                        "
                    >{{ $deger }}</div>
                @endif
            @endforeach
        </div>
    </div>
@endforeach
</body>
</html>
