<?php

/**
 * Sertifika / katılım belgesi şablon varsayılanları.
 *
 * Çalışma zamanı ayarları Sabit Tanımlar > Sertifika Ayarları sekmesinden
 * yönetilir (veritabanı + yüklenen şablon dosyaları). Bu dosya yalnızca
 * varsayılan değerleri sağlar.
 *
 * Metinler şablon görselinin üzerine yazılır; şablon dosyasında metin olmamalıdır.
 */

return [

    'kurum_adi' => env('SERTIFIKA_KURUM_ADI', env('APP_NAME', 'Başvuru 360')),

    'hak_eden_kodlar' => [
        'sertifika_hak_etti',
        'katilim_belgesi_hak_etti',
    ],

    /*
    | Yer tutucular — belge metni ve alt metinde kullanılabilir.
    */
    'yer_tutucular' => [
        ':kisi' => 'Katılımcı adı soyadı',
        ':tc' => 'T.C. kimlik no',
        ':kurs' => 'Kurs / branş adı',
        ':kurs_no' => 'Kurs numarası',
        ':alan' => 'Alan adı',
        ':merkez' => 'Merkez adı',
        ':kurum' => 'Kurum / kuruluş adı',
        ':egitmen' => 'Eğitmen adları',
        ':sure' => 'Toplam süre (yalnızca sayı, örn. 48)',
        ':baslangic' => 'Kurs başlangıç tarihi',
        ':bitis' => 'Kurs bitiş tarihi',
        ':donem' => 'Kurs dönemi (başlangıç – bitiş)',
        ':belge_no' => 'Belge numarası',
        ':tarih' => 'Belge düzenleme tarihi',
    ],

    'sablonlar' => [

        'sertifika_hak_etti' => [
            'kod' => 'SRT',
            'baslik' => 'SERTİFİKA',
            'alt_baslik' => 'Başarı Belgesi',
            'arka_plan' => resource_path('sertifika/sablonlar/sertifika.svg'),
            'metin' => ':kisi adlı katılımcı, :kurs eğitimini başarıyla tamamlayarak bu sertifikayı almaya hak kazanmıştır.',
            'alt_metin' => 'Alan: :alan · Merkez: :merkez · Kurs dönemi: :donem · Toplam süre: :sure saat · Eğitmen: :egitmen',
            // Şablon kenarlığının kalınlığı (mm). Yazılar bu alanın dışına taşmaz.
            'kenarlik_olcusu' => 12,
            'egitmen_imzasi' => true,
            'diger_imzaci' => false,
            'diger_imzaci_unvan' => '',
            'diger_imzaci_ad_soyad' => '',
            'belge_no_yazdir' => true,
            'tarih_yazdir' => true,
            'renk' => '#1e3a5f',
            'vurgu' => '#b8860b',
            'alanlar' => [
                'kurum' => ['top' => '2%', 'left' => '4%', 'width' => '92%', 'align' => 'center', 'size' => '13pt', 'weight' => 'bold'],
                'baslik' => ['top' => '10%', 'left' => '4%', 'width' => '92%', 'align' => 'center', 'size' => '28pt', 'weight' => 'bold'],
                'alt_baslik' => ['top' => '22%', 'left' => '4%', 'width' => '92%', 'align' => 'center', 'size' => '12pt', 'weight' => 'normal'],
                'kisi' => ['top' => '34%', 'left' => '6%', 'width' => '88%', 'align' => 'center', 'size' => '22pt', 'weight' => 'bold'],
                'metin' => ['top' => '48%', 'left' => '8%', 'width' => '84%', 'align' => 'center', 'size' => '11pt', 'weight' => 'normal'],
                'detay' => ['top' => '56%', 'left' => '8%', 'width' => '84%', 'align' => 'center', 'size' => '10pt', 'weight' => 'normal'],
                'belge_no' => ['top' => '78%', 'left' => '4%', 'width' => '40%', 'align' => 'left', 'size' => '9pt', 'weight' => 'normal'],
                'tarih' => ['top' => '78%', 'left' => '56%', 'width' => '40%', 'align' => 'right', 'size' => '9pt', 'weight' => 'normal'],
                'imza_sol' => ['top' => '86%', 'left' => '6%', 'width' => '34%', 'align' => 'center', 'size' => '9pt', 'weight' => 'normal'],
                'imza_sag' => ['top' => '86%', 'left' => '60%', 'width' => '34%', 'align' => 'center', 'size' => '9pt', 'weight' => 'normal'],
            ],
        ],

        'katilim_belgesi_hak_etti' => [
            'kod' => 'KTB',
            'baslik' => 'KATILIM BELGESİ',
            'alt_baslik' => 'Katılım Belgesi',
            'arka_plan' => resource_path('sertifika/sablonlar/katilim_belgesi.svg'),
            'metin' => ':kisi adlı katılımcı, :kurs eğitimine katılarak bu belgeyi almaya hak kazanmıştır.',
            'alt_metin' => 'Alan: :alan · Merkez: :merkez · Kurs dönemi: :donem · Toplam süre: :sure saat · Eğitmen: :egitmen',
            'kenarlik_olcusu' => 12,
            'egitmen_imzasi' => true,
            'diger_imzaci' => false,
            'diger_imzaci_unvan' => '',
            'diger_imzaci_ad_soyad' => '',
            'belge_no_yazdir' => true,
            'tarih_yazdir' => true,
            'renk' => '#0f4c5c',
            'vurgu' => '#5c7a6e',
            'alanlar' => [
                'kurum' => ['top' => '2%', 'left' => '4%', 'width' => '92%', 'align' => 'center', 'size' => '13pt', 'weight' => 'bold'],
                'baslik' => ['top' => '10%', 'left' => '4%', 'width' => '92%', 'align' => 'center', 'size' => '26pt', 'weight' => 'bold'],
                'alt_baslik' => ['top' => '22%', 'left' => '4%', 'width' => '92%', 'align' => 'center', 'size' => '12pt', 'weight' => 'normal'],
                'kisi' => ['top' => '34%', 'left' => '6%', 'width' => '88%', 'align' => 'center', 'size' => '22pt', 'weight' => 'bold'],
                'metin' => ['top' => '48%', 'left' => '8%', 'width' => '84%', 'align' => 'center', 'size' => '11pt', 'weight' => 'normal'],
                'detay' => ['top' => '56%', 'left' => '8%', 'width' => '84%', 'align' => 'center', 'size' => '10pt', 'weight' => 'normal'],
                'belge_no' => ['top' => '78%', 'left' => '4%', 'width' => '40%', 'align' => 'left', 'size' => '9pt', 'weight' => 'normal'],
                'tarih' => ['top' => '78%', 'left' => '56%', 'width' => '40%', 'align' => 'right', 'size' => '9pt', 'weight' => 'normal'],
                'imza_sol' => ['top' => '86%', 'left' => '6%', 'width' => '34%', 'align' => 'center', 'size' => '9pt', 'weight' => 'normal'],
                'imza_sag' => ['top' => '86%', 'left' => '60%', 'width' => '34%', 'align' => 'center', 'size' => '9pt', 'weight' => 'normal'],
            ],
        ],

    ],

];
