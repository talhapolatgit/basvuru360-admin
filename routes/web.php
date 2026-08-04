<?php

use App\Http\Controllers\AlanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BasvuruController;
use App\Http\Controllers\BransController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EgitmenController;
use App\Http\Controllers\EntegrasyonController;
use App\Http\Controllers\GenelAyarController;
use App\Http\Controllers\PortalAyarController;
use App\Http\Controllers\EtkinlikBasvuruController;
use App\Http\Controllers\EtkinlikController;
use App\Http\Controllers\KisiController;
use App\Http\Controllers\KullaniciController;
use App\Http\Controllers\KursController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MerkezController;
use App\Http\Controllers\MerkezYetkiController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SabitTanimController;
use App\Http\Controllers\TakvimController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'home'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/giris', [LoginController::class, 'create'])->name('login');
    Route::post('/giris', [LoginController::class, 'store']);
    Route::get('/giris/dogrulama', [LoginController::class, 'dogrulamaForm'])->name('login.dogrulama');
    Route::post('/giris/dogrulama', [LoginController::class, 'dogrulama'])->name('login.dogrulama.submit');
    Route::post('/giris/dogrulama/yenile', [LoginController::class, 'kodYenile'])->name('login.dogrulama.yenile');
});

Route::middleware('auth')->group(function () {
    Route::post('/cikis', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/anasayfa', [DashboardController::class, 'anasayfa'])->name('anasayfa');
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])
        ->middleware('yetki:dashboard.goruntule')
        ->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/sifre', [ProfilController::class, 'updatePassword'])->name('profil.password');
    Route::post('/profil/foto', [ProfilController::class, 'updateFoto'])->name('profil.foto.update');
    Route::delete('/profil/foto', [ProfilController::class, 'deleteFoto'])->name('profil.foto.delete');

    // —— Kurslar (statik path'ler önce) ——
    Route::get('/kurslar', [KursController::class, 'index'])->middleware('yetki:kurs.goruntule')->name('kurslar.index');
    Route::get('/kurslar/yeni', [KursController::class, 'create'])->middleware('yetki:kurs.olustur')->name('kurslar.create');
    Route::post('/kurslar', [KursController::class, 'store'])->middleware('yetki:kurs.olustur')->name('kurslar.store');
    Route::get('/kurslar/excel', [KursController::class, 'export'])->middleware('yetki:kurs.export')->name('kurslar.export');
    Route::get('/kurslar/{kurs}/basvurular', [KursController::class, 'basvurular'])->middleware('yetki:basvuru.goruntule')->name('kurslar.basvurular');
    Route::get('/kurslar/{kurs}/basvurular/excel', [KursController::class, 'exportBasvurular'])->middleware('yetki:basvuru.export')->name('kurslar.basvurular.export');
    Route::get('/kurslar/{kurs}/basvurular/{basvuru}', [KursController::class, 'showBasvuru'])->middleware('yetki:basvuru.goruntule')->name('kurslar.basvurular.show');
    Route::get('/kurslar/{kurs}/yedek-sirasi', [KursController::class, 'yedekSirasi'])->middleware('yetki:basvuru.yedek_sira_guncelle')->name('kurslar.yedek-sirasi');
    Route::put('/kurslar/{kurs}/yedek-sirasi', [KursController::class, 'updateYedekSirasi'])->middleware('yetki:basvuru.yedek_sira_guncelle')->name('kurslar.yedek-sirasi.update');
    Route::get('/kurslar/{kurs}/basvurular/{basvuru}/evraklar/{evrak}', [KursController::class, 'showBasvuruEvrak'])->middleware('yetki:basvuru.evrak_goruntule')->name('kurslar.basvurular.evrak.show');
    Route::post('/kurslar/{kurs}/basvurular/{basvuru}/evraklar', [KursController::class, 'storeBasvuruEvrak'])->middleware('yetki:basvuru.evrak_yukle')->name('kurslar.basvurular.evrak.store');
    Route::delete('/kurslar/{kurs}/basvurular/{basvuru}/evraklar/{evrak}', [KursController::class, 'destroyBasvuruEvrak'])->middleware('yetki:basvuru.evrak_sil')->name('kurslar.basvurular.evrak.destroy');
    Route::put('/kurslar/{kurs}/basvurular/{basvuru}/kursa-baslama', [KursController::class, 'updateKursaBaslama'])->middleware('yetki:basvuru.kursa_baslama_guncelle')->name('kurslar.basvurular.kursa-baslama');
    Route::put('/kurslar/{kurs}/basvurular/{basvuru}/durum', [KursController::class, 'updateBasvuruDurum'])->middleware('yetki:basvuru.durum_guncelle')->name('kurslar.basvurular.durum');
    Route::put('/kurslar/{kurs}/basvurular/{basvuru}/basari', [KursController::class, 'updateBasvuruBasari'])->middleware('yetki:basvuru.basari_guncelle')->name('kurslar.basvurular.basari');
    Route::put('/kurslar/{kurs}/basvurular/{basvuru}/iptal-gerekce', [KursController::class, 'updateBasvuruIptalGerekce'])->middleware('yetki:basvuru.guncelle')->name('kurslar.basvurular.iptal-gerekce');
    Route::put('/kurslar/{kurs}/basvurular/{basvuru}/veli', [KursController::class, 'updateBasvuruVeli'])->middleware('yetki:basvuru.guncelle')->name('kurslar.basvurular.veli');
    Route::get('/kurslar/{kurs}/program/excel', [KursController::class, 'exportProgram'])->middleware('yetki:kurs.export')->name('kurslar.program.export');
    Route::get('/kurslar/{kurs}/takvim/excel', [KursController::class, 'exportTakvim'])->middleware('yetki:kurs.export')->name('kurslar.takvim.export');
    Route::get('/kurslar/{kurs}/takvim/pdf', [KursController::class, 'exportTakvimPdf'])->middleware('yetki:kurs.export')->name('kurslar.takvim.pdf');
    Route::get('/kurslar/{kurs}/sertifikalar/pdf', [KursController::class, 'exportSertifikalar'])->middleware('yetki:kurs.export')->name('kurslar.sertifikalar.pdf');
    Route::get('/kurslar/{kurs}/yoklamalar', [KursController::class, 'yoklamalar'])->middleware('yetki:kurs.yoklama_goruntule')->name('kurslar.yoklamalar');
    Route::get('/kurslar/{kurs}/yoklamalar/excel', [KursController::class, 'exportYoklamalar'])->middleware('yetki:kurs.export')->name('kurslar.yoklamalar.export');
    Route::get('/kurslar/{kurs}/mesajlar', [KursController::class, 'mesajlar'])->middleware('yetki:kurs.mesaj_goruntule')->name('kurslar.mesajlar');
    Route::get('/kurslar/{kurs}/dersler/{ders}/yoklama-formu', [KursController::class, 'exportYoklamaFormu'])->middleware('yetki:kurs.export')->name('kurslar.yoklama.formu');
    Route::post('/kurslar/{kurs}/sms', [KursController::class, 'sendSms'])->middleware('yetki:kurs.sms')->name('kurslar.sms.send');
    Route::get('/kurslar/{kurs}/sms/alicilar', [KursController::class, 'smsAlicilar'])->middleware('yetki:kurs.goruntule')->name('kurslar.sms.alicilar');
    Route::post('/kurslar/{kurs}/eposta', [KursController::class, 'sendEposta'])->middleware('yetki:kurs.eposta')->name('kurslar.eposta.send');
    Route::get('/kurslar/{kurs}/eposta/alicilar', [KursController::class, 'epostaAlicilar'])->middleware('yetki:kurs.goruntule')->name('kurslar.eposta.alicilar');
    Route::get('/kurslar/{kurs}', [KursController::class, 'show'])->middleware('yetki:kurs.goruntule')->name('kurslar.show');
    Route::get('/kurslar/{kurs}/duzenle', [KursController::class, 'edit'])->middleware('yetki:kurs.guncelle')->name('kurslar.edit');
    Route::put('/kurslar/{kurs}', [KursController::class, 'update'])->middleware('yetki:kurs.guncelle')->name('kurslar.update');
    Route::patch('/kurslar/{kurs}/yayin', [KursController::class, 'toggleYayin'])->middleware('yetki:kurs.yayinla')->name('kurslar.toggle-yayin');
    Route::put('/kurslar/{kurs}/ogretmen', [KursController::class, 'assignOgretmen'])->middleware('yetki:kurs.ogretmen_ata')->name('kurslar.ogretmen.assign');
    Route::put('/kurslar/{kurs}/dersler/{ders}/yoklama', [KursController::class, 'saveYoklama'])->middleware('yetki:kurs.yoklama')->name('kurslar.yoklama.save');
    Route::delete('/kurslar/{kurs}/dersler/{ders}/yoklama', [KursController::class, 'deleteYoklama'])->middleware('yetki:kurs.yoklama')->name('kurslar.yoklama.delete');
    Route::put('/kurslar/{kurs}/dersler/{ders}/iptal', [KursController::class, 'cancelDers'])->middleware('yetki:kurs.guncelle')->name('kurslar.dersler.iptal');
    Route::delete('/kurslar/{kurs}/dersler/{ders}/iptal', [KursController::class, 'cancelDersGeriAl'])->middleware('yetki:kurs.guncelle')->name('kurslar.dersler.iptal-geri-al');
    Route::put('/kurslar/{kurs}/dersler/{ders}/tarih', [KursController::class, 'rescheduleDers'])->middleware('yetki:kurs.guncelle')->name('kurslar.dersler.tarih-degistir');

    // —— Etkinlikler (statik path'ler önce) ——
    Route::get('/etkinlikler', [EtkinlikController::class, 'index'])->middleware('yetki:etkinlik.goruntule')->name('etkinlikler.index');
    Route::get('/etkinlikler/yeni', [EtkinlikController::class, 'create'])->middleware('yetki:etkinlik.olustur')->name('etkinlikler.create');
    Route::post('/etkinlikler', [EtkinlikController::class, 'store'])->middleware('yetki:etkinlik.olustur')->name('etkinlikler.store');
    Route::get('/etkinlikler/excel', [EtkinlikController::class, 'export'])->middleware('yetki:etkinlik.export')->name('etkinlikler.export');
    Route::get('/etkinlikler/{etkinlik}/basvurular', [EtkinlikController::class, 'basvurular'])->middleware('yetki:etkinlik_basvuru.goruntule')->name('etkinlikler.basvurular');
    Route::get('/etkinlikler/{etkinlik}/basvurular/excel', [EtkinlikController::class, 'exportBasvurular'])->middleware('yetki:etkinlik_basvuru.export')->name('etkinlikler.basvurular.export');
    Route::get('/etkinlikler/{etkinlik}/basvurular/{basvuru}', [EtkinlikController::class, 'showBasvuru'])->middleware('yetki:etkinlik_basvuru.goruntule')->name('etkinlikler.basvurular.show');
    Route::get('/etkinlikler/{etkinlik}/yedek-sirasi', [EtkinlikController::class, 'yedekSirasi'])->middleware('yetki:etkinlik_basvuru.yedek_sira_guncelle')->name('etkinlikler.yedek-sirasi');
    Route::put('/etkinlikler/{etkinlik}/yedek-sirasi', [EtkinlikController::class, 'updateYedekSirasi'])->middleware('yetki:etkinlik_basvuru.yedek_sira_guncelle')->name('etkinlikler.yedek-sirasi.update');
    Route::put('/etkinlikler/{etkinlik}/basvurular/{basvuru}/durum', [EtkinlikController::class, 'updateBasvuruDurum'])->middleware('yetki:etkinlik_basvuru.guncelle')->name('etkinlikler.basvurular.durum');
    Route::put('/etkinlikler/{etkinlik}/basvurular/{basvuru}/iptal-gerekce', [EtkinlikController::class, 'updateBasvuruIptalGerekce'])->middleware('yetki:etkinlik_basvuru.guncelle')->name('etkinlikler.basvurular.iptal-gerekce');
    Route::put('/etkinlikler/{etkinlik}/basvurular/{basvuru}/veli', [EtkinlikController::class, 'updateBasvuruVeli'])->middleware('yetki:etkinlik_basvuru.guncelle')->name('etkinlikler.basvurular.veli');
    Route::get('/etkinlikler/{etkinlik}/basvurular/{basvuru}/evraklar/{evrak}', [EtkinlikController::class, 'showBasvuruEvrak'])->middleware('yetki:etkinlik_basvuru.evrak_goruntule')->name('etkinlikler.basvurular.evrak.show');
    Route::post('/etkinlikler/{etkinlik}/basvurular/{basvuru}/evraklar', [EtkinlikController::class, 'storeBasvuruEvrak'])->middleware('yetki:etkinlik_basvuru.evrak_yukle')->name('etkinlikler.basvurular.evrak.store');
    Route::delete('/etkinlikler/{etkinlik}/basvurular/{basvuru}/evraklar/{evrak}', [EtkinlikController::class, 'destroyBasvuruEvrak'])->middleware('yetki:etkinlik_basvuru.evrak_sil')->name('etkinlikler.basvurular.evrak.destroy');
    Route::get('/etkinlikler/{etkinlik}/yoklama', [EtkinlikController::class, 'yoklama'])->middleware('yetki:etkinlik.yoklama_goruntule')->name('etkinlikler.yoklama');
    Route::get('/etkinlikler/{etkinlik}/yoklama/excel', [EtkinlikController::class, 'exportYoklama'])->middleware('yetki:etkinlik.export')->name('etkinlikler.yoklama.export');
    Route::get('/etkinlikler/{etkinlik}/mesajlar', [EtkinlikController::class, 'mesajlar'])->middleware('yetki:etkinlik.mesaj_goruntule')->name('etkinlikler.mesajlar');
    Route::put('/etkinlikler/{etkinlik}/yoklama', [EtkinlikController::class, 'saveYoklama'])->middleware('yetki:etkinlik.yoklama')->name('etkinlikler.yoklama.save');
    Route::post('/etkinlikler/{etkinlik}/sms', [EtkinlikController::class, 'sendSms'])->middleware('yetki:etkinlik.sms')->name('etkinlikler.sms.send');
    Route::get('/etkinlikler/{etkinlik}/sms/alicilar', [EtkinlikController::class, 'smsAlicilar'])->middleware('yetki:etkinlik.sms')->name('etkinlikler.sms.alicilar');
    Route::post('/etkinlikler/{etkinlik}/eposta', [EtkinlikController::class, 'sendEposta'])->middleware('yetki:etkinlik.eposta')->name('etkinlikler.eposta.send');
    Route::get('/etkinlikler/{etkinlik}/eposta/alicilar', [EtkinlikController::class, 'epostaAlicilar'])->middleware('yetki:etkinlik.eposta')->name('etkinlikler.eposta.alicilar');
    Route::get('/etkinlikler/{etkinlik}', [EtkinlikController::class, 'show'])->middleware('yetki:etkinlik.goruntule')->name('etkinlikler.show');
    Route::get('/etkinlikler/{etkinlik}/duzenle', [EtkinlikController::class, 'edit'])->middleware('yetki:etkinlik.guncelle')->name('etkinlikler.edit');
    Route::put('/etkinlikler/{etkinlik}', [EtkinlikController::class, 'update'])->middleware('yetki:etkinlik.guncelle')->name('etkinlikler.update');
    Route::patch('/etkinlikler/{etkinlik}/yayin', [EtkinlikController::class, 'toggleYayin'])->middleware('yetki:etkinlik.yayinla')->name('etkinlikler.toggle-yayin');
    Route::put('/etkinlikler/{etkinlik}/sorumlu', [EtkinlikController::class, 'assignSorumlu'])->middleware('yetki:etkinlik.sorumlu_ata')->name('etkinlikler.sorumlu.assign');

    // —— Etkinlik Başvuruları ——
    Route::get('/etkinlik-basvurulari', [EtkinlikBasvuruController::class, 'index'])->middleware('yetki:etkinlik_basvuru.goruntule')->name('etkinlik-basvurulari.index');
    Route::get('/etkinlik-basvurulari/yeni', [EtkinlikBasvuruController::class, 'create'])->middleware('yetki:etkinlik_basvuru.olustur')->name('etkinlik-basvurulari.create');
    Route::post('/etkinlik-basvurulari', [EtkinlikBasvuruController::class, 'store'])->middleware('yetki:etkinlik_basvuru.olustur')->name('etkinlik-basvurulari.store');
    Route::get('/etkinlik-basvurulari/etkinlik/{etkinlik}/ozet', [EtkinlikBasvuruController::class, 'etkinlikOzet'])->middleware('yetki:etkinlik_basvuru.olustur')->name('etkinlik-basvurulari.etkinlik-ozet');
    Route::get('/etkinlik-basvurulari/excel', [EtkinlikBasvuruController::class, 'export'])->middleware('yetki:etkinlik_basvuru.export')->name('etkinlik-basvurulari.export');

    // —— Log / Takvim / Başvurular ——
    Route::get('/log-kayitlari', [LogController::class, 'index'])->middleware('yetki:log.goruntule')->name('loglar.index');
    Route::get('/genel-ayarlar', [GenelAyarController::class, 'edit'])->middleware('yetki:genel_ayar.goruntule')->name('genel-ayarlar.edit');
    Route::put('/genel-ayarlar', [GenelAyarController::class, 'update'])->middleware('yetki:genel_ayar.guncelle')->name('genel-ayarlar.update');

    Route::get('/portal-ayarlar', [PortalAyarController::class, 'index'])->middleware('yetki:portal_ayar.goruntule')->name('portal-ayarlar.index');
    Route::get('/portal-ayarlar/sayfalar/create', [PortalAyarController::class, 'create'])->middleware('yetki:portal_ayar.guncelle')->name('portal-ayarlar.create');
    Route::post('/portal-ayarlar/sayfalar', [PortalAyarController::class, 'store'])->middleware('yetki:portal_ayar.guncelle')->name('portal-ayarlar.store');
    Route::get('/portal-ayarlar/sayfalar/{portalSayfa}/edit', [PortalAyarController::class, 'edit'])->middleware('yetki:portal_ayar.goruntule')->name('portal-ayarlar.edit');
    Route::put('/portal-ayarlar/sayfalar/{portalSayfa}', [PortalAyarController::class, 'update'])->middleware('yetki:portal_ayar.guncelle')->name('portal-ayarlar.update');
    Route::delete('/portal-ayarlar/sayfalar/{portalSayfa}', [PortalAyarController::class, 'destroy'])->middleware('yetki:portal_ayar.guncelle')->name('portal-ayarlar.destroy');
    Route::put('/portal-ayarlar/sayfalar/{portalSayfa}/menude', [PortalAyarController::class, 'menudeToggle'])->middleware('yetki:portal_ayar.guncelle')->name('portal-ayarlar.menude-toggle');
    Route::put('/portal-ayarlar/sayfalar/{portalSayfa}/move', [PortalAyarController::class, 'move'])->middleware('yetki:portal_ayar.guncelle')->name('portal-ayarlar.move');
    Route::get('/entegrasyonlar', [EntegrasyonController::class, 'index'])->middleware('yetki:entegrasyon.goruntule')->name('entegrasyonlar.index');
    Route::put('/entegrasyonlar', [EntegrasyonController::class, 'update'])->middleware('yetki:entegrasyon.guncelle')->name('entegrasyonlar.update');
    Route::put('/entegrasyonlar/{tur}/{saglayici}/ayarlar', [EntegrasyonController::class, 'updateAyarlar'])->middleware('yetki:entegrasyon.guncelle')->name('entegrasyonlar.ayarlar.update');
    Route::get('/log-kayitlari/excel', [LogController::class, 'export'])->middleware('yetki:log.export')->name('loglar.export');
    Route::get('/takvim', [TakvimController::class, 'index'])->middleware('yetki:takvim.goruntule')->name('takvim.index');
    Route::get('/takvim/veri', [TakvimController::class, 'data'])->middleware('yetki:takvim.goruntule')->name('takvim.data');
    Route::get('/takvim/pdf', [TakvimController::class, 'exportPdf'])->middleware('yetki:takvim.goruntule')->name('takvim.pdf');
    Route::get('/kurs-basvurulari', [BasvuruController::class, 'index'])->middleware('yetki:basvuru.goruntule')->name('basvurular.index');
    Route::get('/kurs-basvurulari/yeni', [BasvuruController::class, 'create'])->middleware('yetki:basvuru.olustur')->name('basvurular.create');
    Route::post('/kurs-basvurulari', [BasvuruController::class, 'store'])->middleware('yetki:basvuru.olustur')->name('basvurular.store');
    Route::get('/kurs-basvurulari/kurs/{kurs}/ozet', [BasvuruController::class, 'kursOzet'])->middleware('yetki:basvuru.olustur')->name('basvurular.kurs-ozet');
    Route::get('/kurs-basvurulari/excel', [BasvuruController::class, 'export'])->middleware('yetki:basvuru.export')->name('basvurular.export');

    // —— Merkezler ——
    Route::get('/merkezler', [MerkezController::class, 'index'])->middleware('yetki:merkez.goruntule')->name('merkezler.index');
    Route::post('/merkezler', [MerkezController::class, 'store'])->middleware('yetki:merkez.olustur')->name('merkezler.store');
    Route::get('/merkezler/excel', [MerkezController::class, 'export'])->middleware('yetki:merkez.export')->name('merkezler.export');
    Route::post('/merkezler/{merkez}/sms', [MerkezController::class, 'sendSms'])->middleware(['yetki:merkez.sms', 'merkez.kapsam'])->name('merkezler.sms.send');
    Route::post('/merkezler/{merkez}/eposta', [MerkezController::class, 'sendEposta'])->middleware(['yetki:merkez.eposta', 'merkez.kapsam'])->name('merkezler.eposta.send');
    Route::get('/merkezler/{merkez}', [MerkezController::class, 'show'])->middleware(['yetki:merkez.goruntule', 'merkez.kapsam'])->name('merkezler.show');
    Route::put('/merkezler/{merkez}', [MerkezController::class, 'update'])->middleware(['yetki:merkez.guncelle', 'merkez.kapsam'])->name('merkezler.update');

    // —— Alanlar ——
    Route::get('/alanlar', [AlanController::class, 'index'])->middleware('yetki:alan.goruntule')->name('alanlar.index');
    Route::post('/alanlar', [AlanController::class, 'store'])->middleware('yetki:alan.olustur')->name('alanlar.store');
    Route::get('/alanlar/excel', [AlanController::class, 'export'])->middleware('yetki:alan.export')->name('alanlar.export');
    Route::get('/alanlar/{alan}', [AlanController::class, 'show'])->middleware('yetki:alan.goruntule')->name('alanlar.show');
    Route::put('/alanlar/{alan}', [AlanController::class, 'update'])->middleware('yetki:alan.guncelle')->name('alanlar.update');

    // —— Branşlar ——
    Route::get('/branslar', [BransController::class, 'index'])->middleware('yetki:brans.goruntule')->name('branslar.index');
    Route::post('/branslar', [BransController::class, 'store'])->middleware('yetki:brans.olustur')->name('branslar.store');
    Route::get('/branslar/excel', [BransController::class, 'export'])->middleware('yetki:brans.export')->name('branslar.export');
    Route::get('/branslar/{brans}', [BransController::class, 'show'])->middleware('yetki:brans.goruntule')->name('branslar.show');
    Route::put('/branslar/{brans}', [BransController::class, 'update'])->middleware('yetki:brans.guncelle')->name('branslar.update');

    // —— Sabit Tanımlar ——
    Route::get('/sabit-tanimlar', [SabitTanimController::class, 'index'])->middleware('yetki:sabit.goruntule')->name('sabit-tanimlar.index');
    Route::get('/etkinlik-sabit-tanimlar', [SabitTanimController::class, 'etkinlikIndex'])->middleware('yetki:sabit.goruntule')->name('etkinlik-sabit-tanimlar.index');
    Route::post('/sabit-tanimlar/kurs-tipleri', [SabitTanimController::class, 'storeKursTipi'])->middleware('yetki:sabit.olustur')->name('sabit-tanimlar.kurs-tipleri.store');
    Route::put('/sabit-tanimlar/kurs-tipleri/{kursTipi}', [SabitTanimController::class, 'updateKursTipi'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.kurs-tipleri.update');
    Route::post('/sabit-tanimlar/etkinlik-tipleri', [SabitTanimController::class, 'storeEtkinlikTipi'])->middleware('yetki:sabit.olustur')->name('sabit-tanimlar.etkinlik-tipleri.store');
    Route::put('/sabit-tanimlar/etkinlik-tipleri/{etkinlikTipi}', [SabitTanimController::class, 'updateEtkinlikTipi'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.etkinlik-tipleri.update');
    Route::post('/sabit-tanimlar/evrak-tipleri', [SabitTanimController::class, 'storeEvrakTipi'])->middleware('yetki:sabit.olustur')->name('sabit-tanimlar.evrak-tipleri.store');
    Route::put('/sabit-tanimlar/evrak-tipleri/{evrakTipi}', [SabitTanimController::class, 'updateEvrakTipi'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.evrak-tipleri.update');
    Route::post('/sabit-tanimlar/basvuru-durumlari', [SabitTanimController::class, 'storeBasvuruDurum'])->middleware('yetki:sabit.olustur')->name('sabit-tanimlar.basvuru-durumlari.store');
    Route::put('/sabit-tanimlar/basvuru-durumlari/{basvuruDurum}', [SabitTanimController::class, 'updateBasvuruDurum'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.basvuru-durumlari.update');
    Route::post('/sabit-tanimlar/etkinlik-basvuru-durumlari', [SabitTanimController::class, 'storeEtkinlikBasvuruDurum'])->middleware('yetki:sabit.olustur')->name('sabit-tanimlar.etkinlik-basvuru-durumlari.store');
    Route::put('/sabit-tanimlar/etkinlik-basvuru-durumlari/{etkinlikBasvuruDurum}', [SabitTanimController::class, 'updateEtkinlikBasvuruDurum'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.etkinlik-basvuru-durumlari.update');
    Route::post('/sabit-tanimlar/basari-durumlari', [SabitTanimController::class, 'storeBasariDurum'])->middleware('yetki:sabit.olustur')->name('sabit-tanimlar.basari-durumlari.store');
    Route::put('/sabit-tanimlar/basari-durumlari/{basariDurum}', [SabitTanimController::class, 'updateBasariDurum'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.basari-durumlari.update');
    Route::post('/sabit-tanimlar/iptal-gerekceleri', [SabitTanimController::class, 'storeIptalGerekce'])->middleware('yetki:sabit.olustur')->name('sabit-tanimlar.iptal-gerekceleri.store');
    Route::put('/sabit-tanimlar/iptal-gerekceleri/{iptalGerekce}', [SabitTanimController::class, 'updateIptalGerekce'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.iptal-gerekceleri.update');
    Route::post('/sabit-tanimlar/kurumlar', [SabitTanimController::class, 'storeKurum'])->middleware('yetki:sabit.olustur')->name('sabit-tanimlar.kurumlar.store');
    Route::put('/sabit-tanimlar/kurumlar/{kurum}', [SabitTanimController::class, 'updateKurum'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.kurumlar.update');
    Route::put('/sabit-tanimlar/sertifika-ayarlari', [SabitTanimController::class, 'updateSertifikaAyarlari'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.sertifika-ayarlari.update');
    Route::put('/sabit-tanimlar/diger-ayarlar', [SabitTanimController::class, 'updateDigerAyarlar'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.diger-ayarlar.update');
    Route::put('/sabit-tanimlar/etkinlik-diger-ayarlar', [SabitTanimController::class, 'updateEtkinlikDigerAyarlar'])->middleware('yetki:sabit.guncelle')->name('sabit-tanimlar.etkinlik-diger-ayarlar.update');

    // —— Eğitmenler ——
    Route::get('/egitmenler', [EgitmenController::class, 'index'])->middleware('yetki:egitmen.goruntule')->name('egitmenler.index');
    Route::get('/egitmenler/yeni', [EgitmenController::class, 'create'])->middleware('yetki:egitmen.olustur')->name('egitmenler.create');
    Route::post('/egitmenler', [EgitmenController::class, 'store'])->middleware('yetki:egitmen.olustur')->name('egitmenler.store');
    Route::get('/egitmenler/excel', [EgitmenController::class, 'export'])->middleware('yetki:egitmen.export')->name('egitmenler.export');
    Route::get('/egitmenler/{egitmen}/kurslar/excel', [EgitmenController::class, 'exportKurslar'])->middleware('yetki:egitmen.export')->name('egitmenler.kurslar.export');
    Route::post('/egitmenler/{egitmen}/sms', [EgitmenController::class, 'sendSms'])->middleware('yetki:egitmen.sms')->name('egitmenler.sms.send');
    Route::post('/egitmenler/{egitmen}/eposta', [EgitmenController::class, 'sendEposta'])->middleware('yetki:egitmen.eposta')->name('egitmenler.eposta.send');
    Route::post('/egitmenler/{egitmen}/sifre', [EgitmenController::class, 'sendSifre'])->middleware('yetki:egitmen.sifre')->name('egitmenler.sifre.send');
    Route::get('/egitmenler/{egitmen}', [EgitmenController::class, 'show'])->middleware('yetki:egitmen.goruntule')->name('egitmenler.show');
    Route::get('/egitmenler/{egitmen}/duzenle', [EgitmenController::class, 'edit'])->middleware('yetki:egitmen.guncelle')->name('egitmenler.edit');
    Route::put('/egitmenler/{egitmen}', [EgitmenController::class, 'update'])->middleware('yetki:egitmen.guncelle')->name('egitmenler.update');
    Route::post('/egitmenler/{egitmen}/foto', [EgitmenController::class, 'updateFoto'])->middleware('yetki:egitmen.guncelle')->name('egitmenler.foto.update');
    Route::delete('/egitmenler/{egitmen}/foto', [EgitmenController::class, 'deleteFoto'])->middleware('yetki:egitmen.guncelle')->name('egitmenler.foto.delete');

    Route::get('/merkez-yetkileri', [MerkezYetkiController::class, 'index'])->middleware('yetki:kullanici.goruntule')->name('merkez-yetkileri.index');
    Route::get('/merkez-yetkileri/excel', [MerkezYetkiController::class, 'export'])->middleware('yetki:kullanici.export')->name('merkez-yetkileri.export');

    // —— Kullanıcılar ——
    Route::get('/kullanicilar', [KullaniciController::class, 'index'])->middleware('yetki:kullanici.goruntule')->name('kullanicilar.index');
    Route::get('/kullanicilar/yeni', [KullaniciController::class, 'create'])->middleware('yetki:kullanici.olustur')->name('kullanicilar.create');
    Route::post('/kullanicilar', [KullaniciController::class, 'store'])->middleware('yetki:kullanici.olustur')->name('kullanicilar.store');
    Route::get('/kullanicilar/excel', [KullaniciController::class, 'export'])->middleware('yetki:kullanici.export')->name('kullanicilar.export');
    Route::post('/kullanicilar/{kullanici}/sms', [KullaniciController::class, 'sendSms'])->middleware('yetki:kullanici.sms')->name('kullanicilar.sms.send');
    Route::post('/kullanicilar/{kullanici}/eposta', [KullaniciController::class, 'sendEposta'])->middleware('yetki:kullanici.eposta')->name('kullanicilar.eposta.send');
    Route::post('/kullanicilar/{kullanici}/sifre', [KullaniciController::class, 'sendSifre'])->middleware('yetki:kullanici.sifre')->name('kullanicilar.sifre.send');
    Route::post('/kullanicilar/{kullanici}/foto', [KullaniciController::class, 'updateFoto'])->middleware('yetki:kullanici.guncelle')->name('kullanicilar.foto.update');
    Route::delete('/kullanicilar/{kullanici}/foto', [KullaniciController::class, 'deleteFoto'])->middleware('yetki:kullanici.guncelle')->name('kullanicilar.foto.delete');
    Route::get('/kullanicilar/{kullanici}', [KullaniciController::class, 'show'])->middleware('yetki:kullanici.goruntule')->name('kullanicilar.show');
    Route::get('/kullanicilar/{kullanici}/merkez-yetkileri', [KullaniciController::class, 'editMerkezYetkileri'])->middleware('yetki:kullanici.guncelle')->name('kullanicilar.merkez-yetkileri.edit');
    Route::put('/kullanicilar/{kullanici}/merkez-yetkileri', [KullaniciController::class, 'updateMerkezYetkileri'])->middleware('yetki:kullanici.guncelle')->name('kullanicilar.merkez-yetkileri.update');
    Route::get('/kullanicilar/{kullanici}/duzenle', [KullaniciController::class, 'edit'])->middleware('yetki:kullanici.guncelle')->name('kullanicilar.edit');
    Route::put('/kullanicilar/{kullanici}', [KullaniciController::class, 'update'])->middleware('yetki:kullanici.guncelle')->name('kullanicilar.update');

    // —— Kişiler ——
    Route::get('/kisiler', [KisiController::class, 'index'])->middleware('yetki:kisi.goruntule')->name('kisiler.index');
    Route::get('/kisiler/yeni', [KisiController::class, 'create'])->middleware('yetki:kisi.olustur')->name('kisiler.create');
    Route::post('/kisiler', [KisiController::class, 'store'])->middleware('yetki:kisi.olustur')->name('kisiler.store');
    Route::post('/kisiler/kimlik-sorgula', [KisiController::class, 'kimlikSorgula'])->middleware('yetki:kisi.olustur,kisi.guncelle,kullanici.olustur,kullanici.guncelle,egitmen.olustur,egitmen.guncelle,basvuru.olustur,etkinlik_basvuru.olustur')->name('kisiler.kimlik-sorgula');
    Route::post('/kisiler/adres-sorgula', [KisiController::class, 'adresSorgula'])->middleware('yetki:kisi.olustur,kisi.guncelle,kullanici.olustur,kullanici.guncelle,egitmen.olustur,egitmen.guncelle,basvuru.olustur,etkinlik_basvuru.olustur')->name('kisiler.adres-sorgula');
    Route::get('/kisiler/excel', [KisiController::class, 'export'])->middleware('yetki:kisi.export')->name('kisiler.export');
    Route::post('/kisiler/{kisi}/sms', [KisiController::class, 'sendSms'])->middleware('yetki:kisi.sms')->name('kisiler.sms.send');
    Route::post('/kisiler/{kisi}/eposta', [KisiController::class, 'sendEposta'])->middleware('yetki:kisi.eposta')->name('kisiler.eposta.send');
    Route::post('/kisiler/{kisi}/foto', [KisiController::class, 'updateFoto'])->middleware('yetki:kisi.guncelle')->name('kisiler.foto.update');
    Route::delete('/kisiler/{kisi}/foto', [KisiController::class, 'deleteFoto'])->middleware('yetki:kisi.guncelle')->name('kisiler.foto.delete');
    Route::get('/kisiler/{kisi}', [KisiController::class, 'show'])->middleware('yetki:kisi.goruntule')->name('kisiler.show');
    Route::get('/kisiler/{kisi}/basvurular', [KisiController::class, 'basvurular'])->middleware('yetki:kisi.goruntule')->name('kisiler.basvurular');
    Route::get('/kisiler/{kisi}/duzenle', [KisiController::class, 'edit'])->middleware('yetki:kisi.guncelle')->name('kisiler.edit');
    Route::put('/kisiler/{kisi}', [KisiController::class, 'update'])->middleware('yetki:kisi.guncelle')->name('kisiler.update');

    // —— Roller ——
    Route::get('/roller', [RolController::class, 'index'])->middleware('yetki:rol.goruntule')->name('roller.index');
    Route::get('/roller/yeni', [RolController::class, 'create'])->middleware('yetki:rol.yonet')->name('roller.create');
    Route::post('/roller', [RolController::class, 'store'])->middleware('yetki:rol.yonet')->name('roller.store');
    Route::get('/roller/{rol}', [RolController::class, 'show'])->middleware('yetki:rol.goruntule')->name('roller.show');
    Route::get('/roller/{rol}/duzenle', [RolController::class, 'edit'])->middleware('yetki:rol.yonet')->name('roller.edit');
    Route::put('/roller/{rol}', [RolController::class, 'update'])->middleware('yetki:rol.yonet')->name('roller.update');
    Route::delete('/roller/{rol}', [RolController::class, 'destroy'])->middleware('yetki:rol.yonet')->name('roller.destroy');
});
