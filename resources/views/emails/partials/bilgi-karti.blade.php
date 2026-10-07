{{-- Başlıklı, etiket/değer satırlarından oluşan gri bilgi kartı. $baslik, $satirlar (etiket => değer). --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f6f8fb; border:1px solid #e3e8f0; border-radius:14px;">
    <tr>
        <td style="padding:20px 24px 6px; font-size:12px; letter-spacing:1.5px; text-transform:uppercase; font-weight:700; color:{{ $renk }};">{{ $baslik }}</td>
    </tr>
    <tr>
        <td style="padding:6px 24px 18px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px; line-height:1.5;">
                @foreach ($satirlar as $etiket => $deger)
                    <tr>
                        <td style="padding:9px 0; color:#7a869c; width:42%; border-top:{{ $loop->first ? '0' : '1px solid #e3e8f0' }};">{{ $etiket }}</td>
                        <td style="padding:9px 0; color:#0f1f33; font-weight:600; border-top:{{ $loop->first ? '0' : '1px solid #e3e8f0' }};">{{ $deger }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
