<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Display the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Authenticate the user.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email address or password is incorrect.'])
                ->onlyInput('email');
        }

        if (!Auth::user()->is_active) {
            Auth::logout();
            return back()
                ->withErrors(['email' => 'Your account has been deactivated.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        $user->update([
            'last_login_at' => now(),
        ]);

        if ($user->hasRole('supervisor') && !$user->hasRole(['admin', 'super-admin'])) {
            return redirect()->route('supervisor.dashboard');
        }

        if ($user->hasRole('loan-officer')) {
            return redirect()->route('loan-officer.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}