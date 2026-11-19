<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AdminMfaController extends Controller
{
    public function setup(Request $request, Google2FA $totp): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageAccounts() && ($user->isSuperAdmin() || $user->department?->is_active), 403);
        if ($user->mfa_secret !== null) {
            return to_route('dashboard');
        }

        $setup = $request->session()->get('mfa_setup');
        if (! is_array($setup) || $setup['user_id'] !== $user->id || $setup['expires_at'] < now()->timestamp) {
            $setup = ['user_id' => $user->id, 'secret' => Crypt::encryptString($totp->generateSecretKey(32)), 'expires_at' => now()->addMinutes(10)->timestamp];
            $request->session()->put('mfa_setup', $setup);
        }

        return response()->view('auth.mfa', ['setup' => Crypt::decryptString($setup['secret'])])->header('Cache-Control', 'no-store');
    }

    public function enroll(Request $request, Google2FA $totp): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageAccounts() && ($user->isSuperAdmin() || $user->department?->is_active), 403);
        $input = $request->validate(['password' => ['required', 'current_password'], 'code' => ['required', 'digits:6']]);
        $setup = $request->session()->get('mfa_setup');
        if (! is_array($setup) || $setup['user_id'] !== $user->id || $setup['expires_at'] < now()->timestamp) {
            return to_route('mfa.setup')->withErrors(['code' => 'Pengaturan kedaluwarsa. Masukkan kunci pengaturan yang baru.']);
        }

        $secret = Crypt::decryptString($setup['secret']);
        $step = $totp->verifyKeyNewer($secret, $input['code'], 0, 1);
        if ($step === false) {
            throw ValidationException::withMessages(['code' => 'Kode tidak sesuai. Periksa waktu perangkat Anda.']);
        }

        DB::transaction(function () use ($user, $secret, $step): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_if($locked->mfa_secret !== null, 409);
            abort_unless(hash_equals($locked->getAuthPassword(), $user->getAuthPassword()), 409);
            $locked->forceFill([
                'mfa_secret' => $secret,
                'mfa_last_step' => $step,
                'remember_token' => Str::random(60),
            ])->save();
            $locked->revokeDatabaseSessions();
        });

        $request->session()->forget('mfa_setup');
        $request->session()->regenerate(true);
        $request->session()->put('mfa_user', $user->id);

        return to_route('dashboard')->with('status', 'Verifikasi dua langkah aktif.');
    }

    public function challenge(Request $request): Response|RedirectResponse
    {
        if ($this->pendingUser($request) === null) {
            return to_route('login');
        }

        return response()->view('auth.mfa', ['setup' => null])->header('Cache-Control', 'no-store');
    }

    public function verify(Request $request, Google2FA $totp): RedirectResponse
    {
        $input = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $this->pendingUser($request);
        if ($user === null) {
            return to_route('login');
        }

        $step = $totp->verifyKeyNewer($user->mfa_secret, $input['code'], $user->mfa_last_step ?? 0, 1);
        $updated = $step !== false && User::query()->whereKey($user->id)
            ->where('mfa_secret', $user->getRawOriginal('mfa_secret'))
            ->where('password', $user->getAuthPassword())
            ->where(fn ($query) => $query->whereNull('mfa_last_step')->orWhere('mfa_last_step', '<', $step))
            ->update(['mfa_last_step' => $step]) === 1;
        if (! $updated) {
            throw ValidationException::withMessages(['code' => 'Kode salah atau sudah digunakan. Tunggu kode berikutnya.']);
        }

        Auth::login($user);
        $request->session()->forget('pending_mfa');
        $request->session()->regenerate(true);
        $request->session()->put('mfa_user', $user->id);

        return redirect()->intended(route('dashboard'));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('pending_mfa');
        if (! is_array($pending) || $pending['expires_at'] < now()->timestamp) {
            $request->session()->forget('pending_mfa');

            return null;
        }

        $user = User::query()->find($pending['user_id']);
        if ($user?->mfa_secret === null || ! hash_equals($pending['password_fingerprint'], hash('sha256', $user->getAuthPassword()))
            || (! $user->isSuperAdmin() && ! $user->department?->is_active)) {
            $request->session()->forget('pending_mfa');

            return null;
        }

        return $user;
    }
}
