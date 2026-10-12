@extends('layouts.public')
@section('title', 'Jadwal Piket Kelurahan')
@section('content')
    <div>
        <p class="text-sm font-semibold text-sky-700">Informasi untuk umum</p>
        <h1 class="page-title mt-1">Jadwal piket kelurahan</h1>
        <p class="page-description">Hanya menampilkan jadwal kemarin, hari ini, dan besok. Halaman ini hanya untuk dibaca.</p>
        <p class="mt-2 text-xs text-slate-500">Tanggal mengikuti waktu Asia/Pontianak.</p>
    </div>
    <section class="card mt-5" aria-label="Jadwal piket kelurahan tiga hari">
        @if ($kelurahans->isEmpty())
            <p class="text-sm text-slate-500">Belum ada kelurahan aktif.</p>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200">
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
                                                <p class="font-semibold {{ $isPast ? '' : 'text-sky-700' }}">Kelompok {{ $schedule->group_number }}</p>
                                                <p class="mt-1 font-medium">{{ $schedule->coordinator }}</p>
                                                @foreach ($schedule->members ?? [] as $member)
                                                    <p class="mt-1">{{ $member }}</p>
                                                @endforeach
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
