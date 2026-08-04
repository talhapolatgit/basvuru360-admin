<?php

namespace App\Http\Controllers\Auth;

use App\Enums\IkiAsamaliGuvenlik;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\IkiAsamaliGirisServisi;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function home(): RedirectResponse
    {
        return auth()->check()
            ? redirect()->route('anasayfa')
            : redirect()->route('login');
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, IkiAsamaliGirisServisi $ikiAsamali): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'password.required' => 'Şifre zorunludur.',
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user && ! $user->aktif) {
            throw ValidationException::withMessages([
                'email' => 'Bu hesap bloke edilmiş veya pasif durumda. Yöneticinize başvurun.',
            ])->redirectTo(back()->getTargetUrl());
        }

        if (! Auth::validate($credentials)) {
            Event::dispatch(new Failed('web', $user, $credentials));

            if ($user) {
                if ($ikiAsamali->hataliGirisKaydet($user)) {
                    throw ValidationException::withMessages([
                        'email' => 'Ardışık 4 hatalı giriş nedeniyle hesabınız bloke edildi. Yöneticinize başvurun.',
                    ])->redirectTo(back()->getTargetUrl());
                }
            }

            throw ValidationException::withMessages([
                'email' => 'Girdiğiniz bilgiler kayıtlarımızla eşleşmiyor.',
            ])->redirectTo(back()->getTargetUrl());
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Girdiğiniz bilgiler kayıtlarımızla eşleşmiyor.',
            ])->redirectTo(back()->getTargetUrl());
        }

        $ikiAsamali->hataliGirisSifirla($user);

        $kanal = $user->iki_asamali_guvenlik ?? IkiAsamaliGuvenlik::Hayir;
        $remember = $request->boolean('remember');

        if ($kanal instanceof IkiAsamaliGuvenlik && $kanal->aktifMi()) {
            $gonderim = $ikiAsamali->kodGonder($user);

            $request->session()->put('login.pending_user_id', $user->id);
            $request->session()->put('login.remember', $remember);
            $request->session()->put('login.kanal', $gonderim['kanal']);
            $request->session()->put('login.hedef', $gonderim['hedef']);
            $request->session()->put(
                'login.resend_after',
                now()->timestamp + (int) ($gonderim['yeniden_gonderim_saniye'] ?? 120)
            );
            $request->session()->regenerate();

            $target = route('login.dogrulama');

            if ($request->expectsJson()) {
                return response()->json([
                    'redirect' => $target,
                    'iki_asamali' => true,
                    'message' => 'Doğrulama kodu gönderildi.',
                ]);
            }

            return redirect()->to($target)
                ->with('success', 'Doğrulama kodu '.$gonderim['hedef'].' adresine gönderildi.');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $target = redirect()->intended(route('anasayfa'))->getTargetUrl();

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $target]);
        }

        return redirect()->to($target);
    }

    public function dogrulamaForm(Request $request, IkiAsamaliGirisServisi $ikiAsamali): View|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.login-dogrulama', [
            'kanal' => $request->session()->get('login.kanal', 'sms'),
            'hedef' => $request->session()->get('login.hedef', '****'),
            'yenidenGonderimKalan' => max(
                $ikiAsamali->yenidenGonderimKalanSaniye($user->id),
                max(0, (int) $request->session()->get('login.resend_after', 0) - now()->timestamp)
            ),
        ]);
    }

    public function dogrulama(Request $request, IkiAsamaliGirisServisi $ikiAsamali): RedirectResponse|JsonResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            throw ValidationException::withMessages([
                'kod' => 'Oturum doğrulama süresi dolmuş. Lütfen tekrar giriş yapın.',
            ])->redirectTo(route('login'));
        }

        $validated = $request->validate([
            'kod' => ['required', 'string', 'min:4', 'max:12'],
        ], [
            'kod.required' => 'Doğrulama kodu zorunludur.',
        ]);

        $ikiAsamali->koduDogrula($user, $validated['kod']);
        $ikiAsamali->hataliGirisSifirla($user);

        $remember = (bool) $request->session()->pull('login.remember', false);
        $this->clearPendingLogin($request);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $target = redirect()->intended(route('anasayfa'))->getTargetUrl();

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $target]);
        }

        return redirect()->to($target);
    }

    public function kodYenile(Request $request, IkiAsamaliGirisServisi $ikiAsamali): RedirectResponse|JsonResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            throw ValidationException::withMessages([
                'kod' => 'Oturum doğrulama süresi dolmuş. Lütfen tekrar giriş yapın.',
            ])->redirectTo(route('login'));
        }

        $gonderim = $ikiAsamali->kodGonder($user, yeniden: true);
        $request->session()->put('login.kanal', $gonderim['kanal']);
        $request->session()->put('login.hedef', $gonderim['hedef']);
        $request->session()->put(
            'login.resend_after',
            now()->timestamp + (int) ($gonderim['yeniden_gonderim_saniye'] ?? 120)
        );

        $message = 'Yeni doğrulama kodu '.$gonderim['hedef'].' adresine gönderildi.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'hedef' => $gonderim['hedef'],
                'kanal' => $gonderim['kanal'],
                'yeniden_gonderim_saniye' => $gonderim['yeniden_gonderim_saniye'],
            ]);
        }

        return back()->with('success', $message);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = (int) $request->session()->get('login.pending_user_id', 0);
        if ($userId <= 0) {
            return null;
        }

        return User::query()->whereKey($userId)->where('aktif', true)->first();
    }

    private function clearPendingLogin(Request $request): void
    {
        $request->session()->forget([
            'login.pending_user_id',
            'login.remember',
            'login.kanal',
            'login.hedef',
            'login.resend_after',
        ]);
    }
}
