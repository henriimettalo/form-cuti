@extends('layouts.public')

@section('title', 'Verifikasi Dua Langkah')
@section('robots')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <section class="card mx-auto max-w-lg">
        <h1 class="page-title">{{ $setup ? 'Aktifkan verifikasi dua langkah' : 'Verifikasi dua langkah' }}</h1>
        @if ($setup)
            <p class="page-description">Di aplikasi autentikator, tambahkan akun dengan kunci pengaturan berbasis waktu. Masukkan kunci berikut, lalu konfirmasi kata sandi dan kode dari aplikasi.</p>
            <p class="mt-4 break-all font-mono text-sm text-slate-900">{{ $setup }}</p>
        @else
            <p class="page-description">Masukkan kode dari aplikasi autentikator Anda.</p>
        @endif

        <form class="mt-6 space-y-4" method="POST" action="{{ $setup ? route('mfa.enroll') : route('mfa.verify') }}">
            @csrf
            @if ($setup)
                <div>
                    <label class="form-label" for="mfa-password">Kata sandi saat ini</label>
                    <input class="form-input" id="mfa-password" name="password" type="password" autocomplete="current-password" required>
                    @error('password') <p class="form-error" role="alert">{{ $message }}</p> @enderror
                </div>
            @endif
            <div>
                <label class="form-label" for="mfa-code">Kode 6 digit</label>
                <input class="form-input" id="mfa-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                @error('code') <p class="form-error" role="alert">{{ $message }}</p> @enderror
            </div>
            <button class="btn-primary" type="submit">{{ $setup ? 'Aktifkan verifikasi' : 'Verifikasi dan masuk' }}</button>
        </form>
    </section>
@endsection
