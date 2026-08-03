<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
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
