<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_operator_can_login(): void
    {
        $user = User::query()->create([
            'name' => 'Operator Uji',
            'email' => 'operator@example.test',
            'password' => Hash::make('kata-sandi-aman'),
            'role' => 'operator',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'kata-sandi-aman',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
