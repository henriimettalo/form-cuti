@extends('layouts.public')

@php
    $scheduleTitle = $tab === 'apel' ? 'Apel / Upacara' : 'Piket Loket';
    $periodLabel = $month->locale('id')->translatedFormat('F Y');
@endphp
@section('title', 'Jadwal '.$scheduleTitle.' '.$periodLabel)
@section('description', $tab === 'apel'
    ? 'Lihat jadwal apel dan upacara beserta unit petugas di Kecamatan Pontianak Selatan untuk '.$periodLabel.'.'
    : 'Lihat jadwal piket loket, kelompok, dan petugas Kecamatan Pontianak Selatan untuk '.$periodLabel.'.')
@section('share-url', route('public-schedules.index', ['tab' => $tab, 'month' => $month->format('Y-m')]))

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-sky-700">Informasi untuk umum</p>
            <h1 class="page-title mt-1">Jadwal kegiatan</h1>
            <p class="page-description">Lihat jadwal apel/upacara dan piket loket tanpa perlu masuk ke sistem.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 print:hidden">
            <button class="btn-secondary" type="button" data-public-print>Unduh PDF / Cetak</button>
            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">View only</span>
        </div>
    </div>

    <section class="card mt-5" aria-labelledby="public-calendar-heading" data-public-calendar data-public-calendar-type="{{ $tab }}">
        <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="section-heading" id="public-calendar-heading">{{ $scheduleTitle }} {{ $periodLabel }}</h2>
                <p class="section-description">Jadwal yang sudah dipublikasikan oleh pengelola.</p>
            </div>
            <div class="flex flex-wrap items-end gap-2 print:hidden">
                <form class="w-full sm:w-auto" method="GET" action="{{ route('public-schedules.index') }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <x-month-picker id="public-month" name="month" value="{{ $month->format('Y-m') }}" :years="$years" :auto-submit="true" />
                </form>
                <a class="btn-secondary" href="{{ route('public-schedules.index', ['tab' => $tab, 'month' => $month->subMonth()->format('Y-m')]) }}" aria-label="Lihat {{ $month->subMonth()->locale('id')->translatedFormat('F Y') }}">Bulan sebelumnya</a>
                <a class="btn-secondary" href="{{ route('public-schedules.index', ['tab' => $tab, 'month' => $month->addMonth()->format('Y-m')]) }}" aria-label="Lihat {{ $month->addMonth()->locale('id')->translatedFormat('F Y') }}">Bulan berikutnya</a>
            </div>
        </div>

        <div class="mt-5 hidden overflow-x-auto rounded-xl border border-slate-200 lg:block print:block" data-public-calendar-grid>
            <div class="grid min-w-[42rem] grid-cols-7 bg-emerald-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                    <div class="border-b border-r border-slate-200 px-2 py-3">{{ $weekday }}</div>
                @endforeach
            </div>
            <div class="grid min-w-[42rem] grid-cols-7">
                @foreach ($calendarWeeks as $week)
                    @foreach ($week as $day)
                        @php($dateKey = $day['date']->toDateString())
                        @php($daySchedules = $day['inMonth'] ? $schedules->get($dateKey, collect()) : collect())
                        <div class="min-h-32 border-b border-r border-slate-200 p-3 {{ $day['inMonth'] ? 'bg-white' : 'bg-slate-50 text-slate-600' }}">
                            <div class="flex items-start justify-between gap-2">
                                <span class="grid size-7 place-items-center rounded-full text-sm {{ $daySchedules->isNotEmpty() ? 'bg-sky-100 font-semibold text-sky-800' : 'font-medium text-slate-600' }}">{{ $day['date']->format('j') }}</span>
                                @if ($daySchedules->isNotEmpty())
                                    <span class="text-[11px] text-slate-600">{{ $day['date']->format('d/m') }}</span>
                                @endif
                            </div>
                            @foreach ($daySchedules as $schedule)
                                <div class="{{ $loop->first ? '' : 'mt-2 border-t border-slate-200 pt-2' }}">
                                    @include('public-schedules.schedule-entry')
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        @if ($schedules->isNotEmpty())
            <ol class="mt-5 divide-y divide-slate-200 lg:hidden print:hidden" aria-label="Daftar jadwal bulanan" data-public-schedule-list>
                @foreach ($schedules as $date => $daySchedules)
                    <li class="py-4 first:pt-0 last:pb-0">
                        <h3 class="font-semibold text-slate-900">
                            <time datetime="{{ $date }}">{{ $daySchedules->first()->event_date->locale('id')->translatedFormat('l, j F Y') }}</time>
                        </h3>
                        @foreach ($daySchedules as $schedule)
                            <div class="{{ $loop->first ? '' : 'mt-2 border-t border-slate-100 pt-2' }}">
                                @include('public-schedules.schedule-entry')
                            </div>
                        @endforeach
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($schedules->isEmpty())
            <p class="mt-4 text-sm text-slate-500">Belum ada jadwal pada bulan ini.</p>
        @endif
    </section>
@endsection
