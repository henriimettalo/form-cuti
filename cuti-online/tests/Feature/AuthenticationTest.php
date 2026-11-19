<?php

namespace Tests\Feature;

use App\Models\Department;
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

    public function test_legacy_operator_with_an_active_unit_has_unit_admin_access(): void
    {
        $user = User::query()->create([
            'name' => 'Operator Uji',
            'email' => 'operator@example.test',
            'password' => Hash::make('kata-sandi-aman'),
            'role' => 'operator',
            'department_id' => Department::query()->where('is_active', true)->firstOrFail()->id,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'kata-sandi-aman',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('users.index'))->assertOk();
        $this->get(route('employees.index'))->assertForbidden();
    }

    public function test_legacy_operator_without_a_unit_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'department_id' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
