@extends('layouts.app')

@section('title', 'Data Pegawai')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between sm:gap-6">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">{{ $showArchived ? 'Arsip data' : 'Master data' }}</p>
            <h1 class="page-title mt-2">{{ $showArchived ? 'Arsip pegawai' : 'Pegawai' }}</h1>
            <p class="page-description">{{ $showArchived ? 'Data pegawai yang diarsipkan tidak dapat dipilih pada formulir cuti, tetapi tetap dapat dipulihkan kapan saja.' : 'Kelola profil, pangkat, dan jabatan. Unit kerja mengikuti Profil Instansi, lalu modul cuti memakai data aktif dari sini.' }}</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-end">
            @if ($showArchived)
                <a class="btn-secondary shrink-0" href="{{ route('employees.index', ['per_page' => $perPage]) }}">
                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    Pegawai aktif
                </a>
            @else
                <a class="btn-secondary shrink-0" href="{{ route('employees.index', ['archived' => 1, 'per_page' => $perPage]) }}">
                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5V6A2.25 2.25 0 0 1 6 3.75h12A2.25 2.25 0 0 1 20.25 6v1.5M3.75 7.5h16.5m-16.5 0v10.75A2.25 2.25 0 0 0 6 20.5h12a2.25 2.25 0 0 0 2.25-2.25V7.5" /></svg>
                    Arsip pegawai
                </a>
                <a class="btn-secondary shrink-0" href="{{ route('employees.import.create') }}">
                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9 3.75-3.75M12 16.5 8.25 12.75M4.5 18.75v.75A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5v-.75" /></svg>
                    Impor pegawai
                </a>
                <a class="btn-primary shrink-0" href="{{ route('employees.create') }}">
                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah pegawai
                </a>
            @endif
        </div>
    </div>

    <form class="card mt-7" id="employee-filter-form" method="GET" action="{{ route('employees.index') }}">
        @if ($showArchived)
            <input type="hidden" name="archived" value="1">
        @endif

        <div @class([
            'grid gap-4 md:grid-cols-2 xl:items-end',
            'xl:grid-cols-[minmax(0,1.2fr)_12rem_14rem_11rem_auto]' => ! $showArchived,
            'xl:grid-cols-[minmax(0,1.2fr)_12rem_14rem_auto]' => $showArchived,
        ])>
            <div>
                <label class="form-label" for="search">Cari pegawai</label>
                <div class="relative">
                    <svg aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                    <input class="form-input pl-10" id="search" name="search" value="{{ $filters['search'] }}" placeholder="Nama, NIP, pangkat, jabatan, atau unit kerja">
                </div>
            </div>

            <div>
                <label class="form-label" for="employment_status">Status kepegawaian</label>
                <select class="form-select" id="employment_status" name="employment_status">
                    <option value="">Semua status</option>
                    @foreach (['PNS', 'PPPK', 'Lainnya'] as $employmentStatus)
                        <option value="{{ $employmentStatus }}" @selected($filters['employment_status'] === $employmentStatus)>{{ $employmentStatus }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="department_id">Unit kerja</label>
                <select class="form-select" id="department_id" name="department_id">
                    <option value="">Semua unit kerja</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) $filters['department_id'] === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>

            @if (! $showArchived)
                <div>
                    <label class="form-label" for="is_active">Status data</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="">Aktif dan nonaktif</option>
                        <option value="1" @selected($filters['is_active'] === '1')>Aktif</option>
                        <option value="0" @selected($filters['is_active'] === '0')>Nonaktif</option>
                    </select>
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">
                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                    Cari
                </button>
                @if ($hasFilters)
                    <a class="btn-secondary" href="{{ route('employees.index', $showArchived ? ['archived' => 1, 'per_page' => $perPage] : ['per_page' => $perPage]) }}">Reset</a>
                @endif
            </div>
        </div>
        <p class="mt-3 text-sm text-slate-500">Cari berdasarkan nama, NIP, pangkat, jabatan, atau unit kerja.</p>
    </form>

    <section class="card mt-5">
        @if ($employees->isEmpty())
            <div class="card-inner text-center">
                <p class="text-sm font-medium text-slate-700">{{ $hasFilters ? 'Tidak ada pegawai yang sesuai dengan pencarian atau filter.' : ($showArchived ? 'Belum ada pegawai yang diarsipkan.' : 'Belum ada data pegawai.') }}</p>
                @if ($hasFilters)
                    <a class="btn-secondary mt-4" href="{{ route('employees.index', $showArchived ? ['archived' => 1, 'per_page' => $perPage] : ['per_page' => $perPage]) }}">Hapus filter</a>
                @elseif ($showArchived)
                    <p class="mt-1 text-sm text-slate-500">Pegawai yang diarsipkan akan tetap berada di sini sampai dipulihkan.</p>
                @else
                    <p class="mt-1 text-sm text-slate-500">Tambahkan pegawai pertama sebelum membuat formulir cuti.</p>
                    <a class="btn-primary mt-4" href="{{ route('employees.create') }}">Tambah pegawai</a>
                @endif
            </div>
        @else
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">Menampilkan <span class="font-semibold tabular-nums text-slate-700">{{ number_format($employees->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($employees->lastItem() ?? 0, 0, ',', '.') }}</span> dari <span class="font-semibold tabular-nums text-slate-700">{{ number_format($employees->total(), 0, ',', '.') }}</span> pegawai.</p>
                <div class="flex flex-wrap items-center gap-3">
                    @if ($hasFilters)
                        <p class="text-sm font-medium text-sky-700">Filter diterapkan</p>
                    @endif
                    <div class="flex min-h-11 items-center gap-2">
                        <label class="shrink-0 text-sm font-medium text-slate-600" for="per_page">Tampilkan</label>
                        <select class="form-select w-32 tabular-nums" id="per_page" name="per_page" form="employee-filter-form" data-employee-per-page>
                            @foreach ($perPageOptions as $perPageOption)
                                <option value="{{ $perPageOption }}" @selected($perPage === $perPageOption)>{{ $perPageOption }} baris</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Pegawai</th>
                            <th class="pb-3 pr-5 font-semibold">NIP</th>
                            <th class="pb-3 pr-5 font-semibold">Pangkat</th>
                            <th class="pb-3 pr-5 font-semibold">Jabatan</th>
                            <th class="pb-3 pr-5 font-semibold">Unit kerja</th>
                            <th class="pb-3 pr-5 font-semibold">Status</th>
                            <th class="pb-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employees as $employee)
                            <tr>
                                <td class="py-4 pr-5 font-semibold text-slate-800">{{ $employee->full_name }}</td>
                                <td class="py-4 pr-5 font-mono text-xs text-slate-600">{{ $employee->nip }}</td>
                                <td class="py-4 pr-5 text-slate-600">{{ \App\Support\EmployeeRankOptions::format($employee->employment_status, $employee->rank_name, $employee->grade) }}</td>
                                <td class="py-4 pr-5 text-slate-600">{{ $employee->position?->name ?? $employee->position_title }}</td>
                                <td class="py-4 pr-5 text-slate-600">{{ $employee->department?->name ?? '-' }}</td>
                                <td class="py-4 pr-5"><span class="{{ $showArchived ? 'status-void' : ($employee->is_active ? 'status-generated' : 'status-void') }}">{{ $showArchived ? 'Diarsipkan' : ($employee->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
                                <td class="py-4 text-right">
                                    <div class="inline-block text-left" data-employee-actions>
                                        <button class="inline-flex size-11 items-center justify-center rounded-xl bg-white text-slate-600 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.1)] transition-[background-color,box-shadow,transform] duration-150 hover:bg-slate-50 hover:text-slate-950 active:scale-[0.96]" type="button" data-employee-actions-toggle aria-expanded="false" aria-controls="employee-actions-{{ $employee->id }}" aria-label="Aksi untuk {{ $employee->full_name }}" title="Aksi pegawai">
                                            <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75h.008v.008H12V6.75Zm0 5.246h.008v.008H12v-.008Zm0 5.246h.008v.008H12v-.008Z" /></svg>
                                        </button>

                                        <div class="fixed left-0 top-0 z-50 w-44 origin-top-right rounded-[14px] bg-white p-1.5 opacity-0 pointer-events-none scale-[0.96] shadow-[0_12px_30px_rgba(15,23,42,0.16),0_2px_8px_rgba(15,23,42,0.08)] transition-[opacity,scale] duration-150" id="employee-actions-{{ $employee->id }}" data-employee-actions-panel aria-hidden="true" inert>
                                            @if ($showArchived)
                                                <form method="POST" action="{{ route('employees.restore', $employee) }}">
                                                    @csrf
                                                    <button class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium text-emerald-700 transition-[background-color,color,transform] duration-150 hover:bg-emerald-50 active:scale-[0.96]" type="submit">
                                                        <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-.582 4.992a9 9 0 1 0 2.122 5.829" /></svg>
                                                        Pulihkan
                                                    </button>
                                                </form>
                                            @else
                                                <a class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium text-slate-700 transition-[background-color,color] duration-150 hover:bg-slate-50 hover:text-slate-950" href="{{ route('employees.show', $employee) }}">
                                                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12S5.25 6.75 12 6.75 21.75 12 21.75 12 18.75 17.25 12 17.25 2.25 12 2.25 12Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 14.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" /></svg>
                                                    Profil
                                                </a>
                                                <a class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium text-slate-700 transition-[background-color,color] duration-150 hover:bg-slate-50 hover:text-slate-950" href="{{ route('employees.edit', $employee) }}">
                                                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 2.651 2.651M4.5 19.5l3.792-.758L18.75 8.284a1.875 1.875 0 0 0-2.652-2.652L5.64 16.09 4.5 19.5Z" /></svg>
                                                    Edit
                                                </a>
                                                <div class="my-1 border-t border-slate-100"></div>
                                                <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Arsipkan pegawai ini? Data dan riwayat tetap tersimpan dan dapat dipulihkan.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 text-left text-sm font-medium text-rose-700 transition-[background-color,color,transform] duration-150 hover:bg-rose-50 active:scale-[0.96]" type="submit">
                                                        <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9 14.394 18m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.14-2.032-2.172a48.114 48.114 0 0 0-3.736 0C8.16 2.338 7.25 3.297 7.25 4.477v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                        Arsipkan
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $employees->links() }}</div>
        @endif
    </section>
@endsection
