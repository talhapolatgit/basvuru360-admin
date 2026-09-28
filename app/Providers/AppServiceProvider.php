<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Adres\AdresSorgulama;
use App\Services\Email\EmailSender;
use App\Services\Email\LoggingEmailSender;
use App\Services\Entegrasyon\EntegrasyonCozumleyici;
use App\Services\GuvenilirIpServisi;
use App\Services\Kimlik\KimlikSorgulama;
use App\Services\LogKaydedici;
use App\Services\Sms\LoggingSmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->girisHizSinirlariniKaydet();
    }

    /**
     * Admin ve portal girişi için IP bazlı istek sınırları. Güvenilir IP adresleri hiçbir sınıra takılmaz.
     */
    private function girisHizSinirlariniKaydet(): void
    {
        $sinir = fn (Request $request, Limit $limit) => app(GuvenilirIpServisi::class)->guvenilirMi($request->ip())
            ? Limit::none()
            : $limit;

        $asimYaniti = fn (string $alan) => function (Request $request, array $headers) use ($alan) {
            $saniye = max(1, (int) ($headers['Retry-After'] ?? 60));
            $mesaj = 'Çok fazla deneme yapıldı. Lütfen '
                .($saniye >= 60 ? (int) ceil($saniye / 60).' dakika' : $saniye.' saniye')
                .' sonra tekrar deneyin.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $mesaj,
                    'errors' => [$alan => [$mesaj]],
                ], 429, $headers);
            }

            return back()
                ->withInput($request->except('password', 'kod'))
                ->withErrors([$alan => $mesaj]);
        };

        RateLimiter::for('admin-giris', fn (Request $request) => $sinir($request, Limit::perMinute(30)
            ->by('admin-giris|'.$request->ip())
            ->response($asimYaniti('email'))));

        RateLimiter::for('admin-giris-dogrulama', fn (Request $request) => $sinir($request, Limit::perMinute(30)
            ->by('admin-giris-dogrulama|'.$request->ip())
            ->response($asimYaniti('kod'))));

        RateLimiter::for('admin-giris-kod-yenile', fn (Request $request) => $sinir($request, Limit::perMinutes(10, 15)
            ->by('admin-giris-kod-yenile|'.$request->ip())
            ->response($asimYaniti('kod'))));

        RateLimiter::for('portal-kayit', fn (Request $request) => $sinir($request, Limit::perMinute(30)
            ->by('portal-kayit|'.$request->ip())));

        RateLimiter::for('portal-giris', fn (Request $request) => $sinir($request, Limit::perMinute(30)
            ->by('portal-giris|'.$request->ip())));

        RateLimiter::for('portal-token-yenile', fn (Request $request) => $sinir($request, Limit::perMinute(90)
            ->by('portal-token-yenile|'.$request->ip())));
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
