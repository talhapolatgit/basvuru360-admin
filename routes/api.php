<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BasvurularimController;
use App\Http\Controllers\Api\V1\EtkinlikBasvuruController;
use App\Http\Controllers\Api\V1\EtkinlikController;
use App\Http\Controllers\Api\V1\GenelAyarController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\KursBasvuruController;
use App\Http\Controllers\Api\V1\KursController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\PortalSayfaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class)->name('api.v1.health');
    Route::get('/genel-ayarlar', GenelAyarController::class)->name('api.v1.genel-ayarlar');
    Route::get('/logo', [GenelAyarController::class, 'logo'])->name('api.v1.logo');
    Route::get('/sidebar-logo', [GenelAyarController::class, 'sidebarLogo'])->name('api.v1.sidebar-logo');
    Route::get('/header-logo', [GenelAyarController::class, 'headerLogo'])->name('api.v1.header-logo');
    Route::get('/favicon', [GenelAyarController::class, 'favicon'])->name('api.v1.favicon');
    Route::get('/kurslar', [KursController::class, 'index'])->name('api.v1.kurslar.index');
    Route::get('/kurslar/{id}', [KursController::class, 'show'])->name('api.v1.kurslar.show');
    Route::get('/etkinlikler', [EtkinlikController::class, 'index'])->name('api.v1.etkinlikler.index');
    Route::get('/etkinlikler/{id}', [EtkinlikController::class, 'show'])->name('api.v1.etkinlikler.show');

    Route::get('/portal-sayfalar', [PortalSayfaController::class, 'index'])->name('api.v1.portal-sayfalar.index');
    Route::get('/portal-sayfalar/{slug}/anasayfa-logo', [PortalSayfaController::class, 'anasayfaLogo'])->name('api.v1.portal-sayfalar.anasayfa-logo');
    Route::get('/portal-sayfalar/{slug}/anasayfa-menu-arkaplan', [PortalSayfaController::class, 'anasayfaMenuArkaplan'])->name('api.v1.portal-sayfalar.anasayfa-menu-arkaplan');
    Route::get('/portal-sayfalar/{slug}/sidebar-ikon', [PortalSayfaController::class, 'sidebarIkon'])->name('api.v1.portal-sayfalar.sidebar-ikon');
    Route::get('/portal-sayfalar/{slug}', [PortalSayfaController::class, 'show'])->name('api.v1.portal-sayfalar.show');
    Route::get('/portal-sayfalar/{slug}/filtreler', [PortalSayfaController::class, 'filtreler'])->name('api.v1.portal-sayfalar.filtreler');
    Route::get('/portal-sayfalar/{slug}/kurslar', [PortalSayfaController::class, 'kurslar'])->name('api.v1.portal-sayfalar.kurslar');
    Route::get('/portal-sayfalar/{slug}/etkinlikler', [PortalSayfaController::class, 'etkinlikler'])->name('api.v1.portal-sayfalar.etkinlikler');

    Route::prefix('lookups')->group(function () {
        Route::get('/merkezler', [LookupController::class, 'merkezler'])->name('api.v1.lookups.merkezler');
        Route::get('/alanlar', [LookupController::class, 'alanlar'])->name('api.v1.lookups.alanlar');
        Route::get('/branslar', [LookupController::class, 'branslar'])->name('api.v1.lookups.branslar');
        Route::get('/kurs-tipleri', [LookupController::class, 'kursTipleri'])->name('api.v1.lookups.kurs-tipleri');
        Route::get('/etkinlik-tipleri', [LookupController::class, 'etkinlikTipleri'])->name('api.v1.lookups.etkinlik-tipleri');
        Route::get('/iptal-gerekceleri', [LookupController::class, 'iptalGerekceleri'])->name('api.v1.lookups.iptal-gerekceleri');
        Route::get('/iller', [LookupController::class, 'iller'])->name('api.v1.lookups.iller');
        Route::get('/ilceler', [LookupController::class, 'ilceler'])->name('api.v1.lookups.ilceler');
    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:10,1')
            ->name('api.v1.auth.register');

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('api.v1.auth.login');

        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:30,1')
            ->name('api.v1.auth.refresh');

        Route::middleware('jwt')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
            Route::put('/profil', [AuthController::class, 'updateProfil'])->name('api.v1.auth.profil');
            Route::put('/sifre', [AuthController::class, 'updateSifre'])->name('api.v1.auth.sifre');
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        });
    });

    Route::middleware('jwt')->group(function () {
        Route::get('/basvurularim', [BasvurularimController::class, 'index'])->name('api.v1.basvurularim');

        Route::post('/kurs-basvurulari', [KursBasvuruController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('api.v1.kurs-basvurulari.store');
        Route::get('/kurs-basvurulari/{id}', [KursBasvuruController::class, 'show'])
            ->name('api.v1.kurs-basvurulari.show');
        Route::get('/kurs-basvurulari/{id}/belge', [KursBasvuruController::class, 'belge'])
            ->middleware('throttle:20,1')
            ->name('api.v1.kurs-basvurulari.belge');
        Route::post('/kurs-basvurulari/{id}/iptal', [KursBasvuruController::class, 'iptal'])
            ->middleware('throttle:20,1')
            ->name('api.v1.kurs-basvurulari.iptal');

        Route::post('/etkinlik-basvurulari', [EtkinlikBasvuruController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('api.v1.etkinlik-basvurulari.store');
        Route::get('/etkinlik-basvurulari/{id}', [EtkinlikBasvuruController::class, 'show'])
            ->name('api.v1.etkinlik-basvurulari.show');
        Route::post('/etkinlik-basvurulari/{id}/iptal', [EtkinlikBasvuruController::class, 'iptal'])
            ->middleware('throttle:20,1')
            ->name('api.v1.etkinlik-basvurulari.iptal');
    });
});
