<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorVerify
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Skip if user doesn't have 2FA enabled
        if (!$user->google2fa_secret) {
            return $next($request);
        }
        
        // Check if 2FA is required but not passed
        if (session('2fa_required') === true && session('2fa_passed') !== true) {
            return redirect()->route('2fa.verify');
        }
        
        return $next($request);
    }
}
