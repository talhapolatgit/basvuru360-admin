# KURS, ETKİNLİK VE KREŞ BAŞVURU YÖNETİM SİSTEMİ
# TEKNİK ŞARTNAMESİ

---

## 1. AMAÇ

Bu şartname; İdare tarafından yürütülen kurs, etkinlik ve kreş hizmetlerine vatandaşların internet üzerinden başvurabilmesi ve bu başvuruların İdare personeli tarafından tek merkezden yönetilebilmesi için temin edilecek web tabanlı **Başvuru Portalı ve Yönetim Paneli** yazılımının özelliklerini belirler.

## 2. KAPSAM

Yüklenici aşağıdakileri sağlayacaktır:

- Vatandaşların kullanacağı **Başvuru Portalı**,
- İdare personelinin kullanacağı **Yönetim Paneli**,
- Sistemin kurulumu ve yapılandırılması,
- SMS, e-posta ve kimlik doğrulama entegrasyonları,
- Kullanıcı eğitimleri ve kullanım kılavuzları,
- Garanti süresince bakım ve teknik destek.

## 3. GENEL ŞARTLAR

3.1. Sistem web tabanlı olacak, kullanıcı bilgisayarına ek yazılım kurulmasını gerektirmeyecektir.

3.2. Sistem yaygın kullanılan güncel internet tarayıcılarında çalışacaktır.

3.3. Portal ve Yönetim Paneli bilgisayar, tablet ve cep telefonu ekranlarına uyumlu olacaktır.

3.4. Tüm ekranlar, mesajlar ve dokümanlar Türkçe olacaktır.

3.5. Kurum adı, logo, renkler ve iletişim bilgileri Yönetim Panelinden, yazılım geliştirme gerektirmeden değiştirilebilecektir.

3.6. Sistem, İdare'nin sunucularına veya İdare'nin uygun göreceği barındırma ortamına kurulabilecektir.

3.7. Kullanıcı sayısı sınırı olmayacaktır.

## 4. GÜVENLİK

4.1. Yönetim Paneline giriş kullanıcı adı ve şifre ile yapılacak; isteğe bağlı olarak SMS veya e-posta ile gönderilen doğrulama koduyla **iki aşamalı giriş** kullanılabilecektir.

4.2. Portala giriş yöntemi İdare tarafından seçilebilecektir: T.C. Kimlik No + şifre, T.C. Kimlik No + doğum tarihi veya e-posta + şifre.

4.3. Art arda hatalı giriş denemelerinde hesaplar geçici olarak kilitlenecek, aşırı istek gönderen IP adresleri geçici olarak engellenecektir. Kilitli hesaplar yetkili personel tarafından açılabilecektir.

4.4. İdare'ye ait sabit IP adresleri **Güvenilir IP** olarak tanımlanabilecek ve bu adresler engellemeden muaf tutulacaktır.

4.5. Sistemde **rol ve yetki** yönetimi bulunacaktır. Personelin hangi ekranları görebileceği ve hangi işlemleri yapabileceği rol bazında belirlenebilecektir.

4.6. Personelin görebileceği veriler merkez ve kurum bazında sınırlandırılabilecek; eğitmenler yalnızca kendilerine atanan kursları görebilecektir.

4.7. Sistemde yapılan tüm önemli işlemler (giriş, kayıt değişiklikleri, durum güncellemeleri, mesaj gönderimleri vb.) işlemi yapan kişi, tarih ve IP bilgisiyle kayıt altına alınacaktır.

## 5. YÖNETİM PANELİ

### 5.1. Ana Sayfa ve Gösterge Paneli
- Aktif kurs, günün dersleri, onay bekleyen ve kesin kayıtlı başvuru sayıları,
- Yaklaşan dersler ve etkinlikler,
- Eğitmenler için yoklaması alınmamış dersler,
- Başvuru ve kurs istatistikleri, aylık başvuru grafikleri.

### 5.2. Kurs Yönetimi
- Kurs tanımlama: merkez, alan, branş, kurs tipi, tarih aralığı, başvuru tarihleri, kontenjan ve yedek kontenjan,
- Başvuru şartları: yaş aralığı, cinsiyet, ikamet (ilçe) şartı, istenecek evraklar,
- Haftalık ders programı girildiğinde ders günlerinin otomatik oluşturulması,
- Kursa eğitmen atama, kursu portalda yayınlama / yayından kaldırma,
- Ders iptali ve ders tarihini değiştirme,
- Ders bazında yoklama alma (Var / Yok / İzinli),
- Kursiyerlere toplu SMS ve e-posta gönderme,
- Kurs takvimi ve yoklama formu çıktısı,
- Kurs sonunda **sertifika ve katılım belgesi** üretimi; belge tasarımının panelden düzenlenebilmesi.

### 5.3. Başvuru Yönetimi
- Başvuru durumları: Onay Bekliyor, Kesin Kayıt, Yedek, İptal,
- Kontenjan dolduğunda başvuruların otomatik olarak **yedek listeye** sıra numarasıyla alınması, yedek sırasının düzenlenebilmesi,
- Durum değişikliklerinde başvurana otomatik SMS / e-posta bildirimi,
- Başvuru evraklarının görüntülenmesi ve yüklenmesi,
- 18 yaş altı başvurularda veli bilgisi,
- Kursiyerlerin başarı durumunun girilmesi,
- Personel tarafından vatandaş adına başvuru girilebilmesi,
- Ad, T.C. Kimlik No, kurs, merkez, durum ve tarih gibi kriterlerle filtreleme.

### 5.4. Etkinlik Yönetimi
- Etkinlik tanımlama: ad, tür, merkez, etkinlik yeri, tarih, başvuru tarihleri, kontenjan ve şartlar,
- Etkinliğe sorumlu personel atama,
- Etkinlik başvurularının kurslarla aynı şekilde yönetilmesi,
- Etkinlik katılım yoklaması.

### 5.5. Kreş Yönetimi
- Başvuru dönemleri, okullar ve gruplar (yaş aralığı, kontenjan),
- Okul bazında doluluk durumu,
- Döneme özel başvuru soru formu hazırlama (metin, seçim, tarih, dosya vb. soru türleri),
- Kreş başvurularının listelenmesi ve durumlarının güncellenmesi.

### 5.6. Kişi, Eğitmen ve Kullanıcı Yönetimi
- Vatandaş kayıtları, başvuru geçmişi, yoklamaları ve aile / yakın bilgileri,
- T.C. Kimlik No ile kimlik ve adres bilgilerinin otomatik doldurulması,
- Eğitmen kayıtları; atandıkları kurslar, dersler ve yoklamalar,
- Personel hesapları, rolleri ve merkez yetkileri,
- Kişilere SMS, e-posta ve yeni şifre gönderimi.

### 5.7. Takvim
- Tüm ders ve etkinliklerin aylık takvimde gösterilmesi,
- Eğitmen ve merkez bazında filtreleme, takvim çıktısı alma.

### 5.8. Tanımlar ve Ayarlar
- Merkezler, alanlar, branşlar, kurs ve etkinlik türleri, evrak türleri, iptal gerekçeleri, kurumlar,
- Bildirim mesajı metinleri, KVKK ve aydınlatma metinleri,
- Kurum bilgileri, logolar ve portal görünümü,
- Portal menüsünde gösterilecek sayfalar ve bu sayfalarda listelenecek kurs / etkinlikler,
- SMS, e-posta ve kimlik doğrulama entegrasyon ayarları,
- Güvenilir IP adresleri,
- İşlem kayıtlarının görüntülenmesi.

### 5.9. Raporlama
- Kurs, başvuru, etkinlik, kreş, kişi, eğitmen ve yoklama listelerinin Excel ile açılabilir biçimde dışa aktarılması,
- Sertifika, katılım belgesi, takvim ve yoklama formlarının PDF olarak alınması.

## 6. BAŞVURU PORTALI

### 6.1. Ana Sayfa ve Katalog
- Kurum logosu ve bilgileriyle ana sayfa,
- Kurs ve etkinliklerin listelenmesi; merkez, alan, branş, tür ve gün gibi kriterlerle filtreleme,
- Tüm kurs ve etkinliklerde hızlı arama,
- Kurs / etkinlik detayı: tarih, program, kontenjan, şartlar, gerekli evraklar ve açıklama,
- İdare'nin panelden tanımladığı özel sayfalar.

### 6.2. Kayıt ve Giriş
- T.C. Kimlik No, ad, soyad, doğum tarihi ve telefon ile üyelik,
- İdare'nin seçtiği yönteme göre giriş,
- Eksik veya hatalı bilgilerde anında uyarı.

### 6.3. Başvuru
- Kurs ve etkinliklere çevrim içi başvuru,
- Kendisi veya çocuğu / eşi adına başvuru,
- 18 yaş altı için veli bilgisi,
- Gerekli evrakların yüklenmesi,
- KVKK ve aydınlatma metni onayı,
- Yaş, cinsiyet, ikamet ve kontenjan şartlarının otomatik kontrolü,
- Kontenjan doluysa yedek listeye alınma ve sıra numarasının bildirilmesi.

### 6.4. Kreş Başvurusu
- Adım adım başvuru: veli ve öğrenci bilgileri, okul seçimi, grup seçimi, başvuru formu,
- Öğrencinin yaşına uygun okul ve grupların otomatik listelenmesi.

### 6.5. Başvurularım
- Kurs, etkinlik ve kreş başvurularının durumunun takibi,
- Yedek sıra numarasının görüntülenmesi,
- Başvuru iptali,
- Sertifika / katılım belgesinin indirilmesi.

### 6.6. Profil
- Kişisel bilgilerin görüntülenmesi,
- Telefon, e-posta ve adres bilgilerinin güncellenmesi,
- Şifre değiştirme.

## 7. ENTEGRASYONLAR

7.1. Yüklenici, İdare'nin kullandığı **SMS** hizmet sağlayıcısı ile entegrasyonu yapacaktır.

7.2. Sistem **e-posta** gönderimi için kurumsal e-posta sunucusunu (SMTP) destekleyecektir.

7.3. Yüklenici, İdare'nin yetkili olduğu **kimlik ve adres sorgulama** servisleriyle (NVİ veya dengi) entegrasyonu yapacaktır. Servislere erişim yetkisi İdare tarafından sağlanacaktır.

## 8. KİŞİSEL VERİLERİN KORUNMASI

8.1. Sistem, 6698 sayılı Kişisel Verilerin Korunması Kanunu'na uygun kullanıma imkân verecektir.

8.2. Vatandaşlardan başvuru sırasında KVKK ve aydınlatma metni onayı alınacaktır.

8.3. Kişisel verilere erişim yetki ile sınırlandırılacak ve erişimler kayıt altına alınacaktır.

8.4. Tüm veriler İdare'ye ait olacak, sözleşme sonunda İdare'ye okunabilir formatta teslim edilecektir.

## 9. KURULUM VE EĞİTİM

9.1. Yüklenici, sistemi kuracak ve İdare ile birlikte merkezler, roller, tanımlar ve portal görünümünü yapılandıracaktır.

9.2. İdare'nin mevcut verileri (varsa) sisteme aktarılacaktır.

9.3. Yönetici, personel ve eğitmenlere yönelik eğitim verilecektir.

9.4. Yönetim Paneli ve Portal için Türkçe kullanım kılavuzları teslim edilecektir.

## 10. GARANTİ VE DESTEK

10.1. Sistem, kabul tarihinden itibaren en az **12 ay** garantili olacaktır.

10.2. Garanti süresince yazılım hataları ücretsiz giderilecek, güvenlik güncellemeleri ve yeni sürümler ücretsiz sağlanacaktır.

10.3. Destek telefon ve e-posta ile verilecektir. Sistemin kullanılamaz hâle geldiği durumlarda aynı iş günü içinde müdahale edilecektir.

## 11. KABUL

11.1. Kabul, İdare'nin oluşturacağı komisyon tarafından bu şartnamedeki özelliklerin kontrol edilmesiyle yapılacaktır.

11.2. Kurulum, entegrasyonlar, eğitim ve kılavuzlar tamamlanmadan kabul yapılmayacaktır.
