<?php

/**
 * Entegrasyon türleri ve sağlayıcı kataloğu.
 *
 * Aynı türe birden fazla sağlayıcı eklenebilir; aktif olan
 * Entegrasyonlar ekranından seçilir (entegrasyon_ayarlari tablosu).
 *
 * Sağlayıcı `alanlar` anahtarı isteğe bağlıdır; varsa Entegrasyonlar
 * ekranında ayar formu gösterilir ve `entegrasyon_ayarlari.ayarlar`
 * JSON alanında saklanır.
 */

return [

    'turler' => [
        'sms' => [
            'ad' => 'SMS Entegrasyonu',
            'aciklama' => 'Toplu ve tekil SMS gönderiminde kullanılacak sağlayıcı.',
        ],
        'eposta' => [
            'ad' => 'E-posta Entegrasyonu',
            'aciklama' => 'Toplu ve tekil e-posta gönderiminde kullanılacak sağlayıcı.',
        ],
        'kimlik_sorgulama' => [
            'ad' => 'Kimlik Sorgulama Entegrasyonu',
            'aciklama' => 'T.C. kimlik doğrulama ve kişi bilgisi sorgusunda kullanılacak sağlayıcı.',
        ],
        'adres_sorgulama' => [
            'ad' => 'Adres Sorgulama Entegrasyonu',
            'aciklama' => 'Kişi adres bilgisi sorgusunda kullanılacak sağlayıcı.',
        ],
    ],

    'saglayicilar' => [

        'demo_sms' => [
            'tur' => 'sms',
            'ad' => 'Demo SMS',
            'aciklama' => 'Gerçek SMS göndermez; mesajları uygulama loguna yazar. Geliştirme ve test için uygundur.',
        ],

        'demo_eposta' => [
            'tur' => 'eposta',
            'ad' => 'Demo E-posta',
            'aciklama' => 'Gerçek e-posta göndermez; mesajları uygulama loguna yazar. Geliştirme ve test için uygundur.',
        ],

        'smtp' => [
            'tur' => 'eposta',
            'ad' => 'SMTP',
            'aciklama' => 'Standart SMTP sunucusu üzerinden e-posta gönderir.',
            'alanlar' => [
                'host' => [
                    'etiket' => 'SMTP Host',
                    'tip' => 'text',
                    'zorunlu' => true,
                    'placeholder' => 'smtp.ornek.com',
                ],
                'port' => [
                    'etiket' => 'Port',
                    'tip' => 'number',
                    'zorunlu' => true,
                    'varsayilan' => '587',
                    'placeholder' => '587',
                ],
                'encryption' => [
                    'etiket' => 'Şifreleme',
                    'tip' => 'select',
                    'zorunlu' => true,
                    'varsayilan' => 'tls',
                    'secenekler' => [
                        'tls' => 'TLS',
                        'ssl' => 'SSL',
                        '' => 'Yok',
                    ],
                ],
                'username' => [
                    'etiket' => 'Kullanıcı adı',
                    'tip' => 'text',
                    'zorunlu' => false,
                    'placeholder' => 'kullanici@ornek.com',
                ],
                'password' => [
                    'etiket' => 'Şifre',
                    'tip' => 'password',
                    'zorunlu' => false,
                    'gizli' => true,
                ],
                'from_address' => [
                    'etiket' => 'Gönderen e-posta',
                    'tip' => 'email',
                    'zorunlu' => true,
                    'placeholder' => 'noreply@ornek.com',
                ],
                'from_name' => [
                    'etiket' => 'Gönderen adı',
                    'tip' => 'text',
                    'zorunlu' => false,
                    'placeholder' => 'Başvuru360',
                ],
            ],
        ],

        'gmail_api' => [
            'tur' => 'eposta',
            'ad' => 'Gmail API',
            'aciklama' => 'Google Gmail API (OAuth) ile e-posta gönderir.',
            'alanlar' => [
                'client_id' => [
                    'etiket' => 'Client ID',
                    'tip' => 'text',
                    'zorunlu' => true,
                    'placeholder' => 'xxx.apps.googleusercontent.com',
                ],
                'client_secret' => [
                    'etiket' => 'Client Secret',
                    'tip' => 'password',
                    'zorunlu' => true,
                    'gizli' => true,
                ],
                'refresh_token' => [
                    'etiket' => 'Refresh Token',
                    'tip' => 'password',
                    'zorunlu' => true,
                    'gizli' => true,
                ],
                'from_address' => [
                    'etiket' => 'Gönderen e-posta',
                    'tip' => 'email',
                    'zorunlu' => true,
                    'placeholder' => 'hesap@gmail.com',
                ],
                'from_name' => [
                    'etiket' => 'Gönderen adı',
                    'tip' => 'text',
                    'zorunlu' => false,
                    'placeholder' => 'Başvuru360',
                ],
            ],
        ],

        'demo_kimlik' => [
            'tur' => 'kimlik_sorgulama',
            'ad' => 'Demo Kimlik Sorgulama',
            'aciklama' => 'Gerçek kimlik servisine bağlanmaz; sabit demo yanıt döner. Geliştirme ve test için uygundur.',
        ],

        'demo_adres' => [
            'tur' => 'adres_sorgulama',
            'ad' => 'Demo Adres Sorgulama',
            'aciklama' => 'Gerçek adres servisine bağlanmaz; sabit demo yanıt döner. Geliştirme ve test için uygundur.',
        ],

    ],

    'varsayilanlar' => [
        'sms' => 'demo_sms',
        'eposta' => 'demo_eposta',
        'kimlik_sorgulama' => 'demo_kimlik',
        'adres_sorgulama' => 'demo_adres',
    ],

];
