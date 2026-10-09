@extends('layouts.app')
@section('title', 'Impor Dibatalkan')
@section('content')
    <section class="card" data-import-cancelled>
        <h1 class="page-title">Impor dibatalkan</h1>
        <p class="page-description" role="status">Pratinjau dan file unggahan dihapus. Data pegawai tidak diubah.</p>
        <a class="btn-primary mt-5" href="{{ route('employees.index') }}" data-import-modal-dismiss>Kembali ke pegawai</a>
    </section>
@endsection
