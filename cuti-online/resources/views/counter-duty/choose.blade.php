@extends('layouts.app')
@section('title', 'Jadwal Piket Loket')
@section('content')
    <div>
        <h1 class="page-title">Jadwal piket loket</h1>
        <p class="page-description">Pilih jenis jadwal yang ingin dilihat.</p>
    </div>
    <section class="mt-7 grid gap-5 md:grid-cols-2">
        <a class="card block transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md" href="{{ route('counter-duty.index', ['tab' => 'agenda', 'month' => $month->format('Y-m'), 'year' => $month->year]) }}">
            <p class="text-sm font-semibold text-sky-700">Jadwal kecamatan</p>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">Piket Kecamatan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Lihat jadwal piket loket berdasarkan kelompok petugas kecamatan.</p>
            <span class="btn-primary mt-5 inline-flex">Lihat jadwal kecamatan</span>
        </a>
        <a class="card block transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md" href="{{ route('counter-duty.kelurahan-calendar', ['month' => $month->format('Y-m')]) }}">
            <p class="text-sm font-semibold text-sky-700">Jadwal kelurahan</p>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">Piket Kelurahan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Lihat jadwal mandiri setiap kelurahan berdasarkan kelompok dan pegawainya.</p>
            <span class="btn-primary mt-5 inline-flex">Lihat jadwal kelurahan</span>
        </a>
    </section>
@endsection
