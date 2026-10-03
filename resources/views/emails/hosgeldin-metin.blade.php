Hoş geldiniz, {!! $ad !!}!

Sayın {!! $tamAd !!},

{!! $kurum !!} Başvuru Portalı üyeliğiniz başarıyla oluşturuldu. Artık kurs, etkinlik ve kreş başvurularınızı çevrim içi olarak yapabilir, başvurularınızın durumunu tek bir yerden takip edebilirsiniz.

ÜYELİK BİLGİLERİNİZ
Ad Soyad: {!! $tamAd !!}
T.C. Kimlik No: {!! $tc !!}
@if ($email)
E-posta: {!! $email !!}
@endif
Kayıt Tarihi: {!! $kayitTarihi !!}
@if ($portalUrl)

Portala giriş: {!! $portalUrl !!}
@endif

Bu üyeliği siz oluşturmadıysanız lütfen bize bilgi verin.

{!! $kurum !!}
@if ($telefon)
Tel: {!! $telefon !!}
@endif
@if ($kurumEposta)
E-posta: {!! $kurumEposta !!}
@endif
@if ($webSitesi)
Web: {!! $webSitesi !!}
@endif

Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.
