@extends('emails.layout', [
    'onizleme' => "{$kursAdi} kursu başvurunuz onaylandı.",
    'bantUst' => 'Başvurunuz onaylandı',
    'bantBaslik' => 'Kesin kaydınız yapıldı',
    'bantAlt' => "{$kursAdi} kursuna kaydınız tamamlandı.",
    'butonUrl' => $portalUrl,
    'butonMetin' => 'Başvurularımı Görüntüle',
    'altBilgiNedeni' => "Bu e-posta, {$kurum} Başvuru Portalı'ndaki kurs başvurunuz nedeniyle otomatik olarak gönderilmiştir.",
])

@section('icerik')
    <tr>
        <td class="ic" style="padding:36px 48px 8px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#33415c;">
            <p style="margin:0 0 26px; font-size:15px; line-height:1.75; color:#4a5873;">{!! nl2br(e($mesaj)) !!}</p>

            @include('emails.partials.bilgi-karti', [
                'baslik' => 'Kurs Bilgileri',
                'satirlar' => ['Kurs' => $kursAdi, ...$bilgiler],
            ])
        </td>
    </tr>
@endsection
