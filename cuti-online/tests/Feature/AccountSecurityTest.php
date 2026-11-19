<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ClamavScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PragmaRX\Google2FA\Google2FA;
use RuntimeException;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_admin_needs_a_new_totp_code_after_entering_password(): void
    {
        config(['security.require_admin_mfa' => true]);
        $user = User::factory()->create();
        $totp = new Google2FA;
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('mfa.setup'));
        $this->get(route('mfa.setup'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $secret = Crypt::decryptString(session('mfa_setup.secret'));
        $firstCode = $totp->getCurrentOtp($secret);

        $this->post(route('mfa.enroll'), ['password' => 'password', 'code' => $firstCode])
            ->assertRedirect(route('dashboard'));
        $this->assertNotSame($secret, $user->fresh()->getRawOriginal('mfa_secret'));
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => '1'])
            ->assertRedirect(route('mfa.challenge'));
        $this->assertGuest();
        $this->post(route('mfa.verify'), ['code' => $firstCode])->assertSessionHasErrors('code');
        $this->assertGuest();

        $nextCode = $totp->oathTotp($secret, $user->fresh()->mfa_last_step + 1);
        $this->post(route('mfa.verify'), ['code' => $nextCode])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_password_recovery_code_is_consumed_and_old_api_tokens_are_revoked(): void
    {
        $user = User::factory()->create();
        $user->createToken('Old integration');
        $input = [
            'email' => $user->email,
            'token' => Password::createToken($user),
            'password' => 'new-long-password',
            'password_confirmation' => 'new-long-password',
        ];

        $this->post(route('password.recovery.store'), $input)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-long-password', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->post(route('password.recovery.store'), $input)->assertSessionHasErrors('token');
        $this->assertGuest();
    }

    public function test_upload_is_rejected_before_preview_if_the_scanner_is_unavailable(): void
    {
        config(['security.scan_uploads' => true]);
        $this->mock(ClamavScanner::class)
            ->shouldReceive('isClean')->once()->andThrow(new RuntimeException('Scanner unavailable'));

        $this->actingAs(User::factory()->create())->post(route('employees.identity-import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('pegawai.csv', "nip,nama\n123456789012345678,Nama Uji"),
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('employees', 0);
    }
}
