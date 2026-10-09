@extends('layouts.app')

@section('title', $schedule->exists ? 'Edit Jadwal Kegiatan' : 'Tambah Jadwal Kegiatan')

@section('content')
    @php($backUrl = route($isCounterDuty ? 'counter-duty.index' : 'ceremony-schedules.index', ['month' => $schedule->event_date->format('Y-m')]))
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">{{ $schedule->exists ? ($isCounterDuty ? 'Edit jadwal piket loket' : 'Edit jadwal apel/upacara') : 'Tambah jadwal apel/upacara' }}</h1>
            <p class="page-description">{{ $isCounterDuty ? 'Tentukan tanggal dan kelompok petugas piket loket.' : 'Apel berlangsung setiap Senin; kelurahan petugas bergilir sesuai urutan alfabet.' }} Jadwal dapat dilihat oleh semua pengguna.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ $backUrl }}">Kembali ke jadwal</a>
    </div>

    <form class="card mt-7" method="POST" action="{{ $schedule->exists ? route('ceremony-schedules.update', $schedule) : route('ceremony-schedules.store') }}">
        @csrf
        @if ($schedule->exists)
            @method('PUT')
        @endif
        <h2 class="section-heading">Informasi kegiatan</h2>
        <p class="section-description">Nama, jenis, dan tanggal kegiatan wajib diisi.</p>
        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div class="min-w-0 sm:col-span-2">
                <label class="form-label" for="title">Nama kegiatan</label>
                <input class="form-input" id="title" name="title" value="{{ old('title', $schedule->title) }}" maxlength="180" required>
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-0">
                <label class="form-label" for="type">Jenis kegiatan</label>
                <select class="form-select" id="type" name="type" required>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            @unless ($isCounterDuty)
            <div class="min-w-0 sm:col-span-2">
                <label class="form-label" for="rotation_start_department_id">Kelurahan petugas pertama tahun {{ $schedule->event_date->format('Y') }}</label>
                <select class="form-select" id="rotation_start_department_id" name="rotation_start_department_id" required>
                    <option value="">Pilih kelurahan pertama</option>
                    @foreach ($kelurahans as $kelurahan)
                        <option value="{{ $kelurahan->id }}" @selected((string) old('rotation_start_department_id', $schedule->rotation_start_department_id ?? $rotationStart) === (string) $kelurahan->id)>{{ $kelurahan->name }}</option>
                    @endforeach
                </select>
                <p class="form-help">Urutan mengikuti nama kelurahan: yang dipilih menjadi giliran pertama, lalu berlanjut ke kelurahan berikutnya setiap Senin.</p>
                @error('rotation_start_department_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            @endunless
            <div class="min-w-0">
                <label class="form-label" for="event_date">Tanggal</label>
                <input class="form-input" id="event_date" name="event_date" type="date" value="{{ old('event_date', $schedule->event_date->format('Y-m-d')) }}" required>
                @error('event_date') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-0">
                <label class="form-label" for="department_id">{{ $isCounterDuty ? 'Unit petugas (opsional)' : 'Kelurahan petugas (ditentukan otomatis)' }}</label>
                <select class="form-select" id="department_id" name="department_id">
                    <option value="">{{ $isCounterDuty ? 'Belum ditentukan' : 'Otomatis mengikuti rotasi' }}</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id', $schedule->department_id) === (string) $department->id)>{{ $department->name }}{{ $department->is_active ? '' : ' (nonaktif)' }}</option>
                    @endforeach
                </select>
                @error('department_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-0 sm:col-span-2">
                <label class="form-label" for="notes">Catatan (opsional)</label>
                <textarea class="form-input" id="notes" name="notes" rows="4" maxlength="3000">{{ old('notes', $schedule->notes) }}</textarea>
                <p class="form-help">Tambahkan informasi pakaian, peserta, atau persiapan kegiatan jika diperlukan.</p>
                @error('notes') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            @if ($isCounterDuty)
            <div class="min-w-0 sm:col-span-2">
                <label class="form-label" for="duty_group_id">Kelompok piket (khusus Piket Loket)</label>
                <select class="form-select" name="duty_group_id" id="duty_group_id">
                    <option value="">Belum ditentukan</option>
                    @foreach ($dutyGroups as $group)<option value="{{ $group->id }}" @selected((string) old('duty_group_id', $schedule->duty_group_id) === (string) $group->id)>Kelompok {{ $group->number }} — {{ $group->coordinator }}</option>@endforeach
                </select>
                <p class="form-help">Diabaikan untuk Apel / Upacara. Susunan petugas disimpan bersama jadwal piket.</p>
                @error('duty_group_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            @endif
        </div>
        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a class="btn-secondary" href="{{ $backUrl }}">Batal</a>
            <button class="btn-primary" type="submit">{{ $schedule->exists ? 'Simpan perubahan' : 'Simpan jadwal' }}</button>
        </div>
    </form>
@endsection
