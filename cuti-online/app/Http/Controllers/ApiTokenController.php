<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('api-tokens.index', [
            'tokens' => $user->tokens()->latest()->get(),
            'abilityOptions' => config('api.abilities'),
            'apiBaseUrl' => url('/api/v1'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $abilityKeys = array_keys(config('api.abilities'));
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in($abilityKeys)],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ], [
            'abilities.required' => 'Pilih minimal satu izin untuk token.',
            'abilities.min' => 'Pilih minimal satu izin untuk token.',
        ]);
        $expiresAt = isset($data['expires_at'])
            ? CarbonImmutable::parse($data['expires_at'])->endOfDay()
            : null;
        $token = $request->user()->createToken($data['name'], $data['abilities'], $expiresAt);

        return to_route('api-tokens.index')
            ->with('status', 'Token API berhasil dibuat. Salin nilainya sekarang; token tidak akan ditampilkan lagi.')
            ->with('apiTokenPlainText', $token->plainTextToken);
    }

    public function destroy(Request $request, PersonalAccessToken $personalAccessToken): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $personalAccessToken->tokenable_type === $user->getMorphClass()
            && $personalAccessToken->tokenable_id === $user->getKey(),
            404,
        );

        $personalAccessToken->delete();

        return to_route('api-tokens.index')->with('status', 'Token API telah dicabut.');
    }
}
