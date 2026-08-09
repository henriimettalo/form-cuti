@extends('layouts.app')

@section('title', 'Impor Identitas Pegawai')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Master data</p>
            <h1 class="page-title mt-2">Impor identitas pegawai</h1>
            <p class="page-description">Tambah banyak pegawai sekaligus hanya dengan NIP dan nama. NIP yang sudah ada dilewati. Detail lain dilengkapi dari impor payroll.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('employees.index') }}">
            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Kembali ke pegawai
        </a>
    </div>

    <section class="card mt-7">
        <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <h2 class="section-heading">Mulai dari template</h2>
                <p class="section-description">Template berisi kolom NIP, Nama Lengkap, Jabatan, Nomor Telepon, dan Email. Hanya NIP dan Nama Lengkap yang wajib.</p>
            </div>
            <a class="btn-secondary shrink-0" href="{{ route('employees.identity-import.template') }}">
                <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9 3.75-3.75M12 16.5 8.25 12.75M4.5 18.75v.75A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5v-.75" /></svg>
                Unduh template Excel
            </a>
        </div>
    </section>

    <form class="card mt-7" method="POST" action="{{ route('employees.identity-import.preview') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_13rem] lg:items-start">
            <div class="min-w-0">
                <label class="form-label" for="file">File identitas pegawai</label>
                <input class="form-input" id="file" name="file" type="file" accept=".xlsx,.csv,text/csv" required>
                <p class="form-help">Menerima Excel (.xlsx) atau CSV, maksimal 5 MB. Header: NIP, Nama Lengkap, Jabatan, Nomor Telepon, Email.</p>
                @error('file') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex lg:justify-end">
                <button class="btn-primary" type="submit">Periksa file</button>
            </div>
        </div>
    </form>

    @if (! empty($preview))
        @php($p = $preview)
        <section class="card mt-7">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="section-heading">Pratinjau: {{ $p['file_name'] ?? '' }}</h2>
                    <p class="section-description">
                        {{ count($p['valid_rows']) }} siap diimpor ·
                        {{ count($p['skipped_existing']) }} dilewati (NIP sudah ada) ·
                        {{ count($p['errors']) }} bermasalah
                    </p>
                </div>
                @if (! empty($p['message']))
                    <p class="text-sm font-medium text-rose-700">{{ $p['message'] }}</p>
                @endif
            </div>

            @if (! empty($p['message']) && empty($p['valid_rows']) && empty($p['errors']) && empty($p['skipped_existing']))
                <div class="card-inner mt-5 text-sm text-slate-600">{{ $p['message'] }}</div>
            @endif

            @if (! empty($p['errors']))
                <div class="card-inner mt-5">
                    <h3 class="text-sm font-semibold text-slate-800">Baris bermasalah</h3>
                    <ul class="mt-2 space-y-1 text-sm text-rose-700">
                        @foreach ($p['errors'] as $error)
                            <li>Baris {{ $error['row_number'] }}: {{ implode('; ', $error['messages']) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($p['skipped_existing']))
                <div class="card-inner mt-5">
                    <h3 class="text-sm font-semibold text-slate-800">Dilewati (NIP sudah terdaftar)</h3>
                    <p class="mt-1 text-xs text-slate-500">Pegawai dengan NIP ini sudah ada. Gunakan menu Impor Payroll untuk melengkapi datanya, atau edit manual.</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                        @foreach ($p['skipped_existing'] as $row)
                            <li>{{ $row['nip'] }} · {{ $row['full_name'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($p['valid_rows']))
                <div class="card-inner mt-5">
                    <h3 class="text-sm font-semibold text-slate-800">Siap diimpor</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                                <tr>
                                    <th class="pb-2 pr-5 font-semibold">NIP</th>
                                    <th class="pb-2 pr-5 font-semibold">Nama</th>
                                    <th class="pb-2 pr-5 font-semibold">Jabatan</th>
                                    <th class="pb-2 pr-5 font-semibold">Telepon</th>
                                    <th class="pb-2 font-semibold">Email</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($p['valid_rows'] as $row)
                                    <tr>
                                        <td class="py-3 pr-5 font-mono text-xs text-slate-600">{{ $row['nip'] }}</td>
                                        <td class="py-3 pr-5 font-semibold text-slate-800">{{ $row['full_name'] }}</td>
                                        <td class="py-3 pr-5 text-slate-600">{{ $row['position_title'] ?? '-' }}</td>
                                        <td class="py-3 pr-5 text-slate-600">{{ $row['phone'] ?? '-' }}</td>
                                        <td class="py-3 text-slate-600">{{ $row['email'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <form class="mt-5" method="POST" action="{{ route('employees.identity-import.store') }}">
                    @csrf
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                        <a class="btn-secondary" href="{{ route('employees.identity-import.create') }}">Batal</a>
                        <button class="btn-primary" type="submit" onclick="return confirm('Impor {{ count($p['valid_rows']) }} pegawai?')">Impor {{ count($p['valid_rows']) }} pegawai</button>
                    </div>
                </form>
            @endif
        </section>
    @endif
@endsection