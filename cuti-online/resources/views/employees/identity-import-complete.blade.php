@extends('layouts.app')
@section('title', 'Impor Selesai')
@section('content')
    <section class="card" data-import-completed>
        <h1 class="page-title">Impor selesai</h1>
        <p class="page-description" role="status">{{ $count }} data identitas pegawai berhasil diimpor.</p>
        <a class="btn-primary mt-5" href="{{ route('employees.index') }}" data-import-modal-dismiss>Tutup dan perbarui daftar</a>
    </section>
@endsection
