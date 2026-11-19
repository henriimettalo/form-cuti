<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:create-admin {name? : Nama lengkap administrator} {email? : Email administrator} {--password= : Kata sandi administrator}', function (?string $name = null, ?string $email = null) {
    $name ??= $this->ask('Nama administrator');
    $email ??= $this->ask('Email administrator');
    $password = $this->option('password') ?: $this->secret('Kata sandi');

    if (blank($name) || blank($email) || blank($password)) {
        $this->components->error('Nama, email, dan kata sandi wajib diisi.');

        return Command::FAILURE;
    }

    User::query()->updateOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make($password),
            'role' => 'admin',
        ],
    );

    $this->components->info('Akun administrator siap digunakan.');

    return Command::SUCCESS;
})->purpose('Membuat atau memperbarui akun administrator SIMPEG');

Artisan::command('app:issue-password-recovery {email?}', function (?string $email = null) {
    $email ??= $this->ask('Email akun yang identitasnya sudah dikonfirmasi');
    $user = User::query()->where('email', trim((string) $email))->first();
    if ($user === null || ! $this->confirm('Terbitkan kode sekali pakai setelah identitas pengguna diverifikasi?')) {
        return Command::FAILURE;
    }

    $this->line(Password::createToken($user));
    $this->info('Kode berlaku 30 menit. Berikan secara privat kepada pemilik akun.');

    return Command::SUCCESS;
})->purpose('Menerbitkan kode pemulihan kata sandi sekali pakai');

Artisan::command('app:clear-admin-mfa {email?}', function (?string $email = null) {
    $email ??= $this->ask('Email admin yang identitasnya sudah dikonfirmasi');
    $user = User::query()->where('email', trim((string) $email))->first();
    if (! $user?->canManageAccounts() || ! $this->confirm('Cabut MFA dan semua sesi akun ini setelah identitasnya diverifikasi?')) {
        return Command::FAILURE;
    }

    DB::transaction(function () use ($user): void {
        $user->forceFill(['mfa_secret' => null, 'mfa_last_step' => null, 'remember_token' => Str::random(60)])->save();
        $user->revokeDatabaseSessions();
        $user->tokens()->delete();
    });

    $this->info('MFA dilepas. Admin harus mendaftarkan ulang perangkat verifikasi.');

    return Command::SUCCESS;
})->purpose('Pemulihan MFA Admin dari terminal server');
