@extends('layouts.public')

@section('title', 'Halaman Tidak Ditemukan')
@section('robots')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <section class="card mx-auto max-w-xl">
        <p class="text-sm font-semibold text-slate-600">404</p>
        <h1 class="page-title mt-2">Halaman tidak ditemukan</h1>
        <p class="page-description">Periksa kembali alamatnya atau buka jadwal publik.</p>
        <a class="btn-primary mt-5" href="{{ route('public-schedules.index') }}">Buka jadwal publik</a>
    </section>
@endsection
