@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $dashboardUser = auth()->user();
        $canManageMasterData = $dashboardUser->isSuperAdmin()
            || strtolower(trim((string) $dashboardUser->role)) === 'operator';
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Sistem kepegawaian</p>
            <h1 class="page-title mt-2">{{ $canManageMasterData ? 'Kelola data dan riwayat pegawai dalam satu tempat.' : 'Buat dan pantau formulir cuti unit kerja Anda.' }}</h1>
            <p class="page-description">{{ $canManageMasterData ? 'Profil pegawai menjadi sumber data utama; formulir cuti menggunakan data tersebut lalu menyimpannya sebagai arsip dokumen.' : 'Formulir cuti hanya menampilkan pegawai dan dokumen pada unit kerja Anda.' }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <a class="btn-secondary whitespace-nowrap" href="{{ route('leave-requests.create') }}" data-workspace-link data-workspace-title="Buat Formulir Cuti">Buat formulir cuti</a>
            @if ($canManageMasterData)
                <a class="btn-primary whitespace-nowrap" href="{{ route('employees.create') }}">
                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah pegawai
                </a>
            @endif
        </div>
    </div>

    <div class="dashboard-summary mt-4 grid grid-cols-2 gap-3 sm:mt-7 sm:gap-4 xl:grid-cols-4">
        <div class="card">
            <p class="text-sm font-medium text-slate-500">Pegawai aktif</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-slate-950">{{ $employeeCount }}</p>
            @if ($canManageMasterData)
                <a class="mt-4 inline-flex text-sm font-semibold text-sky-700 hover:text-sky-800" href="{{ route('employees.index') }}">Kelola pegawai →</a>
            @else
                <p class="mt-4 text-sm text-slate-500">Sesuai unit kerja Anda.</p>
            @endif
        </div>
        <div class="card">
            <p class="text-sm font-medium text-slate-500">Perubahan karier bulan ini</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-slate-950">{{ $careerChangeCount }}</p>
            @if ($canManageMasterData)
                <a class="mt-4 inline-flex text-sm font-semibold text-sky-700 hover:text-sky-800" href="{{ route('employees.index') }}">Buka profil pegawai →</a>
            @else
                <p class="mt-4 text-sm text-slate-500">Ringkasan aktivitas bulan ini.</p>
            @endif
        </div>
        <div class="card">
            <p class="text-sm font-medium text-slate-500">Formulir tersimpan</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-slate-950">{{ $requestCount }}</p>
            <a class="mt-4 inline-flex text-sm font-semibold text-sky-700 hover:text-sky-800" href="{{ route('leave-requests.index') }}">Lihat riwayat →</a>
        </div>
        <div class="card">
            <p class="text-sm font-medium text-slate-500">Dokumen Word</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight tabular-nums text-slate-950">{{ $documentCount }}</p>
            <p class="mt-4 text-sm text-slate-500">Arsip tersedia untuk diunduh ulang.</p>
        </div>
    </div>

    <section class="card mt-7">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="section-heading">Formulir cuti terakhir</h2>
                <p class="section-description">Dokumen cuti yang baru dibuat muncul di sini sebagai arsip layanan kepegawaian.</p>
            </div>
            <a class="text-sm font-semibold text-sky-700 hover:text-sky-800" href="{{ route('leave-requests.index') }}">Semua riwayat</a>
        </div>

        @if ($recentRequests->isEmpty())
            <div class="card-inner mt-5 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada formulir cuti.</p>
                <p class="mt-1 text-sm text-slate-500">Buat formulir cuti pertama untuk pegawai di unit kerja Anda.</p>
            </div>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Nomor</th>
                            <th class="pb-3 pr-5 font-semibold">Pegawai</th>
                            <th class="pb-3 pr-5 font-semibold">Jenis</th>
                            <th class="pb-3 pr-5 font-semibold">Status</th>
                            <th class="pb-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($recentRequests as $leaveRequest)
                            <tr>
                                <td class="py-3.5 pr-5 font-medium text-slate-700">{{ $leaveRequest->request_number }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">{{ $leaveRequest->employee->full_name }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">{{ $leaveRequest->leaveType->name }}</td>
                                <td class="py-3.5 pr-5"><span class="status-{{ $leaveRequest->status }}">{{ $leaveRequest->status === 'generated' ? 'Dibuat' : ucfirst($leaveRequest->status) }}</span></td>
                                <td class="py-3.5 text-right"><a class="font-semibold text-sky-700 hover:text-sky-800" href="{{ route('leave-requests.show', $leaveRequest) }}">Buka</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
