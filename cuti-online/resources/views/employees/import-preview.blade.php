@extends('layouts.app')

@section('title', 'Pratinjau Impor Pegawai')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Master data</p>
            <h1 class="page-title mt-2">Pratinjau impor</h1>
            <p class="page-description">Pastikan data berikut sudah benar. Unit Kerja dari file dipertahankan; jika kosong, Profil Instansi dipakai sebagai bawaan. Setelah dikonfirmasi, pegawai, saldo cuti, dan riwayat awal dibuat sekaligus.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('employees.import.create') }}">
            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Unggah file lain
        </a>
    </div>

    @error('import')
        <div class="mt-7 rounded-xl bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-700 shadow-[inset_0_0_0_1px_rgba(225,29,72,0.14)]">{{ $message }}</div>
    @enderror

    <section class="card mt-7">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card-inner">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">File</p>
                <p class="mt-2 break-words text-sm font-semibold text-slate-800">{{ $employeeImport->original_filename }}</p>
            </div>
            <div class="card-inner">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Pegawai siap impor</p>
                <p class="mt-2 text-2xl font-semibold tracking-[-0.02em] text-slate-950 tabular-nums">{{ number_format($employeeImport->total_rows, 0, ',', '.') }}</p>
            </div>
            <div class="card-inner">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Status</p>
                <p class="mt-2"><span class="{{ $employeeImport->status === 'completed' ? 'status-generated' : 'status-draft' }}">{{ $employeeImport->status === 'completed' ? 'Sudah diimpor' : 'Siap dikonfirmasi' }}</span></p>
            </div>
        </div>
    </section>

    <section class="card mt-7">
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="section-heading">Contoh data</h2>
                <p class="section-description">Menampilkan {{ number_format($previewRows->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($previewRows->lastItem() ?? 0, 0, ',', '.') }} dari {{ number_format($previewRows->total(), 0, ',', '.') }} baris yang akan diproses.</p>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                    <tr>
                        <th class="pb-3 pr-5 font-semibold">NIP</th>
                        <th class="pb-3 pr-5 font-semibold">Nama</th>
                        <th class="pb-3 pr-5 font-semibold">Status</th>
                        <th class="pb-3 pr-5 font-semibold">Pangkat</th>
                        <th class="pb-3 pr-5 font-semibold">Unit kerja</th>
                        <th class="pb-3 font-semibold">Jabatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($previewRows as $row)
                        <tr>
                            <td class="py-4 pr-5 font-mono text-xs text-slate-600">{{ $row['nip'] }}</td>
                            <td class="py-4 pr-5 font-semibold text-slate-800">{{ $row['full_name'] }}</td>
                            <td class="py-4 pr-5 text-slate-600">{{ $row['employment_status'] }}</td>
                            <td class="py-4 pr-5 text-slate-600">{{ \App\Support\EmployeeRankOptions::format($row['employment_status'], $row['rank_name'], $row['grade']) }}</td>
                            <td class="py-4 pr-5 text-slate-600">{{ $row['department_name'] }}</td>
                            <td class="py-4 text-slate-600">{{ $row['position_title'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($previewRows->hasPages())
            @php
                $pageNumbers = collect([
                    1,
                    2,
                    $previewRows->currentPage() - 1,
                    $previewRows->currentPage(),
                    $previewRows->currentPage() + 1,
                    $previewRows->lastPage() - 1,
                    $previewRows->lastPage(),
                ])
                    ->filter(static fn (int $page): bool => $page >= 1 && $page <= $previewRows->lastPage())
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
                $lastVisiblePage = null;
            @endphp

            <nav class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between" aria-label="Halaman pratinjau impor">
                <p class="text-sm text-slate-500">Halaman <span class="font-semibold tabular-nums text-slate-700">{{ $previewRows->currentPage() }}</span> dari <span class="font-semibold tabular-nums text-slate-700">{{ $previewRows->lastPage() }}</span></p>

                <div class="flex items-center gap-1.5" role="list">
                    @if ($previewRows->onFirstPage())
                        <span class="inline-flex size-11 cursor-not-allowed items-center justify-center rounded-xl bg-slate-100 text-slate-300" aria-disabled="true">
                            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" /></svg>
                            <span class="sr-only">Halaman sebelumnya</span>
                        </span>
                    @else
                        <a class="inline-flex size-11 items-center justify-center rounded-xl bg-white text-slate-600 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.1)] transition-transform duration-150 hover:bg-slate-50 hover:text-slate-950 active:scale-[0.96]" href="{{ $previewRows->previousPageUrl() }}" aria-label="Halaman sebelumnya">
                            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" /></svg>
                        </a>
                    @endif

                    @foreach ($pageNumbers as $page)
                        @if ($lastVisiblePage !== null && $page > $lastVisiblePage + 1)
                            <span class="inline-flex size-11 items-center justify-center text-sm font-semibold text-slate-400" aria-hidden="true">…</span>
                        @endif

                        @if ($page === $previewRows->currentPage())
                            <span class="inline-flex size-11 items-center justify-center rounded-xl bg-sky-600 text-sm font-semibold text-white shadow-[0_6px_16px_rgba(2,132,199,0.24)] tabular-nums" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="inline-flex size-11 items-center justify-center rounded-xl bg-white text-sm font-semibold text-slate-600 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.1)] transition-transform duration-150 hover:bg-slate-50 hover:text-slate-950 active:scale-[0.96] tabular-nums" href="{{ $previewRows->url($page) }}">{{ $page }}</a>
                        @endif

                        @php
                            $lastVisiblePage = $page;
                        @endphp
                    @endforeach

                    @if ($previewRows->hasMorePages())
                        <a class="inline-flex size-11 items-center justify-center rounded-xl bg-white text-slate-600 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.1)] transition-transform duration-150 hover:bg-slate-50 hover:text-slate-950 active:scale-[0.96]" href="{{ $previewRows->nextPageUrl() }}" aria-label="Halaman berikutnya">
                            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                        </a>
                    @else
                        <span class="inline-flex size-11 cursor-not-allowed items-center justify-center rounded-xl bg-slate-100 text-slate-300" aria-disabled="true">
                            <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                            <span class="sr-only">Halaman berikutnya</span>
                        </span>
                    @endif
                </div>
            </nav>
        @endif

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-xl text-sm leading-6 text-slate-500">Konfirmasi hanya dapat diproses sekali. Jika tombol terkirim ulang, sistem akan mengenali impor yang sudah selesai dan tidak membuat data ganda.</p>

            @if ($employeeImport->status === 'completed')
                <a class="btn-primary shrink-0" href="{{ route('employees.index') }}">Lihat data pegawai</a>
            @else
                <form method="POST" action="{{ route('employees.import.store', $employeeImport) }}" data-employee-import-form>
                    @csrf
                    <button class="btn-primary shrink-0" type="submit" data-submit-employee-import>
                        <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Impor {{ number_format($employeeImport->total_rows, 0, ',', '.') }} pegawai
                    </button>
                </form>
            @endif
        </div>
    </section>
@endsection
