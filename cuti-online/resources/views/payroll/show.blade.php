@extends('layouts.app')

@section('title', 'Payroll '.$period->label())

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Payroll bulanan</p>
            <h1 class="page-title mt-2">Periode {{ $period->label() }}</h1>
            <p class="page-description">{{ $period->source_file ?: 'Tanpa file sumber' }} · {{ number_format($period->imported_rows, 0, ',', '.') }} pegawai{{ $period->hasTpp() ? ' · TPP '.number_format($period->tpp_imported_rows, 0, ',', '.').' pegawai' : '' }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="btn-secondary" href="{{ route('payroll.index') }}">Kembali</a>
            <a class="btn-secondary" href="{{ route('payroll.export', $period) }}">Export Excel BKAD</a>
            @if ($period->hasTpp())
                <a class="btn-secondary" href="{{ route('payroll.export-tpp', $period) }}">Export TPP SIPD</a>
            @endif
            @if ($period->status === 'draft')
                <form method="POST" action="{{ route('payroll.reject', $period) }}" onsubmit="return confirm('Tolak data payroll periode ini? Setelah ditolak, periode dapat diimpor ulang.')">
                    @csrf
                    <button class="btn-danger" type="submit">Tolak data</button>
                </form>
                <form method="POST" action="{{ route('payroll.lock', $period) }}" onsubmit="return confirm('Kunci periode payroll ini? Setelah dikunci, file tidak dapat diimpor ulang.')">
                    @csrf
                    <button class="btn-primary" type="submit">Kunci periode</button>
                </form>
            @elseif ($period->status === 'rejected')
                <span class="status-void self-center">Ditolak</span>
                <a class="btn-primary" href="{{ route('payroll.import.create') }}">Impor ulang</a>
            @else
                <span class="status-generated self-center">Terkunci</span>
            @endif
        </div>
    </div>

    @if (session()->has('payroll_changes'))
        @php($changeReport = session('payroll_changes'))
        <section class="card mt-6">
            <div>
                <h2 class="section-heading">Perubahan data master dari file</h2>
                @if ($changeReport['total'] === 0)
                    <p class="section-description">Tidak ada perbedaan data master pegawai pada file ini.</p>
                @else
                    <p class="section-description">Ditemukan {{ number_format($changeReport['total'], 0, ',', '.') }} pegawai terdaftar yang mengalami perubahan. Nilai gaji dan tunjangan tidak termasuk laporan ini.</p>
                @endif
            </div>

            @if ($changeReport['total'] > 0)
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-[900px] text-left text-sm">
                        <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                            <tr>
                                <th class="pb-3 pr-5 font-semibold">NIP</th>
                                <th class="pb-3 pr-5 font-semibold">Nama dari file</th>
                                <th class="pb-3 font-semibold">Perbedaan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($changeReport['items'] as $change)
                                <tr>
                                    <td class="whitespace-nowrap py-3.5 pr-5 font-mono text-xs text-slate-700">{{ $change['nip'] }}</td>
                                    <td class="py-3.5 pr-5 font-semibold text-slate-800">{{ $change['name'] }}</td>
                                    <td class="py-3.5 text-slate-600">
                                        @if ($change['type'] === 'new')
                                            <span class="status-generated">Pegawai baru</span>
                                        @else
                                            <ul class="space-y-1">
                                                @foreach ($change['changes'] as $fieldChange)
                                                    <li><span class="font-semibold text-slate-800">{{ $fieldChange['field'] }}:</span> {{ $fieldChange['old'] }} → {{ $fieldChange['new'] }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($changeReport['total'] > count($changeReport['items']))
                    <p class="form-help mt-4">Rincian yang ditampilkan dibatasi {{ number_format(count($changeReport['items']), 0, ',', '.') }} baris pertama.</p>
                @endif
            @endif
        </section>
    @endif

    <div class="mt-7 grid gap-4 sm:grid-cols-4">
        <div class="card"><p class="text-sm text-slate-500">Data payroll</p><p class="mt-2 text-2xl font-semibold tabular-nums text-slate-950">{{ number_format($period->imported_rows, 0, ',', '.') }}</p></div>
        <div class="card"><p class="text-sm text-slate-500">Data TPP SIPD</p><p class="mt-2 text-2xl font-semibold tabular-nums text-slate-950">{{ number_format($period->tpp_imported_rows, 0, ',', '.') }}</p><p class="mt-1 text-xs {{ $tppMismatchCount > 0 ? 'text-amber-700' : 'text-slate-500' }}">{{ $tppMismatchCount > 0 ? number_format($tppMismatchCount, 0, ',', '.').' perlu dicek' : ($period->hasTpp() ? 'Sesuai sumber' : 'Belum diimpor') }}</p></div>
        <div class="card"><p class="text-sm text-slate-500">Perlu rekonsiliasi</p><p class="mt-2 text-2xl font-semibold tabular-nums {{ $mismatchCount > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ number_format($mismatchCount, 0, ',', '.') }}</p></div>
        <div class="card"><p class="text-sm text-slate-500">Status</p><p class="mt-2 text-2xl font-semibold text-slate-950">{{ $period->statusLabel() }}</p></div>
    </div>

    <form class="card mt-7" method="GET" action="{{ route('payroll.show', $period) }}">
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem_auto] md:items-end">
            <div>
                <label class="form-label" for="search">Cari pegawai</label>
                <input class="form-input" id="search" name="search" value="{{ $search }}" placeholder="Nama, NIP, atau SKPD">
            </div>
            <div>
                <label class="form-label" for="status">Rekonsiliasi</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Semua</option>
                    <option value="matched" @selected($statusFilter === 'matched')>Sesuai</option>
                    <option value="mismatch" @selected($statusFilter === 'mismatch')>Perlu dicek</option>
                </select>
            </div>
            <button class="btn-secondary" type="submit">Terapkan</button>
        </div>
    </form>

    <section class="card mt-6">
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] text-left text-sm">
                <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                    <tr>
                        <th class="pb-3 pr-5 font-semibold">Pegawai</th>
                        <th class="pb-3 pr-5 font-semibold">Gaji pokok</th>
                        <th class="pb-3 pr-5 font-semibold">Tunjangan keluarga</th>
                        <th class="pb-3 pr-5 font-semibold">Tunjangan lain</th>
                        <th class="pb-3 pr-5 font-semibold">Total gaji</th>
                        <th class="pb-3 pr-5 font-semibold">Potongan</th>
                        <th class="pb-3 pr-5 font-semibold">Ditransfer</th>
                        <th class="pb-3 pr-5 font-semibold">Cek</th>
                        <th class="pb-3 text-right font-semibold">Slip</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr>
                            <td class="py-3.5 pr-5"><a class="font-semibold text-sky-700 hover:text-sky-800" href="{{ $record->employee ? route('employees.show', $record->employee) : '#' }}">{{ $record->employee_name }}</a><p class="mt-1 font-mono text-xs text-slate-500">{{ $record->employee_nip }}</p></td>
                            <td class="whitespace-nowrap py-3.5 pr-5 tabular-nums text-slate-700">Rp {{ number_format($record->basic_salary, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap py-3.5 pr-5 tabular-nums text-slate-700">Rp {{ number_format($record->family_allowance, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap py-3.5 pr-5 tabular-nums text-slate-700">Rp {{ number_format($record->position_allowance + $record->functional_allowance + $record->general_functional_allowance + $record->rice_allowance + $record->pph_allowance + $record->papua_special_allowance, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap py-3.5 pr-5 font-semibold tabular-nums text-slate-800">Rp {{ number_format($record->total_earnings, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap py-3.5 pr-5 tabular-nums text-slate-700">Rp {{ number_format($record->total_deductions, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap py-3.5 pr-5 font-semibold tabular-nums text-slate-800">Rp {{ number_format($record->transferred_amount, 0, ',', '.') }}</td>
                            <td class="py-3.5 pr-5"><span class="{{ $record->reconciliation_status === 'matched' ? 'text-emerald-700' : 'font-semibold text-amber-700' }}">{{ $record->reconciliation_status === 'matched' ? 'Sesuai' : 'Perlu dicek' }}</span></td>
                            <td class="py-3.5 text-right"><a class="font-semibold text-sky-700 hover:text-sky-800" href="{{ route('payroll.slip', [$period, $record]) }}">Buka</a></td>
                        </tr>
                    @empty
                        <tr><td class="py-8 text-center text-slate-500" colspan="9">Tidak ada data payroll yang sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="mt-5">{{ $records->links() }}</div>
        @endif
    </section>
@endsection
