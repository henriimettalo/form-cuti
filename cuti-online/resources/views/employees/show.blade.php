@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')
    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Profil pegawai</p>
            <h1 class="page-title mt-2">{{ $employee->full_name }}</h1>
            <p class="page-description">{{ $employee->nip }} · {{ $employee->employment_status }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="btn-secondary" href="{{ route('employees.index') }}">Kembali</a>
            <a class="btn-secondary" href="{{ route('employees.edit', $employee) }}">Edit data</a>
            <a class="btn-primary" href="{{ route('employees.rank-histories.create', $employee) }}">Catat pangkat</a>
            <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Arsipkan pegawai ini? Data dan riwayat tetap tersimpan dan dapat dipulihkan.')">
                @csrf
                @method('DELETE')
                <button class="btn-danger" type="submit">Arsipkan</button>
            </form>
        </div>
    </div>

    <section class="card mt-7">
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Pangkat / golongan</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ \App\Support\EmployeeRankOptions::format($employee->employment_status, $employee->rank_name, $employee->grade) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Jabatan</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $employee->position?->name ?? $employee->position_title ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Unit kerja</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $currentDepartmentName ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Mulai masa kerja</p>
                <p class="mt-2 text-sm font-semibold tabular-nums text-slate-900">{{ $employee->service_started_on?->format('d/m/Y') ?? '-' }}</p>
                @if ($employee->nip_tmt_valid === false)
                    <p class="mt-1 text-xs font-medium text-amber-700">TMT NIP tidak valid; gunakan TMT manual.</p>
                @endif
            </div>
        </div>

        <div class="mt-7 grid gap-5 border-t border-slate-100 pt-6 md:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Kontak</p>
                <p class="mt-2 text-sm text-slate-700">{{ $employee->phone ?: '-' }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $employee->email ?: '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Alamat</p>
                <p class="mt-2 text-sm leading-6 text-slate-700">{{ $employee->address ?: '-' }}</p>
            </div>
        </div>
    </section>

    <section class="card mt-6">
        <div>
            <h2 class="section-heading">Administrasi kepegawaian</h2>
            <p class="section-description">Data identitas dan administrasi yang melengkapi profil SIMPEG.</p>
        </div>
        <div class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @if ($canViewSensitive)
                <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">NIK</p><p class="mt-2 text-sm font-medium text-slate-800">{{ $employee->nik ?: '-' }}</p></div>
                <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">NPWP</p><p class="mt-2 text-sm font-medium text-slate-800">{{ $employee->npwp ?: '-' }}</p></div>
            @endif
            <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Tanggal lahir</p><p class="mt-2 text-sm font-medium tabular-nums text-slate-800">{{ $employee->birth_date?->format('d/m/Y') ?? '-' }}</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Jenis kelamin</p><p class="mt-2 text-sm font-medium text-slate-800">{{ $employee->gender === 'L' ? 'Laki-laki' : ($employee->gender === 'P' ? 'Perempuan' : '-') }}</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Tipe jabatan</p><p class="mt-2 text-sm font-medium text-slate-800">{{ [1 => 'Struktural', 3 => 'Fungsional Umum'][$employee->position_type] ?? '-' }}</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Eselon</p><p class="mt-2 text-sm font-medium text-slate-800">{{ $employee->eselon ?: '00' }}</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Status pernikahan</p><p class="mt-2 text-sm font-medium text-slate-800">{{ [1 => 'Menikah', 2 => 'Belum menikah'][$employee->marital_status] ?? '-' }}</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Tanggungan</p><p class="mt-2 text-sm font-medium tabular-nums text-slate-800">{{ $employee->spouse_count + $employee->child_count }} ({{ $employee->spouse_count }} pasangan · {{ $employee->child_count }} anak)</p></div>
            <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Pasangan PNS</p><p class="mt-2 text-sm font-medium text-slate-800">{{ $employee->spouse_is_pns === null ? '-' : ($employee->spouse_is_pns ? 'YA' : 'TIDAK') }}</p></div>
            @if ($canViewSensitive)
                <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">NIP pasangan</p><p class="mt-2 text-sm font-medium text-slate-800">{{ $employee->spouse_nip ?: '-' }}</p></div>
            @endif
            <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Masa kerja golongan</p><p class="mt-2 text-sm font-medium tabular-nums text-slate-800">{{ $employee->grade_service_years === null ? '-' : $employee->grade_service_years.' tahun '.($employee->grade_service_months ?? 0).' bulan' }}</p></div>
            @if ($canViewSensitive)
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Rekening utama</p>
                    @php($primaryBank = $employee->bankAccounts->firstWhere('is_primary', true) ?? $employee->bankAccounts->first())
                    <p class="mt-2 text-sm font-medium text-slate-800">{{ $primaryBank ? $primaryBank->bank_name.' · '.$primaryBank->account_number : '-' }}</p>
                </div>
            @else
                <div class="card-inner text-sm text-slate-600">NIK, NPWP, NIP pasangan, dan rekening hanya ditampilkan untuk admin/pejabat berwenang.</div>
            @endif
        </div>
    </section>

    <section class="card mt-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="section-heading">Riwayat pangkat dan golongan</h2>
                <p class="section-description">Setiap kenaikan pangkat disimpan sebagai rekam jejak pegawai.</p>
            </div>
            <a class="btn-secondary shrink-0" href="{{ route('employees.rank-histories.create', $employee) }}">Catat riwayat pangkat</a>
        </div>

        @if ($employee->rankHistories->isEmpty())
            <p class="card-inner mt-5 text-sm text-slate-600">Belum ada riwayat pangkat. Catat kondisi saat ini atau kenaikan berikutnya.</p>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Berlaku</th>
                            <th class="pb-3 pr-5 font-semibold">Pangkat / golongan</th>
                            <th class="pb-3 pr-5 font-semibold">Nomor SK</th>
                            <th class="pb-3 font-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employee->rankHistories as $history)
                            <tr>
                                <td class="whitespace-nowrap py-3.5 pr-5 tabular-nums text-slate-600">{{ $history->effective_on->format('d/m/Y') }}</td>
                                <td class="py-3.5 pr-5 font-medium text-slate-800">{{ \App\Support\EmployeeRankOptions::format($employee->employment_status, $history->rank_name, $history->grade) }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">{{ $history->decree_number ?: '-' }}</td>
                                <td class="py-3.5 text-slate-600">{{ $history->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="card mt-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="section-heading">Payroll terbaru</h2>
                <p class="section-description">Snapshot gaji bulanan yang sudah diimpor untuk pegawai ini.</p>
            </div>
            <a class="btn-secondary shrink-0" href="{{ route('payroll.index') }}">Buka payroll</a>
        </div>
        @if ($employee->payrollRecords->isEmpty())
            <p class="card-inner mt-5 text-sm text-slate-600">Belum ada payroll bulanan untuk pegawai ini.</p>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr><th class="pb-3 pr-5 font-semibold">Periode</th><th class="pb-3 pr-5 font-semibold">Total gaji</th><th class="pb-3 pr-5 font-semibold">Potongan</th><th class="pb-3 pr-5 font-semibold">Ditransfer</th><th class="pb-3 font-semibold">Cek</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employee->payrollRecords as $record)
                            <tr>
                                <td class="py-3.5 pr-5 font-medium text-slate-800">{{ $record->payrollPeriod?->label() ?? '-' }}</td>
                                <td class="py-3.5 pr-5 tabular-nums text-slate-700">Rp {{ number_format($record->total_earnings, 0, ',', '.') }}</td>
                                <td class="py-3.5 pr-5 tabular-nums text-slate-700">Rp {{ number_format($record->total_deductions, 0, ',', '.') }}</td>
                                <td class="py-3.5 pr-5 font-semibold tabular-nums text-slate-800">Rp {{ number_format($record->transferred_amount, 0, ',', '.') }}</td>
                                <td class="py-3.5 pr-5 {{ $record->reconciliation_status === 'matched' ? 'text-emerald-700' : 'font-semibold text-amber-700' }}">{{ $record->reconciliation_status === 'matched' ? 'Sesuai' : 'Perlu dicek' }}</td>
                                <td class="py-3.5 text-right"><a class="font-semibold text-sky-700 hover:text-sky-800" href="{{ route('payroll.slip', [$record->payrollPeriod, $record]) }}">Slip</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="card mt-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="section-heading">Riwayat gaji pokok</h2>
                <p class="section-description">Riwayat gaji pokok dari SK/manual. Payroll bulanan dihitung dan ditampilkan pada bagian di atas.</p>
            </div>
            <a class="btn-secondary shrink-0" href="{{ route('employees.salary-histories.create', $employee) }}">Catat gaji pokok</a>
        </div>

        @if ($employee->salaryHistories->isEmpty())
            <p class="card-inner mt-5 text-sm text-slate-600">Belum ada riwayat gaji pokok.</p>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Berlaku</th>
                            <th class="pb-3 pr-5 font-semibold">Gaji pokok</th>
                            <th class="pb-3 pr-5 font-semibold">Alasan</th>
                            <th class="pb-3 pr-5 font-semibold">Pangkat terkait</th>
                            <th class="pb-3 pr-5 font-semibold">Nomor SK</th>
                            <th class="pb-3 font-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employee->salaryHistories as $history)
                            <tr>
                                <td class="whitespace-nowrap py-3.5 pr-5 tabular-nums text-slate-600">{{ $history->effective_on->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap py-3.5 pr-5 font-semibold tabular-nums text-slate-800">Rp {{ number_format($history->basic_salary, 0, ',', '.') }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">{{ \App\Models\EmployeeSalaryHistory::reasonOptions()[$history->change_reason] ?? $history->change_reason }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">{{ $history->rankHistory ? \App\Support\EmployeeRankOptions::format($employee->employment_status, $history->rankHistory->rank_name, $history->rankHistory->grade) : '-' }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">{{ $history->decree_number ?: '-' }}</td>
                                <td class="py-3.5 text-slate-600">{{ $history->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="card mt-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="section-heading">Riwayat jabatan</h2>
                <p class="section-description">Mutasi atau promosi tetap tersimpan setelah data aktif diperbarui. Ringkasan unit kerja mengikuti riwayat jabatan terbaru.</p>
            </div>
            <a class="btn-secondary shrink-0" href="{{ route('employees.position-histories.create', $employee) }}">Catat riwayat jabatan</a>
        </div>

        @if ($employee->positionHistories->isEmpty())
            <p class="card-inner mt-5 text-sm text-slate-600">Belum ada riwayat jabatan. Catat kondisi saat ini atau perubahan berikutnya.</p>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Berlaku</th>
                            <th class="pb-3 pr-5 font-semibold">Jabatan</th>
                            <th class="pb-3 pr-5 font-semibold">Unit kerja</th>
                            <th class="pb-3 pr-5 font-semibold">Nomor SK</th>
                            <th class="pb-3 font-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($employee->positionHistories as $history)
                            <tr>
                                <td class="whitespace-nowrap py-3.5 pr-5 tabular-nums text-slate-600">{{ $history->effective_on->format('d/m/Y') }}</td>
                                <td class="py-3.5 pr-5 font-medium text-slate-800">{{ $history->position_title }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">{{ $history->department_name }}</td>
                                <td class="py-3.5 pr-5 text-slate-600">{{ $history->decree_number ?: '-' }}</td>
                                <td class="py-3.5 text-slate-600">{{ $history->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
