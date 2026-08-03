@extends('layouts.app')

@section('title', 'Impor Pegawai')

@section('content')
    @php($importErrors = session('importErrors', []))

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Master data</p>
            <h1 class="page-title mt-2">Impor pegawai</h1>
            <p class="page-description">Unggah data pegawai sekaligus dari CSV atau Excel. Data diperiksa terlebih dahulu sebelum disimpan.</p>
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
                <p class="section-description">Template menjaga urutan kolom, format NIP, dan format tanggal tetap benar. Unit kerja diisi otomatis dari Profil Instansi.</p>
            </div>
            <a class="btn-secondary shrink-0" href="{{ route('employees.import.template') }}">
                <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9 3.75-3.75M12 16.5 8.25 12.75M4.5 18.75v.75A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5v-.75" /></svg>
                Unduh template Excel
            </a>
        </div>
    </section>

    <form class="card mt-7" method="POST" action="{{ route('employees.import.upload') }}" enctype="multipart/form-data" data-employee-import-form>
        @csrf

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_13rem] lg:items-start">
            <div class="min-w-0" data-file-picker>
                <label class="form-label" for="file">File pegawai</label>
                <input
                    class="peer sr-only"
                    id="file"
                    name="file"
                    type="file"
                    accept=".xlsx,.csv,text/csv"
                    required
                    data-file-input
                    aria-describedby="{{ $errors->has('file') ? 'file-help file-error' : 'file-help' }}"
                    aria-invalid="{{ $errors->has('file') ? 'true' : 'false' }}"
                >
                <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-2xl bg-slate-100 p-1 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.08)] transition-shadow duration-150 hover:bg-slate-50 peer-focus-visible:bg-white peer-focus-visible:shadow-[inset_0_0_0_2px_rgba(14,165,233,0.6)]" for="file">
                    <span class="inline-flex min-h-9 shrink-0 items-center gap-2 rounded-xl bg-white px-3 text-sm font-semibold text-sky-700 shadow-[0_1px_2px_rgba(15,23,42,0.08)]">
                        <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 0 3.75 3.75M12 7.5 8.25 11.25M4.5 18.75v.75A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5v-.75" /></svg>
                        Pilih file
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm text-slate-500" data-file-name title="Belum ada file dipilih">Belum ada file dipilih</span>
                    <svg aria-hidden="true" class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                </label>
                <p class="form-help" id="file-help">Menerima Excel (.xlsx) atau CSV, maksimal 5 MB dan {{ number_format(\App\Services\EmployeeImportService::MAX_ROWS, 0, ',', '.') }} pegawai per file.</p>
                @error('file')
                    <p class="form-error" id="file-error">{{ $message }}</p>
                @enderror
            </div>
            <button class="btn-primary w-full whitespace-nowrap disabled:cursor-wait disabled:opacity-75 lg:mt-[1.625rem] lg:w-52" type="submit" data-submit-employee-import>
                <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5v-9m0 9 3.75-3.75M12 16.5 8.25 12.75M4.5 18.75v.75A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5v-.75" /></svg>
                <span data-submit-label>Pratinjau data</span>
            </button>
        </div>
    </form>

    @if ($importErrors !== [])
        <section class="card mt-7">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-rose-700">Perlu diperbaiki</p>
                    <h2 class="section-heading mt-2">{{ number_format(count($importErrors), 0, ',', '.') }} baris belum dapat diimpor</h2>
                    @if (session('importFileName'))
                        <p class="mt-1 text-sm text-slate-500">
                            File: {{ session('importFileName') }}
                            @if (session('importTotalRows'))
                                <span>· {{ number_format((int) session('importTotalRows'), 0, ',', '.') }} baris data</span>
                            @endif
                        </p>
                    @endif
                </div>
                <span class="status-void shrink-0 tabular-nums">{{ number_format(count($importErrors), 0, ',', '.') }} masalah</span>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Baris</th>
                            <th class="pb-3 font-semibold">Masalah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (array_slice($importErrors, 0, 50) as $error)
                            <tr>
                                <td class="py-4 pr-5 align-top font-mono text-xs text-slate-600">{{ $error['row_number'] }}</td>
                                <td class="py-4 align-top text-sm leading-6 text-slate-700">
                                    <ul class="space-y-1">
                                        @foreach ($error['messages'] as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (count($importErrors) > 50)
                <p class="mt-4 text-sm text-slate-500">Menampilkan 50 masalah pertama. Perbaiki seluruh data lalu unggah ulang file.</p>
            @endif
        </section>
    @endif
@endsection
