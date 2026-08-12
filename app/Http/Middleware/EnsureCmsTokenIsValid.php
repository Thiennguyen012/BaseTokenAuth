<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsTokenIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $expiresAt = $request->session()->get('cms_access_token_expires_at');

        if ($expiresAt && now()->timestamp >= (int) $expiresAt) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('cms.login')
                ->withErrors(['email' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']);
        }

        return $next($request);
    }
}
