<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\User\UserAuthService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsTokenIsValid
{
    public function __construct(private UserAuthService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $expiresAt = $request->session()->get('cms_access_token_expires_at');

        if ($expiresAt && now()->timestamp >= (int) $expiresAt) {
            try {
                $result = $this->authService->refresh((string) $request->session()->get('cms_refresh_token', ''));
                $request->session()->put([
                    'cms_access_token' => $result['access_token'],
                    'cms_refresh_token' => $result['refresh_token'],
                    'cms_access_token_expires_at' => now()->addSeconds($result['access_token_expires_in'])->timestamp,
                ]);
            } catch (ValidationException) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('cms.login')
                    ->withErrors(['email' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']);
            }
        }

        return $next($request);
    }
}
