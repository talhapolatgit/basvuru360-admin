@extends('emails.layout', [
    'onizleme' => "Başvuru doğrulama kodunuz: {$kod}. Kod {$dakika} dakika geçerlidir.",
    'bantUst' => 'Başvuru doğrulama',
    'bantBaslik' => 'Doğrulama kodunuz hazır',
    'bantAlt' => 'Başvurunuzu tamamlamak için aşağıdaki kodu başvuru ekranına girin.',
    'bantIkon' => '&#128274;',
    'altBilgiNedeni' => "Bu e-posta, {$kurum} Başvuru Portalı'nda başlattığınız başvuru nedeniyle otomatik olarak gönderilmiştir.",
])

@section('icerik')
    <tr>
        <td class="ic" style="padding:36px 48px 8px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#33415c;">
            <p style="margin:0 0 16px; font-size:16px; line-height:1.7;">Sayın <strong style="color:#0f1f33;">{{ $tamAd }}</strong>,</p>
            <p style="margin:0 0 26px; font-size:15px; line-height:1.75; color:#4a5873;">
                Başvurunuzu tamamlamak için tek kullanımlık doğrulama kodunuz aşağıdadır.
            </p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f6f8fb; border:1px solid #e3e8f0; border-radius:14px;">
                <tr>
                    <td align="center" style="padding:22px 24px 4px; font-size:12px; letter-spacing:1.5px; text-transform:uppercase; font-weight:700; color:{{ $renk }};">Doğrulama Kodu</td>
                </tr>
                <tr>
                    <td align="center" style="padding:6px 24px 8px;">
                        <div class="kod" style="font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:40px; line-height:1.2; font-weight:700; letter-spacing:12px; color:#0f1f33; padding-left:12px;">{{ $kod }}</div>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:0 24px 22px; font-size:13px; color:#7a869c;">
                        Bu kod <strong style="color:#33415c;">{{ $dakika }} dakika</strong> geçerlidir.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
@endsection

@section('uyari')
    <strong>Kodu kimseyle paylaşmayın.</strong> Kurumumuz sizden bu kodu telefonla veya başka bir yolla asla istemez. Bu başvuruyu siz başlatmadıysanız bu e-postayı dikkate almayın ve şifrenizi değiştirin.
@endsection
