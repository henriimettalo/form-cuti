@extends('layouts.app')

@section('title', 'Catat Riwayat Jabatan')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Riwayat kepegawaian</p>
            <h1 class="page-title mt-2">Catat riwayat jabatan</h1>
            <p class="page-description">Perubahan ini akan memperbarui jabatan aktif sekaligus menambah jejak riwayat pegawai.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('employees.show', $employee) }}">Kembali ke profil</a>
    </div>

    <form class="card mt-7" method="POST" action="{{ route('employees.position-histories.store', $employee) }}">
        @csrf
        <div class="card-inner">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Pegawai</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $employee->full_name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $employee->nip }} · Saat ini: {{ $employee->position?->name ?? $employee->position_title ?? '-' }}</p>
        </div>

        <div class="mt-7 grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="organization_name">Unit kerja</label>
                <div class="form-input flex min-h-11 items-center bg-slate-50 text-slate-700" id="organization_name" role="status" aria-label="Unit kerja pegawai">{{ $currentDepartmentName ?? $employee->department?->name ?? $organizationProfile->name }}</div>
                <p class="form-help text-pretty">Mengikuti unit kerja aktif pegawai. Profil Instansi hanya dipakai sebagai bawaan jika unit kerja belum tersedia.</p>
            </div>
            <div>
                <label class="form-label" for="position_title">Jabatan baru</label>
                <input class="form-input" id="position_title" name="position_title" value="{{ old('position_title', $employee->position?->name ?? $employee->position_title) }}" maxlength="255" required>
                @error('position_title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="effective_on">Tanggal berlaku</label>
                <input class="form-input" id="effective_on" type="date" name="effective_on" value="{{ old('effective_on', now()->toDateString()) }}" required>
                @error('effective_on') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="decree_number">Nomor SK</label>
                <input class="form-input" id="decree_number" name="decree_number" value="{{ old('decree_number') }}" maxlength="100" placeholder="Opsional">
                @error('decree_number') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="form-label" for="notes">Keterangan</label>
                <textarea class="form-textarea" id="notes" name="notes" maxlength="1000" placeholder="Contoh: Mutasi internal">{{ old('notes') }}</textarea>
                @error('notes') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a class="btn-secondary" href="{{ route('employees.show', $employee) }}">Batal</a>
            <button class="btn-primary" type="submit">Simpan riwayat jabatan</button>
        </div>
    </form>
@endsection
