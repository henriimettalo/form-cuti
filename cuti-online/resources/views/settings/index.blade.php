@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Master pendukung</p>
        <h1 class="page-title mt-2">Pengaturan formulir</h1>
        <p class="page-description">Atur saldo cuti dan atasan langsung organisasi agar formulir diisi dari data yang konsisten.</p>
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-2">
        <section class="card">
            <h2 class="section-heading">Saldo cuti tahunan {{ $currentYear }}</h2>
            <p class="section-description">Simpan saldo N, N-1, dan N-2 sebelum membuat formulir cuti tahunan.</p>

            @if ($employees->isEmpty())
                <div class="card-inner mt-5 text-sm text-slate-600">Tambahkan pegawai terlebih dahulu.</div>
            @else
                <form class="mt-5 grid gap-4 sm:grid-cols-2" method="POST" action="{{ route('settings.balances.store') }}" data-leave-balance-form>
                    @csrf
                    <div class="sm:col-span-2">
                        @php($selectedBalanceEmployee = $employees->firstWhere('id', (int) old('employee_id')))
                        <label class="form-label" for="balance_employee_search">Pegawai</label>
                        <div class="relative" data-balance-employee-combobox>
                            <input id="employee_id" type="hidden" name="employee_id" value="{{ old('employee_id') }}" data-balance-employee-value>
                            <div class="relative">
                                <svg aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                                <input class="form-input pl-10" id="balance_employee_search" type="search" data-balance-employee-search autocomplete="off" placeholder="Cari nama atau NIP, mis. suri*008" value="{{ $selectedBalanceEmployee ? $selectedBalanceEmployee->full_name.' · '.$selectedBalanceEmployee->nip : '' }}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-required="true" aria-controls="balance-employee-search-results" aria-describedby="balance-employee-search-help balance-employee-search-result">
                            </div>
                            <div class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl bg-white p-1 shadow-[0_1px_2px_rgba(15,23,42,0.06),0_12px_28px_rgba(15,23,42,0.14)]" data-balance-employee-search-panel hidden>
                                <ul class="max-h-72 space-y-1 overflow-y-auto" id="balance-employee-search-results" role="listbox" aria-label="Hasil pencarian pegawai">
                                    @foreach ($employees as $employee)
                                    @php($balanceEmployeeLabel = $employee->full_name.' · '.$employee->nip)
                                    <li>
                                        <button class="flex min-h-11 w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-sm text-slate-700 outline-none transition-colors duration-150 hover:bg-sky-50 hover:text-sky-950 focus-visible:bg-sky-50 focus-visible:shadow-[inset_0_0_0_2px_rgba(14,165,233,0.6)]" id="balance-employee-search-option-{{ $employee->id }}" type="button" role="option" aria-selected="{{ (string) old('employee_id') === (string) $employee->id ? 'true' : 'false' }}" data-balance-employee-option data-balance-employee-id="{{ $employee->id }}" data-balance-employee-label="{{ $balanceEmployeeLabel }}">
                                            <span class="flex min-w-0 items-center gap-2">
                                                <span class="shrink-0 text-xs font-semibold tabular-nums text-slate-400" data-balance-employee-option-index>{{ $loop->iteration }}.</span>
                                                <span class="truncate font-medium">{{ $employee->full_name }}</span>
                                            </span>
                                            <span class="shrink-0 text-xs tabular-nums text-slate-500">{{ $employee->nip }}</span>
                                        </button>
                                    </li>
                                    @endforeach
                                </ul>
                                <p class="border-t border-slate-100 px-3 py-2 text-xs font-medium tabular-nums text-slate-500" id="balance-employee-search-result" data-balance-employee-search-result aria-live="polite">{{ $employees->count() }} pegawai tersedia.</p>
                            </div>
                        </div>
                        <p class="form-help text-pretty" id="balance-employee-search-help">Ketik sebagian nama atau NIP. Gunakan <span class="font-semibold text-slate-600">*</span> untuk wildcard.</p>
                        @error('employee_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <input type="hidden" name="leave_year" value="{{ $currentYear }}">
                    <div>
                        <label class="form-label" for="current_year_entitlement">Hak cuti N</label>
                        <input class="form-input tabular-nums" id="current_year_entitlement" type="number" min="0" name="current_year_entitlement" value="12" required>
                    </div>
                    <div>
                        <label class="form-label" for="current_year_used">Sudah digunakan N</label>
                        <input class="form-input tabular-nums" id="current_year_used" type="number" min="0" name="current_year_used" value="0" required>
                    </div>
                    <div>
                        <label class="form-label" for="carryover_n1">Sisa N-1</label>
                        <input class="form-input tabular-nums" id="carryover_n1" type="number" min="0" name="carryover_n1" value="0" required>
                    </div>
                    <div>
                        <label class="form-label" for="carryover_n2">Sisa N-2</label>
                        <input class="form-input tabular-nums" id="carryover_n2" type="number" min="0" name="carryover_n2" value="0" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label" for="balance_notes">Keterangan</label>
                        <textarea class="form-textarea" id="balance_notes" name="notes"></textarea>
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button class="btn-primary" type="submit">Simpan saldo</button>
                    </div>
                </form>
            @endif
        </section>

        <section class="card">
            <h2 class="section-heading">Atasan langsung organisasi</h2>
            <p class="section-description">Berlaku untuk {{ $organizationProfile->name }}. Pejabat berwenang tetap dikelola melalui menu khusus.</p>

            <form class="mt-5 grid gap-4 sm:grid-cols-2" method="POST" action="{{ route('settings.supervisors.store') }}">
                @csrf
                <div>
                    <label class="form-label" for="full_name">Nama</label>
                    <input class="form-input" id="full_name" name="full_name" value="{{ old('full_name', $supervisor?->full_name) }}" required>
                </div>
                <div>
                    <label class="form-label" for="nip">NIP</label>
                    <input class="form-input" id="nip" name="nip" value="{{ old('nip', $supervisor?->nip) }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="position_title">Jabatan</label>
                    <input class="form-input" id="position_title" name="position_title" value="{{ old('position_title', $supervisor?->position_title) }}" required>
                </div>
                <div class="sm:col-span-2 flex justify-end">
                    <button class="btn-primary" type="submit">Simpan atasan</button>
                </div>
            </form>
        </section>
    </div>

    <section class="card mt-6">
        <h2 class="section-heading">Data tersimpan</h2>
        <div class="mt-5 grid gap-6 lg:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Saldo cuti</p>
                @if ($balances->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Belum ada saldo yang disesuaikan.</p>
                @else
                    <div class="mt-3 divide-y divide-slate-100">
                        @foreach ($balances as $balance)
                            <div class="flex items-center justify-between gap-4 py-3 text-sm">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $balance->employee->full_name }}</p>
                                    <p class="text-xs text-slate-500">N: {{ $balance->current_year_entitlement - $balance->current_year_used }} · N-1: {{ $balance->carryover_n1 }} · N-2: {{ $balance->carryover_n2 }}</p>
                                </div>
                                <span class="text-xs text-slate-400">{{ $balance->leave_year }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Atasan langsung</p>
                @if (! $supervisor)
                    <p class="mt-3 text-sm text-slate-500">Belum ada atasan langsung aktif.</p>
                @else
                    <div class="mt-3 text-sm">
                        <p class="font-medium text-slate-800">{{ $supervisor->full_name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $organizationProfile->name }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
