@extends('layouts.public')
@section('title', 'Jadwal Publik')
@section('content')
    <h1 class="page-title">Jadwal publik</h1>
    <p class="page-description">Pilih jadwal yang ingin dilihat. Tidak perlu masuk ke sistem; semua jadwal hanya untuk dibaca.</p>
    <nav class="mt-6 grid gap-4 md:grid-cols-3" aria-label="Pilih jenis jadwal">
        @foreach (['apel' => ['Apel / Upacara', 'Lihat jadwal apel dan upacara setiap bulan.'], 'piket' => ['Piket Kecamatan', 'Lihat jadwal kelompok petugas piket kecamatan.'], 'kelurahan' => ['Piket Kelurahan', 'Lihat kelompok petugas kelurahan untuk kemarin, hari ini, dan besok.']] as $value => $details)
            <a class="card block focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-600 hover:border-sky-300" href="{{ route('public-schedules.index', ['tab' => $value]) }}">
                <h2 class="section-heading text-sky-700">{{ $details[0] }}</h2>
                <p class="section-description mt-2">{{ $details[1] }}</p>
            </a>
        @endforeach
    </nav>
@endsection
