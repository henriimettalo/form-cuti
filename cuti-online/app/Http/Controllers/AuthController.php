<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $request->session()->forget('pending_mfa');
        $pending = null;
        $inactive = false;
        $accepted = Auth::attemptWhen($credentials, function (User $user) use (&$pending, &$inactive): bool {
            if (! $user->isSuperAdmin() && ! $user->department?->is_active) {
                $inactive = true;

                return false;
            }

            if ($user->mfa_secret !== null) {
                $pending = $user;

                return false;
            }

            return true;
        }, $request->boolean('remember'));

        if ($pending instanceof User) {
            $request->session()->regenerate();
            $request->session()->put('pending_mfa', [
                'user_id' => $pending->id,
                'password_fingerprint' => hash('sha256', $pending->getAuthPassword()),
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]);

            return to_route('mfa.challenge');
        }

        if (! $accepted) {
            return to_route('login')
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => $inactive
                    ? 'Akun diblokir karena unit kerja Anda sedang nonaktif.'
                    : 'Email atau kata sandi tidak sesuai.']);
        }

        $request->session()->forget(['pending_mfa', 'mfa_user']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
