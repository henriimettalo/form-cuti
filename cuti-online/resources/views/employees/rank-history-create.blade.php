@extends('layouts.app')

@section('title', 'Catat Riwayat Pangkat')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Riwayat kepegawaian</p>
            <h1 class="page-title mt-2">Catat riwayat pangkat</h1>
            <p class="page-description">Perubahan ini akan memperbarui pangkat aktif sekaligus menambah jejak riwayat pegawai.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('employees.show', $employee) }}">Kembali ke profil</a>
    </div>

    <form class="card mt-7" method="POST" action="{{ route('employees.rank-histories.store', $employee) }}">
        @csrf
        <div class="card-inner">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Pegawai</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->full_name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $employee->nip }} · Saat ini: {{ \App\Support\EmployeeRankOptions::format($employee->employment_status, $employee->rank_name, $employee->grade) }}</p>
            @if ($currentSalary)
                <p class="mt-1 text-sm text-slate-600">Gaji pokok terakhir: <span class="font-semibold text-slate-800">Rp {{ number_format($currentSalary->basic_salary, 0, ',', '.') }}</span> (berlaku {{ $currentSalary->effective_on->format('d/m/Y') }})</p>
            @endif
        </div>

        <div class="mt-7 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="form-label" for="rank_grade">Pangkat / golongan baru</label>
                <select class="form-select" id="rank_grade" name="rank_grade" required>
                    <option value="">Pilih pangkat / golongan</option>
                    @foreach ($rankGroups as $rankGroup)
                        <optgroup label="{{ $rankGroup['label'] }}">
                            @foreach ($rankGroup['ranks'] as $rank)
                                <option value="{{ $rank['grade'] }}" @selected(old('rank_grade', $employee->grade) === $rank['grade'])>{{ $rank['label'] ?? ($rank['name'].' ('.$rank['grade'].')') }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('rank_grade') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="effective_on">Tanggal berlaku</label>
                <input class="form-input" id="effective_on" type="date" name="effective_on" value="{{ old('effective_on', now()->toDateString()) }}" required>
                @error('effective_on') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="basic_salary">Gaji pokok baru</label>
                <input class="form-input" id="basic_salary" type="number" name="basic_salary" value="{{ old('basic_salary') }}" min="0" step="1" inputmode="numeric" placeholder="Opsional">
                <p class="form-help">Jika diisi, otomatis ditautkan ke riwayat pangkat ini. Nilai dicatat apa adanya dan tidak dihitung sistem.</p>
                @error('basic_salary') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="decree_number">Nomor SK</label>
                <input class="form-input" id="decree_number" name="decree_number" value="{{ old('decree_number') }}" maxlength="100" placeholder="Opsional">
                @error('decree_number') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="form-label" for="notes">Keterangan</label>
                <textarea class="form-textarea" id="notes" name="notes" maxlength="1000" placeholder="Contoh: Kenaikan pangkat reguler">{{ old('notes') }}</textarea>
                @error('notes') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a class="btn-secondary" href="{{ route('employees.show', $employee) }}">Batal</a>
            <button class="btn-primary" type="submit">Simpan riwayat pangkat</button>
        </div>
    </form>
@endsection
