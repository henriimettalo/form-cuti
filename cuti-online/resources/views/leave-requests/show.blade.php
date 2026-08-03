@extends('layouts.app')

@section('title', $leaveRequest->request_number)

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">{{ $leaveRequest->request_number }}</p>
            <h1 class="page-title mt-2">Formulir {{ $leaveRequest->leaveType->name }}</h1>
            <p class="page-description">{{ $leaveRequest->employee_snapshot['full_name'] }} · {{ $leaveRequest->start_date->format('d M Y') }} sampai {{ $leaveRequest->end_date->format('d M Y') }}</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('leave-requests.index') }}">Kembali ke riwayat</a>
    </div>

    <div class="mt-7 grid gap-5 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <section class="card">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="section-heading">Ringkasan formulir</h2>
                    <p class="section-description">Data ini merupakan snapshot saat dokumen dibuat.</p>
                </div>
                <span class="status-{{ $leaveRequest->status }}">{{ $leaveRequest->status === 'generated' ? 'Dokumen dibuat' : ucfirst($leaveRequest->status) }}</span>
            </div>

            <dl class="mt-6 divide-y divide-slate-100 text-sm">
                <div class="grid gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)]">
                    <dt class="font-medium text-slate-500">Pegawai</dt>
                    <dd class="font-semibold text-slate-800">{{ $leaveRequest->employee_snapshot['full_name'] }}<span class="ml-2 font-mono text-xs font-normal text-slate-500">{{ $leaveRequest->employee_snapshot['nip'] }}</span></dd>
                </div>
                <div class="grid gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)]">
                    <dt class="font-medium text-slate-500">Alasan</dt>
                    <dd class="whitespace-pre-line text-slate-700">{{ $leaveRequest->reason }}</dd>
                </div>
                <div class="grid gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)]">
                    <dt class="font-medium text-slate-500">Lama cuti</dt>
                    <dd class="text-slate-700">{{ $leaveRequest->duration_value }} {{ $leaveRequest->duration_unit === 'day' ? 'hari' : ($leaveRequest->duration_unit === 'month' ? 'bulan' : 'tahun') }}</dd>
                </div>
                <div class="grid gap-1 py-3.5 sm:grid-cols-[10rem_minmax(0,1fr)]">
                    <dt class="font-medium text-slate-500">Alamat / telepon</dt>
                    <dd class="text-slate-700">{{ $leaveRequest->address_during_leave ?: '-' }}<br>{{ $leaveRequest->phone_during_leave ?: '-' }}</dd>
                </div>
            </dl>
        </section>

        <aside class="card h-fit">
            <h2 class="section-heading">Dokumen</h2>
            <p class="section-description">Unduh ulang dokumen Word yang tersimpan di arsip.</p>

            <div class="mt-5 grid gap-3">
                @forelse ($leaveRequest->generatedDocuments as $document)
                    <a class="btn-primary w-full" href="{{ route('documents.download', $document) }}">
                        <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9 3.5-3.5M12 16.5l-3.5-3.5M5 20h14" /></svg>
                        Unduh {{ strtoupper($document->format) }}
                    </a>
                @empty
                    <p class="card-inner text-sm text-slate-500">Dokumen belum tersedia.</p>
                @endforelse
            </div>
        </aside>
    </div>
@endsection
