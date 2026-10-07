@extends('emails.layout', [
    'onizleme' => "Üyeliğiniz oluşturuldu. {$kurum} Başvuru Portalı'nda kurs, etkinlik ve kreş başvurularınızı artık kolayca yapabilirsiniz.",
    'bantUst' => 'Üyeliğiniz oluşturuldu',
    'bantBaslik' => "Hoş geldiniz, {$ad}!",
    'bantAlt' => "{$kurum} Başvuru Portalı ailesine katıldığınız için teşekkür ederiz.",
    'butonUrl' => $portalUrl,
    'butonMetin' => 'Portala Giriş Yap',
    'altBilgiNedeni' => "Bu e-posta, {$kurum} Başvuru Portalı'na üyeliğiniz nedeniyle otomatik olarak gönderilmiştir.",
])

@section('icerik')
    {{-- İçerik --}}
    <tr>
        <td class="ic" style="padding:36px 48px 8px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#33415c;">
            <p style="margin:0 0 16px; font-size:16px; line-height:1.7;">Sayın <strong style="color:#0f1f33;">{{ $tamAd }}</strong>,</p>
            <p style="margin:0 0 26px; font-size:15px; line-height:1.75; color:#4a5873;">
                Portal üyeliğiniz başarıyla oluşturuldu. Artık kurs, etkinlik ve kreş başvurularınızı çevrim içi olarak yapabilir, başvurularınızın durumunu tek bir yerden takip edebilirsiniz.
            </p>

            @include('emails.partials.bilgi-karti', [
                'baslik' => 'Üyelik Bilgileriniz',
                'satirlar' => array_filter([
                    'Ad Soyad' => $tamAd,
                    'T.C. Kimlik No' => $tc,
                    'E-posta' => $email,
                    'Kayıt Tarihi' => $kayitTarihi,
                ]),
            ])
        </td>
    </tr>

    {{-- Neler yapabilirsiniz --}}
    <tr>
        <td class="ic" style="padding:30px 48px 6px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
            <div style="font-size:17px; font-weight:700; color:#0f1f33; margin-bottom:16px;">Portalda neler yapabilirsiniz?</div>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    @foreach ([
                        ['1', 'Kurslar', 'Size uygun kursa birkaç adımda başvurun.'],
                        ['2', 'Etkinlikler', 'Kültür, sanat ve sosyal etkinliklere katılın.'],
                        ['3', 'Başvurularım', 'Tüm başvurularınızın durumunu takip edin.'],
                    ] as [$no, $baslik, $metin])
                        <td class="adim-hucre" width="33%" valign="top" height="100%" style="width:33%; height:100%; padding:0 {{ $loop->last ? '0' : '8px' }} 0 {{ $loop->first ? '0' : '8px' }};">
                            <table role="presentation" width="100%" height="100%" cellpadding="0" cellspacing="0" border="0" style="height:100%; border:1px solid #e3e8f0; border-radius:12px; background-color:#fbfcfe;">
                                <tr>
                                    <td valign="top" style="padding:16px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="28" height="28" align="center" valign="middle" style="width:28px; height:28px; border-radius:8px; background-color:{{ $renk }}; color:#ffffff; font-size:13px; font-weight:700; line-height:28px;">{{ $no }}</td>
                                                <td valign="middle" style="padding-left:10px; font-size:14px; font-weight:700; color:#0f1f33;">{{ $baslik }}</td>
                                            </tr>
                                        </table>
                                        <div style="margin-top:10px; font-size:13px; line-height:1.55; color:#6b778d;">{{ $metin }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
@endsection

@section('uyari')
    <strong>Bu üyeliği siz oluşturmadıysanız</strong> lütfen bu e-postayı dikkate almayın ve aşağıdaki iletişim kanallarından bize bilgi verin.
@endsection
