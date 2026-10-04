@extends('layouts.app')

@section('title', 'Payroll Bulanan')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Payroll</p>
            <h1 class="page-title mt-2">Payroll bulanan</h1>
            <p class="page-description">Perbarui data pegawai dari file payroll bulanan. Nomor rekening ikut disimpan jika tersedia. Nominal payroll diabaikan.</p>
        </div>
        <a class="btn-primary shrink-0" href="{{ route('payroll.import.create') }}">Impor data pegawai</a>
    </div>

    <section class="card mt-7">
        <h2 class="section-heading">Riwayat impor data pegawai</h2>
        @forelse ($employeeImports as $employeeImport)
            <a class="mt-4 block border-t border-slate-100 pt-4 text-sm text-sky-800" href="{{ route('payroll.import.preview', $employeeImport) }}">
                {{ sprintf('%02d/%d', $employeeImport->month, $employeeImport->year) }} · {{ $employeeImport->original_filename }} · {{ $employeeImport->imported_rows }} pegawai
            </a>
        @empty
            <p class="section-description">Belum ada impor data pegawai dari payroll yang selesai.</p>
        @endforelse
    </section>

    <section class="card mt-7">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="section-heading">Arsip payroll sebelumnya</h2>
                <p class="section-description">Draf dapat diperiksa sebelum dikunci sebagai arsip resmi.</p>
            </div>
        </div>

        @if ($periods->isEmpty())
            <div class="card-inner mt-5 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada payroll bulanan.</p>
                <p class="mt-1 text-sm text-slate-500">Impor data pegawai tidak membuat periode gaji.</p>
            </div>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Periode</th>
                            <th class="pb-3 pr-5 font-semibold">Status</th>
                            <th class="pb-3 pr-5 font-semibold">Pegawai</th>
                            <th class="pb-3 pr-5 font-semibold">Rekonsiliasi</th>
                            <th class="pb-3 pr-5 font-semibold">File sumber</th>
                            <th class="pb-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($periods as $period)
                            <tr>
                                <td class="py-3.5 pr-5 font-semibold text-slate-800">{{ $period->label() }}</td>
                                <td class="py-3.5 pr-5">
                                    <span class="{{ $period->statusBadgeClass() }}">
                                        {{ $period->statusLabel() }}
                                    </span>
                                </td>
                                <td class="py-3.5 pr-5 tabular-nums text-slate-600">{{ number_format($period->records_count, 0, ',', '.') }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">
                                    @if ($period->mismatch_count > 0)
                                        <span class="font-semibold text-amber-700">{{ number_format($period->mismatch_count, 0, ',', '.') }} perlu dicek</span>
                                    @else
                                        <span class="text-emerald-700">Sesuai</span>
                                    @endif
                                </td>
                                <td class="max-w-64 truncate py-3.5 pr-5 text-slate-600">{{ $period->source_file ?: '-' }}</td>
                                <td class="py-3.5 text-right"><a class="font-semibold text-sky-700 hover:text-sky-800" href="{{ route('payroll.show', $period) }}">Buka</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
