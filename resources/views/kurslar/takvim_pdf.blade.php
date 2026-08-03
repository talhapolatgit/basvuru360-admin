<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Takvim — Kurs #{{ $kurs->kurs_no }}</title>
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
            border-radius: 2px;
        }
        .lesson.is-cancelled {
            border-color: #d99;
            background: #fdeff2;
            color: #b5697a;
            text-decoration: line-through;
        }
        .lesson .iptal-tag {
            text-decoration: none;
            font-weight: bold;
        }
        .footer {
            margin-top: 8px;
            font-size: 8px;
            color: #666;
            text-align: right;
        }
    </style>
</head>
<body>
    <h1 class="title">DERS TAKVİMİ</h1>
    <p class="subtitle">
        Kurs #{{ $kurs->kurs_no }} —
        {{ $ayBaslangic->locale('tr')->isoFormat('MMMM YYYY') }}
    </p>

    <table class="meta">
        <tr>
            <td class="label">Merkez:</td>
            <td class="value">{{ $kurs->merkez?->ad ?? '—' }}</td>
            <td class="label">Alan / Branş:</td>
            <td class="value">{{ $kurs->alan?->ad ?? '—' }} / {{ $kurs->brans?->ad ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Öğretmen:</td>
            <td class="value">{{ $kurs->ogretmenAdlari() }}</td>
            <td class="label">Ders sayısı:</td>
            <td class="value">{{ $aylikDersler->count() }}</td>
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
                'dersler' => $byDate->get($key, collect()),
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
                                @foreach ($cell['dersler'] as $ders)
                                    <div class="lesson{{ $ders->iptal_edildi ? ' is-cancelled' : '' }}">
                                        {{ substr((string) $ders->baslangic_saati, 0, 5) }}–{{ substr((string) $ders->bitis_saati, 0, 5) }}
                                        @if ($ders->iptal_edildi)
                                            <span class="iptal-tag">· İPTAL</span>
                                        @endif
                                        · {{ $kurs->brans?->ad ?? ('Kurs #'.$kurs->kurs_no) }}
                                        @if ($ders->sinif)
                                            · {{ $ders->sinif }}
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
