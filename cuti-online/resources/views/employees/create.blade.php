@extends('layouts.app')

@section('title', 'Tambah Pegawai')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Master data</p>
            <h1 class="page-title mt-2">Tambah pegawai</h1>
            <p class="page-description">Data jabatan disimpan sebagai master data. Unit kerja mengikuti profil instansi aplikasi.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('employees.index') }}">Kembali ke pegawai</a>
    </div>

    @include('employees._form', [
        'employee' => null,
        'action' => route('employees.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan pegawai',
    ])
@endsection
