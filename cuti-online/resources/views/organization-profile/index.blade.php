@extends('layouts.app')

@section('title', 'Profil Instansi')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Identitas aplikasi</p>
        <h1 class="page-title mt-2 text-balance">Profil instansi</h1>
        <p class="page-description text-pretty">Atur satu unit kerja yang dipakai otomatis oleh seluruh data pegawai dan formulir baru.</p>
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="card">
            <h2 class="section-heading text-balance">Unit kerja aplikasi</h2>
            <p class="section-description text-pretty">Nama ini tidak lagi diisi per pegawai. Perubahannya diterapkan ke data pegawai aktif maupun arsip.</p>

            <form class="mt-6 grid gap-5" method="POST" action="{{ route('organization-profile.store') }}">
                @csrf
                <div>
                    <label class="form-label" for="name">Nama unit kerja</label>
                    <input class="form-input" id="name" name="name" value="{{ old('name', $organizationProfile->name) }}" placeholder="Contoh: Kecamatan Pontianak Selatan" required>
                    <p class="form-help text-pretty">Digunakan pada data pegawai baru, impor pegawai, riwayat jabatan baru, dan formulir cuti yang dibuat setelah perubahan.</p>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end">
                    <button class="btn-primary" type="submit">Simpan profil instansi</button>
                </div>
            </form>
        </section>

        <aside class="card h-fit">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-sky-700">Berlaku global</p>
            <p class="mt-3 text-base font-semibold text-slate-900">{{ $organizationProfile->name }}</p>
            <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500 text-pretty">Riwayat jabatan yang telah tersimpan tidak diubah, sehingga jejak mutasi sebelumnya tetap aman.</p>
        </aside>
    </div>
@endsection
