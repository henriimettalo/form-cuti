@extends('layouts.app')

@section('title', 'Catat Riwayat Gaji Pokok')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Riwayat kepegawaian</p>
            <h1 class="page-title mt-2">Catat riwayat gaji pokok</h1>
            <p class="page-description">Simpan nilai gaji pokok sesuai dokumen sumber tanpa menghitung payroll.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('employees.show', $employee) }}">Kembali ke profil</a>
    </div>

    <form class="card mt-7" method="POST" action="{{ route('employees.salary-histories.store', $employee) }}">
        @csrf
        <div class="card-inner">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Pegawai</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->full_name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $employee->nip }} · {{ \App\Support\EmployeeRankOptions::format($employee->employment_status, $employee->rank_name, $employee->grade) }}</p>
            @if ($currentSalary)
                <p class="mt-1 text-sm text-slate-600">Gaji pokok terakhir: <span class="font-semibold text-slate-800">Rp {{ number_format($currentSalary->basic_salary, 0, ',', '.') }}</span></p>
            @endif
        </div>

        <div class="mt-7 grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="basic_salary">Gaji pokok</label>
                <input class="form-input" id="basic_salary" type="number" name="basic_salary" value="{{ old('basic_salary') }}" min="0" step="1" inputmode="numeric" required>
                <p class="form-help">Masukkan angka rupiah tanpa titik atau simbol mata uang.</p>
                @error('basic_salary') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="effective_on">Tanggal berlaku</label>
                <input class="form-input" id="effective_on" type="date" name="effective_on" value="{{ old('effective_on', now()->toDateString()) }}" required>
                @error('effective_on') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="change_reason">Alasan perubahan</label>
                <select class="form-select" id="change_reason" name="change_reason" required>
                    @foreach ($reasonOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('change_reason', 'other') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('change_reason') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="employee_rank_history_id">Riwayat pangkat terkait</label>
                <select class="form-select" id="employee_rank_history_id" name="employee_rank_history_id">
                    <option value="">Tidak ditautkan</option>
                    @foreach ($employee->rankHistories as $rankHistory)
                        <option value="{{ $rankHistory->id }}" @selected(old('employee_rank_history_id') == $rankHistory->id)>
                            {{ $rankHistory->effective_on->format('d/m/Y') }} · {{ \App\Support\EmployeeRankOptions::format($employee->employment_status, $rankHistory->rank_name, $rankHistory->grade) }}
                        </option>
                    @endforeach
                </select>
                @error('employee_rank_history_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="form-label" for="decree_number">Nomor SK</label>
                <input class="form-input" id="decree_number" name="decree_number" value="{{ old('decree_number') }}" maxlength="100" placeholder="Opsional">
                @error('decree_number') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="form-label" for="notes">Keterangan</label>
                <textarea class="form-textarea" id="notes" name="notes" maxlength="1000" placeholder="Contoh: Penyesuaian berdasarkan SK kenaikan gaji berkala">{{ old('notes') }}</textarea>
                @error('notes') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a class="btn-secondary" href="{{ route('employees.show', $employee) }}">Batal</a>
            <button class="btn-primary" type="submit">Simpan riwayat gaji</button>
        </div>
    </form>
@endsection
