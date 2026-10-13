@extends('layouts.app')
@section('title', 'Kalender Kelurahan')
@section('content')
    <div>
        <h1 class="page-title">Kalender kelurahan</h1>
        <p class="page-description">Tampilan khusus jadwal piket berdasarkan tanggal dan kelurahan.</p>
    </div>

    <section class="card mt-5">
        <div class="border-b border-slate-100 pb-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <h2 class="section-heading">Piket {{ $month->locale('id')->translatedFormat('F Y') }}</h2>
                        <p class="section-description">Kelurahan berada di bagian atas, tanggal berada di sisi kiri.</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 xl:flex-1">
                        <div class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                            <a class="btn-secondary shrink-0 whitespace-nowrap" href="{{ route('counter-duty.kelurahan-calendar', ['month' => $month->subMonth()->format('Y-m')]) }}">Bulan sebelumnya</a>
                            <form class="w-44 shrink-0" method="GET" action="{{ route('counter-duty.kelurahan-calendar') }}">
                                <label class="sr-only" for="kelurahan-calendar-month-trigger">Bulan dan tahun</label>
                                <x-month-picker id="kelurahan-calendar-month" name="month" value="{{ $month->format('Y-m') }}" :years="range(2026, 2030)" :auto-submit="true" />
                            </form>
                            <a class="btn-secondary shrink-0 whitespace-nowrap" href="{{ route('counter-duty.kelurahan-calendar', ['month' => $month->addMonth()->format('Y-m')]) }}">Bulan berikutnya</a>
                        </div>
                        @if ($canManage)<a class="btn-primary shrink-0 whitespace-nowrap xl:ml-auto" href="{{ route('counter-duty.kelurahan-assignments', ['month' => $month->format('Y-m')]) }}">Atur petugas kelurahan</a>@endif
                    </div>
                </div>
            @if ($canManage)<p class="mt-3 text-xs leading-relaxed text-slate-500">Klik sel kelurahan untuk memilih kelompok. Rotasi mengisi tanggal hari ini dan seterusnya dalam bulan ini, termasuk akhir pekan. Jadwal tanggal yang sudah lewat terkunci.</p>@endif
        </div>

        <div class="mt-5 max-h-[70vh] overflow-auto rounded-xl border border-slate-200" tabindex="0" role="region" aria-label="Tabel jadwal piket kelurahan">
            <table class="w-full min-w-[760px] border-collapse text-left text-xs">
                <thead class="bg-emerald-50 text-slate-700">
                    <tr>
                        <th scope="col" class="sticky left-0 top-0 z-30 min-w-28 border-b border-r border-slate-200 bg-emerald-50 px-3 py-3 font-semibold">Tanggal</th>
                        @foreach ($kelurahans as $kelurahan)
                            <th scope="col" class="sticky top-0 z-20 min-w-40 border-b border-r border-slate-200 bg-emerald-50 px-3 py-3 font-semibold">{{ $kelurahan->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (range(1, $month->daysInMonth) as $dayNumber)
                        @php($date = $month->startOfMonth()->addDays($dayNumber - 1))
                        @php($daySchedules = $schedules->get($date->toDateString(), collect()))
                        @php($isPast = $date->lessThan($today))
                        <tr class="{{ $isPast ? 'bg-slate-100 text-slate-500' : ($date->isWeekend() ? 'bg-slate-50' : 'bg-white') }}" @if ($date->isSameDay($today)) data-calendar-today tabindex="-1" @endif>
                            <th scope="row" class="sticky left-0 z-10 whitespace-nowrap border-b border-r border-slate-200 bg-inherit px-3 py-2 font-medium {{ $isPast ? 'text-slate-500' : 'text-slate-700' }}">{{ $date->locale('id')->translatedFormat('D, j') }} {{ mb_strtolower($date->translatedFormat('F Y')) }}</th>
                            @foreach ($kelurahans as $kelurahan)
                                @php($departmentGroups = $groups->get($kelurahan->id, collect()))
                                @php($isEmptyCell = $departmentGroups->isEmpty() && $daySchedules->where('department_id', $kelurahan->id)->isEmpty())
                                <td class="border-b border-r border-slate-200 px-3 py-2 {{ $isEmptyCell ? 'align-middle text-center' : 'align-top' }}">
                                    @foreach ($daySchedules->where('department_id', $kelurahan->id) as $schedule)
                                        <div class="{{ $loop->first ? '' : 'mt-2 border-t border-slate-100 pt-2' }}">
                                            <p class="font-semibold {{ $isPast ? 'text-slate-500' : 'text-sky-700' }}">Kelompok {{ $schedule->group_number }}</p>
                                            <p class="mt-1 font-medium {{ $isPast ? 'text-slate-500' : 'text-slate-800' }}">{{ $schedule->coordinator }}</p>
                                            @foreach ($schedule->members ?? [] as $member)
                                                <p class="mt-0.5 {{ $isPast ? 'text-slate-500' : 'text-slate-600' }}">{{ $loop->iteration }}. {{ $member }}</p>
                                            @endforeach
                                        </div>
                                    @endforeach
                                    @if ($canManage && $date->lessThan($today))
                                        <p class="mt-2 text-slate-500">Tanggal sudah lewat · terkunci</p>
                                    @elseif ($canManage)
                                        @if ($departmentGroups->isNotEmpty())
                                            <details class="mt-2">
                                                <summary class="cursor-pointer rounded-lg px-2 py-2 font-semibold text-sky-700 hover:bg-sky-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-600">{{ $daySchedules->where('department_id', $kelurahan->id)->isEmpty() ? 'Pilih kelompok' : 'Ganti kelompok' }}<span class="sr-only"> — {{ $kelurahan->name }}, {{ $date->translatedFormat('j F Y') }}</span></summary>
                                                <form method="POST" action="{{ route('counter-duty.kelurahan-calendar.rotate') }}" class="mt-2 min-w-48 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white" data-confirm-title="Isi rotasi kelompok?" data-confirm-message="Jadwal {{ $kelurahan->name }} pada tanggal yang belum lewat dalam bulan ini akan diganti mengikuti kelompok pada tanggal {{ $date->translatedFormat('j F Y') }}. Jadwal yang sudah lewat tidak diubah." data-confirm-button="Isi rotasi">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="department_id" value="{{ $kelurahan->id }}">
                                                    <input type="hidden" name="event_date" value="{{ $date->toDateString() }}">
                                                    @foreach ($departmentGroups as $group)
                                                        <button type="submit" name="group_id" value="{{ $group->id }}" class="block w-full px-3 py-3 text-left hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-600">
                                                            <span class="block font-semibold text-sky-700">Kelompok {{ $group->group_number }}</span>
                                                            <span class="mt-1 block font-medium text-slate-800">{{ $group->coordinator }}</span>
                                                            @foreach ($group->members ?? [] as $member)
                                                                <span class="mt-1 block text-slate-600">{{ $loop->iteration }}. {{ $member }}</span>
                                                            @endforeach
                                                        </button>
                                                    @endforeach
                                                </form>
                                            </details>
                                        @else
                                            <p class="text-slate-500">Belum ada kelompok.</p>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
