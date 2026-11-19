<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->hasAnyRole(...$roles),
            403,
        );

        if (! $user->isSuperAdmin() && ! $user->department?->is_active) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun diblokir karena unit kerja Anda sedang nonaktif.',
                ], 403);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')
                ->withErrors(['email' => 'Akun diblokir karena unit kerja Anda sedang nonaktif.']);
        }

        if (! $request->is('api/*')) {
            if ($user->mfa_secret !== null && $request->session()->get('mfa_user') !== $user->id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return to_route('login');
            }

            if ($user->canManageAccounts() && $user->mfa_secret === null && config('security.require_admin_mfa')) {
                return to_route('mfa.setup');
            }
        }

        return $next($request);
    }
}
