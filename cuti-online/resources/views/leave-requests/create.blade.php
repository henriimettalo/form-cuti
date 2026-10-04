@extends('layouts.app')

@section('title', 'Buat Formulir Cuti')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Formulir baru</p>
            <h1 class="page-title mt-2">Buat formulir cuti</h1>
            <p class="page-description">Data disimpan sebagai arsip, kemudian otomatis dibuatkan dokumen Word.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('leave-requests.index') }}">Kembali ke formulir cuti</a>
    </div>

    @if ($employees->isEmpty())
        <section class="card mt-7">
            <div class="card-inner text-center">
                <p class="text-sm font-medium text-slate-700">Data pegawai belum tersedia.</p>
                <p class="mt-1 text-sm text-slate-500">Tambahkan pegawai terlebih dahulu agar formulir dapat dibuat.</p>
                <a class="btn-primary mt-4" href="{{ route('employees.create') }}">Tambah pegawai</a>
            </div>
        </section>
    @else
        <form class="card mt-7" method="POST" action="{{ route('leave-requests.store') }}" data-leave-form>
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <h2 class="section-heading">Data pengajuan</h2>
                    <p class="section-description">Pegawai dipilih dari master data agar identitas selalu konsisten.</p>
                </div>
                <div>
                    @php($selectedEmployee = $employees->firstWhere('id', (int) old('employee_id')))
                    <label class="form-label" for="employee_search">Pegawai</label>
                    <div class="relative" data-employee-combobox data-search-combobox>
                        <input id="employee_id" type="hidden" name="employee_id" value="{{ old('employee_id') }}" data-combobox-value>
                        <div class="relative">
                            <svg aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                            <input class="form-input pl-10" id="employee_search" type="search" data-employee-search data-combobox-search autocomplete="off" placeholder="Cari nama atau NIP, mis. suri*008" value="{{ $selectedEmployee ? $selectedEmployee->full_name.' · '.$selectedEmployee->nip : '' }}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-required="true" aria-controls="employee-search-results" aria-describedby="employee-search-help employee-search-result">
                        </div>
                        <div class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl bg-white p-1 shadow-[0_1px_2px_rgba(15,23,42,0.06),0_12px_28px_rgba(15,23,42,0.14)]" data-employee-search-panel data-combobox-panel hidden>
                            <ul class="max-h-72 space-y-1 overflow-y-auto" id="employee-search-results" role="listbox" aria-label="Hasil pencarian pegawai">
                                @foreach ($employees as $employee)
                                    @php($employeeLabel = $employee->full_name.' · '.$employee->nip)
                                    <li>
                                        <button class="flex min-h-11 w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-sm text-slate-700 outline-none transition-colors duration-150 hover:bg-sky-50 hover:text-sky-950 focus-visible:bg-sky-50 focus-visible:shadow-[inset_0_0_0_2px_rgba(14,165,233,0.6)]" id="employee-search-option-{{ $employee->id }}" type="button" role="option" aria-selected="{{ (string) old('employee_id') === (string) $employee->id ? 'true' : 'false' }}" data-employee-option data-combobox-option data-employee-id="{{ $employee->id }}" data-combobox-id="{{ $employee->id }}" data-employee-label="{{ $employeeLabel }}" data-combobox-label="{{ $employeeLabel }}" data-department-id="{{ $employee->department_id }}">
                                            <span class="flex min-w-0 items-center gap-2">
                                                <span class="shrink-0 text-xs font-semibold tabular-nums text-slate-400" data-employee-option-index data-combobox-option-index>{{ $loop->iteration }}.</span>
                                                <span class="truncate font-medium">{{ $employee->full_name }}</span>
                                            </span>
                                            <span class="shrink-0 text-xs tabular-nums text-slate-500">{{ $employee->nip }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="border-t border-slate-100 px-3 py-2 text-xs font-medium tabular-nums text-slate-500" id="employee-search-result" data-employee-search-result data-combobox-result aria-live="polite">{{ $employees->count() }} pegawai tersedia.</p>
                        </div>
                    </div>
                    <p class="form-help text-pretty" id="employee-search-help">Ketik sebagian nama atau NIP. Gunakan <span class="font-semibold text-slate-600">*</span> untuk wildcard.</p>
                    @error('employee_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="leave_type_id">Jenis cuti</label>
                    <select class="form-select" id="leave_type_id" name="leave_type_id" required>
                        <option value="">Pilih jenis cuti</option>
                        @foreach ($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->id }}" data-code="{{ $leaveType->code }}" @selected((string) old('leave_type_id', $defaultLeaveTypeId) === (string) $leaveType->id)>
                                {{ $leaveType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('leave_type_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="form_date">Tanggal formulir</label>
                    <input class="form-input" id="form_date" type="date" name="form_date" value="{{ old('form_date', now()->toDateString()) }}" required>
                </div>
                <div>
                    <label class="form-label" for="duration_unit">Satuan lama cuti</label>
                    <select class="form-select" id="duration_unit" name="duration_unit" required>
                        <option value="day" @selected(old('duration_unit', 'day') === 'day')>Hari</option>
                        <option value="month" @selected(old('duration_unit') === 'month')>Bulan</option>
                        <option value="year" @selected(old('duration_unit') === 'year')>Tahun</option>
                    </select>
                    @error('duration_unit') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="reason">Alasan cuti</label>
                    <textarea class="form-textarea" id="reason" name="reason" maxlength="2000" required placeholder="Contoh: Cuti Tahunan">{{ old('reason') }}</textarea>
                    @error('reason') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 grid gap-5 border-t border-slate-100 pt-7 md:grid-cols-2">
                <div class="md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Langkah 2 dari 4</p>
                    <h2 class="section-heading mt-2 text-lg" id="date-range-heading">Pilih hari cuti <span class="text-rose-500" aria-hidden="true">*</span></h2>
                    <p class="section-description">Pilih hari satu per satu secara berurutan. Akhir pekan dilewati otomatis untuk Cuti Tahunan.</p>
                </div>
                <div class="date-range-picker md:col-span-2" data-date-range-picker role="group" aria-labelledby="date-range-heading" aria-required="true">
                    <div class="date-range-picker-header">
                        <div class="date-range-picker-navigation">
                            <button class="date-range-picker-nav" type="button" data-date-range-prev aria-label="Bulan sebelumnya">
                                <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" /></svg>
                            </button>
                            <p class="date-range-picker-month" data-date-range-month aria-live="polite">Pilih bulan</p>
                            <button class="date-range-picker-nav" type="button" data-date-range-next aria-label="Bulan berikutnya">
                                <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                            </button>
                        </div>
                    </div>
                    <div class="date-range-picker-weekdays" aria-hidden="true">
                        <span>Min</span>
                        <span>Sen</span>
                        <span>Sel</span>
                        <span>Rab</span>
                        <span>Kam</span>
                        <span>Jum</span>
                        <span>Sab</span>
                    </div>
                    <div class="date-range-picker-grid" data-date-range-grid role="grid" aria-label="Kalender tanggal cuti"></div>
                    <p class="date-range-picker-summary" data-date-range-summary role="status" aria-live="polite">Pilih hari pertama cuti.</p>
                    <p class="form-help" data-date-range-selection-help>Untuk Cuti Tahunan, Sabtu dan Minggu tidak dapat dipilih. Klik ulang hari pertama atau terakhir untuk membatalkannya.</p>
                </div>
                <input id="start_date" type="hidden" name="start_date" value="{{ old('start_date') }}" data-date-range-start>
                <input id="end_date" type="hidden" name="end_date" value="{{ old('end_date') }}" data-date-range-end>
                @error('start_date') <p class="form-error md:col-span-2">{{ $message }}</p> @enderror
                @error('end_date') <p class="form-error md:col-span-2">{{ $message }}</p> @enderror
                <div class="md:col-span-2">
                    <div class="card-inner">
                        <p class="text-sm font-medium text-slate-700" data-duration-output>Pilih tanggal mulai dan selesai untuk melihat perkiraan durasi.</p>
                        <p class="mt-1 text-xs text-slate-500">Perhitungan akhir dilakukan server menggunakan data hari libur.</p>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="address_during_leave">Alamat selama menjalankan cuti</label>
                    <textarea class="form-textarea" id="address_during_leave" name="address_during_leave" maxlength="2000">{{ old('address_during_leave') }}</textarea>
                </div>
                <div>
                    <label class="form-label" for="phone_during_leave">Nomor telepon selama cuti</label>
                    <input class="form-input" id="phone_during_leave" type="tel" inputmode="numeric" name="phone_during_leave" value="{{ old('phone_during_leave') }}" maxlength="15" required>
                </div>
            </div>

            <div class="mt-8 grid gap-5 border-t border-slate-100 pt-7 md:grid-cols-2">
                <div class="md:col-span-2">
                    <h2 class="section-heading">Blok penandatangan</h2>
                    <p class="section-description">Data ini dicetak pada dokumen untuk ditandatangani secara manual; tidak ada proses approval elektronik.</p>
                </div>
                <div class="md:col-span-2 card-inner">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-800" for="is_camat">
                            <input class="size-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" type="checkbox" name="is_camat" id="is_camat" value="1" data-camat-toggle @checked(old('is_camat'))>
                            Pemohon adalah Camat
                        </label>
                        <p class="text-xs leading-5 text-slate-500" data-camat-hint>Atasan langsung otomatis diisi Sekda dan pejabat berwenang otomatis diisi Wali Kota.</p>
                    </div>
                    <label class="mt-4 flex items-center gap-2 text-sm font-medium text-slate-800" for="plh_toggle">
                        <input class="size-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" type="checkbox" id="plh_toggle" data-plh-toggle>
                        Pejabat berwenang diisi PLH
                    </label>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Pilih PLH dari daftar pegawai. Pejabat berwenang pada formulir akan memakai data PLH tersebut.</p>
                </div>
                <div class="card-inner grid gap-4">
                    @php($selectedSupervisor = $employees->firstWhere('id', (int) old('supervisor_employee_id')))
                    <p class="text-sm font-semibold text-slate-800" data-supervisor-heading>Atasan langsung</p>
                    <div>
                        <label class="form-label" for="supervisor_search">Nama</label>
                        <div class="relative" data-supervisor-combobox data-search-combobox>
                            <input id="supervisor_employee_id" type="hidden" name="supervisor_employee_id" value="{{ old('supervisor_employee_id') }}" data-supervisor-select data-combobox-value>
                            <div class="relative">
                                <svg aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                                <input class="form-input pl-10" id="supervisor_search" type="search" data-supervisor-search data-combobox-search autocomplete="off" placeholder="Cari nama atau NIP, mis. suri*008" value="{{ $selectedSupervisor ? $selectedSupervisor->full_name.' · '.$selectedSupervisor->nip : '' }}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-required="true" aria-controls="supervisor-search-results" aria-describedby="supervisor-search-help supervisor-search-result">
                            </div>
                            <div class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl bg-white p-1 shadow-[0_1px_2px_rgba(15,23,42,0.06),0_12px_28px_rgba(15,23,42,0.14)]" data-supervisor-search-panel data-combobox-panel hidden>
                                <ul class="max-h-72 space-y-1 overflow-y-auto" id="supervisor-search-results" role="listbox" aria-label="Hasil pencarian atasan langsung">
                                    @foreach ($employees as $supervisor)
                                        @php($supervisorPositionTitle = $supervisor->position?->name ?? $supervisor->position_title ?? '')
                                        @php($supervisorLabel = $supervisor->full_name.' · '.$supervisor->nip)
                                        <li>
                                            <button class="flex min-h-11 w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-sm text-slate-700 outline-none transition-colors duration-150 hover:bg-sky-50 hover:text-sky-950 focus-visible:bg-sky-50 focus-visible:shadow-[inset_0_0_0_2px_rgba(14,165,233,0.6)]" id="supervisor-search-option-{{ $supervisor->id }}" type="button" role="option" aria-selected="{{ (string) old('supervisor_employee_id') === (string) $supervisor->id ? 'true' : 'false' }}" data-supervisor-option data-combobox-option data-combobox-id="{{ $supervisor->id }}" data-combobox-label="{{ $supervisorLabel }}" data-full-name="{{ $supervisor->full_name }}" data-nip="{{ $supervisor->nip }}" data-position-title="{{ $supervisorPositionTitle }}">
                                                <span class="flex min-w-0 items-center gap-2">
                                                    <span class="shrink-0 text-xs font-semibold tabular-nums text-slate-400" data-supervisor-option-index data-combobox-option-index>{{ $loop->iteration }}.</span>
                                                    <span class="truncate font-medium">{{ $supervisor->full_name }}</span>
                                                </span>
                                                <span class="shrink-0 text-xs tabular-nums text-slate-500">{{ $supervisor->nip }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="border-t border-slate-100 px-3 py-2 text-xs font-medium tabular-nums text-slate-500" id="supervisor-search-result" data-supervisor-search-result data-combobox-result aria-live="polite">{{ $employees->count() }} pegawai tersedia.</p>
                            </div>
                        </div>
                        <p class="form-help text-pretty" id="supervisor-search-help">Ketik sebagian nama atau NIP. Gunakan <span class="font-semibold text-slate-600">*</span> untuk wildcard. Pegawai yang dipilih sebagai pemohon tidak dapat menjadi atasan langsung.</p>
                        @error('supervisor_employee_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <p class="text-xs leading-5 text-slate-500">Pilih dari data pegawai aktif. NIP dan jabatan terisi otomatis sesuai data yang dipilih.</p>
                    <div>
                        <label class="form-label" for="supervisor_nip">NIP</label>
                        <input class="form-input cursor-not-allowed bg-slate-50 text-slate-600" id="supervisor_nip" name="supervisor_nip" value="{{ $selectedSupervisor?->nip ?? '' }}" readonly aria-readonly="true">
                    </div>
                    <div>
                        <label class="form-label" for="supervisor_position_title">Jabatan</label>
                        <textarea class="form-textarea resize-none cursor-not-allowed bg-slate-50 text-slate-600" id="supervisor_position_title" name="supervisor_position_title" rows="3" readonly aria-readonly="true">{{ $selectedSupervisor?->position?->name ?? $selectedSupervisor?->position_title ?? '' }}</textarea>
                    </div>
                </div>
                <div class="card-inner grid gap-4">
                    <p class="text-sm font-semibold text-slate-800" data-official-heading>Pejabat berwenang</p>
                    <p class="text-xs leading-5 text-slate-500" data-official-hint>Data dikelola dari <a class="font-medium text-sky-700 underline underline-offset-2" href="{{ route('authorized-official.index') }}">menu Pejabat Berwenang</a> dan dikunci pada formulir.</p>
                    @php($selectedPlh = $employees->firstWhere('id', (int) old('plh_employee_id')))
                    <input id="plh_employee_id" type="hidden" name="plh_employee_id" value="{{ old('plh_employee_id') }}" data-plh-select data-combobox-value>
                    <div>
                        <label class="form-label" for="official_name">Nama</label>
                        <div class="relative" data-plh-combobox data-search-combobox>
                            <div class="relative">
                                <svg aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" /></svg>
                                <input class="form-input pl-10" id="official_name" name="official_name" data-plh-search data-combobox-search data-plh-input-value autocomplete="off" placeholder="Cari nama atau NIP PLH, mis. suri*008" value="{{ old('plh_employee_id') ? ($selectedPlh->full_name ?? '') : ($authorizedOfficial['full_name'] ?? '') }}" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="plh-search-results" aria-describedby="plh-search-help plh-search-result" data-custom-readonly="{{ old('plh_employee_id') ? '' : '1' }}">
                            </div>
                            <div class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl bg-white p-1 shadow-[0_1px_2px_rgba(15,23,42,0.06),0_12px_28px_rgba(15,23,42,0.14)]" data-plh-search-panel data-combobox-panel hidden>
                                <ul class="max-h-72 space-y-1 overflow-y-auto" id="plh-search-results" role="listbox" aria-label="Hasil pencarian PLH">
                                    @foreach ($employees as $plh)
                                        @php($plhPositionTitle = $plh->position?->name ?? $plh->position_title ?? '')
                                        @php($plhLabel = $plh->full_name.' · '.$plh->nip)
                                        <li>
                                            <button class="flex min-h-11 w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-sm text-slate-700 outline-none transition-colors duration-150 hover:bg-sky-50 hover:text-sky-950 focus-visible:bg-sky-50 focus-visible:shadow-[inset_0_0_0_2px_rgba(14,165,233,0.6)]" id="plh-search-option-{{ $plh->id }}" type="button" role="option" aria-selected="{{ (string) old('plh_employee_id') === (string) $plh->id ? 'true' : 'false' }}" data-plh-option data-combobox-option data-combobox-id="{{ $plh->id }}" data-combobox-label="{{ $plhLabel }}" data-full-name="{{ $plh->full_name }}" data-nip="{{ $plh->nip }}" data-position-title="{{ $plhPositionTitle }}">
                                                <span class="flex min-w-0 items-center gap-2">
                                                    <span class="shrink-0 text-xs font-semibold tabular-nums text-slate-400" data-plh-option-index data-combobox-option-index>{{ $loop->iteration }}.</span>
                                                    <span class="truncate font-medium">{{ $plh->full_name }}</span>
                                                </span>
                                                <span class="shrink-0 text-xs tabular-nums text-slate-500">{{ $plh->nip }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="border-t border-slate-100 px-3 py-2 text-xs font-medium tabular-nums text-slate-500" id="plh-search-result" data-plh-search-result data-combobox-result aria-live="polite">{{ $employees->count() }} pegawai tersedia.</p>
                            </div>
                        </div>
                        <p class="form-help text-pretty" id="plh-search-help">Ketik sebagian nama atau NIP. Gunakan <span class="font-semibold text-slate-600">*</span> untuk wildcard. Saat PLH dicentang, nama diisi dari daftar pegawai.</p>
                        @error('plh_employee_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="official_nip">NIP</label>
                        <input class="form-input cursor-not-allowed bg-slate-50 text-slate-600" id="official_nip" name="official_nip" value="{{ $authorizedOfficial['nip'] ?? '' }}" readonly aria-readonly="true">
                    </div>
                    <div>
                        <label class="form-label" for="official_position_title">Jabatan</label>
                        <textarea class="form-textarea resize-none cursor-not-allowed bg-slate-50 text-slate-600" id="official_position_title" name="official_position_title" rows="3" readonly aria-readonly="true">{{ $authorizedOfficial['position_title'] ?? '' }}</textarea>
                    </div>
                    @error('authorized_official') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                <a class="btn-secondary" href="{{ route('leave-requests.index') }}">Batal</a>
                <button class="btn-primary disabled:cursor-wait disabled:opacity-70" data-submit-leave-form type="submit">Buat dokumen Word</button>
            </div>
        </form>

        <script id="authorized-official" type="application/json">@json($authorizedOfficial)</script>
        <script id="sekda-official" type="application/json">@json($sekdaOfficial)</script>
        <script id="walikota-official" type="application/json">@json($walikotaOfficial)</script>
    @endif
@endsection
