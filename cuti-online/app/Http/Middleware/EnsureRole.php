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

        $isLegacyOperator = $user instanceof User
            && strtolower(trim((string) $user->role)) === 'operator';
        $canUseLegacyRoute = $isLegacyOperator && in_array(User::ROLE_SUPER_ADMIN, $roles, true);

        abort_unless(
            $user instanceof User && ($user->hasAnyRole(...$roles) || $canUseLegacyRoute),
            403,
        );

        if (! $canUseLegacyRoute && ! $user->isSuperAdmin() && ! $user->department?->is_active) {
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

        abort_if(! $canUseLegacyRoute && ! $user->isSuperAdmin() && $user->department_id === null, 403);

        return $next($request);
    }
}
