<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle user login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Check if 2FA is enabled
            if (Auth::user()->google2fa_secret) {
                return redirect()->route('2fa.verify');
            }

            return redirect()->intended('home');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Show the 2FA verification form.
     *
     * @return \Illuminate\View\View
     */
    public function show2faVerificationForm()
    {
        return view('auth.2fa_verify');
    }

    /**
     * Verify 2FA code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required',
        ]);

        $google2fa = new Google2FA();
        $user = Auth::user();

        if ($google2fa->verifyKey($user->google2fa_secret, $request->one_time_password)) {
            $request->session()->put('2fa_verified', true);
            return redirect()->intended('home');
        }

        return back()->withErrors([
            'one_time_password' => 'The provided 2FA code is invalid.',
        ]);
    }

    /**
     * Show the registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle user registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'kyc_status' => 'pending',
        ]);

        Auth::login($user);
        
        return redirect()->route('wallets.setup');
    }

    /**
     * Show the 2FA setup form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function show2faSetupForm(Request $request)
    {
        $google2fa = new Google2FA();
        $user = Auth::user();
        
        // Generate the secret key for the user
        $secretKey = $google2fa->generateSecretKey();
        
        // Store it temporarily in the session
        $request->session()->put('2fa_secret', $secretKey);
        
        // Generate the QR code URL
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secretKey
        );
        
        return view('auth.2fa_setup', compact('qrCodeUrl', 'secretKey'));
    }

    /**
     * Complete 2FA setup.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function complete2faSetup(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required',
        ]);

        $google2fa = new Google2FA();
        $user = Auth::user();
        $secretKey = $request->session()->get('2fa_secret');

        if (!$secretKey) {
            return back()->withErrors([
                'setup' => 'The 2FA setup session has expired. Please try again.',
            ]);
        }

        if ($google2fa->verifyKey($secretKey, $request->one_time_password)) {
            // The verification was successful, save the secret
            $user->google2fa_secret = $secretKey;
            $user->save();
            
            // Remove the temporary secret from the session
            $request->session()->forget('2fa_secret');
            
            return redirect()->route('home')->with('success', '2FA has been enabled for your account!');
        }

        return back()->withErrors([
            'one_time_password' => 'The provided 2FA code is invalid.',
        ]);
    }

    /**
     * Logout the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}
