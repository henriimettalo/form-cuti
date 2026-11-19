@extends('layouts.public')
@section('title', 'Jadwal Piket Kelurahan')
@section('description', 'Lihat jadwal piket kelurahan Kecamatan Pontianak Selatan untuk kemarin, hari ini, dan besok.')
@section('share-url', route('public-schedules.kelurahan'))
@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
        <p class="text-sm font-semibold text-sky-700">Informasi untuk umum</p>
        <h1 class="page-title mt-1">Jadwal piket kelurahan</h1>
        <p class="page-description">Hanya menampilkan jadwal kemarin, hari ini, dan besok. Halaman ini hanya untuk dibaca.</p>
        <p class="mt-2 text-xs text-slate-500">Tanggal mengikuti waktu Asia/Pontianak.</p>
        </div>
        <button class="btn-secondary print:hidden" type="button" data-public-print>Unduh PDF / Cetak</button>
    </div>
    <section class="card mt-5" aria-label="Jadwal piket kelurahan tiga hari" data-public-calendar data-public-calendar-type="kelurahan">
        @if ($kelurahans->isEmpty())
            <p class="text-sm text-slate-500">Belum ada kelurahan aktif.</p>
        @else
            <ol class="divide-y divide-slate-200 lg:hidden print:hidden" aria-label="Daftar jadwal piket kelurahan" data-public-schedule-list>
                @foreach ($dates as $date)
                    @php($daySchedules = $schedules->get($date->toDateString(), collect()))
                    <li class="py-4 first:pt-0 last:pb-0">
                        <h2 class="font-semibold text-slate-900">
                            {{ $date->lessThan($today) ? 'Kemarin' : ($date->isSameDay($today) ? 'Hari ini' : 'Besok') }}
                            <time class="block text-sm font-normal text-slate-600" datetime="{{ $date->toDateString() }}">{{ $date->locale('id')->translatedFormat('l, j F Y') }}</time>
                        </h2>
                        @forelse ($daySchedules->groupBy('department_id') as $departmentId => $unitSchedules)
                            <div class="mt-3 text-sm text-slate-700">
                                <h3 class="font-semibold text-slate-900">{{ $kelurahans->firstWhere('id', $departmentId)?->name }}</h3>
                                @foreach ($unitSchedules as $schedule)
                                    <div class="mt-2">
                                        @include('public-schedules.kelurahan-entry')
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <p class="mt-2 text-sm text-slate-600">Belum ada jadwal.</p>
                        @endforelse
                    </li>
                @endforeach
            </ol>
            <div class="hidden overflow-x-auto rounded-xl border border-slate-200 lg:block print:block" data-public-kelurahan-table>
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="bg-emerald-50 text-slate-700">
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 border-b border-r border-slate-200 bg-emerald-50 px-3 py-3">Tanggal</th>
                            @foreach ($kelurahans as $kelurahan)
                                <th scope="col" class="min-w-48 border-b border-r border-slate-200 px-3 py-3">{{ $kelurahan->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dates as $date)
                            @php($isPast = $date->lessThan($today))
                            @php($daySchedules = $schedules->get($date->toDateString(), collect()))
                            <tr class="{{ $isPast ? 'bg-slate-100 text-slate-500' : 'bg-white text-slate-700' }}">
                                <th scope="row" class="sticky left-0 z-10 whitespace-nowrap border-b border-r border-slate-200 bg-inherit px-3 py-3 align-top">
                                    <span class="block {{ $date->isSameDay($today) ? 'text-sky-700' : '' }}">{{ $isPast ? 'Kemarin' : ($date->isSameDay($today) ? 'Hari ini' : 'Besok') }}</span>
                                    <span class="mt-1 block font-normal">{{ $date->locale('id')->translatedFormat('D, j F Y') }}</span>
                                </th>
                                @foreach ($kelurahans as $kelurahan)
                                    <td class="border-b border-r border-slate-200 px-3 py-3 align-top">
                                        @forelse ($daySchedules->where('department_id', $kelurahan->id) as $schedule)
                                            <div class="{{ $loop->first ? '' : 'mt-3 border-t border-slate-200 pt-3' }}">
                                                @include('public-schedules.kelurahan-entry')
                                            </div>
                                        @empty
                                            <p class="text-slate-500">Belum ada jadwal.</p>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
