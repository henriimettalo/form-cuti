@extends('layouts.app')

@section('title', 'Slip Gaji '.$record->employee_name)

@section('content')
    @php
        $earnings = [
            'Gaji pokok' => $record->basic_salary,
            'Tunjangan pasangan' => $record->spouse_allowance,
            'Tunjangan anak' => $record->child_allowance,
            'Tunjangan keluarga (otomatis)' => $record->family_allowance,
            'Tunjangan jabatan' => $record->position_allowance,
            'Tunjangan fungsional' => $record->functional_allowance,
            'Tunjangan fungsional umum' => $record->general_functional_allowance,
            'Tunjangan beras' => $record->rice_allowance,
            'Tunjangan PPh' => $record->pph_allowance,
            'Pembulatan' => $record->rounding,
            'Tunjangan khusus Papua' => $record->papua_special_allowance,
            'Iuran kesehatan dalam penghasilan' => $record->health_contribution,
            'Iuran kecelakaan kerja dalam penghasilan' => $record->work_accident_contribution,
            'Iuran kematian dalam penghasilan' => $record->death_contribution,
        ];
        $deductions = [
            'Iuran kesehatan' => $record->health_contribution,
            'Iuran kecelakaan kerja' => $record->work_accident_contribution,
            'Iuran kematian' => $record->death_contribution,
            'Tapera' => $record->tapera,
            'Pensiun' => $record->pension_contribution,
            'Tunjangan JHT' => $record->jht_allowance,
            'Potongan IWP' => $record->iwp_deduction,
            'Potongan PPh21' => $record->pph21_deduction,
            'Zakat' => $record->zakat,
            'Bulog' => $record->bulog,
        ];
        $tppEarnings = [
            'TPP beban kerja' => $record->tpp_workload,
            'TPP tempat bertugas' => $record->tpp_workplace,
            'TPP kondisi kerja' => $record->tpp_work_conditions,
            'TPP kelangkaan profesi' => $record->tpp_profession_scarcity,
            'TPP prestasi kerja' => $record->tpp_performance,
            'Tunjangan PPh TPP' => $record->tpp_pph_allowance,
            'Iuran kesehatan TPP' => $record->tpp_health_contribution,
            'Iuran kecelakaan kerja TPP' => $record->tpp_work_accident_contribution,
            'Iuran kematian TPP' => $record->tpp_death_contribution,
        ];
        $tppDeductions = [
            'Iuran kesehatan TPP' => $record->tpp_health_contribution,
            'Iuran kecelakaan kerja TPP' => $record->tpp_work_accident_contribution,
            'Iuran kematian TPP' => $record->tpp_death_contribution,
            'Tapera TPP' => $record->tpp_tapera,
            'Pensiun TPP' => $record->tpp_pension_contribution,
            'Tunjangan JHT TPP' => $record->tpp_jht_allowance,
            'Potongan IWP TPP' => $record->tpp_iwp_deduction,
            'Potongan PPh21 TPP' => $record->tpp_pph21_deduction,
            'Zakat TPP' => $record->tpp_zakat,
            'Bulog TPP' => $record->tpp_bulog,
        ];
    @endphp

    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Slip gaji</p>
            <h1 class="page-title mt-2">{{ $record->employee_name }}</h1>
            <p class="page-description">{{ $record->employee_nip }} · Periode {{ $period->label() }} · {{ $record->skpd_snapshot ?: '-' }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="btn-secondary" href="{{ route('payroll.show', $period) }}">Kembali ke payroll</a>
            <button class="btn-primary" type="button" onclick="window.print()">Cetak</button>
        </div>
    </div>

    <section class="card mt-7">
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Rekening</p>
                @if ($canViewSensitive)
                    <p class="mt-2 text-sm font-semibold text-slate-800">{{ $record->bank_name ?: '-' }}</p>
                    <p class="mt-1 font-mono text-xs text-slate-500">{{ $record->account_number ?: 'Nomor rekening belum tersedia' }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-600">Terbatas untuk admin/pejabat berwenang.</p>
                @endif
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Status rekonsiliasi</p>
                <p class="mt-2 text-sm font-semibold {{ $record->reconciliation_status === 'matched' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $record->reconciliation_status === 'matched' ? 'Sesuai sumber' : 'Perlu dicek' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Status periode</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $period->status === 'locked' ? 'Terkunci' : 'Draf' }}</p>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card">
            <h2 class="section-heading">Gaji dan tunjangan</h2>
            <div class="mt-5 divide-y divide-slate-100">
                @foreach ($earnings as $label => $amount)
                    <div class="flex items-center justify-between gap-5 py-3 text-sm">
                        <span class="text-slate-600">{{ $label }}</span>
                        <span class="tabular-nums text-slate-800">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 font-semibold">
                <span>Total gaji dan tunjangan</span>
                <span class="tabular-nums">Rp {{ number_format($record->total_earnings, 0, ',', '.') }}</span>
            </div>
        </section>

        <section class="card">
            <h2 class="section-heading">Potongan</h2>
            <div class="mt-5 divide-y divide-slate-100">
                @foreach ($deductions as $label => $amount)
                    <div class="flex items-center justify-between gap-5 py-3 text-sm">
                        <span class="text-slate-600">{{ $label }}</span>
                        <span class="tabular-nums text-slate-800">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 font-semibold">
                <span>Total potongan</span>
                <span class="tabular-nums">Rp {{ number_format($record->total_deductions, 0, ',', '.') }}</span>
            </div>
        </section>
    </div>

    <section class="card mt-6">
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Jumlah diterima</p>
                <p class="mt-2 text-xl font-semibold tabular-nums text-emerald-700">Rp {{ number_format($record->transferred_amount, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Total sumber</p>
                <p class="mt-2 text-sm tabular-nums text-slate-700">Gaji Rp {{ number_format($record->source_total_earnings, 0, ',', '.') }}</p>
                <p class="mt-1 text-sm tabular-nums text-slate-700">Potongan Rp {{ number_format($record->source_total_deductions, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Transfer sumber</p>
                <p class="mt-2 text-sm font-semibold tabular-nums text-slate-800">Rp {{ number_format($record->source_transferred_amount, 0, ',', '.') }}</p>
            </div>
        </div>
        <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500">Total ditampilkan dari perhitungan server. Nilai sumber disimpan untuk rekonsiliasi dengan file impor.</p>
    </section>

    @if ($record->tpp_source_row_checksum)
        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <section class="card">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="section-heading">TPP SIPD</h2>
                    <span class="text-xs font-semibold {{ $record->tpp_reconciliation_status === 'matched' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $record->tpp_reconciliation_status === 'matched' ? 'Sesuai sumber' : 'Perlu dicek' }}</span>
                </div>
                <div class="mt-5 divide-y divide-slate-100">
                    @foreach ($tppEarnings as $label => $amount)
                        <div class="flex items-center justify-between gap-5 py-3 text-sm">
                            <span class="text-slate-600">{{ $label }}</span>
                            <span class="tabular-nums text-slate-800">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 font-semibold">
                    <span>Total TPP</span>
                    <span class="tabular-nums">Rp {{ number_format($record->tpp_total, 0, ',', '.') }}</span>
                </div>
            </section>

            <section class="card">
                <h2 class="section-heading">Potongan TPP</h2>
                <div class="mt-5 divide-y divide-slate-100">
                    @foreach ($tppDeductions as $label => $amount)
                        <div class="flex items-center justify-between gap-5 py-3 text-sm">
                            <span class="text-slate-600">{{ $label }}</span>
                            <span class="tabular-nums text-slate-800">Rp {{ number_format($amount, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 font-semibold">
                    <span>Total potongan TPP</span>
                    <span class="tabular-nums">Rp {{ number_format($record->tpp_total_deductions, 0, ',', '.') }}</span>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4 font-semibold text-emerald-700">
                    <span>TPP diterima</span>
                    <span class="tabular-nums">Rp {{ number_format($record->tpp_transferred_amount, 0, ',', '.') }}</span>
                </div>
            </section>
        </div>
    @endif
@endsection
