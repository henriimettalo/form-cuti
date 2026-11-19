@extends('layouts.public')

@section('title', 'Pemulihan Kata Sandi')
@section('robots')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <section class="card mx-auto max-w-lg">
        <h1 class="page-title">Pemulihan kata sandi</h1>
        <p class="page-description">Minta kode sekali pakai kepada pengelola SIMPEG setelah identitas Anda diverifikasi. Kode berlaku 30 menit.</p>
        <form class="mt-6 space-y-4" method="POST" action="{{ route('password.recovery.store') }}">
            @csrf
            <div>
                <label class="form-label" for="recovery-email">Email akun</label>
                <input class="form-input" id="recovery-email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required>
                @error('email') <p class="form-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="recovery-code">Kode pemulihan</label>
                <input class="form-input" id="recovery-code" name="token" type="password" autocomplete="off" minlength="64" maxlength="64" required>
                @error('token') <p class="form-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="recovery-password">Kata sandi baru</label>
                <input class="form-input" id="recovery-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
                @error('password') <p class="form-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="recovery-password-confirmation">Ulangi kata sandi baru</label>
                <input class="form-input" id="recovery-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            </div>
            <button class="btn-primary" type="submit">Simpan kata sandi baru</button>
        </form>
    </section>
@endsection
