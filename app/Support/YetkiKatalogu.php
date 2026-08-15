<?php

namespace App\Support;

/**
 * Sistemdeki tüm yetki kodlarının tek kaynağı.
 * Yeni yetki eklemek: buraya satır ekle + ilgili route/middleware.
 *
 * @phpstan-type YetkiTanim array{kod: string, ad: string, modul: string, aciklama?: string}
 */
class YetkiKatalogu
{
    public const MODULLER = [
        'dashboard' => 'Dashboard',
        'kurs' => 'Kurslar',
        'basvuru' => 'Başvurular',
        'etkinlik' => 'Etkinlikler',
        'etkinlik_basvuru' => 'Etkinlik Başvuruları',
        'kres' => 'Kreş Yönetimi',
        'merkez' => 'Merkezler',
        'alan' => 'Alanlar',
        'brans' => 'Branşlar',
        'sabit' => 'Sabit Tanımlar',
        'entegrasyon' => 'Entegrasyonlar',
        'genel_ayar' => 'Genel Ayarlar',
        'portal_ayar' => 'Portal Ayarları',
        'takvim' => 'Takvim',
        'log' => 'İşlem Kayıtları',
        'egitmen' => 'Eğitmenler',
        'kullanici' => 'Kullanıcılar',
        'kisi' => 'Kişiler',
        'rol' => 'Roller',
    ];

    /**
     * @return list<YetkiTanim>
     */
    public static function tumu(): array
    {
        $sira = 0;
        $items = [];

        foreach (self::tanimlar() as $tanim) {
            $items[] = $tanim + ['sira' => $sira++];
        }

        return $items;
    }

    /**
     * @return list<YetkiTanim>
     */
    public static function tanimlar(): array
    {
        return [
            ['kod' => 'dashboard.goruntule', 'ad' => 'Dashboard Görüntüle', 'modul' => 'dashboard'],

            ['kod' => 'kurs.goruntule', 'ad' => 'Kursları Görüntüle', 'modul' => 'kurs'],
            ['kod' => 'kurs.sadece_atanan', 'ad' => 'Yalnızca Kendine Atanan Kursları Görüntüle', 'modul' => 'kurs'],
            ['kod' => 'kurs.sadece_yetkili_merkez', 'ad' => 'Yalnızca Yetkilendirildiği Merkezleri Görüntüle', 'modul' => 'kurs'],
            ['kod' => 'kurs.tum_merkezler', 'ad' => 'Tüm Merkezleri Görüntüle', 'modul' => 'kurs'],
            ['kod' => 'kurs.sadece_kendi_kurum', 'ad' => 'Yalnızca Kendi Kurumunu Görüntüle', 'modul' => 'kurs'],
            ['kod' => 'kurs.tum_kurumlar', 'ad' => 'Tüm Kurumları Görüntüle', 'modul' => 'kurs'],
            ['kod' => 'kurs.olustur', 'ad' => 'Kurs Oluştur', 'modul' => 'kurs'],
            ['kod' => 'kurs.guncelle', 'ad' => 'Kurs Güncelle', 'modul' => 'kurs'],
            ['kod' => 'kurs.ogretmen_ata', 'ad' => 'Öğretmen Ata', 'modul' => 'kurs'],
            ['kod' => 'kurs.yayinla', 'ad' => 'Kurs Yayınla / Yayından Kaldır', 'modul' => 'kurs'],
            ['kod' => 'kurs.mesaj_goruntule', 'ad' => 'Mesajları Görüntüle', 'modul' => 'kurs'],
            ['kod' => 'kurs.sms', 'ad' => 'Kurs SMS Gönder', 'modul' => 'kurs'],
            ['kod' => 'kurs.eposta', 'ad' => 'Kurs E-posta Gönder', 'modul' => 'kurs'],
            ['kod' => 'kurs.yoklama_goruntule', 'ad' => 'Yoklamaları Görüntüle', 'modul' => 'kurs'],
            ['kod' => 'kurs.yoklama', 'ad' => 'Yoklama Al / Düzenle', 'modul' => 'kurs'],
            ['kod' => 'kurs.export', 'ad' => 'Kurs Excel / PDF Dışa Aktar', 'modul' => 'kurs'],

            ['kod' => 'basvuru.goruntule', 'ad' => 'Başvuruları Görüntüle', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.olustur', 'ad' => 'Başvuru Oluştur', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.guncelle', 'ad' => 'Başvuru Güncelle', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.durum_guncelle', 'ad' => 'Durum Güncelle', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.yedek_sira_guncelle', 'ad' => 'Yedek Sıra Güncelle', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.basari_guncelle', 'ad' => 'Başarı Güncelle', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.kursa_baslama_guncelle', 'ad' => 'Kursa Başlama Tarihi Güncelle', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.evrak_goruntule', 'ad' => 'Evrak Görüntüle', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.evrak_yukle', 'ad' => 'Evrak Yükle', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.evrak_sil', 'ad' => 'Evrak Sil', 'modul' => 'basvuru'],
            ['kod' => 'basvuru.export', 'ad' => 'Başvuru Excel Dışa Aktar', 'modul' => 'basvuru'],

            ['kod' => 'etkinlik.goruntule', 'ad' => 'Etkinlikleri Görüntüle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.sadece_atanan', 'ad' => 'Yalnızca Sorumlu Olduğu Etkinlikleri Görüntüle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.sadece_yetkili_merkez', 'ad' => 'Yalnızca Yetkilendirildiği Merkezleri Görüntüle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.tum_merkezler', 'ad' => 'Tüm Merkezleri Görüntüle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.sadece_kendi_kurum', 'ad' => 'Yalnızca Kendi Kurumunu Görüntüle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.tum_kurumlar', 'ad' => 'Tüm Kurumları Görüntüle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.olustur', 'ad' => 'Etkinlik Oluştur', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.guncelle', 'ad' => 'Etkinlik Güncelle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.sorumlu_ata', 'ad' => 'Sorumlu Ata', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.yayinla', 'ad' => 'Etkinlik Yayınla / Yayından Kaldır', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.mesaj_goruntule', 'ad' => 'Mesajları Görüntüle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.sms', 'ad' => 'Etkinlik SMS Gönder', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.eposta', 'ad' => 'Etkinlik E-posta Gönder', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.yoklama_goruntule', 'ad' => 'Yoklamaları Görüntüle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.yoklama', 'ad' => 'Etkinlik Yoklama Al / Düzenle', 'modul' => 'etkinlik'],
            ['kod' => 'etkinlik.export', 'ad' => 'Etkinlik Excel Dışa Aktar', 'modul' => 'etkinlik'],

            ['kod' => 'etkinlik_basvuru.goruntule', 'ad' => 'Etkinlik Başvurularını Görüntüle', 'modul' => 'etkinlik_basvuru'],
            ['kod' => 'etkinlik_basvuru.olustur', 'ad' => 'Etkinlik Başvurusu Oluştur', 'modul' => 'etkinlik_basvuru'],
            ['kod' => 'etkinlik_basvuru.guncelle', 'ad' => 'Etkinlik Başvurusu Güncelle', 'modul' => 'etkinlik_basvuru'],
            ['kod' => 'etkinlik_basvuru.yedek_sira_guncelle', 'ad' => 'Yedek Sıra Güncelle', 'modul' => 'etkinlik_basvuru'],
            ['kod' => 'etkinlik_basvuru.evrak_goruntule', 'ad' => 'Evrak Görüntüle', 'modul' => 'etkinlik_basvuru'],
            ['kod' => 'etkinlik_basvuru.evrak_yukle', 'ad' => 'Evrak Yükle', 'modul' => 'etkinlik_basvuru'],
            ['kod' => 'etkinlik_basvuru.evrak_sil', 'ad' => 'Evrak Sil', 'modul' => 'etkinlik_basvuru'],
            ['kod' => 'etkinlik_basvuru.export', 'ad' => 'Etkinlik Başvurusu Excel Dışa Aktar', 'modul' => 'etkinlik_basvuru'],

            ['kod' => 'kres.goruntule', 'ad' => 'Kreş Yönetimini Görüntüle', 'modul' => 'kres'],
            ['kod' => 'kres.donem_yonet', 'ad' => 'Kreş Dönemi Yönet', 'modul' => 'kres'],
            ['kod' => 'kres.okul_yonet', 'ad' => 'Kreş Okulu Yönet', 'modul' => 'kres'],
            ['kod' => 'kres.grup_yonet', 'ad' => 'Kreş Grubu Yönet', 'modul' => 'kres'],
            ['kod' => 'kres.soru_formu_yonet', 'ad' => 'Kreş Soru Formu Yönet', 'modul' => 'kres'],
            ['kod' => 'kres.basvuru_goruntule', 'ad' => 'Kreş Başvurularını Görüntüle', 'modul' => 'kres'],
            ['kod' => 'kres.basvuru_olustur', 'ad' => 'Kreş Başvurusu Oluştur', 'modul' => 'kres'],
            ['kod' => 'kres.basvuru_guncelle', 'ad' => 'Kreş Başvurusu Güncelle', 'modul' => 'kres'],
            ['kod' => 'kres.basvuru_durum_guncelle', 'ad' => 'Kreş Başvuru Durumu Güncelle', 'modul' => 'kres'],

            ['kod' => 'merkez.goruntule', 'ad' => 'Merkezleri Görüntüle', 'modul' => 'merkez'],
            ['kod' => 'merkez.olustur', 'ad' => 'Merkez Oluştur', 'modul' => 'merkez'],
            ['kod' => 'merkez.guncelle', 'ad' => 'Merkez Güncelle', 'modul' => 'merkez'],
            ['kod' => 'merkez.sms', 'ad' => 'Merkez SMS Gönder', 'modul' => 'merkez'],
            ['kod' => 'merkez.eposta', 'ad' => 'Merkez E-posta Gönder', 'modul' => 'merkez'],
            ['kod' => 'merkez.export', 'ad' => 'Merkez Excel Dışa Aktar', 'modul' => 'merkez'],

            ['kod' => 'alan.goruntule', 'ad' => 'Alanları Görüntüle', 'modul' => 'alan'],
            ['kod' => 'alan.olustur', 'ad' => 'Alan Oluştur', 'modul' => 'alan'],
            ['kod' => 'alan.guncelle', 'ad' => 'Alan Güncelle', 'modul' => 'alan'],
            ['kod' => 'alan.export', 'ad' => 'Alan Excel Dışa Aktar', 'modul' => 'alan'],

            ['kod' => 'brans.goruntule', 'ad' => 'Branşları Görüntüle', 'modul' => 'brans'],
            ['kod' => 'brans.olustur', 'ad' => 'Branş Oluştur', 'modul' => 'brans'],
            ['kod' => 'brans.guncelle', 'ad' => 'Branş Güncelle', 'modul' => 'brans'],
            ['kod' => 'brans.export', 'ad' => 'Branş Excel Dışa Aktar', 'modul' => 'brans'],

            ['kod' => 'sabit.goruntule', 'ad' => 'Sabit Tanımları Görüntüle', 'modul' => 'sabit'],
            ['kod' => 'sabit.olustur', 'ad' => 'Sabit Tanım Oluştur', 'modul' => 'sabit'],
            ['kod' => 'sabit.guncelle', 'ad' => 'Sabit Tanım Güncelle', 'modul' => 'sabit'],

            ['kod' => 'entegrasyon.goruntule', 'ad' => 'Entegrasyonları Görüntüle', 'modul' => 'entegrasyon'],
            ['kod' => 'entegrasyon.guncelle', 'ad' => 'Entegrasyon Ayarlarını Güncelle', 'modul' => 'entegrasyon'],

            ['kod' => 'genel_ayar.goruntule', 'ad' => 'Genel Ayarları Görüntüle', 'modul' => 'genel_ayar'],
            ['kod' => 'genel_ayar.guncelle', 'ad' => 'Genel Ayarları Güncelle', 'modul' => 'genel_ayar'],

            ['kod' => 'portal_ayar.goruntule', 'ad' => 'Portal Ayarlarını Görüntüle', 'modul' => 'portal_ayar'],
            ['kod' => 'portal_ayar.guncelle', 'ad' => 'Portal Ayarlarını Güncelle', 'modul' => 'portal_ayar'],

            ['kod' => 'takvim.goruntule', 'ad' => 'Takvimi Görüntüle', 'modul' => 'takvim'],

            ['kod' => 'log.goruntule', 'ad' => 'İşlem Kayıtlarını Görüntüle', 'modul' => 'log'],
            ['kod' => 'log.export', 'ad' => 'İşlem Kayıtları Excel Dışa Aktar', 'modul' => 'log'],

            ['kod' => 'egitmen.goruntule', 'ad' => 'Eğitmenleri Görüntüle', 'modul' => 'egitmen'],
            ['kod' => 'egitmen.olustur', 'ad' => 'Eğitmen Oluştur', 'modul' => 'egitmen'],
            ['kod' => 'egitmen.guncelle', 'ad' => 'Eğitmen Güncelle', 'modul' => 'egitmen'],
            ['kod' => 'egitmen.sms', 'ad' => 'Eğitmen SMS Gönder', 'modul' => 'egitmen'],
            ['kod' => 'egitmen.eposta', 'ad' => 'Eğitmen E-posta Gönder', 'modul' => 'egitmen'],
            ['kod' => 'egitmen.sifre', 'ad' => 'Eğitmen Şifre Gönder', 'modul' => 'egitmen'],
            ['kod' => 'egitmen.export', 'ad' => 'Eğitmen Excel Dışa Aktar', 'modul' => 'egitmen'],

            ['kod' => 'kullanici.goruntule', 'ad' => 'Kullanıcıları Görüntüle', 'modul' => 'kullanici'],
            ['kod' => 'kullanici.olustur', 'ad' => 'Kullanıcı Oluştur', 'modul' => 'kullanici'],
            ['kod' => 'kullanici.guncelle', 'ad' => 'Kullanıcı Güncelle', 'modul' => 'kullanici'],
            ['kod' => 'kullanici.sms', 'ad' => 'Kullanıcı SMS Gönder', 'modul' => 'kullanici'],
            ['kod' => 'kullanici.eposta', 'ad' => 'Kullanıcı E-posta Gönder', 'modul' => 'kullanici'],
            ['kod' => 'kullanici.sifre', 'ad' => 'Kullanıcı Şifre Gönder', 'modul' => 'kullanici'],
            ['kod' => 'kullanici.export', 'ad' => 'Kullanıcı Excel Dışa Aktar', 'modul' => 'kullanici'],

            ['kod' => 'kisi.goruntule', 'ad' => 'Kişileri Görüntüle', 'modul' => 'kisi'],
            ['kod' => 'kisi.olustur', 'ad' => 'Kişi Oluştur', 'modul' => 'kisi'],
            ['kod' => 'kisi.guncelle', 'ad' => 'Kişi Güncelle', 'modul' => 'kisi'],
            ['kod' => 'kisi.sms', 'ad' => 'Kişi SMS Gönder', 'modul' => 'kisi'],
            ['kod' => 'kisi.eposta', 'ad' => 'Kişi E-posta Gönder', 'modul' => 'kisi'],
            ['kod' => 'kisi.export', 'ad' => 'Kişi Excel Dışa Aktar', 'modul' => 'kisi'],

            ['kod' => 'rol.goruntule', 'ad' => 'Rolleri Görüntüle', 'modul' => 'rol'],
            ['kod' => 'rol.yonet', 'ad' => 'Rol Oluştur / Güncelle / Sil', 'modul' => 'rol'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function kodlar(): array
    {
        return array_column(self::tanimlar(), 'kod');
    }

    /**
     * Personel rolüne varsayılan olarak verilen yetkiler (rol yönetimi hariç hepsi).
     *
     * @return list<string>
     */
    public static function personelYetkileri(): array
    {
        return array_values(array_filter(
            self::kodlar(),
            fn (string $kod) => ! str_starts_with($kod, 'rol.')
                && $kod !== 'kurs.sadece_atanan'
                && $kod !== 'kurs.sadece_yetkili_merkez'
                && $kod !== 'kurs.sadece_kendi_kurum'
                && $kod !== 'etkinlik.sadece_atanan'
                && $kod !== 'etkinlik.sadece_yetkili_merkez'
                && $kod !== 'etkinlik.sadece_kendi_kurum'
        ));
    }

    /**
     * Öğretmen rolüne varsayılan yetkiler.
     *
     * @return list<string>
     */
    public static function ogretmenYetkileri(): array
    {
        return [
            'dashboard.goruntule',
            'kurs.goruntule',
            'kurs.sadece_atanan',
            'kurs.sadece_yetkili_merkez',
            'kurs.sadece_kendi_kurum',
            'kurs.yoklama_goruntule',
            'kurs.yoklama',
            'kurs.export',
            'basvuru.goruntule',
            'basvuru.evrak_goruntule',
            'etkinlik.goruntule',
            'etkinlik.sadece_atanan',
            'etkinlik.sadece_yetkili_merkez',
            'etkinlik.sadece_kendi_kurum',
            'etkinlik.yoklama_goruntule',
            'etkinlik.yoklama',
            'etkinlik.export',
            'etkinlik_basvuru.goruntule',
            'etkinlik_basvuru.evrak_goruntule',
            'etkinlik_basvuru.olustur',
            'takvim.goruntule',
            'egitmen.goruntule',
        ];
    }

    /**
     * Modüle göre gruplanmış yetkiler.
     *
     * @return array<string, list<YetkiTanim>>
     */
    public static function modulGruplari(): array
    {
        $gruplar = [];
        foreach (self::tanimlar() as $tanim) {
            $gruplar[$tanim['modul']][] = $tanim;
        }

        return $gruplar;
    }
}
