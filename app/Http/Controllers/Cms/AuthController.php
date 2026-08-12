<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Services\User\UserAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(protected UserAuthService $authService) {}

    public function showLogin(): View|RedirectResponse
    {
        $expiresAt = (int) session('cms_access_token_expires_at', 0);
        if (Auth::check() && session()->has('cms_access_token') && session('cms_access_token') !== '' && (!$expiresAt || $expiresAt > now()->timestamp)) {
            return redirect()->route('cms.dashboard');
        }

        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return view('cms.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $this->authService->login($credentials, [
                'device_name' => 'CMS Web',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (ValidationException) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])->onlyInput('email');
        }

        Auth::login($result['user']);
        $request->session()->regenerate();
        $request->session()->put([
            'cms_access_token' => $result['access_token'],
            'cms_refresh_token' => $result['refresh_token'],
            'cms_access_token_expires_at' => now()->addSeconds($result['access_token_expires_in'])->timestamp,
        ]);

        return redirect()->intended(route('cms.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('cms.login');
    }
}
