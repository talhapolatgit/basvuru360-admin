{{--
    Ortak kurum e-posta tasarımı. @extends('emails.layout', [...]) ile kullanılır:
    onizleme, bantUst, bantBaslik, bantAlt, bantIkon (HTML), butonUrl, butonMetin, altBilgiNedeni.
    Bölümler: @section('icerik') zorunlu, @section('uyari') isteğe bağlı (sarı not kutusu).
--}}
@php
    $font = "'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="tr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $konu }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            .kapsayici { width: 100% !important; }
            .ic { padding-left: 24px !important; padding-right: 24px !important; }
            .baslik { font-size: 26px !important; }
            .adim-hucre { display: block !important; width: 100% !important; padding: 0 0 12px 0 !important; }
            .kod { font-size: 34px !important; letter-spacing: 8px !important; }
        }
        a { color: {{ $renk }}; }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#eef1f6; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    @if (! empty($onizleme))
        <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#eef1f6;">{{ $onizleme }}</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef1f6;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" class="kapsayici" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 8px 30px rgba(12,33,56,0.08);">

                    {{-- Logo --}}
                    <tr>
                        <td align="center" style="padding:28px 40px 22px; background-color:#ffffff;">
                            @if ($logoCid)
                                <img src="cid:{{ $logoCid }}" alt="{{ $kurum }}" width="300" style="display:block; width:300px; max-width:100%; height:auto; border:0; outline:none;">
                            @else
                                <div style="font-family:{!! $font !!}; font-size:20px; font-weight:700; color:{{ $renk }};">{{ $kurum }}</div>
                            @endif
                        </td>
                    </tr>

                    {{-- Bant --}}
                    <tr>
                        <td bgcolor="{{ $renk }}" style="background-color:{{ $renk }}; background-image:linear-gradient(135deg, {{ $renk }} 0%, {{ $renkAcik }} 100%);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="ic" align="center" style="padding:44px 48px 40px; font-family:{!! $font !!};">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 18px;">
                                            <tr>
                                                <td width="56" height="56" align="center" valign="middle" style="width:56px; height:56px; border-radius:50%; background-color:rgba(255,255,255,0.16); border:1px solid rgba(255,255,255,0.35); font-size:26px; line-height:56px; color:#ffffff;">{!! $bantIkon ?? '&#10003;' !!}</td>
                                            </tr>
                                        </table>
                                        @if (! empty($bantUst))
                                            <div style="font-size:13px; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,0.78); font-weight:600; margin-bottom:10px;">{{ $bantUst }}</div>
                                        @endif
                                        <h1 class="baslik" style="margin:0; font-size:30px; line-height:1.25; font-weight:700; color:#ffffff;">{{ $bantBaslik }}</h1>
                                        @if (! empty($bantAlt))
                                            <p style="margin:14px 0 0; font-size:16px; line-height:1.6; color:rgba(255,255,255,0.88);">{{ $bantAlt }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @yield('icerik')

                    {{-- Buton --}}
                    @if (! empty($butonUrl))
                        <tr>
                            <td align="center" style="padding:30px 48px 6px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td align="center" bgcolor="{{ $renk }}" style="border-radius:12px; background-color:{{ $renk }};">
                                            <a href="{{ $butonUrl }}" target="_blank" style="display:inline-block; padding:15px 38px; font-family:{!! $font !!}; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">{{ $butonMetin }} &rarr;</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- Uyarı notu --}}
                    @hasSection('uyari')
                        <tr>
                            <td class="ic" style="padding:28px 48px 36px; font-family:{!! $font !!};">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fff8e6; border-left:4px solid #f2b417; border-radius:8px;">
                                    <tr>
                                        <td style="padding:14px 18px; font-size:13px; line-height:1.6; color:#6b5512;">
                                            @yield('uyari')
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @else
                        <tr><td style="height:36px; line-height:36px; font-size:0;">&nbsp;</td></tr>
                    @endif

                    {{-- Alt bilgi --}}
                    <tr>
                        <td class="ic" align="center" style="padding:26px 48px 30px; background-color:#f6f8fb; border-top:1px solid #e3e8f0; font-family:{!! $font !!};">
                            <div style="font-size:14px; font-weight:700; color:#0f1f33;">{{ $kurum }}</div>
                            @if ($adres)
                                <div style="margin-top:6px; font-size:13px; color:#7a869c;">{{ $adres }}</div>
                            @endif
                            @php
                                $iletisim = array_filter([
                                    $telefon ? '<a href="tel:'.e(preg_replace('/\s+/', '', $telefon)).'" style="color:#4a5873; text-decoration:none;">'.e($telefon).'</a>' : null,
                                    $kurumEposta ? '<a href="mailto:'.e($kurumEposta).'" style="color:#4a5873; text-decoration:none;">'.e($kurumEposta).'</a>' : null,
                                    $webSitesi ? '<a href="'.e($webSitesi).'" target="_blank" style="color:'.e($renk).'; text-decoration:none; font-weight:600;">'.e(preg_replace('#^https?://#', '', rtrim($webSitesi, '/'))).'</a>' : null,
                                ]);
                            @endphp
                            @if ($iletisim)
                                <div style="margin-top:10px; font-size:13px; color:#4a5873;">{!! implode(' &nbsp;&middot;&nbsp; ', $iletisim) !!}</div>
                            @endif
                            <div style="margin-top:18px; font-size:11px; line-height:1.6; color:#9aa4b5;">
                                {{ $altBilgiNedeni ?? "Bu e-posta, {$kurum} Başvuru Portalı tarafından otomatik olarak gönderilmiştir." }} Lütfen yanıtlamayınız.<br>
                                &copy; {{ $yil }} {{ $kurum }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
