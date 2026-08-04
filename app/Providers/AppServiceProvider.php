<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Adres\AdresSorgulama;
use App\Services\Email\EmailSender;
use App\Services\Email\LoggingEmailSender;
use App\Services\Entegrasyon\EntegrasyonCozumleyici;
use App\Services\Kimlik\KimlikSorgulama;
use App\Services\LogKaydedici;
use App\Services\Sms\LoggingSmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsSender::class, function ($app) {
            $inner = $app->make(EntegrasyonCozumleyici::class)->smsSender();

            return new LoggingSmsSender($inner);
        });

        $this->app->bind(KimlikSorgulama::class, function ($app) {
            return $app->make(EntegrasyonCozumleyici::class)->kimlikSorgulama();
        });

        $this->app->bind(AdresSorgulama::class, function ($app) {
            return $app->make(EntegrasyonCozumleyici::class)->adresSorgulama();
        });

        $this->app->bind(EmailSender::class, function ($app) {
            $inner = $app->make(EntegrasyonCozumleyici::class)->emailSender();

            return new LoggingEmailSender($inner);
        });
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tailwind');

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->yetkiSisteminiKaydet();
        $this->oturumLoglariniDinle();
    }

    /**
     * Gate, Blade @yetki / @anyYetki direktifleri.
     */
    private function yetkiSisteminiKaydet(): void
    {
        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (! $user instanceof User) {
                return null;
            }

            // Admin her yetkiyi geçer.
            if ($user->isAdmin()) {
                return true;
            }

            return null;
        });

        Gate::define('yetki', function (User $user, string $kod) {
            return $user->hasYetki($kod);
        });

        Blade::if('yetki', function (string|array $kod) {
            $user = auth()->user();

            return $user instanceof User && $user->hasYetki($kod);
        });

        Blade::if('anyYetki', function (string|array $kodlar) {
            $user = auth()->user();

            return $user instanceof User && $user->hasAnyYetki($kodlar);
        });

        View::composer('*', function () {
            $user = auth()->user();
            if ($user instanceof User) {
                $user->loadMissing(['roller.yetkiler']);
            }
        });
    }

    /**
     * Giriş, çıkış ve başarısız giriş denemelerini log_kayitlari tablosuna yazar.
     */
    private function oturumLoglariniDinle(): void
    {
        Event::listen(function (Login $event) {
            $kullanici = $event->user;

            LogKaydedici::kaydet(
                islem: 'oturum.giris',
                aciklama: ($kullanici->tam_adi ?? 'Kullanıcı').' sisteme giriş yaptı.',
                konu: $kullanici instanceof Model ? $kullanici : null,
                konuAdi: $kullanici->tam_adi ?? null,
                userId: (int) $kullanici->getAuthIdentifier(),
            );
        });

        Event::listen(function (Logout $event) {
            $kullanici = $event->user;

            if (! $kullanici) {
                return;
            }

            LogKaydedici::kaydet(
                islem: 'oturum.cikis',
                aciklama: ($kullanici->tam_adi ?? 'Kullanıcı').' sistemden çıkış yaptı.',
                konu: $kullanici instanceof Model ? $kullanici : null,
                konuAdi: $kullanici->tam_adi ?? null,
                userId: (int) $kullanici->getAuthIdentifier(),
            );
        });

        Event::listen(function (Failed $event) {
            $email = is_array($event->credentials) ? ($event->credentials['email'] ?? null) : null;

            LogKaydedici::kaydet(
                islem: 'oturum.basarisiz_giris',
                aciklama: 'Başarısız giriş denemesi'.($email ? ': '.$email : '').'.',
                konu: $event->user instanceof Model ? $event->user : null,
                konuAdi: $email,
                ekstra: $email ? ['email' => $email] : [],
            );
        });
    }
}
