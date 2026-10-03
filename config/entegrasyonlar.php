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
        'yakin_sorgulama' => [
            'ad' => 'Yakın Sorgulama Entegrasyonu',
            'aciklama' => 'Kişinin 1. derece yakınlarını (eşi, çocukları, anne ve babası) listelemekte kullanılacak sağlayıcı.',
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

        'gmail' => [
            'tur' => 'eposta',
            'ad' => 'Gmail',
            'aciklama' => 'Gmail SMTP sunucusu (smtp.gmail.com:587, TLS) üzerinden e-posta gönderir. Google hesabında 2 adımlı doğrulama açık olmalı ve myaccount.google.com/apppasswords adresinden oluşturulan 16 haneli uygulama şifresi kullanılmalıdır; normal hesap şifresi çalışmaz.',
            'alanlar' => [
                'email' => [
                    'etiket' => 'Gmail adresi',
                    'tip' => 'email',
                    'zorunlu' => true,
                    'placeholder' => 'hesap@gmail.com',
                ],
                'app_password' => [
                    'etiket' => 'Uygulama şifresi',
                    'tip' => 'password',
                    'zorunlu' => true,
                    'gizli' => true,
                    'placeholder' => 'abcd efgh ijkl mnop',
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

        'flexcity_kimlik' => [
            'tur' => 'kimlik_sorgulama',
            'ad' => 'Flexcity',
            'aciklama' => 'Flexcity SBS servisi üzerinden NVİ kimlik sorgulaması yapar (FindSbsKisiDtoByNvi).',
            'alanlar' => [
                'adres' => [
                    'etiket' => 'Servis adresi',
                    'tip' => 'text',
                    'zorunlu' => true,
                    'varsayilan' => 'https://servis.beyoglu.bel.tr/FlexCityUi/rest/json/sbs/FindSbsKisiDtoByNvi',
                    'placeholder' => 'https://.../FlexCityUi/rest/json/sbs/FindSbsKisiDtoByNvi',
                ],
                'authorization' => [
                    'etiket' => 'Authorization',
                    'tip' => 'password',
                    'zorunlu' => true,
                    'gizli' => true,
                ],
                'timeout' => [
                    'etiket' => 'Zaman aşımı (saniye)',
                    'tip' => 'number',
                    'zorunlu' => false,
                    'varsayilan' => '30',
                    'placeholder' => '30',
                ],
            ],
        ],

        'demo_adres' => [
            'tur' => 'adres_sorgulama',
            'ad' => 'Demo Adres Sorgulama',
            'aciklama' => 'Gerçek adres servisine bağlanmaz; sabit demo yanıt döner. Geliştirme ve test için uygundur.',
        ],

        'flexcity_adres' => [
            'tur' => 'adres_sorgulama',
            'ad' => 'Flexcity',
            'aciklama' => 'Flexcity NVİ servisi üzerinden adres sorgulaması yapar (FindAllBaseAdresDto).',
            'alanlar' => [
                'adres' => [
                    'etiket' => 'Servis adresi',
                    'tip' => 'text',
                    'zorunlu' => true,
                    'varsayilan' => 'https://servis.beyoglu.bel.tr/FlexCityUi/rest/json/nvi/FindAllBaseAdresDto',
                    'placeholder' => 'https://.../FlexCityUi/rest/json/nvi/FindAllBaseAdresDto',
                ],
                'authorization' => [
                    'etiket' => 'Authorization',
                    'tip' => 'password',
                    'zorunlu' => true,
                    'gizli' => true,
                ],
                'timeout' => [
                    'etiket' => 'Zaman aşımı (saniye)',
                    'tip' => 'number',
                    'zorunlu' => false,
                    'varsayilan' => '30',
                    'placeholder' => '30',
                ],
            ],
        ],

        'demo_yakin' => [
            'tur' => 'yakin_sorgulama',
            'ad' => 'Demo Yakın Sorgulama',
            'aciklama' => 'Gerçek servise bağlanmaz; sabit demo yakın listesi döner. Geliştirme ve test için uygundur.',
        ],

        'flexcity_yakin' => [
            'tur' => 'yakin_sorgulama',
            'ad' => 'Flexcity',
            'aciklama' => 'Flexcity SBS servisi üzerinden NVİ aile bireyleri sorgulaması yapar (FindAllSbsKisiAileBireyleriByNvi).',
            'alanlar' => [
                'adres' => [
                    'etiket' => 'Servis adresi',
                    'tip' => 'text',
                    'zorunlu' => true,
                    'varsayilan' => 'https://servis.beyoglu.bel.tr/FlexCityUi/rest/json/sbs/FindAllSbsKisiAileBireyleriByNvi',
                    'placeholder' => 'https://.../FlexCityUi/rest/json/sbs/FindAllSbsKisiAileBireyleriByNvi',
                ],
                'authorization' => [
                    'etiket' => 'Authorization',
                    'tip' => 'password',
                    'zorunlu' => true,
                    'gizli' => true,
                ],
                'timeout' => [
                    'etiket' => 'Zaman aşımı (saniye)',
                    'tip' => 'number',
                    'zorunlu' => false,
                    'varsayilan' => '30',
                    'placeholder' => '30',
                ],
            ],
        ],

    ],

    'varsayilanlar' => [
        'sms' => 'demo_sms',
        'eposta' => 'demo_eposta',
        'kimlik_sorgulama' => 'demo_kimlik',
        'adres_sorgulama' => 'demo_adres',
        'yakin_sorgulama' => 'demo_yakin',
    ],

];
