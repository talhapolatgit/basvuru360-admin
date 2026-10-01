# KURS, ETKİNLİK VE KREŞ BAŞVURU YÖNETİM SİSTEMİ
# (BAŞVURU PORTALI VE YÖNETİM PANELİ)
# TEKNİK ŞARTNAMESİ

---

## 1. AMAÇ

1.1. Bu teknik şartname; İdare bünyesinde yürütülen kurs, etkinlik ve kreş hizmetlerine ait başvuruların vatandaşlar tarafından internet üzerinden yapılabilmesi, başvuruların, kontenjanların, yedek listelerin, ders programlarının, yoklamaların, belgelerin ve bildirimlerin tek merkezden yönetilebilmesi amacıyla temin edilecek web tabanlı "Başvuru Portalı ve Yönetim Paneli" yazılımının (bundan sonra "Sistem" olarak anılacaktır) teknik ve fonksiyonel özelliklerini, kurulum, eğitim, garanti ve destek şartlarını belirlemek amacıyla hazırlanmıştır.

## 2. KAPSAM

2.1. İş kapsamında Yüklenici tarafından aşağıdakiler sağlanacaktır:

a) Vatandaşa yönelik, mobil uyumlu **Başvuru Portalı** (web uygulaması),
b) İdare personeline yönelik, rol ve yetki tabanlı **Yönetim Paneli** (web uygulaması),
c) Portal ile Yönetim Paneli arasında veri alışverişini sağlayan, sürümlenmiş **Uygulama Programlama Arayüzü (API)**,
d) Sistemin İdare altyapısına veya İdare'nin belirleyeceği barındırma ortamına kurulumu ve yapılandırılması,
e) SMS, e-posta, kimlik doğrulama ve adres sorgulama entegrasyonlarının İdare'nin sağlayacağı servis bilgileriyle yapılandırılması,
f) Mevcut verilerin (varsa) Sisteme aktarılması,
g) Kullanıcı ve yönetici eğitimleri,
h) Kullanım ve teknik dokümantasyon,
i) Garanti süresince bakım, güncelleme ve teknik destek hizmetleri.

## 3. TANIMLAR VE KISALTMALAR

| Terim | Açıklama |
|---|---|
| İdare | Bu şartname kapsamında hizmeti satın alan kamu kurumu |
| Yüklenici | Sözleşme imzalanan istekli |
| Sistem | Başvuru Portalı, Yönetim Paneli ve API'nin bütünü |
| Portal | Vatandaşların kurs, etkinlik ve kreş başvurusu yaptığı web uygulaması |
| Yönetim Paneli | İdare personelinin kullandığı web tabanlı yönetim uygulaması |
| Kişi / Vatandaş | Portal üzerinden kayıt olan veya adına başvuru yapılan gerçek kişi |
| Kullanıcı | Yönetim Paneline giriş yetkisi olan İdare personeli |
| Eğitmen / Öğretmen | Kurslara atanan ve yoklama alan kullanıcı |
| Merkez | Kurs ve etkinliklerin yürütüldüğü hizmet birimi |
| Kurum | Kursu veya etkinliği düzenleyen/iş birliği yapan kuruluş |
| Kontenjan | Bir kurs, etkinlik veya kreş grubuna kabul edilebilecek asıl katılımcı sayısı |
| Yedek Kontenjan | Asıl kontenjan dolduğunda sıra numarasıyla alınabilecek başvuru sayısı |
| Kesin Kayıt | Başvurusu onaylanarak katılım hakkı kazanan başvuru durumu |
| Yoklama | Ders veya etkinlik bazında katılım kaydı |
| API | Uygulama Programlama Arayüzü |
| JWT | JSON Web Token; portal oturumlarında kullanılan imzalı erişim anahtarı |
| KVKK | 6698 sayılı Kişisel Verilerin Korunması Kanunu |
| CIDR | IP adres aralığı gösterimi (örn. 85.105.10.0/24) |
| CSV | Virgül/noktalı virgül ile ayrılmış, elektronik tablo yazılımlarında açılabilen veri dosyası |

## 4. GENEL ŞARTLAR

4.1. Sistem tamamen web tabanlı olacak; kullanıcı bilgisayarlarına ek yazılım, eklenti veya istemci kurulumu gerektirmeyecektir.

4.2. Sistem; güncel sürümdeki Chrome, Edge, Firefox ve Safari tarayıcıları veya dengi tarayıcılarda sorunsuz çalışacaktır.

4.3. Sistemin tüm arayüzleri, mesajları, bildirimleri ve dokümantasyonu Türkçe olacaktır.

4.4. Portal ve Yönetim Paneli masaüstü, tablet ve cep telefonu ekranlarına uyumlu (duyarlı / responsive) tasarıma sahip olacaktır.

4.5. Sistem, kaynak kodu erişilebilir, yaygın kullanılan açık kaynak yazılım geliştirme çatıları ve veritabanı sistemleri üzerinde geliştirilmiş olacak; lisans bedeli gerektiren üçüncü taraf bileşen kullanılması hâlinde bu bileşenlerin lisansları Yüklenici tarafından sağlanacaktır.

4.6. Sistemde kullanılan tüm tarih ve saat bilgileri Türkiye saat dilimine (Europe/Istanbul) göre gösterilecektir.

4.7. Sistem; kayıtların silinmesi yerine pasife alınması / arşivlenmesi prensibiyle çalışacak, geçmişe dönük veri bütünlüğü korunacaktır.

4.8. Sistem, İdare'nin kurumsal kimliğine (kurum adı, logo, renkler, iletişim bilgileri) Yönetim Paneli üzerinden, yazılım geliştirme gerektirmeden uyarlanabilecektir.

## 5. TEKNİK ALTYAPI VE MİMARİ GEREKSİNİMLER

### 5.1. Mimari

5.1.1. Sistem; sunucu tarafında çalışan Yönetim Paneli ve API uygulaması ile bu API'yi kullanan, tek sayfa uygulaması (SPA) mimarisinde geliştirilmiş Portal uygulamasından oluşacaktır.

5.1.2. Portal ve Yönetim Paneli birbirinden bağımsız olarak yayınlanabilecek, farklı alan adları veya alt alan adları üzerinden hizmet verebilecektir.

5.1.3. API sürümlenmiş olacak (örn. `/api/v1`), tüm yanıtlar standart bir JSON yapısında (`success`, `message`, `data`, `errors`) dönecektir.

5.1.4. API'ye yalnızca İdare'nin tanımladığı kaynaklardan (alan adlarından) erişilebilmesi için CORS izin listesi yapılandırılabilir olacaktır.

5.1.5. Sistem; sağlık kontrolü (health check) uç noktası sunacak, konteyner/orkestrasyon ortamlarında izlenebilir olacaktır.

### 5.2. Yazılım Bileşenleri

5.2.1. Sunucu tarafı; PHP 8.3 veya üzeri sürümde, güncel ve üretici desteği devam eden bir MVC yazılım çatısı veya dengi ile geliştirilmiş olacaktır.

5.2.2. Portal; TypeScript ile geliştirilmiş, bileşen tabanlı güncel bir arayüz kütüphanesi veya dengi ile oluşturulmuş olacaktır.

5.2.3. Veritabanı olarak MySQL 8 veya üzeri ya da dengi ilişkisel veritabanı yönetim sistemi kullanılacaktır. Veritabanı şeması sürüm kontrollü göç (migration) dosyalarıyla yönetilecektir.

5.2.4. PDF çıktıları (sertifika, katılım belgesi, takvim, yoklama formu) sunucu tarafında üretilecektir.

### 5.3. Kurulum ve Barındırma

5.3.1. Sistem, konteyner (Docker veya dengi) imajı olarak paketlenmiş şekilde teslim edilecek; İdare'nin sunucularına (yerinde) veya İdare'nin uygun göreceği bulut/barındırma ortamına kurulabilecektir.

5.3.2. Yönetim Paneli konteyneri; uygulama sunucusu ve web sunucusunu barındıracak, açılışta veritabanı hazır olana kadar bekleyerek şema güncellemelerini otomatik uygulayacak ve yapılandırma önbelleklerini oluşturacaktır.

5.3.3. Portal, derlenmiş statik dosyalar olarak bir web sunucusu (Nginx veya dengi) konteyneri üzerinden sunulacak; tek sayfa uygulaması yönlendirmesi, statik dosyalar için uzun süreli önbellek ve sıkıştırma (gzip) yapılandırılmış olacaktır.

5.3.4. Yüklenen dosyalar (logolar, evraklar, sertifika görselleri, profil fotoğrafları) ve günlük dosyaları kalıcı depolama alanlarında tutulacak; uygulama güncellemelerinde kaybolmayacaktır.

5.3.5. Sistem, ek bir kuyruk sunucusu veya önbellek sunucusu (Redis vb.) kurulmasını zorunlu kılmadan çalışabilecektir.

5.3.6. SSL/TLS sonlandırması İdare'nin ters vekil sunucusunda (reverse proxy) veya yük dengeleyicisinde yapılabilecek; Sistem HTTPS zorlamasını destekleyecektir.

5.3.7. İdare tarafından sağlanması önerilen asgari sunucu kaynakları: 4 vCPU, 8 GB RAM, 100 GB disk (veritabanı ve dosya depolama dâhil). Kesin kaynak ihtiyacı kullanıcı ve başvuru hacmine göre Yüklenici tarafından kurulum öncesinde yazılı olarak bildirilecektir.

## 6. GÜVENLİK GEREKSİNİMLERİ

### 6.1. Yönetim Paneli Kimlik Doğrulama

6.1.1. Yönetim Paneline giriş e-posta adresi ve şifre ile yapılacaktır.

6.1.2. Kullanıcı bazında **iki aşamalı doğrulama** tanımlanabilecektir. Seçenekler: kapalı, SMS ile doğrulama kodu, e-posta ile doğrulama kodu.

6.1.3. İki aşamalı doğrulamada:
a) 6 haneli, tek kullanımlık doğrulama kodu üretilecek, kod sunucuda özetlenmiş (hash) olarak saklanacaktır,
b) Kod 10 dakika geçerli olacak, en fazla 5 hatalı deneme yapılabilecektir,
c) Kodun yeniden gönderilmesi için en az 120 saniye beklenecektir,
d) Kodun gönderildiği telefon/e-posta ekranda maskelenmiş olarak gösterilecektir.

6.1.4. Art arda belirli sayıda (varsayılan 4) hatalı şifre girişinde kullanıcı hesabı otomatik olarak pasife alınacak ve bu durum işlem kayıtlarına yazılacaktır.

6.1.5. Başarılı girişte oturum kimliği yenilenecek (oturum sabitleme saldırılarına karşı), oturumlar belirli süre (varsayılan 120 dakika) hareketsizlik sonrasında sona erecektir.

6.1.6. Giriş formunda zorunlu alanlar ve e-posta biçimi sunucuya istek gönderilmeden tarayıcıda doğrulanacaktır.

### 6.2. Portal Kimlik Doğrulama

6.2.1. Portal giriş yöntemi Yönetim Panelinden seçilebilecektir:
a) T.C. Kimlik No + Şifre,
b) T.C. Kimlik No + Doğum Tarihi,
c) E-posta + Şifre.

6.2.2. Portal oturumları imzalı erişim anahtarı (JWT) ve yenileme anahtarı ile yönetilecektir. Erişim anahtarı süresi varsayılan 60 dakika, yenileme anahtarı süresi varsayılan 14 gün olacak ve yapılandırılabilecektir.

6.2.3. Her yenileme işleminde eski yenileme anahtarı geçersiz kılınacak (anahtar rotasyonu); çıkış yapıldığında erişim ve yenileme anahtarları kara listeye alınacaktır.

6.2.4. **Hesap kilitleme:** Bir portal hesabına 30 dakika içinde 10 hatalı giriş denemesi yapılması hâlinde hesap 2 saat süreyle geçici olarak kilitlenecek; kullanıcıya kilidin biteceği tarih ve saat bildirilecektir. Başarılı giriş hatalı deneme sayacını sıfırlayacaktır.

6.2.5. Geçici kilit, yetkili kullanıcı tarafından Yönetim Panelindeki kişi detay ekranından, onay penceresi ile kaldırılabilecek ve bu işlem kayıt altına alınacaktır.

6.2.6. Portal giriş ve kayıt formlarında zorunlu alanlar, T.C. Kimlik No uzunluğu, tarih geçerliliği ve şifre eşleşmesi sunucuya istek gönderilmeden doğrulanacaktır.

### 6.3. İstek Sınırlama (Kaba Kuvvet Koruması)

6.3.1. Sistem, IP adresi bazlı istek sınırlaması uygulayacaktır. Asgari olarak aşağıdaki uç noktalar sınırlandırılacaktır:

| Uç nokta | Varsayılan sınır |
|---|---|
| Yönetim Paneli giriş | 30 istek / dakika |
| Yönetim Paneli doğrulama kodu girişi | 30 istek / dakika |
| Yönetim Paneli doğrulama kodu yeniden gönderme | 15 istek / 10 dakika |
| Portal giriş | 30 istek / dakika |
| Portal kayıt | 30 istek / dakika |
| Portal oturum yenileme | 90 istek / dakika |
| Portal başvuru oluşturma / iptal / belge indirme | 20 istek / dakika (kullanıcı bazlı) |

6.3.2. Sınır aşıldığında kullanıcıya Türkçe ve bekleme süresini içeren bir mesaj gösterilecek; Yönetim Paneli giriş ekranında bekleme süresi boyunca giriş düğmesi pasif hâle getirilerek üzerinde geri sayım gösterilecektir.

6.3.3. **Güvenilir IP Adresleri:** Yönetim Panelinde, IP bazlı istek sınırlarından muaf tutulacak IP adresleri ve IP aralıkları (IPv4/IPv6 tekil adres veya CIDR) açıklamasıyla birlikte tanımlanabilecektir. Güvenlik amacıyla aşırı geniş aralıkların (IPv4 için /16'dan, IPv6 için /48'den geniş) tanımlanması engellenecektir. Bu özellik, aynı sabit IP'yi paylaşan kurum ağlarındaki kullanıcıların toplu olarak engellenmesini önlemek amacıyla kullanılacaktır.

### 6.4. Yetkilendirme

6.4.1. Sistem **rol tabanlı erişim denetimi** sağlayacaktır. Roller Yönetim Panelinden oluşturulabilecek, her role modül bazında gruplanmış yetkiler atanabilecektir.

6.4.2. Sistemde asgari 20 modülde 110'dan fazla ayrıntılı yetki bulunacaktır (görüntüleme, oluşturma, güncelleme, durum güncelleme, dışa aktarma, SMS/e-posta gönderme, yoklama, evrak işlemleri vb.). Yetki listesi EK-2'de yer almaktadır.

6.4.3. Bir kullanıcıya birden fazla rol atanabilecektir. Sistem rolleri (yönetici, personel, öğretmen) silinemeyecek; kullanıcısı bulunan roller silinemeyecektir. Sistemde son kalan yönetici kullanıcının yönetici rolü kaldırılamayacaktır.

6.4.4. **Veri kapsamı yetkileri:** Kurs ve etkinlik verilerine erişim aşağıdaki kapsamlarla sınırlandırılabilecektir:
a) Yalnızca kendisine atanan kurslar / sorumlu olduğu etkinlikler,
b) Yalnızca yetkilendirildiği merkezler,
c) Tüm merkezler,
d) Yalnızca kendi kurumu,
e) Tüm kurumlar.

6.4.5. Kullanıcılara birden fazla merkez için yetki verilebilecek; merkez yetkilendirmeleri ayrı bir ekrandan toplu olarak izlenebilecektir.

6.4.6. Yetki kapsamı; listeler, detay ekranları, ana sayfa sayaçları, takvim ve dışa aktarımlar dâhil tüm ekranlarda uygulanacaktır.

### 6.5. İşlem Kayıtları (Denetim İzi)

6.5.1. Sistem; giriş, çıkış, başarısız giriş, hesap bloke, kayıt oluşturma/güncelleme, durum değişikliği, yoklama, ders iptali/tarih değişikliği, SMS/e-posta gönderimi, yetki ve rol değişiklikleri, güvenilir IP değişiklikleri gibi işlemleri kalıcı olarak kayıt altına alacaktır.

6.5.2. Her işlem kaydında asgari olarak şu bilgiler tutulacaktır: işlemi yapan kullanıcı, işlem türü, açıklama, işlem yapılan kayıt, değişiklik öncesi ve sonrası veriler, IP adresi, tarayıcı, işletim sistemi, cihaz tipi, HTTP metodu, erişilen adres ve tarih/saat.

6.5.3. İşlem kayıtları arama, kurs no, işlem türü, kullanıcı ve tarih aralığına göre filtrelenebilecek ve dışa aktarılabilecektir.

6.5.4. Kayıt tutma işlemi asıl işlemi hiçbir koşulda kesintiye uğratmayacaktır.

### 6.6. Uygulama Güvenliği

6.6.1. Tüm formlarda sunucu tarafı doğrulama yapılacak; CSRF, XSS ve SQL enjeksiyonu saldırılarına karşı koruma sağlanacaktır.

6.6.2. Portalda gösterilen zengin metin içerikleri izinli etiket listesiyle temizlenecek; betik ve olay nitelikleri kaldırılacaktır.

6.6.3. Şifreler geri döndürülemez biçimde, güncel özetleme algoritmalarıyla saklanacaktır.

6.6.4. Yüklenen dosyaların türü ve boyutu sunucu tarafında doğrulanacaktır.

6.6.5. Sistem ters vekil sunucu arkasında çalışırken gerçek istemci IP adresini doğru tespit edecek şekilde yapılandırılabilecektir.

## 7. YÖNETİM PANELİ FONKSİYONEL GEREKSİNİMLERİ

### 7.1. Genel Arayüz

7.1.1. Yönetim Paneli; sol menü, üst bar, profil erişimi ve çıkış düğmesinden oluşan bir yerleşime sahip olacaktır. Menü öğeleri kullanıcının yetkilerine göre dinamik olarak gösterilecektir.

7.1.2. Listelerde; filtreleme, sıralama, sayfalama (20/50/100 kayıt), sütun seçimi ve sütun tercihlerini hatırlama özellikleri bulunacaktır.

7.1.3. Metin tabanlı numara filtrelerinde "tam eşleşme, ile başlar, ile biter, içerir" eşleşme modları desteklenecektir.

7.1.4. Tüm ana listeler Excel ile açılabilir biçimde (UTF-8 kodlu CSV) dışa aktarılabilecektir.

7.1.5. İşlem sonuçları ekranda bildirim (toast) mesajlarıyla gösterilecek; kritik işlemler onay penceresi ile yapılacaktır.

### 7.2. Ana Sayfa ve Gösterge Paneli

7.2.1. **Ana Sayfa**, kullanıcının yetki kapsamına göre hesaplanan şu bilgileri gösterecektir:
a) Sayaçlar: aktif kurs, bugünkü ders, onay bekleyen başvuru, kesin kayıt, aktif etkinlik, başvuruya açık etkinlik, etkinlik onay bekleyen, etkinlik kesin kayıt,
b) Hızlı erişim bağlantıları,
c) Önümüzdeki 7 günün dersleri ve etkinlikleri (kaydırılabilir liste),
d) Son kurs ve etkinlik başvuruları,
e) Öğretmen rolündeki kullanıcılar için **Bekleyen Yoklamalar**: saati geçmiş ancak yoklaması alınmamış son 10 ders ve doğrudan yoklama ekranına giden "Yoklama Al" düğmesi.

7.2.2. **Gösterge Paneli (Dashboard)** şu istatistikleri sunacaktır:
a) Toplamlar: kurs, aktif kurs, başvuru, kesin kayıt, etkinlik, etkinlik başvurusu, kullanıcı, öğretmen, personel, merkez, alan, branş,
b) Kurs, etkinlik ve başvuru durum dağılımları,
c) Son 6 aya ait aylık başvuru sayıları,
d) Merkez, branş ve etkinlik tipi bazında ilk 6 sıralamaları.

### 7.3. Kurs Yönetimi

7.3.1. **Kurs listesi** şu filtreleri destekleyecektir: kurs no (eşleşme modlu), alan, branş, merkez, kurum, öğretmen, kurs tipi, durum (Hazırlık, Aktif, Tamamlanan, İptal Edilen), başvuru durumu (Açık, Yakında, Kapandı, Kapalı), haftanın günleri, kayıt/başlama/bitiş tarih aralıkları. Listede özet kartları (toplam, aktif, hazırlık, başvuruya açık) bulunacaktır. Filtreler mobil cihazlarda varsayılan olarak daraltılmış gelecektir.

7.3.2. **Kurs tanımlama formu** asgari şu alanları içerecektir:
a) Merkez, alan, branş, kurs tipi, durum, kurumlar (çoklu),
b) Kontenjan, yedek kontenjan, MEB numarası, toplam kurs saati,
c) Kurs başlama ve bitiş tarihleri; başvuru başlama ve bitiş tarih-saatleri,
d) Portalda yayınlama seçeneği,
e) Başvuru koşulları: asgari/azami yaş, cinsiyet şartı, ikamet şartı (evet / hayır / kısmen) ve "kısmen" seçiminde ilçe dışı kontenjan,
f) Başvuruda istenecek evrak tipleri,
g) Zengin metin editörüyle açıklama,
h) Haftalık ders programı: gün, başlangıç-bitiş saati, ders saati, sınıf/derslik.

7.3.3. Kurs numarası numaratör ile otomatik verilecektir. Tarih tutarlılıkları (bitiş ≥ başlama, başvuru bitişi ≤ kurs bitişi, başlama gününün programdaki günlerden biri olması) sunucu tarafında denetlenecektir.

7.3.4. **Otomatik ders oturumu üretimi:** Haftalık program ve kurs tarihlerinden tek tek ders oturumları otomatik oluşturulacak; program değiştiğinde oturumlar güncellenecek, fazla oturumlar arşivlenecek, elle taşınmış dersler korunacaktır.

7.3.5. **Kurs detay ekranı** asgari şu sekmelerden oluşacaktır:
a) **Kurs Detayları:** istatistikler, koşullar, gerekli evraklar, öğretmen atama (çoklu), yayına alma / yayından kaldırma, düzenleme, SMS ve e-posta gönderimi, sertifika çıktısı,
b) **Başvurular:** durum filtreleri, sütun seçici, dışa aktarım, yeni başvuru ekleme, yedek sıra düzenleme,
c) **Ders Programı:** program tablosu ve dışa aktarım,
d) **Takvim:** takvim görünümü, dışa aktarım ve aylık PDF (A4 yatay),
e) **Yoklamalar:** ders bazında yoklama,
f) **Mesajlar:** kursa gönderilen son SMS ve e-postalar.

7.3.6. **Ders işlemleri:** Ders gerekçe girilerek iptal edilebilecek ve iptal geri alınabilecektir. Dersin tarihi değiştirilebilecek; yoklaması alınmış derslerde ve başka bir dersle çakışma olduğunda tarih değişikliği engellenecektir.

7.3.7. **Yoklama:**
a) Yoklama ders saati bazında "Var / Yok / İzinli" seçenekleri ve açıklama ile alınacaktır,
b) Yoklama listesine yalnızca kesin kayıtlı ve kursa başlama tarihi ders tarihinden önce veya aynı gün olan kursiyerler gelecektir,
c) İptal edilmiş derslerde yoklama alınamayacaktır,
d) Yoklama silinebilecek, boş yoklama formu PDF olarak alınabilecek, yoklamalar dışa aktarılabilecektir.

7.3.8. **Toplu bildirim:** Kursun katılımcılarına toplu SMS (en fazla 480 karakter, kişiye özel ad-soyad yer tutucusu ile) ve e-posta (konu ve metin) gönderilebilecektir.

7.3.9. **Sertifika ve katılım belgesi:**
a) Başarı durumu "sertifika hak etti" veya "katılım belgesi hak etti" olan kursiyerler için toplu PDF üretilecektir,
b) Belge numarası otomatik ve benzersiz üretilecektir,
c) Belge şablonu; arka plan görseli, kenarlık, renkler, imzalar, metin konumları ve 10'dan fazla yer tutucu (ad soyad, kurs adı, tarihler, saat vb.) ile Yönetim Panelinden tasarlanabilecektir.

### 7.4. Kurs Başvuruları

7.4.1. **Başvuru durumları:** Onay Bekliyor, Kesin Kayıt, Yedek, İptal. Durum geçişlerinde şu iş kuralları uygulanacaktır:
a) İptalde iptal gerekçesi zorunludur,
b) Asıl kontenjan doluyken kesin kayda geçiş engellenir,
c) Yedek kontenjan dolu veya tanımsızken yedeğe alma engellenir,
d) Kesin kayıtta kursa başlama tarihi girilir ve bu tarih kurs tarihleri içinde olmalıdır,
e) Sertifika/katılım belgesi verilmiş kesin kayıt iptal edilemez.

7.4.2. Durum değişikliklerinde başvurana SMS ve/veya e-posta bildirimi, Yönetim Panelindeki ayara göre otomatik, isteğe bağlı veya kapalı olarak gönderilecektir. Bildirim metinleri durum bazında yapılandırılabilecektir.

7.4.3. **Başarı durumu** yalnızca kesin kayıtlı başvurulara girilebilecek; sertifika ve katılım belgesi seçenekleri yalnızca "Tamamlanan" durumundaki kurslarda kullanılabilecektir.

7.4.4. **Yedek liste yönetimi:**
a) Asıl kontenjan dolduğunda yeni başvurular sıradaki yedek numarasıyla alınacak, yedek kontenjan da doluysa başvuru kabul edilmeyecektir,
b) İptal ve durum değişikliklerinde yedek sıraları otomatik yeniden numaralanacaktır,
c) Yedek sırası sürükle-bırak ile düzenlenebilecek, eşzamanlı değişikliklere karşı kontrol yapılacaktır.

7.4.5. **Evrak yönetimi:** Başvurulara PDF, JPG, PNG türünde (en fazla 5 MB) evrak yüklenebilecek, tarayıcıda görüntülenebilecek, silinebilecektir. Silme işlemi arşivleme şeklinde yapılacak, silen kullanıcı kaydedilecektir.

7.4.6. **Veli bilgisi:** 18 yaş altındaki katılımcılar için veli bilgisi zorunlu olacak; velinin T.C. Kimlik No'su katılımcınınkinden farklı olmalıdır.

7.4.7. **Başvuru listesi** şu filtreleri destekleyecektir: ad, soyad, T.C. Kimlik No, telefon, kurs no, alan, branş, merkez, kurum, öğretmen, başvuru durumu, başarı durumu, kurs durumu, başvuru ve kursa başlama tarih aralıkları. Özet sayaçları ve dışa aktarım bulunacaktır.

7.4.8. **Personel tarafından başvuru girişi:** Yetkili personel, vatandaş adına Yönetim Panelinden başvuru oluşturabilecektir. Bu ekranda T.C. Kimlik No ile kişi bulunacak veya yeni kişi oluşturulacak, kimlik ve adres sorgulama entegrasyonları kullanılabilecek, zorunlu evraklar yüklenebilecektir. Aynı kişinin aynı kursa ikinci aktif başvurusu engellenecektir.

### 7.5. Etkinlik Yönetimi ve Etkinlik Başvuruları

7.5.1. Etkinlik tanımlama formu asgari şu alanları içerecektir: etkinlik adı, zengin metin açıklama, merkez, etkinlik yeri, etkinlik tipi, durum, portalda yayınlama, başlangıç/bitiş tarih-saati, başvuru tarihleri, kontenjan ve yedek kontenjan, yaş/cinsiyet/ikamet koşulları ve ilçe dışı kontenjan, gerekli evraklar, kurumlar.

7.5.2. Etkinlik numarası otomatik verilecek, etkinliğe birden fazla sorumlu kullanıcı atanabilecektir.

7.5.3. Etkinlik detay ekranı; Etkinlik Detayları, Başvurular, Yoklama ve Mesajlar sekmelerinden oluşacaktır.

7.5.4. Etkinlik yoklaması başvuru bazında "Katıldı / Katılmadı" olarak alınacak; yoklama listesi tümü, katılanlar, katılmayanlar ve yoklaması alınmayanlar olarak dışa aktarılabilecektir.

7.5.5. Etkinlik başvurularında kontenjan, yedek liste, durum, evrak, veli ve bildirim kuralları kurs başvurularıyla aynı şekilde işleyecektir.

### 7.6. Kreş Yönetimi

7.6.1. **Dönem yönetimi:** Kreş başvuru dönemleri (ad, başlangıç ve bitiş tarihleri) tanımlanacak; aynı anda yalnızca bir dönem aktif olabilecek, dönemin portalda başvuruya açılması ayrıca yayınlama seçeneğiyle yapılacaktır.

7.6.2. **Okul yönetimi:** Kreş okulları ad, adres, telefon ve aktiflik bilgileriyle tanımlanacaktır.

7.6.3. **Grup yönetimi:** Her okul ve dönem için gruplar; yaş aralığı, kontenjan, yedek kontenjan ve cinsiyet şartı ile tanımlanacaktır.

7.6.4. **Kreş ana ekranı:** Seçilen dönem için her okulun grup sayısı, kesin kayıt, yedek, kontenjan ve doluluk oranı gösterilecektir.

7.6.5. **Grup detayı:** Grup başvuruları listelenecek, dışa aktarılabilecek, personel tarafından kişi arayarak başvuru eklenebilecek ve başvuru durumları güncellenebilecektir.

7.6.6. **Soru formu tasarımcısı:** Her dönem için başvuruda doldurulacak özel bir soru formu tasarlanabilecektir. Asgari şu soru tipleri desteklenecektir: kısa metin, uzun metin, sayı (asgari/azami değer), açılır liste, onay kutusu (asgari/azami seçim), tekli seçim, tarih, dosya, resim, T.C. Kimlik No, cep telefonu, e-posta. Sorular zorunlu/isteğe bağlı işaretlenebilecek, sıralanabilecek ve form önizlenebilecektir.

### 7.7. Kişi (Vatandaş) Yönetimi

7.7.1. Kişi kaydı; ad, soyad, T.C. Kimlik No (benzersiz), doğum tarihi, cinsiyet, doğum yeri, medeni durum, uyruk, anne ve baba adı, telefon, e-posta, il, ilçe, adres ve fotoğraf bilgilerini içerecektir.

7.7.2. Kişi listesi ad soyad, T.C. Kimlik No, telefon, e-posta, cinsiyet ve durum filtrelerini destekleyecektir.

7.7.3. **Kişi detay ekranı** asgari şu sekmelerden oluşacaktır: Başvurular (kurs ve etkinlik), Yoklamalar, Mesajlar, Aile.

7.7.4. **Aile / yakınlar:** Kişiye başka kişiler yakınlık derecesiyle (Eşi, Oğlu, Kızı, Annesi, Babası) bağlanabilecektir.

7.7.5. Kişi formlarında **kimlik sorgulama** ve **adres sorgulama** düğmeleriyle T.C. Kimlik No ve doğum tarihi üzerinden kimlik ve adres bilgileri otomatik doldurulabilecektir (bkz. Madde 9).

7.7.6. Kişi detayında portal hesabının geçici kilit durumu, kilidin bitiş zamanı ve kalan süre gösterilecek; yetkili kullanıcı kilidi kaldırabilecektir.

7.7.7. Kişiye bireysel SMS ve e-posta gönderilebilecektir.

### 7.8. Kullanıcı ve Eğitmen Yönetimi

7.8.1. Kullanıcı kaydı kişisel bilgilere ek olarak roller (en az bir), kurumlar ve iki aşamalı doğrulama tercihi içerecektir.

7.8.2. Şifre alanı boş bırakıldığında Sistem güçlü bir geçici şifre üretecek ve yalnızca bir kez gösterecektir. "Şifre gönder" işlemiyle yeni şifre üretilip SMS, e-posta veya her ikisiyle kullanıcıya iletilebilecektir.

7.8.3. Eğitmen tanımlandığında öğretmen rolü otomatik atanacaktır. Eğitmen detay ekranı; Atanan Kurslar, Dersler, Yoklamalar, Atanan Etkinlikler ve Mesajlar sekmelerinden oluşacaktır.

7.8.4. Kullanıcı ve eğitmenlere profil fotoğrafı yüklenebilecek, SMS ve e-posta gönderilebilecektir.

7.8.5. Her kullanıcı kendi profil bilgilerini, fotoğrafını ve şifresini (mevcut şifre doğrulamasıyla) güncelleyebilecektir.

### 7.9. Tanım Ekranları

7.9.1. Aşağıdaki tanımlar Yönetim Panelinden yönetilebilecek ve pasife alınabilecektir:
a) Merkezler (ad, il, ilçe; detayda aktif kurslar ve etkinlikler, merkez kursiyerlerine toplu SMS/e-posta),
b) Alanlar,
c) Branşlar (alana bağlı),
d) Kurs tipleri, etkinlik tipleri,
e) Evrak tipleri,
f) Başvuru durumları (kurs, etkinlik, kreş),
g) Başarı durumları (sertifika hak etti, katılım belgesi hak etti, devamsızlık, sınava girmedi, sınav başarısız vb.),
h) İptal gerekçeleri,
i) Kurumlar,
j) Sertifika ayarları.

7.9.2. **Başvuru ayarları** (kurs ve etkinlik için ayrı ayrı):
a) Onay, iptal ve yedek durumları için SMS gönderimi (evet / hayır / isteğe bağlı) ve SMS metinleri,
b) Aynı durumlar için e-posta gönderimi, e-posta konuları ve metinleri,
c) Vatandaşın kesin kaydını portaldan iptal edip edemeyeceği,
d) Başvuru formunda gösterilecek KVKK ve Aydınlatma metinleri (başlık ve zengin metin).

### 7.10. Takvim

7.10.1. Aylık takvim görünümünde kurs dersleri ve etkinlikler gösterilecektir.

7.10.2. Takvim ay, öğretmen ve merkez bazında filtrelenebilecek, kullanıcının yetki kapsamına göre gösterilecek ve aylık olarak PDF (A4 yatay) çıktısı alınabilecektir.

### 7.11. Genel Ayarlar

7.11.1. Kurum bilgileri: kurum adı, telefon, e-posta, il, ilçe, adres, web sitesi, site açıklaması (arama motorları için).

7.11.2. Görsel kimlik: logo, portal yan menü logosu, mobil üst bar logosu, favicon; yan menü başlığı, alt başlığı, arka plan rengi (düz veya gradyan) ve logo arka plan rengi.

7.11.3. Portal kişi ayarları: portal giriş yöntemi, yakın adına başvuru yapılabilmesi, portalda yakının elle eklenebilmesi.

### 7.12. Portal Ayarları (Portal Sayfa Yönetimi)

7.12.1. Portalda gösterilecek sayfalar Yönetim Panelinden yönetilebilecektir. Her sayfa için: başlık, açıklama, menü açıklaması, adres (URL kısa adı), menüde gösterim, yalnızca giriş yapmış kullanıcılara gösterim, ana sayfa kart logosu, kart arka plan görseli (kapla/sığdır) ve menü ikonu tanımlanabilecektir.

7.12.2. Sayfalar sıralanabilecek, menüde gösterimi tek tıkla açılıp kapatılabilecektir.

7.12.3. **İçerik kuralları:** Özel sayfalarda hangi kursların/etkinliklerin listeleneceği kurallarla belirlenebilecektir:
a) Kurslar için: tümü, belirli kurs numaraları, branş, alan, merkez,
b) Etkinlikler için: tümü, belirli etkinlik numaraları, etkinlik tipi, merkez.

7.12.4. Sistem sayfaları (Kurslar, Etkinlikler, Başvurularım, Profil, Kreş Başvuru) silinemeyecek, ancak başlık, açıklama ve görselleri düzenlenebilecektir. Sistem tarafından kullanılan adresler sayfa adresi olarak kullanılamayacaktır.

### 7.13. Entegrasyon Ayarları

7.13.1. SMS, e-posta, kimlik sorgulama ve adres sorgulama entegrasyonları Yönetim Panelinden açılıp kapatılabilecek ve aktif sağlayıcı seçilebilecektir.

7.13.2. E-posta için SMTP (sunucu, port, şifreleme, kullanıcı, şifre, gönderen adı ve adresi) ve OAuth tabanlı e-posta servisi (istemci kimliği, gizli anahtar, yenileme anahtarı) seçenekleri bulunacaktır.

7.13.3. Test/geliştirme amacıyla, gerçek gönderim yapmadan kayıt tutan deneme (demo) sağlayıcıları bulunacaktır.

7.13.4. Gönderilen tüm SMS ve e-postalar ilgili kurs, etkinlik, kişi veya kullanıcı kaydında "Mesajlar" sekmesinde görüntülenebilecektir.

## 8. BAŞVURU PORTALI FONKSİYONEL GEREKSİNİMLERİ

### 8.1. Genel

8.1.1. Portal, kurum kimliğini (logo, renkler, kurum adı, iletişim bilgileri) Yönetim Panelindeki ayarlardan otomatik olarak alacaktır.

8.1.2. Masaüstünde sabit sol menü; 900 piksel ve altındaki ekranlarda açılır-kapanır menü, yapışkan üst bar ve arama kısayolu bulunacaktır.

8.1.3. Menüde Ana Sayfa, Hızlı Arama ve Yönetim Panelinde tanımlanan sayfalar yer alacak; Başvurularım, Profil ve Kreş Başvuru sayfaları yalnızca oturum açıkken gösterilecektir. Alt kısımda kurum telefonu ve e-postası tıklanabilir bağlantı olarak yer alacaktır.

8.1.4. Veri yüklenirken iskelet (skeleton) görünümler, işlemler sırasında düğme durum metinleri gösterilecek; hata mesajları Türkçe ve anlaşılır olacaktır.

8.1.5. Oturum gerektiren sayfalara oturumsuz erişimde kullanıcı giriş sayfasına yönlendirilecek, giriş sonrası kaldığı sayfaya geri döndürülecektir.

8.1.6. Portal; ekran okuyucu etiketleri, klavye erişimi için uygun rol ve nitelikler (aria), hareket azaltma tercihine uyum gibi erişilebilirlik özelliklerini destekleyecektir.

### 8.2. Ana Sayfa

8.2.1. Ana sayfada kurum adı ve karşılama metniyle birlikte, menüde gösterilen her portal sayfası için görsel bir kart (başlık, açıklama, logo, arka plan görseli) gösterilecektir.

### 8.3. Kurs ve Etkinlik Katalogları

8.3.1. Katalog sayfalarında kurslar ve etkinlikler kartlar hâlinde listelenecektir.
a) Kurs kartı: kurs no, başvuru durumu (Başvuru açık / Yakında / Kapandı), branş, merkez ve ilçe, tarih aralığı, kontenjan doluluğu, toplam saat, kurs tipi, haftalık program,
b) Etkinlik kartı: etkinlik no, tip, durum, ad, merkez ve ilçe, tarih, kontenjan doluluğu.

8.3.2. Filtreler: arama, merkez, alan, branş (alana bağlı dinamik), kurs tipi, haftanın günleri (kurslar için); tip ve merkez (etkinlikler için). Filtre seçenekleri yalnızca sayfada listelenen kayıtlarda geçen değerlerden oluşacaktır.

8.3.3. Listeler sayfalanacak; başvurusu açık olanlar öncelikli, ardından yakında açılacaklar gösterilecektir. Yalnızca yayında ve aktif olan kayıtlar listelenecektir.

8.3.4. **Hızlı Arama:** Tek bir arama kutusundan kurs no, etkinlik no, alan, branş, tip veya etkinlik adına göre tüm katalogda arama yapılabilecek; arama adresi paylaşılabilir olacaktır.

### 8.4. Kurs ve Etkinlik Detayı

8.4.1. Kurs detayında: kurs no, branş, alan, kurs tipi, başvuru durumu, merkez, tarihler, başvuru bitiş tarihi, kontenjan, toplam saat, başvuru şartları (yaş, cinsiyet, ikamet) özeti, haftalık program, gerekli evraklar ve açıklama gösterilecektir.

8.4.2. Etkinlik detayında: etkinlik no, tip, ad, durum, merkez, etkinlik yeri, tarih, başvuru bitiş tarihi, kontenjan, şartlar, gerekli evraklar ve açıklama gösterilecektir.

8.4.3. "Başvur" düğmesi yalnızca başvuru dönemi açıkken aktif olacaktır. Kurs detayında paylaşım düğmesi (cihazın paylaşım menüsü veya bağlantıyı kopyalama) bulunacaktır.

8.4.4. Vatandaş, başvurusu bulunan kurs ve etkinliklerin detaylarını, kayıt portaldan kaldırılmış veya tamamlanmış olsa dahi görüntüleyebilecektir.

### 8.5. Kayıt ve Giriş

8.5.1. **Kayıt formu:** ad, soyad, T.C. Kimlik No, doğum tarihi, telefon zorunlu; e-posta (e-posta ile giriş yönteminde zorunlu), il, ilçe, adres, cinsiyet; şifre gerektiren yöntemlerde şifre ve şifre tekrarı. T.C. Kimlik No ve e-posta benzersiz olacaktır. Başarılı kayıt sonrası kullanıcı otomatik olarak oturum açmış olacaktır.

8.5.2. **Giriş formu:** Seçilen giriş yöntemine göre alanlar dinamik olarak gösterilecek; doğum tarihi GG.AA.YYYY maskesiyle girilecek, şifre göster/gizle düğmesi bulunacaktır. Hatalı giriş, hesap kilidi ve istek sınırı durumlarında açıklayıcı mesaj gösterilecektir.

### 8.6. Kurs ve Etkinlik Başvurusu

8.6.1. Başvuru formunda başvuranın adı ve T.C. Kimlik No'su gösterilecektir.

8.6.2. **Yakın adına başvuru:** Yönetim Panelindeki ayar açıksa 18 yaş ve üzeri başvuranlar "Kendim için" veya "Çocuğum / eşim için" seçeneğiyle başvuru yapabilecektir. Kayıtlı yakınlar listelenecek; ayar açıksa yeni yakın (yakınlık derecesi, ad, soyad, T.C. Kimlik No, doğum tarihi) eklenebilecektir. Kimlik sorgulama entegrasyonu açıksa yakının kimlik bilgileri doğrulanacaktır.

8.6.3. **Eksik profil bilgileri:** Başvuranın profilinde eksik olan telefon, e-posta, il, ilçe, adres ve (gerekiyorsa) cinsiyet bilgileri başvuru formunda istenecek ve profile kaydedilecektir. İl ve ilçe seçim listeleriyle girilecek, kursun merkezinin il/ilçesi listede öne alınacaktır.

8.6.4. **Veli bilgileri:** 18 yaş altı başvurularda veli T.C. Kimlik No, doğum tarihi, ad ve soyadı zorunlu olacaktır.

8.6.5. **Evrak yükleme:** Kursta/etkinlikte evrak zorunluysa her evrak tipi için dosya yükleme alanı gösterilecektir (PDF, JPG, PNG; en fazla 5 MB).

8.6.6. **KVKK ve Aydınlatma onayı:** Yönetim Panelinde tanımlanan metinler açılır pencerede okunabilecek ve onaylanmadan başvuru gönderilemeyecektir.

8.6.7. **Sunucu tarafı uygunluk denetimi:** yayında ve başvuruya açık olma, yaş aralığı, cinsiyet şartı, ikamet şartı ve ilçe dışı kontenjan, mükerrer başvuru, kontenjan ve yedek kontenjan kontrolleri yapılacaktır.

8.6.8. Başvuru sonucu kullanıcıya "Başvurunuz alındı" veya "Başvurunuz yedek listeye alındı (sıra: n)" şeklinde bildirilecek ve kullanıcı Başvurularım sayfasına yönlendirilecektir.

### 8.7. Kreş Başvurusu

8.7.1. Kreş başvurusu yalnızca aktif ve yayınlanmış bir dönem varken yapılabilecektir.

8.7.2. Başvuru 4 adımlı sihirbaz şeklinde olacaktır:
a) **Bilgiler:** veli (ad, soyad, T.C. Kimlik No, doğum tarihi, cep telefonu, e-posta, ev adresi; profilden önceden doldurulur) ve öğrenci (ad, soyad, T.C. Kimlik No, doğum tarihi) bilgileri,
b) **Okul seçimi:** öğrencinin yaşına uygun grubu bulunan okullar,
c) **Grup seçimi:** yaş aralığı, cinsiyet şartı, kontenjan ve kesin kayıt bilgileriyle,
d) **Soru formu:** dönem için tasarlanan özel sorular.

8.7.3. Her adımda istemci tarafı doğrulama yapılacak; sunucu tarafında yaş uygunluğu, mükerrer başvuru, soru tipi doğrulamaları ve (entegrasyon açıksa) kimlik doğrulaması yapılacaktır.

### 8.8. Başvurularım

8.8.1. Kurs, etkinlik ve kreş başvuruları tek listede, en yeniden eskiye gösterilecektir. Kişinin katılımcı, başvuran veya veli olduğu tüm başvurular listelenecektir.

8.8.2. Her başvuruda: tür, numara, başvuru durumu (yedekse sıra numarasıyla), başarı durumu, kurs/etkinlik/okul-grup adı, merkez, başvuru tarihi, yakın adına yapılan başvurularda yakın bilgisi ve iptal gerekçesi gösterilecektir.

8.8.3. Vatandaş başvurusuna ait kurs/etkinlik detaylarını açılır pencerede görüntüleyebilecektir.

8.8.4. **Belge indirme:** Sertifika veya katılım belgesi hak eden kurs başvurularında belge PDF olarak indirilebilecektir.

8.8.5. **Başvuru iptali:** Onay bekleyen ve yedekteki başvurular onay penceresiyle iptal edilebilecek; kesin kayıtların iptali Yönetim Panelindeki ayara bağlı olacaktır. Belge hak edilmiş başvurular iptal edilemeyecektir. İptal sonrası yedek sıraları otomatik güncellenecektir.

### 8.9. Profil

8.9.1. Ad, soyad, T.C. Kimlik No ve doğum tarihi (GG.AA.YYYY) salt okunur gösterilecektir. İkamet adresi kişisel verilerin korunması amacıyla maskelenmiş olarak gösterilecektir.

8.9.2. Telefon, e-posta ve diğer adres bilgileri güncellenebilecektir.

8.9.3. Şifre gerektiren giriş yöntemlerinde mevcut şifre doğrulanarak şifre değiştirilebilecektir.

## 9. ENTEGRASYON GEREKSİNİMLERİ

9.1. Sistem, entegrasyonları değiştirilebilir sağlayıcı (adaptör) mimarisiyle sunacak; yeni bir sağlayıcı eklenmesi mevcut iş akışlarında değişiklik gerektirmeyecektir.

9.2. **SMS:** Yüklenici, İdare'nin sözleşmeli olduğu SMS hizmet sağlayıcısının API'si ile entegrasyonu, İdare'nin sağlayacağı hesap bilgileriyle kurulum sürecinde tamamlayacaktır. Telefon numaraları gönderim öncesi standart biçime dönüştürülecektir.

9.3. **E-posta:** SMTP ve OAuth tabanlı e-posta servisleri desteklenecektir.

9.4. **Kimlik doğrulama:** Yüklenici, İdare'nin yetkili olduğu kimlik paylaşım servisi (NVİ Kimlik Paylaşım Sistemi veya İdare'nin belirleyeceği dengi servis) ile entegrasyonu, İdare'nin sağlayacağı yetki ve erişim bilgileriyle tamamlayacaktır. Entegrasyon açıkken portal başvurularında yakın ve öğrenci kimlik bilgileri doğrulanacak, cinsiyet bilgisi kimlik kaydından alınacaktır.

9.5. **Adres sorgulama:** Yüklenici, İdare'nin yetkili olduğu adres sorgulama servisi ile entegrasyonu İdare'nin sağlayacağı erişim bilgileriyle tamamlayacaktır.

9.6. Kamu servislerine erişim için gerekli protokol, yetkilendirme ve sabit IP tanımları İdare tarafından sağlanacaktır.

## 10. RAPORLAMA VE DIŞA AKTARIM

10.1. Aşağıdaki listeler ve raporlar dışa aktarılabilecektir: kurslar, kurs başvuruları, ders programı, kurs takvimi, yoklamalar, etkinlikler, etkinlik başvuruları, etkinlik yoklamaları, kreş dönemleri, okulları, grupları, grup başvuruları ve soru formları, merkezler, alanlar, branşlar, eğitmenler ve atandıkları kurslar, kullanıcılar, merkez yetkilendirmeleri, kişiler, işlem kayıtları.

10.2. PDF çıktıları: sertifika ve katılım belgeleri, kurs takvimi, genel takvim, boş yoklama formu.

10.3. Dışa aktarımlar, kullanıcının filtre ve yetki kapsamına uygun verileri içerecektir.

## 11. PERFORMANS, KULLANILABİLİRLİK VE ERİŞİLEBİLİRLİK

11.1. Portal statik dosyaları sıkıştırılmış ve uzun süreli önbelleğe alınabilir biçimde sunulacaktır.

11.2. Liste ekranları sunucu tarafında sayfalanacak, filtre değişikliklerinde gereksiz istekleri önlemek için gecikmeli istek (debounce) uygulanacaktır.

11.3. Portal; ortak kullanılan ayar ve sayfa bilgilerini tarayıcıda önbelleğe alarak açılış süresini kısaltacaktır.

11.4. Portal, 320 piksel genişliğe kadar mobil ekranlarda yatay kaydırma gerektirmeden kullanılabilecektir.

## 12. KİŞİSEL VERİLERİN KORUNMASI VE MEVZUAT UYUMU

12.1. Sistem, 6698 sayılı Kişisel Verilerin Korunması Kanunu ve ilgili ikincil mevzuata uygun şekilde kişisel verilerin işlenmesine imkân verecektir.

12.2. KVKK ve aydınlatma metinleri Yönetim Panelinden düzenlenebilecek ve başvuru sırasında onay alınacaktır.

12.3. Kişisel verilere erişim rol, yetki ve veri kapsamı kurallarıyla sınırlandırılacak; erişim ve değişiklikler işlem kayıtlarında izlenebilecektir.

12.4. Sistem, İdare'nin belirleyeceği sunucularda barındırılarak verilerin yurt içinde tutulmasına imkân verecektir.

12.5. Yüklenici, Cumhurbaşkanlığı Dijital Dönüşüm Ofisi Bilgi ve İletişim Güvenliği Rehberi kapsamında İdare'nin talep edeceği güvenlik kontrollerine ilişkin bilgi ve belgeleri sağlayacaktır.

## 13. KURULUM, YAPILANDIRMA VE VERİ AKTARIMI

13.1. Yüklenici, sözleşmenin imzalanmasından itibaren İdare ile birlikte belirlenecek iş planı çerçevesinde Sistemi test ve canlı ortamlarına kuracaktır.

13.2. Kurulum kapsamında; kurum kimliği, roller ve yetkiler, merkezler, alanlar, branşlar, sabit tanımlar, portal sayfaları, bildirim metinleri ve entegrasyon ayarları İdare ile birlikte yapılandırılacaktır.

13.3. İdare'nin mevcut sistemlerinde bulunan kurs, kişi ve başvuru verileri, İdare'nin sağlayacağı okunabilir formatta (Excel, CSV, veritabanı dökümü vb.) Sisteme aktarılacaktır. Aktarım sonrası doğrulama raporu İdare'ye sunulacaktır.

13.4. Yüklenici, kurulum sonrasında yedekleme ve geri yükleme prosedürünü İdare'nin bilgi işlem birimiyle birlikte test edecektir.

## 14. EĞİTİM

14.1. Yüklenici aşağıdaki eğitimleri İdare'nin belirleyeceği yer ve tarihlerde verecektir:
a) **Yönetici eğitimi:** rol/yetki yönetimi, tanımlar, portal ayarları, entegrasyonlar, işlem kayıtları (en az 1 gün),
b) **Personel eğitimi:** kurs, etkinlik ve kreş yönetimi, başvuru işlemleri, yedek liste, bildirimler, dışa aktarımlar (en az 1 gün),
c) **Eğitmen eğitimi:** yoklama, takvim ve ders işlemleri (en az yarım gün),
d) **Teknik eğitim:** kurulum, güncelleme, yedekleme ve sorun giderme (en az yarım gün).

14.2. Eğitim dokümanları Türkçe olarak basılı ve/veya elektronik ortamda teslim edilecektir.

## 15. DOKÜMANTASYON

15.1. Yüklenici aşağıdaki dokümanları Türkçe olarak teslim edecektir:
a) Yönetim Paneli kullanım kılavuzu,
b) Portal kullanım kılavuzu (vatandaş için),
c) Kurulum ve yapılandırma kılavuzu,
d) API dokümantasyonu,
e) Veritabanı şeması ve veri sözlüğü,
f) Yedekleme ve geri yükleme prosedürü.

## 16. GARANTİ, BAKIM VE TEKNİK DESTEK

16.1. Sistem, kabul tarihinden itibaren en az **12 (on iki) ay** süreyle Yüklenici garantisi altında olacaktır.

16.2. Garanti süresince Yüklenici:
a) Yazılım hatalarını ücretsiz giderecek,
b) Güvenlik güncellemelerini ve kullanılan yazılım bileşenlerinin güvenlik yamalarını uygulayacak,
c) Sistemin yeni sürümlerini ücretsiz sağlayacak,
d) Mevzuat değişikliklerinden kaynaklanan zorunlu güncellemeleri yapacaktır.

16.3. Destek; telefon, e-posta ve çağrı/talep takip sistemi üzerinden mesai saatleri içinde verilecektir. Arızalar için aşağıdaki müdahale süreleri uygulanacaktır:

| Öncelik | Tanım | İlk müdahale | Çözüm / geçici çözüm |
|---|---|---|---|
| Kritik | Sistemin tamamen kullanılamaması, veri kaybı riski | 2 saat | 8 saat |
| Yüksek | Başvuru alma veya temel iş akışının engellenmesi | 4 saat | 1 iş günü |
| Orta | Bir fonksiyonun hatalı çalışması, geçici yöntemle devam edilebilmesi | 1 iş günü | 3 iş günü |
| Düşük | Görsel hata, iyileştirme talebi | 2 iş günü | Sonraki sürüm |

16.4. Garanti süresi sonunda bakım ve destek hizmeti ayrı bir sözleşmeyle sürdürülebilecektir.

## 17. KABUL VE MUAYENE

17.1. Kabul işlemleri İdare tarafından oluşturulacak muayene ve kabul komisyonu tarafından yapılacaktır.

17.2. Komisyon, bu şartnamede belirtilen tüm fonksiyonları test senaryoları üzerinden Yüklenici ile birlikte kontrol edecektir. Test senaryoları Yüklenici tarafından hazırlanıp İdare'nin onayına sunulacaktır.

17.3. Kabul testlerinde tespit edilen eksiklikler Yüklenici tarafından komisyonun vereceği süre içinde giderilecektir.

17.4. Kurulum, yapılandırma, veri aktarımı, entegrasyonlar, eğitimler ve dokümantasyon teslim edilmeden kabul yapılmayacaktır.

## 18. LİSANS VE FİKRİ MÜLKİYET

18.1. Yüklenici, Sistemin İdare bünyesinde süresiz ve kullanıcı sayısı sınırlaması olmaksızın kullanım hakkını sağlayacaktır.

18.2. Sistemin kullanıldığı sürece İdare'ye ait tüm veriler İdare'nin mülkiyetindedir. Sözleşme sona erdiğinde Yüklenici, İdare'nin talebi hâlinde tüm verileri okunabilir formatta teslim edecektir.

18.3. Kaynak kodun İdare'ye teslimi veya bir emanet (escrow) düzenlemesi, İdare'nin tercihine göre sözleşmede ayrıca belirlenecektir.

---

## EK-1: EKRAN LİSTESİ

### EK-1.A: Yönetim Paneli Ekranları

| No | Ekran | Açıklama |
|---|---|---|
| 1 | Giriş | E-posta ve şifre ile giriş, istek sınırı geri sayımı |
| 2 | İki Aşamalı Doğrulama | SMS / e-posta doğrulama kodu girişi, kodu yeniden gönderme |
| 3 | Ana Sayfa | Sayaçlar, hızlı erişim, yaklaşan dersler ve etkinlikler, bekleyen yoklamalar, son başvurular |
| 4 | Gösterge Paneli | İstatistikler, dağılımlar, aylık başvuru grafikleri, sıralamalar |
| 5 | Kurslar | Filtreli kurs listesi, özet kartları, dışa aktarım |
| 6 | Kurs Oluştur / Düzenle | Kurs bilgileri, koşullar, evraklar, haftalık program |
| 7 | Kurs Detay: Kurs Detayları | İstatistikler, öğretmen atama, yayın, SMS, e-posta, sertifika |
| 8 | Kurs Detay: Başvurular | Kursa ait başvurular, durum işlemleri |
| 9 | Kurs Detay: Ders Programı | Haftalık program |
| 10 | Kurs Detay: Takvim | Ders takvimi, iptal ve tarih değişikliği, PDF |
| 11 | Kurs Detay: Yoklamalar | Ders bazında yoklama alma ve düzenleme |
| 12 | Kurs Detay: Mesajlar | Gönderilen SMS ve e-postalar |
| 13 | Kurs Başvuru Detayı | Başvuru bilgileri, durum, başarı, veli, evraklar, kursa başlama tarihi |
| 14 | Yedek Sırası | Sürükle-bırak yedek sıra düzenleme |
| 15 | Kurs Başvuruları | Tüm kurs başvuruları, filtreler, dışa aktarım |
| 16 | Yeni Kurs Başvurusu | Personel tarafından başvuru girişi |
| 17 | Etkinlikler | Filtreli etkinlik listesi |
| 18 | Etkinlik Oluştur / Düzenle | Etkinlik bilgileri ve koşulları |
| 19 | Etkinlik Detay (Detaylar, Başvurular, Yoklama, Mesajlar) | Etkinlik yönetimi sekmeleri |
| 20 | Etkinlik Başvuru Detayı | Başvuru bilgileri, durum, veli, evraklar |
| 21 | Etkinlik Başvuruları | Tüm etkinlik başvuruları |
| 22 | Yeni Etkinlik Başvurusu | Personel tarafından başvuru girişi |
| 23 | Kreş Ana Ekranı | Dönem seçimi, okul bazında doluluk özeti |
| 24 | Kreş Dönemleri | Dönem tanımları |
| 25 | Kreş Okulları ve Okul Detayı | Okul tanımları ve grupları |
| 26 | Kreş Grupları ve Grup Detayı | Grup tanımları, grup başvuruları |
| 27 | Kreş Soru Formları, Form Detayı ve Önizleme | Soru formu tasarımcısı |
| 28 | Takvim | Aylık ders ve etkinlik takvimi, PDF |
| 29 | Merkezler ve Merkez Detayı | Merkez tanımları, aktif kurslar ve etkinlikler |
| 30 | Alanlar ve Alan Detayı | Alan tanımları |
| 31 | Branşlar ve Branş Detayı | Branş tanımları |
| 32 | Eğitmenler, Eğitmen Oluştur / Düzenle, Eğitmen Detayı | Eğitmen yönetimi |
| 33 | Kişiler, Kişi Oluştur / Düzenle, Kişi Detayı | Vatandaş kayıtları, aile, giriş kilidi |
| 34 | Kullanıcılar, Kullanıcı Oluştur / Düzenle, Kullanıcı Detayı | Personel hesapları |
| 35 | Merkez Yetkilendirme | Kullanıcı-merkez yetki eşleştirmeleri |
| 36 | Roller, Rol Oluştur / Düzenle, Rol Detayı | Rol ve yetki yönetimi |
| 37 | Sabit Tanımlar (Kurs) | Kurs tipleri, evrak tipleri, durumlar, iptal gerekçeleri, kurumlar, sertifika ayarları, başvuru ayarları |
| 38 | Sabit Tanımlar (Etkinlik) | Etkinlik tipleri, etkinlik başvuru ayarları |
| 39 | Genel Ayarlar | Kurum bilgileri, logolar, portal görünümü, giriş yöntemi |
| 40 | Portal Ayarları, Sayfa Oluştur / Düzenle | Portal sayfaları ve içerik kuralları |
| 41 | Entegrasyonlar | SMS, e-posta, kimlik ve adres sorgulama ayarları |
| 42 | Güvenilir IP Adresleri | İstek sınırından muaf IP tanımları |
| 43 | İşlem Kayıtları | Denetim izi, filtreler, dışa aktarım |
| 44 | Profil | Kullanıcının kendi bilgileri, fotoğraf ve şifre |

### EK-1.B: Başvuru Portalı Ekranları

| No | Ekran | Oturum | Açıklama |
|---|---|---|---|
| 1 | Ana Sayfa | Gerekmez | Karşılama ve portal sayfası kartları |
| 2 | Kurslar | Gerekmez | Kurs kataloğu ve filtreler |
| 3 | Kurs Detayı | Gerekmez | Kurs bilgileri, paylaşım, başvuru |
| 4 | Etkinlikler | Gerekmez | Etkinlik kataloğu ve filtreler |
| 5 | Etkinlik Detayı | Gerekmez | Etkinlik bilgileri, başvuru |
| 6 | Özel Katalog Sayfaları | Ayara göre | Yönetim Panelinde tanımlanan kural tabanlı sayfalar |
| 7 | Hızlı Arama | Gerekmez | Tüm katalogda arama |
| 8 | Kayıt Ol | Gerekmez | Vatandaş kaydı |
| 9 | Giriş Yap | Gerekmez | Seçilen yönteme göre giriş |
| 10 | Kurs / Etkinlik Başvurusu | Gerekir | Kendisi veya yakını için başvuru, veli, evrak, KVKK |
| 11 | Kreş Başvurusu | Gerekir | 4 adımlı başvuru sihirbazı |
| 12 | Başvurularım | Gerekir | Başvuru takibi, iptal, belge indirme |
| 13 | Profil | Gerekir | Kişisel bilgiler, iletişim güncelleme, şifre değiştirme |

## EK-2: YETKİ MODÜLLERİ

| Modül | Yetkiler |
|---|---|
| Dashboard | Görüntüleme |
| Kurslar | Görüntüleme; veri kapsamı (atanan, yetkili merkez, tüm merkezler, kendi kurumu, tüm kurumlar); oluşturma; güncelleme; öğretmen atama; yayınlama; mesaj görüntüleme; SMS; e-posta; yoklama görüntüleme; yoklama alma; dışa aktarma |
| Başvurular | Görüntüleme; oluşturma; güncelleme; durum güncelleme; yedek sıra güncelleme; başarı güncelleme; kursa başlama tarihi güncelleme; evrak görüntüleme / yükleme / silme; dışa aktarma |
| Etkinlikler | Görüntüleme; veri kapsamı; oluşturma; güncelleme; sorumlu atama; yayınlama; mesaj görüntüleme; SMS; e-posta; yoklama görüntüleme; yoklama alma; dışa aktarma |
| Etkinlik Başvuruları | Görüntüleme; oluşturma; güncelleme; yedek sıra güncelleme; evrak görüntüleme / yükleme / silme; dışa aktarma |
| Kreş Yönetimi | Görüntüleme; dönem, okul, grup ve soru formu yönetimi; başvuru görüntüleme / oluşturma / güncelleme / durum güncelleme |
| Merkezler | Görüntüleme; oluşturma; güncelleme; SMS; e-posta; dışa aktarma |
| Alanlar, Branşlar | Görüntüleme; oluşturma; güncelleme; dışa aktarma |
| Sabit Tanımlar | Görüntüleme; oluşturma; güncelleme |
| Entegrasyonlar | Görüntüleme; güncelleme |
| Genel Ayarlar | Görüntüleme; güncelleme |
| Güvenilir IP Adresleri | Görüntüleme; yönetme |
| Portal Ayarları | Görüntüleme; güncelleme |
| Takvim | Görüntüleme |
| İşlem Kayıtları | Görüntüleme; dışa aktarma |
| Eğitmenler | Görüntüleme; oluşturma; güncelleme; SMS; e-posta; şifre gönderme; dışa aktarma |
| Kullanıcılar | Görüntüleme; oluşturma; güncelleme; SMS; e-posta; şifre gönderme; dışa aktarma |
| Kişiler | Görüntüleme; oluşturma; güncelleme; SMS; e-posta; dışa aktarma |
| Roller | Görüntüleme; oluşturma / güncelleme / silme |
