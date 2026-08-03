@extends('layouts.app')

@section('title', 'Formulir Cuti')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Arsip dokumen</p>
            <h1 class="page-title mt-2">Formulir cuti</h1>
            <p class="page-description">Setiap formulir tersimpan bersama snapshot data dan file Word hasilnya.</p>
        </div>
        <a class="btn-primary shrink-0" href="{{ route('leave-requests.create') }}" data-workspace-link data-workspace-title="Buat Formulir">
            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Buat formulir
        </a>
    </div>

    <section class="card mt-7">
        @if ($leaveRequests->isEmpty())
            <div class="card-inner text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada formulir.</p>
                <p class="mt-1 text-sm text-slate-500">Pastikan data pegawai tersedia, lalu buat formulir pertama.</p>
                <a class="btn-primary mt-4" href="{{ route('leave-requests.create') }}" data-workspace-link data-workspace-title="Buat Formulir">Buat formulir</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Nomor</th>
                            <th class="pb-3 pr-5 font-semibold">Pegawai</th>
                            <th class="pb-3 pr-5 font-semibold">Jenis / Periode</th>
                            <th class="pb-3 pr-5 font-semibold">Status</th>
                            <th class="pb-3 text-right font-semibold">Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($leaveRequests as $leaveRequest)
                            <tr>
                                <td class="py-4 pr-5 font-medium text-slate-700">{{ $leaveRequest->request_number }}</td>
                                <td class="py-4 pr-5 text-slate-700">{{ $leaveRequest->employee->full_name }}</td>
                                <td class="py-4 pr-5 text-slate-600">
                                    <p>{{ $leaveRequest->leaveType->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $leaveRequest->start_date->format('d/m/Y') }} — {{ $leaveRequest->end_date->format('d/m/Y') }}</p>
                                </td>
                                <td class="py-4 pr-5"><span class="status-{{ $leaveRequest->status }}">{{ $leaveRequest->status === 'generated' ? 'Dibuat' : ucfirst($leaveRequest->status) }}</span></td>
                                <td class="py-4 text-right"><a class="font-semibold text-sky-700 hover:text-sky-800" href="{{ route('leave-requests.show', $leaveRequest) }}">Buka</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $leaveRequests->links() }}</div>
        @endif
    </section>
@endsection
