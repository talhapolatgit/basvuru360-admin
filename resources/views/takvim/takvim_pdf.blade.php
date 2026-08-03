<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Takvim — {{ $ayBaslangic->locale('tr')->isoFormat('MMMM YYYY') }}</title>
    <style>
        @page { margin: 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .title { font-size: 15px; font-weight: bold; text-align: center; margin: 0 0 4px; }
        .subtitle { text-align: center; margin: 0 0 10px; font-size: 11px; }
        .meta { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .meta td { padding: 2px 8px 2px 0; }
        .meta .label { color: #555; width: 90px; }
        .meta .value { font-weight: bold; }
        .cal { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .cal th, .cal td {
            border: 1px solid #333;
            vertical-align: top;
            padding: 4px;
            height: 78px;
        }
        .cal th {
            height: auto;
            background: #efefef;
            text-align: center;
            font-size: 9px;
            padding: 5px;
        }
        .day-num { font-weight: bold; font-size: 11px; display: block; margin-bottom: 3px; }
        .empty { background: #f7f7f7; }
        .lesson {
            font-size: 8px;
            line-height: 1.25;
            margin-top: 2px;
            padding: 2px 3px;
            border: 1px solid #999;
            border-left-width: 3px;
            border-radius: 2px;
        }
        .lesson-name { font-weight: bold; }
        .lesson.is-cancelled {
            background: #fdeff2;
            color: #b5697a;
        }
        .lesson.is-cancelled .lesson-name {
            text-decoration: line-through;
        }
        .lesson .iptal-tag { font-weight: bold; }
        .footer {
            margin-top: 8px;
            font-size: 8px;
            color: #666;
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        $items = $items ?? collect();
        $etkinlikGorebilir = $etkinlikGorebilir ?? false;
        $baslik = ($egitmen ?? null)
            ? trim($egitmen->ad.' '.$egitmen->soyad)
            : (($merkez ?? null) ? $merkez->ad : 'Aktif Kurslar'.($etkinlikGorebilir ? ' & Etkinlikler' : ''));
        $kursSayisi = $items->where('tip', 'kurs')->pluck('kurs_id')->unique()->filter()->count();
        $etkinlikSayisi = $items->where('tip', 'etkinlik')->pluck('etkinlik_id')->unique()->filter()->count();
    @endphp
    <h1 class="title">TAKVİM</h1>
    <p class="subtitle">
        {{ $baslik }} —
        {{ $ayBaslangic->locale('tr')->isoFormat('MMMM YYYY') }}
    </p>

    <table class="meta">
        <tr>
            <td class="label">Aktif kurs:</td>
            <td class="value">{{ $kursSayisi }}</td>
            @if ($etkinlikGorebilir)
                <td class="label">Etkinlik:</td>
                <td class="value">{{ $etkinlikSayisi }}</td>
            @endif
            <td class="label">Kayıt:</td>
            <td class="value">{{ $items->count() }}</td>
        </tr>
    </table>

    @php
        $weekDays = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
        $startOffset = $ayBaslangic->dayOfWeekIso - 1; // Mon=1
        $daysInMonth = $ayBaslangic->daysInMonth;
        $cells = [];
        for ($i = 0; $i < $startOffset; $i++) {
            $cells[] = null;
        }
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $key = $ayBaslangic->copy()->day($day)->format('Y-m-d');
            $cells[] = [
                'day' => $day,
                'key' => $key,
                'items' => $byDate->get($key, collect()),
            ];
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }
        $rows = array_chunk($cells, 7);
    @endphp

    <table class="cal">
        <thead>
            <tr>
                @foreach ($weekDays as $gun)
                    <th>{{ $gun }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        @if ($cell === null)
                            <td class="empty"></td>
                        @else
                            <td>
                                <span class="day-num">{{ $cell['day'] }}</span>
                                @foreach ($cell['items'] as $item)
                                    @php
                                        $renk = $item['renk'] ?? '#999999';
                                        $ad = $item['ad'] ?? ($item['kurs_adi'] ?? '—');
                                        $iptal = ! empty($item['iptal_edildi']);
                                        $isEtkinlik = ($item['tip'] ?? '') === 'etkinlik';
                                    @endphp
                                    <div class="lesson{{ $iptal ? ' is-cancelled' : '' }}" style="border-left-color: {{ $renk }};">
                                        <span class="lesson-name">{{ $ad }}</span><br>
                                        @if ($isEtkinlik)
                                            Etkinlik
                                            @if (! empty($item['sinif']))
                                                · {{ $item['sinif'] }}
                                            @endif
                                        @else
                                            {{ $item['baslangic'] ?? '' }}–{{ $item['bitis'] ?? '' }}
                                            @if (! empty($item['sinif']))
                                                · {{ $item['sinif'] }}
                                            @endif
                                        @endif
                                        @if ($iptal)
                                            <span class="iptal-tag">· İPTAL</span>
                                        @endif
                                        @if (! empty($item['merkez']))
                                            · {{ $item['merkez'] }}
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Başvuru 360 — Takvim PDF · {{ now()->format('d.m.Y H:i') }}</div>
</body>
</html>
