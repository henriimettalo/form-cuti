@extends('layouts.app')

@section('title', 'Edit Pegawai')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Master data</p>
            <h1 class="page-title mt-2">Edit pegawai</h1>
            <p class="page-description">Perbarui data aktif pegawai. Perubahan pangkat atau jabatan akan tercatat otomatis sebagai riwayat pada tanggal hari ini.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('employees.index') }}">Kembali</a>
    </div>

    @include('employees._form', [
        'employee' => $employee,
        'action' => route('employees.update', $employee),
        'method' => 'PUT',
        'submitLabel' => 'Simpan perubahan',
    ])
@endsection
