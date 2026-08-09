@extends('layouts.app')

@section('title', 'Profil Instansi')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Identitas aplikasi</p>
        <h1 class="page-title mt-2 text-balance">Profil instansi</h1>
        <p class="page-description text-pretty">Atur unit kerja bawaan untuk pegawai baru dan data impor yang tidak memiliki kolom Unit Kerja.</p>
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="card">
            <h2 class="section-heading text-balance">Unit kerja bawaan</h2>
            <p class="section-description text-pretty">Data pegawai yang sudah memiliki unit kerja sendiri tidak diubah saat nilai ini diperbarui.</p>

            <form class="mt-6 grid gap-5" method="POST" action="{{ route('organization-profile.store') }}">
                @csrf
                <div>
                    <label class="form-label" for="name">Nama unit kerja</label>
                    <input class="form-input" id="name" name="name" value="{{ old('name', $organizationProfile->name) }}" maxlength="255" placeholder="Contoh: Kecamatan Pontianak Selatan" required>
                    <p class="form-help text-pretty">Digunakan pada pegawai baru dan impor tanpa Unit Kerja. Data dari file impor tetap dapat memakai unit kerja masing-masing.</p>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end">
                    <button class="btn-primary" type="submit">Simpan profil instansi</button>
                </div>
            </form>
        </section>

        <aside class="card h-fit">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-sky-700">Bawaan aplikasi</p>
            <p class="mt-3 text-base font-semibold text-slate-900">{{ $organizationProfile->name }}</p>
            <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500 text-pretty">Unit kerja yang sudah tersimpan pada pegawai dan riwayat jabatan tidak diubah, sehingga data kelurahan tetap aman.</p>
        </aside>
    </div>
@endsection
