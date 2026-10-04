@extends('layouts.app')

@section('title', 'Pratinjau Import Payroll')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Payroll</p>
            <h1 class="page-title mt-2">Pratinjau data pegawai</h1>
            <p class="page-description">Periksa perubahan data pegawai dari file periode {{ sprintf('%02d/%d', $payrollImport->month, $payrollImport->year) }}. Nomor rekening ikut diimpor jika tersedia. Nominal payroll diabaikan.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('payroll.import.create') }}">Unggah file lain</a>
    </div>

    @error('import')
        <div class="mt-7 rounded-xl bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-700 shadow-[inset_0_0_0_1px_rgba(225,29,72,0.14)]">{{ $message }}</div>
    @enderror

    @if (! $payrollImport->employee_data_only)
        <p class="card mt-7 text-sm text-amber-800">Pratinjau ini dibuat dengan alur impor lama. Pilih Unggah file lain untuk membuat pratinjau data pegawai saja.</p>
    @endif

    <section class="card mt-7">
        <div class="grid gap-4 sm:grid-cols-5">
            <div class="card-inner">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">File sumber</p>
                <p class="mt-2 break-words text-sm font-semibold text-slate-800">{{ $payrollImport->original_filename }}</p>
            </div>
            <div class="card-inner">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Periode</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">{{ sprintf('%02d/%d', $payrollImport->month, $payrollImport->year) }}</p>
            </div>
            <div class="card-inner">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Pegawai</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950 tabular-nums">{{ number_format($payrollImport->total_rows, 0, ',', '.') }}</p>
            </div>
            <div class="card-inner">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Jenis file</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $payrollImport->sourceTypeLabel() }}</p>
            </div>
            <div class="card-inner">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Status</p>
                <p class="mt-2">
                    <span class="{{ $payrollImport->status === 'completed' ? 'status-generated' : ($payrollImport->status === 'cancelled' ? 'status-void' : 'status-draft') }}">
                        {{ $payrollImport->status === 'completed' ? 'Sudah diimpor' : ($payrollImport->status === 'cancelled' ? 'Dibatalkan' : 'Menunggu konfirmasi') }}
                    </span>
                </p>
            </div>
        </div>
    </section>

    @if ($payrollImport->employee_data_only)
    <section class="card mt-7">
        <div>
            <h2 class="section-heading">Perubahan data master dari file</h2>
            @if ($changeReport['total'] === 0)
                <p class="section-description">Tidak ada perubahan data master pegawai pada file ini.</p>
            @else
                <p class="section-description">Ditemukan {{ number_format($changeReport['total'], 0, ',', '.') }} pegawai yang memiliki perubahan. Perubahan baru disimpan hanya setelah Anda memilih lanjutkan.</p>
            @endif
        </div>

        @if ($changeReport['total'] > 0)
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-[900px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">NIP</th>
                            <th class="pb-3 pr-5 font-semibold">Nama dari file</th>
                            <th class="pb-3 font-semibold">Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($changeReport['items'] as $change)
                            <tr>
                                <td class="whitespace-nowrap py-3.5 pr-5 font-mono text-xs text-slate-700">{{ $change['nip'] }}</td>
                                <td class="py-3.5 pr-5 font-semibold text-slate-800">{{ $change['name'] }}</td>
                                <td class="py-3.5 text-slate-600">
                                    <ul class="space-y-1">
                                        @foreach ($change['changes'] as $fieldChange)
                                            <li><span class="font-semibold text-slate-800">{{ $fieldChange['field'] }}:</span> {{ $fieldChange['old'] }} → {{ $fieldChange['new'] }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($changeReport['total'] > count($changeReport['items']))
                <p class="form-help mt-4">Rincian yang ditampilkan dibatasi {{ number_format(count($changeReport['items']), 0, ',', '.') }} pegawai pertama.</p>
            @endif
        @endif
    </section>

    @endif

    <section class="card mt-7">
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="section-heading">Data pegawai dari file</h2>
                <p class="section-description">Menampilkan {{ number_format($previewRows->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($previewRows->lastItem() ?? 0, 0, ',', '.') }} dari {{ number_format($previewRows->total(), 0, ',', '.') }} baris yang akan diproses.</p>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-[700px] w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                    <tr>
                        <th class="pb-3 pr-5 font-semibold">NIP</th>
                        <th class="pb-3 pr-5 font-semibold">Nama</th>
                        <th class="pb-3 pr-5 font-semibold">Pangkat/golongan</th>
                        <th class="pb-3 pr-5 font-semibold">Tipe jabatan</th>
                        <th class="pb-3 font-semibold">Status kepegawaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($previewRows as $row)
                        <tr>
                            <td class="py-3.5 pr-5 font-mono text-xs text-slate-700">{{ $row['employee_nip'] }}</td>
                            <td class="py-3.5 pr-5 font-semibold text-slate-800">{{ $row['employee_name'] }}</td>
                            <td class="py-3.5 pr-5 text-slate-600">{{ \App\Support\EmployeeRankOptions::format($row['employment_status'], $row['rank_name'], $row['grade']) }}</td>
                            <td class="py-3.5 pr-5 text-slate-600">{{ $row['position_title'] ?? '—' }}</td>
                            <td class="py-3.5 text-slate-600">{{ $row['employment_status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($previewRows->hasPages())
            <div class="mt-5">{{ $previewRows->links() }}</div>
        @endif

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-2xl text-sm leading-6 text-slate-500">Konfirmasi hanya menyimpan perubahan data pegawai dan catatan perubahan. Nomor rekening ikut disimpan jika tersedia. Nominal payroll diabaikan.</p>

            @if ($payrollImport->status === 'completed')
                <a class="btn-primary shrink-0" href="{{ route('employees.index') }}">Lihat pegawai</a>
            @elseif ($payrollImport->status === 'previewed')
                <div class="flex shrink-0 flex-col-reverse gap-3 sm:flex-row">
                    <form method="POST" action="{{ route('payroll.import.cancel', $payrollImport) }}" data-confirm-title="Batalkan pratinjau impor?" data-confirm-message="Pratinjau akan dibatalkan. Data pegawai tetap seperti semula." data-confirm-button="Batalkan pratinjau">
                        @csrf
                        @method('DELETE')
                        <button class="btn-secondary w-full sm:w-auto" type="submit">Batalkan</button>
                    </form>
                    <form method="POST" action="{{ route('payroll.import.confirm', $payrollImport) }}">
                        @csrf
                        <button class="btn-primary w-full whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto" type="submit" @disabled(! $payrollImport->employee_data_only)>Simpan data pegawai</button>
                    </form>
                </div>
            @else
                <a class="btn-primary shrink-0" href="{{ route('payroll.import.create') }}">Unggah file baru</a>
            @endif
        </div>
    </section>
@endsection
