<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Yoklama Formu — Kurs #{{ $kurs->kurs_no }}</title>
    <style>
        @page {
            margin: 18mm 12mm 16mm 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.35;
        }

        .header {
            border-bottom: 1.5px solid #222;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .title {
            font-size: 15px;
            font-weight: bold;
            margin: 0 0 4px 0;
            text-align: center;
        }

        .subtitle {
            font-size: 11px;
            text-align: center;
            margin: 0 0 10px 0;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 2px 6px 2px 0;
            vertical-align: top;
        }

        .meta .label {
            color: #444;
            width: 92px;
            white-space: nowrap;
        }

        .meta .value {
            font-weight: bold;
        }

        .hint {
            margin: 8px 0 10px;
            font-size: 9px;
            color: #333;
            border: 1px solid #bbb;
            padding: 6px 8px;
            background: #f7f7f7;
        }

        table.form {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.form th,
        table.form td {
            border: 1px solid #222;
            padding: 4px 5px;
            vertical-align: middle;
        }

        table.form th {
            background: #efefef;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }

        .col-no { width: 28px; text-align: center; }
        .col-ad { width: 22%; }
        .col-tc { width: 92px; text-align: center; font-family: DejaVu Sans, monospace; font-size: 9px; }
        .col-imza {
            text-align: center;
            height: 34px;
        }

        .imza-box {
            height: 28px;
        }

        .footer {
            margin-top: 18px;
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
        }

        .sign-label {
            font-size: 9px;
            color: #444;
            margin-bottom: 28px;
        }

        .sign-line {
            border-top: 1px solid #222;
            padding-top: 4px;
            font-size: 9px;
            text-align: center;
        }

        .page-note {
            margin-top: 12px;
            font-size: 8px;
            color: #666;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">YOKLAMA FORMU</h1>
        <p class="subtitle">Kurs #{{ $kurs->kurs_no }} — Fiziki İmza Cetveli</p>

        <table class="meta">
            <tr>
                <td class="label">Merkez:</td>
                <td class="value">{{ $kurs->merkez?->ad ?? '—' }}</td>
                <td class="label">Alan / Branş:</td>
                <td class="value">{{ $kurs->alan?->ad ?? '—' }} / {{ $kurs->brans?->ad ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Tarih:</td>
                <td class="value">
                    {{ $ders->tarih?->format('d.m.Y') ?? '—' }}
                    @if ($ders->tarih)
                        ({{ $ders->tarih->locale('tr')->isoFormat('dddd') }})
                    @endif
                </td>
                <td class="label">Saat:</td>
                <td class="value">
                    {{ substr((string) $ders->baslangic_saati, 0, 5) }}
                    –
                    {{ substr((string) $ders->bitis_saati, 0, 5) }}
                    ({{ $saatAdedi }} ders saati)
                </td>
            </tr>
            <tr>
                <td class="label">Sınıf:</td>
                <td class="value">{{ $ders->sinif ?: '—' }}</td>
                <td class="label">Öğretmen:</td>
                <td class="value">{{ $kurs->ogretmenAdlari() }}</td>
            </tr>
            <tr>
                <td class="label">Öğrenci:</td>
                <td class="value">{{ $yoklamaKayitlari->count() }} kişi</td>
                <td class="label">Form tarihi:</td>
                <td class="value">{{ now()->format('d.m.Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="hint">
        Öğrenci, katıldığı her ders saati için ilgili sütuna imza atar.
        Katılmadığı saatler boş bırakılır. Öğretmen formu ders sonunda imzalar.
    </div>

    <table class="form">
        <thead>
            <tr>
                <th class="col-no">#</th>
                <th class="col-ad">Öğrenci Adı Soyadı</th>
                <th class="col-tc">T.C. Kimlik No</th>
                @for ($saat = 1; $saat <= $saatAdedi; $saat++)
                    <th>Saat {{ $saat }}<br><span style="font-weight:normal;">İmza</span></th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @forelse ($yoklamaKayitlari as $index => $yoklama)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td class="col-ad">{{ $yoklama->kisi?->tam_adi ?? '—' }}</td>
                    <td class="col-tc">{{ $yoklama->kisi?->tc_kimlik_no ?? '—' }}</td>
                    @for ($s = 0; $s < $saatAdedi; $s++)
                        <td class="col-imza"><div class="imza-box"></div></td>
                    @endfor
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + $saatAdedi }}" style="text-align:center; padding:16px;">
                        Bu derse yoklama alınacak kesin kayıtlı öğrenci bulunmuyor.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td>
                <div class="sign-label">Ders öğretmeni</div>
                <div class="sign-line">Ad Soyad / İmza</div>
            </td>
            <td>
                <div class="sign-label">Kontrol eden (varsa)</div>
                <div class="sign-line">Ad Soyad / İmza</div>
            </td>
        </tr>
    </table>

    <div class="page-note">Başvuru 360 — Yoklama formu</div>
</body>
</html>
