@extends('layouts.app')

@section('title', 'Log Perubahan Pegawai')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Master data</p>
            <h1 class="page-title mt-2">Log perubahan pegawai</h1>
            <p class="page-description">Catatan perubahan data master yang disetujui melalui import payroll.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('employees.index') }}">Kembali ke pegawai</a>
    </div>

    <form class="card mt-7" method="GET" action="{{ route('employees.change-logs.index') }}">
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_12rem_12rem_auto] md:items-end">
            <div>
                <label class="form-label" for="search">Cari pegawai</label>
                <input class="form-input" id="search" name="search" value="{{ $search }}" placeholder="NIP atau nama">
            </div>
            <div>
                <label class="form-label" for="from">Dari tanggal</label>
                <input class="form-input" id="from" name="from" type="date" value="{{ $from }}">
            </div>
            <div>
                <label class="form-label" for="to">Sampai tanggal</label>
                <input class="form-input" id="to" name="to" type="date" value="{{ $to }}">
            </div>
            <button class="btn-primary" type="submit">Terapkan</button>
        </div>
    </form>

    <section class="card mt-6">
        @if ($logs->isEmpty())
            <div class="card-inner text-center text-sm text-slate-500" role="status" aria-live="polite">
                @if ($search !== '' || $from !== null || $to !== null)
                    <p class="font-semibold text-slate-800">Tidak ada hasil pencarian.</p>
                    <p class="mt-1">
                        Tidak ditemukan log perubahan
                        @if ($search !== '')
                            untuk <span class="font-semibold text-slate-700">“{{ $search }}”</span>
                        @endif
                        yang sesuai dengan filter yang dipilih.
                    </p>
                    <a class="btn-secondary mt-4 inline-flex" href="{{ route('employees.change-logs.index') }}">Hapus filter</a>
                @else
                    <p class="font-semibold text-slate-800">Belum ada log perubahan.</p>
                    <p class="mt-1">Log akan muncul setelah ada perubahan data pegawai yang dikonfirmasi melalui import payroll.</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-[1000px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Tanggal</th>
                            <th class="pb-3 pr-5 font-semibold">Pegawai</th>
                            <th class="pb-3 pr-5 font-semibold">Perubahan</th>
                            <th class="pb-3 pr-5 font-semibold">Sumber</th>
                            <th class="pb-3 font-semibold">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($logs as $log)
                            @php($employee = $log->auditable)
                            @php($metadata = $log->metadata ?? [])
                            <tr>
                                <td class="whitespace-nowrap py-4 pr-5 align-top text-slate-600">
                                    @if (! empty($metadata['uploaded_at']))
                                        <p>{{ \Illuminate\Support\Carbon::parse($metadata['uploaded_at'])->format('d-m-Y H:i') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Tanggal upload</p>
                                    @else
                                        <p>{{ $log->created_at?->format('d-m-Y H:i') }}</p>
                                    @endif
                                    <p class="mt-2 text-xs text-slate-500">Konfirmasi {{ $log->created_at?->format('d-m-Y H:i') }}</p>
                                </td>
                                <td class="py-4 pr-5 align-top">
                                    @if ($employee)
                                        <a class="font-semibold text-sky-700 hover:text-sky-800" href="{{ route('employees.show', $employee) }}">{{ $employee->full_name }}</a>
                                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $employee->nip }}</p>
                                    @else
                                        <span class="text-slate-500">Pegawai sudah diarsipkan</span>
                                    @endif
                                </td>
                                <td class="py-4 pr-5 align-top text-slate-600">
                                    <ul class="space-y-1">
                                        @foreach ($log->change_items as $change)
                                            <li><span class="font-semibold text-slate-800">{{ $change['label'] }}:</span> {{ $change['old'] }} → {{ $change['new'] }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="py-4 pr-5 align-top text-slate-600">
                                    <p>Payroll {{ $metadata['payroll_period'] ?? '-' }}</p>
                                    <p class="mt-1 max-w-[18rem] break-words text-xs text-slate-500">{{ $metadata['source_file'] ?? '-' }}</p>
                                    @if (isset($metadata['source_row_number']))
                                        <p class="mt-1 text-xs text-slate-500">Baris {{ $metadata['source_row_number'] }}</p>
                                    @endif
                                </td>
                                <td class="py-4 align-top text-slate-600">{{ $log->user?->name ?? 'Sistem' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="mt-5">{{ $logs->links() }}</div>
            @endif
        @endif
    </section>
@endsection
