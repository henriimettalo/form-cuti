<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordRecoveryController extends Controller
{
    public function create(): Response
    {
        return response()->view('auth.recovery')->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'size:64'],
            'password' => ['bail', 'required', 'confirmed', PasswordRule::min(12),
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strlen($value) > 72) {
                        $fail('Kata sandi tidak boleh melebihi 72 byte.');
                    }
                }],
        ]);

        $succeeded = Cache::lock('password-recovery:'.hash('sha256', Str::lower($input['email'])), 30)->get(
            fn () => DB::transaction(fn () => Password::reset($input, function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $user->revokeDatabaseSessions();
                $user->tokens()->delete();
            }) === Password::PASSWORD_RESET)
        );

        if (! $succeeded) {
            return back()->withInput($request->only('email'))
                ->withErrors(['token' => 'Kode pemulihan tidak sah atau sudah kedaluwarsa.']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('status', 'Kata sandi sudah diperbarui. Silakan masuk kembali.');
    }
}
