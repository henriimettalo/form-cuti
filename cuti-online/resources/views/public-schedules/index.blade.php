@extends('layouts.public')

@section('title', 'Jadwal kegiatan publik')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-sky-700">Informasi untuk umum</p>
            <h1 class="page-title mt-1">Jadwal kegiatan</h1>
            <p class="page-description">Lihat jadwal apel/upacara dan piket loket tanpa perlu masuk ke sistem.</p>
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">View only</span>
    </div>

    <nav class="mt-6 flex flex-wrap gap-2" aria-label="Jenis jadwal publik">
        <a class="{{ $tab === 'apel' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('public-schedules.index', ['tab' => 'apel', 'month' => $month->format('Y-m')]) }}">Apel / Upacara</a>
        <a class="{{ $tab === 'piket' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('public-schedules.index', ['tab' => 'piket', 'month' => $month->format('Y-m')]) }}">Piket Loket</a>
    </nav>

    <section class="card mt-5" aria-labelledby="public-calendar-heading">
        <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="section-heading" id="public-calendar-heading">{{ $tab === 'apel' ? 'Apel / Upacara' : 'Piket Loket' }} {{ $month->locale('id')->translatedFormat('F Y') }}</h2>
                <p class="section-description">Jadwal yang sudah dipublikasikan oleh pengelola.</p>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <a class="btn-secondary" href="{{ route('public-schedules.index', ['tab' => $tab, 'month' => $month->subMonth()->format('Y-m')]) }}" aria-label="Lihat {{ $month->subMonth()->locale('id')->translatedFormat('F Y') }}">Bulan sebelumnya</a>
                <form method="GET" action="{{ route('public-schedules.index') }}">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <label class="sr-only" for="public-month-trigger">Bulan dan tahun</label>
                    <x-month-picker id="public-month" name="month" value="{{ $month->format('Y-m') }}" :years="$years" :auto-submit="true" />
                </form>
                <a class="btn-secondary" href="{{ route('public-schedules.index', ['tab' => $tab, 'month' => $month->addMonth()->format('Y-m')]) }}" aria-label="Lihat {{ $month->addMonth()->locale('id')->translatedFormat('F Y') }}">Bulan berikutnya</a>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
            <div class="grid min-w-[42rem] grid-cols-7 bg-emerald-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-600">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                    <div class="border-b border-r border-slate-200 px-2 py-3">{{ $weekday }}</div>
                @endforeach
            </div>
            <div class="grid min-w-[42rem] grid-cols-7">
                @foreach ($calendarWeeks as $week)
                    @foreach ($week as $day)
                        @php($dateKey = $day['date']->toDateString())
                        @php($schedule = $day['inMonth'] ? $schedules->get($dateKey) : null)
                        <div class="min-h-32 border-b border-r border-slate-200 p-3 {{ $day['inMonth'] ? 'bg-white' : 'bg-slate-50 text-slate-400' }}">
                            <div class="flex items-start justify-between gap-2">
                                <span class="grid size-7 place-items-center rounded-full text-sm {{ $schedule ? 'bg-sky-100 font-semibold text-sky-800' : 'font-medium text-slate-600' }}">{{ $day['date']->format('j') }}</span>
                                @if ($schedule)
                                    <span class="text-[11px] text-slate-400">{{ $day['date']->format('d/m') }}</span>
                                @endif
                            </div>
                            @if ($schedule && $tab === 'apel')
                                <p class="mt-2 text-xs font-semibold text-sky-700">Kelompok {{ $schedule->ceremony_group_number ?? '-' }}</p>
                                <p class="mt-1 line-clamp-3 text-xs text-slate-600">{{ $schedule->department?->name ?? 'Belum dipilih' }}</p>
                            @elseif ($schedule)
                                <p class="mt-2 text-xs font-semibold text-sky-700">Kelompok {{ data_get($schedule->duty_roster, 'number', $schedule->dutyGroup?->number ?? '-') }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-700">{{ data_get($schedule->duty_roster, 'coordinator', 'Belum dipilih') }}</p>
                                @if (data_get($schedule->duty_roster, 'members', []))
                                    <ol class="mt-1 list-decimal space-y-0.5 pl-4 text-[11px] leading-4 text-slate-600">
                                        @foreach (data_get($schedule->duty_roster, 'members', []) as $member)
                                            <li>{{ $member }}</li>
                                        @endforeach
                                    </ol>
                                @endif
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        @if ($schedules->isEmpty())
            <p class="mt-4 text-sm text-slate-500">Belum ada jadwal pada bulan ini.</p>
        @endif
    </section>
@endsection
