@extends('layouts.app')

@section('title', 'Akun & Unit')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Akses aplikasi</p>
            <h1 class="page-title mt-2">{{ $isSuperAdmin ? 'Akun & Unit Kerja' : 'Akun Pengguna' }}</h1>
            <p class="page-description">
                @if ($isSuperAdmin)
                    Kelola unit kerja dan akun secara terpisah. Unit dapat ditambahkan terlebih dahulu, lalu Admin Unit dibuat saat sudah diperlukan.
                @else
                    Buat akun pengguna untuk {{ $currentDepartment?->name ?? 'unit kerja Anda' }}. Akun baru otomatis terikat ke unit yang sama.
                @endif
            </p>
        </div>
        @if ($currentDepartment)
            <span class="status-generated shrink-0">{{ $currentDepartment->name }}</span>
        @endif
    </div>

    @if ($isSuperAdmin)
        @php
            $departmentFormContext = old('form_context');
            $departmentFormHasErrors = in_array($departmentFormContext, ['department', 'department-edit'], true);
            $defaultAccountTab = in_array(session('accountTab'), ['super-admin', 'units', 'accounts'], true)
                ? session('accountTab')
                : 'super-admin';
            if ($departmentFormHasErrors) {
                $defaultAccountTab = 'units';
            }
        @endphp

        <nav class="mt-7" data-account-tabs data-account-tab-default="{{ $defaultAccountTab }}" role="tablist" aria-label="Administrasi akun dan unit">
            <div class="flex flex-wrap gap-1 rounded-2xl bg-slate-100 p-1">
                <button class="account-tab account-tab-active" id="account-tab-super-admin" type="button" role="tab" aria-selected="true" aria-controls="account-panel-super-admin" data-account-tab="super-admin">Kelola admin</button>
                <button class="account-tab" id="account-tab-units" type="button" role="tab" aria-selected="false" aria-controls="account-panel-units" data-account-tab="units" tabindex="-1">Administrasi Unit</button>
                <button class="account-tab" id="account-tab-accounts" type="button" role="tab" aria-selected="false" aria-controls="account-panel-accounts" data-account-tab="accounts" tabindex="-1">Seluruh Akun</button>
            </div>
        </nav>

        <section class="card mt-4" data-account-tab-panel="super-admin" id="account-panel-super-admin" role="tabpanel" aria-labelledby="account-tab-super-admin" @if ($defaultAccountTab !== 'super-admin') hidden @endif>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700">Super Admin</p>
                <h2 class="section-heading mt-2">Buat Admin Unit</h2>
                <p class="section-description">Pilih unit aktif untuk akun ini. Jika unit belum ada, gunakan tab Administrasi Unit untuk menambahkannya tanpa akun.</p>
            </div>

            <form class="mt-6 grid gap-5" method="POST" action="{{ route('users.unit-admin.store') }}" data-unit-admin-form>
                @csrf

                @php
                    $hasNewDepartmentInput = collect([
                        old('department_code'),
                        old('department_simpeg_code'),
                        old('department_type'),
                        old('department_parent_id'),
                        old('department_name'),
                        old('department_phone'),
                        old('department_address'),
                    ])->contains(fn ($value) => $value !== null && $value !== '');
                    $departmentSource = old('department_source');
                    $useExistingDepartment = $departmentSource === 'existing'
                        || ($departmentSource === null && ! $hasNewDepartmentInput && $departments->isNotEmpty());
                @endphp

                <fieldset>
                    <legend class="form-label">Unit untuk Admin Unit ini</legend>
                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                        <label class="unit-source-option card-inner flex min-h-11 cursor-pointer items-start gap-3" data-unit-source-option>
                            <input class="mt-0.5 size-4 border-slate-300 text-sky-600 focus:ring-sky-500" name="department_source" type="radio" value="existing" @checked($useExistingDepartment)>
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Gunakan unit yang sudah ada</span>
                                <span class="mt-0.5 block text-sm leading-5 text-slate-500">Pilih ketika data pegawainya sudah tercatat di SIMPEG.</span>
                            </span>
                        </label>
                        <label class="unit-source-option card-inner flex min-h-11 cursor-pointer items-start gap-3" data-unit-source-option>
                            <input class="mt-0.5 size-4 border-slate-300 text-sky-600 focus:ring-sky-500" name="department_source" type="radio" value="new" @checked(! $useExistingDepartment)>
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Buat unit sekaligus Admin</span>
                                <span class="mt-0.5 block text-sm leading-5 text-slate-500">Gunakan hanya jika akun Admin Unit sudah siap dibuat.</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div data-existing-unit-section @if (! $useExistingDepartment) hidden @endif>
                    <label class="form-label" for="department_id">Pilih unit kerja</label>
                    <select class="form-input" id="department_id" name="department_id" data-existing-unit-control @if ($useExistingDepartment) required @else disabled @endif>
                        <option value="">Pilih unit kerja</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div data-new-unit-section @if ($useExistingDepartment) hidden @endif>
                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <label class="form-label" for="department_type">Jenis unit</label>
                            <select class="form-input" id="department_type" name="department_type" data-department-type-control data-new-unit-control @if (! $useExistingDepartment) required @else disabled @endif>
                                <option value="">Pilih jenis unit</option>
                                <option value="kecamatan" @selected(old('department_type') === 'kecamatan')>Kecamatan</option>
                                <option value="kelurahan" @selected(old('department_type') === 'kelurahan')>Kelurahan</option>
                            </select>
                            <p class="form-help">Awalan kode akan otomatis menjadi KEC- atau KEL-.</p>
                            @error('department_type') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="department_code">Kode unit aplikasi</label>
                            <input class="form-input" id="department_code" name="department_code" value="{{ old('department_code') }}" maxlength="32" placeholder="Contoh: PS" data-department-code-control data-new-unit-control @if ($useExistingDepartment) disabled @endif>
                            <p class="form-help">Awalan KEC- atau KEL- ditambahkan otomatis.</p>
                            @error('department_code') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 lg:grid-cols-2">
                        <div>
                            <label class="form-label" for="department_simpeg_code">Kode SIMPEG</label>
                            <input class="form-input" id="department_simpeg_code" name="department_simpeg_code" value="{{ old('department_simpeg_code') }}" maxlength="32" placeholder="Contoh: 12.26.09.50.03.06.00" data-new-unit-control @if ($useExistingDepartment) disabled @endif>
                            <p class="form-help">Isi kode resmi dari SIMPEG jika unit sudah terdaftar.</p>
                            @error('department_simpeg_code') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="department_parent_id">Induk unit</label>
                            <select class="form-input" id="department_parent_id" name="department_parent_id" data-department-parent-control data-new-unit-control @if ($useExistingDepartment) disabled @endif>
                                <option value="">Tidak ada / pilih nanti</option>
                                @foreach ($parentDepartments as $parentDepartment)
                                    <option value="{{ $parentDepartment->id }}" @selected((string) old('department_parent_id') === (string) $parentDepartment->id)>
                                        {{ $parentDepartment->name }}{{ $parentDepartment->code ? ' ('.$parentDepartment->code.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="form-help">Wajib dipilih bila jenis unit adalah Kelurahan.</p>
                            @error('department_parent_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 lg:grid-cols-2">
                        <div>
                            <label class="form-label" for="department_name">Nama unit</label>
                            <input class="form-input" id="department_name" name="department_name" value="{{ old('department_name') }}" maxlength="255" placeholder="Contoh: Sekretariat Kecamatan Pontianak Selatan" data-new-unit-control @if (! $useExistingDepartment) required @else disabled @endif>
                            @error('department_name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="department_phone">Telepon unit</label>
                            <input class="form-input" id="department_phone" name="department_phone" value="{{ old('department_phone') }}" maxlength="32" placeholder="Opsional" data-new-unit-control @if ($useExistingDepartment) disabled @endif>
                            @error('department_phone') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="form-label" for="department_address">Alamat unit</label>
                        <textarea class="form-input min-h-24" id="department_address" name="department_address" maxlength="2000" placeholder="Opsional" data-new-unit-control @if ($useExistingDepartment) disabled @endif>{{ old('department_address') }}</textarea>
                        @error('department_address') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <p class="text-sm font-semibold text-slate-800">Akun Admin Unit</p>
                    <p class="mt-1 text-sm text-slate-500">Admin Unit dapat membuat akun Pengguna, tetapi tidak dapat membuat admin atau memilih unit lain.</p>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label class="form-label" for="admin_name">Nama</label>
                        <input class="form-input" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" maxlength="255" required>
                        @error('admin_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="admin_email">Email</label>
                        <input class="form-input" id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" maxlength="255" required>
                        @error('admin_email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="admin_password">Kata sandi</label>
                        <input class="form-input" id="admin_password" name="admin_password" type="password" minlength="8" required>
                        @error('admin_password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="admin_password_confirmation">Konfirmasi kata sandi</label>
                        <input class="form-input" id="admin_password_confirmation" name="admin_password_confirmation" type="password" minlength="8" required>
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-6">
                    <button class="btn-primary" type="submit">Buat Admin Unit</button>
                </div>
            </form>
        </section>
    @else
        <section class="card mt-7">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700">Admin Unit</p>
                <h2 class="section-heading mt-2">Buat akun Pengguna</h2>
                <p class="section-description">Unit kerja tidak dapat dipilih atau diubah dari formulir ini.</p>
            </div>

            <form class="mt-6 grid gap-5" method="POST" action="{{ route('users.pengguna.store') }}">
                @csrf

                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label class="form-label" for="name">Nama</label>
                        <input class="form-input" id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="email">Email</label>
                        <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" required>
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="password">Kata sandi</label>
                        <input class="form-input" id="password" name="password" type="password" minlength="8" required>
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="password_confirmation">Konfirmasi kata sandi</label>
                        <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-6">
                    <button class="btn-primary" type="submit">Buat akun Pengguna</button>
                </div>
            </form>
        </section>
    @endif

    @if ($isSuperAdmin)
        <section class="card mt-4" data-account-tab-panel="units" id="account-panel-units" role="tabpanel" aria-labelledby="account-tab-units" @if ($defaultAccountTab !== 'units') hidden @endif>
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700">Administrasi unit</p>
                    <h2 class="section-heading mt-2">Unit kerja</h2>
                    <p class="section-description">Tambah atau ubah data unit tanpa harus membuat akun Admin Unit. Kode SIMPEG dan hubungan induk ditampilkan untuk mencegah unit tertukar.</p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <span class="status-draft tabular-nums">{{ number_format($allDepartments->count(), 0, ',', '.') }} unit</span>
                    <button class="btn-primary" type="button" data-department-create-open>Tambah unit</button>
                </div>
            </div>

            @if ($allDepartments->isEmpty())
                <div class="card-inner mt-5 text-center">
                    <p class="text-sm font-medium text-slate-700">Belum ada unit kerja.</p>
                </div>
            @else
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                            <tr>
                                <th class="pb-3 pr-5 font-semibold">Jenis</th>
                                <th class="pb-3 pr-5 font-semibold">Nama unit</th>
                                <th class="pb-3 pr-5 font-semibold">Kode aplikasi</th>
                                <th class="pb-3 pr-5 font-semibold">Kode SIMPEG</th>
                                <th class="pb-3 pr-5 font-semibold">Induk unit</th>
                                <th class="pb-3 pr-5 font-semibold">Status</th>
                                <th class="pb-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($allDepartments as $department)
                                <tr>
                                    <td class="py-4 pr-5 text-slate-600">{{ $department->typeLabel() }}</td>
                                    <td class="py-4 pr-5">
                                        <div class="font-semibold text-slate-800">{{ $department->name }}</div>
                                        <span class="mt-1 {{ $department->admin_unit_count > 0 ? 'status-generated' : 'status-draft' }}">
                                            {{ $department->admin_unit_count > 0 ? 'Admin Unit tersedia' : 'Belum ada Admin Unit' }}
                                        </span>
                                    </td>
                                    <td class="py-4 pr-5 tabular-nums text-slate-600">{{ $department->code ?: '-' }}</td>
                                    <td class="py-4 pr-5 tabular-nums text-slate-600">{{ $department->simpeg_code ?: '-' }}</td>
                                    <td class="py-4 pr-5 text-slate-600">{{ $department->parent?->name ?: '-' }}</td>
                                    <td class="py-4 pr-5">
                                        <span class="{{ $department->is_active ? 'status-generated' : 'status-void' }}">
                                            {{ $department->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <button
                                                class="btn-secondary"
                                                type="button"
                                                data-department-edit-open
                                                data-department-id="{{ $department->id }}"
                                                data-department-action="{{ route('users.departments.update', $department) }}"
                                                data-department-name="{{ $department->name }}"
                                                data-department-type="{{ $department->department_type }}"
                                                data-department-code="{{ $department->code }}"
                                                data-department-simpeg-code="{{ $department->simpeg_code }}"
                                                data-department-parent-id="{{ $department->parent_department_id }}"
                                                data-department-address="{{ $department->address }}"
                                                data-department-phone="{{ $department->phone }}"
                                            >Edit</button>
                                        @if ($department->is_active)
                                            <form method="POST" action="{{ route('users.departments.deactivate', $department) }}" data-department-confirm-form data-department-name="{{ $department->name }}" data-department-status="deactivate">
                                                @csrf
                                                <button class="btn-warning" type="submit">Nonaktifkan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('users.departments.activate', $department) }}" data-department-confirm-form data-department-name="{{ $department->name }}" data-department-status="activate">
                                                @csrf
                                                <button class="btn-success" type="submit">Aktifkan kembali</button>
                                            </form>
                                        @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div
            class="fixed inset-0 z-[100] hidden items-start justify-center overflow-y-auto overscroll-contain bg-slate-950/60 p-4 opacity-0 transition-opacity duration-150 sm:items-center"
            data-department-editor-overlay
            data-department-editor-open="{{ $departmentFormHasErrors ? 'true' : 'false' }}"
            data-department-editor-context="{{ $departmentFormContext }}"
            data-department-editor-old-id="{{ old('department_id') }}"
            data-department-editor-store-action="{{ route('users.departments.store') }}"
            data-department-editor-update-template="{{ route('users.departments.update', ['department' => '__DEPARTMENT__']) }}"
            aria-hidden="true"
            style="backdrop-filter: blur(2px);"
        >
            <div class="my-4 max-h-[calc(100dvh-2rem)] w-full max-w-3xl translate-y-2 overflow-y-auto overscroll-contain rounded-2xl bg-white p-6 opacity-0 shadow-[0_24px_60px_rgba(15,23,42,0.24),0_4px_16px_rgba(15,23,42,0.12)] transition-[opacity,transform] duration-150 sm:my-0 sm:max-h-[calc(100dvh-3rem)]" data-department-editor-dialog role="dialog" aria-modal="true" aria-labelledby="department-editor-title" aria-describedby="department-editor-description">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700" data-department-editor-kicker>Administrasi unit</p>
                        <h2 class="section-heading mt-2" id="department-editor-title" data-department-editor-title>Tambah unit kerja</h2>
                        <p class="section-description mt-1" id="department-editor-description" data-department-editor-description>Unit dapat disimpan tanpa membuat akun Admin Unit.</p>
                    </div>
                    <button class="btn-secondary shrink-0 px-3" type="button" aria-label="Tutup" data-department-editor-close>×</button>
                </div>

                <form class="mt-6 grid gap-5" method="POST" action="{{ route('users.departments.store') }}" data-department-editor-form>
                    @csrf
                    <input type="hidden" name="form_context" value="department" data-department-editor-context-input>
                    <input type="hidden" value="" data-department-editor-id-input>
                    <input type="hidden" value="" data-department-editor-method>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <label class="form-label" for="department_editor_type">Jenis unit</label>
                            <select class="form-input" id="department_editor_type" name="department_type" data-department-editor-type required>
                                <option value="">Pilih jenis unit</option>
                                <option value="kecamatan" @selected(old('department_type') === 'kecamatan')>Kecamatan</option>
                                <option value="kelurahan" @selected(old('department_type') === 'kelurahan')>Kelurahan</option>
                            </select>
                            <p class="form-help">Jenis unit disimpan permanen dan menentukan hubungan induk.</p>
                            @error('department_type') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="department_editor_parent">Induk unit</label>
                            <select class="form-input" id="department_editor_parent" name="department_parent_id" data-department-editor-parent>
                                <option value="">Tidak ada induk</option>
                                @foreach ($parentDepartments as $parentDepartment)
                                    <option value="{{ $parentDepartment->id }}" @selected((string) old('department_parent_id') === (string) $parentDepartment->id)>
                                        {{ $parentDepartment->name }}{{ $parentDepartment->code ? ' ('.$parentDepartment->code.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="form-help">Kelurahan wajib berada di bawah Kecamatan yang aktif.</p>
                            @error('department_parent_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <label class="form-label" for="department_editor_code">Kode unit aplikasi</label>
                            <input class="form-input" id="department_editor_code" name="department_code" value="{{ old('department_code') }}" maxlength="32" placeholder="Contoh: PONSEL atau KEL-ACY" data-department-editor-code>
                            <p class="form-help">Awalan KEC- atau KEL- akan disesuaikan otomatis.</p>
                            @error('department_code') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="department_editor_simpeg_code">Kode SIMPEG</label>
                            <input class="form-input" id="department_editor_simpeg_code" name="department_simpeg_code" value="{{ old('department_simpeg_code') }}" maxlength="32" placeholder="Contoh: 12.26.09.50.03.06.00">
                            <p class="form-help">Kode resmi unit dari SIMPEG.</p>
                            @error('department_simpeg_code') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="department_editor_name">Nama unit</label>
                        <input class="form-input" id="department_editor_name" name="department_name" value="{{ old('department_name') }}" maxlength="255" placeholder="Contoh: Kelurahan Akcaya" required>
                        @error('department_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <label class="form-label" for="department_editor_phone">Telepon unit</label>
                            <input class="form-input" id="department_editor_phone" name="department_phone" value="{{ old('department_phone') }}" maxlength="32" placeholder="Opsional">
                            @error('department_phone') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="department_editor_address">Alamat unit</label>
                            <textarea class="form-input min-h-11" id="department_editor_address" name="department_address" maxlength="2000" placeholder="Opsional">{{ old('department_address') }}</textarea>
                            @error('department_address') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                        <button class="btn-secondary" type="button" data-department-editor-cancel>Batal</button>
                        <button class="btn-primary" type="submit" data-department-editor-submit>Simpan unit</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/60 p-4 opacity-0 transition-opacity duration-150" data-department-confirm-overlay aria-hidden="true" style="backdrop-filter: blur(2px);">
            <div class="w-full max-w-md translate-y-2 rounded-2xl bg-white p-6 opacity-0 shadow-[0_24px_60px_rgba(15,23,42,0.24),0_4px_16px_rgba(15,23,42,0.12)] transition-[opacity,transform] duration-150" data-department-confirm-dialog role="dialog" aria-modal="true" aria-labelledby="department-confirm-title" aria-describedby="department-confirm-message">
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700" data-department-confirm-icon>
                        <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-slate-900" id="department-confirm-title" data-department-confirm-title>Konfirmasi perubahan unit</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600" id="department-confirm-message" data-department-confirm-message></p>
                    </div>
                </div>

                <div class="card-inner mt-5 bg-amber-50 text-sm leading-6 text-amber-900" data-department-confirm-note>
                    Data pegawai, akun, dan riwayat cuti tetap tersimpan.
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button class="btn-secondary" type="button" data-department-confirm-cancel>Batal</button>
                    <button class="btn-warning" type="button" data-department-confirm-submit>Nonaktifkan</button>
                </div>
            </div>
        </div>
    @endif

    <section class="card mt-7" @if ($isSuperAdmin) data-account-tab-panel="accounts" id="account-panel-accounts" role="tabpanel" aria-labelledby="account-tab-accounts" @if ($defaultAccountTab !== 'accounts') hidden @endif @endif>
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="section-heading">{{ $isSuperAdmin ? 'Seluruh akun' : 'Pengguna di unit Anda' }}</h2>
                <p class="section-description">{{ $isSuperAdmin ? 'Super Admin dapat melihat hubungan akun dan unit kerja secara menyeluruh.' : 'Hanya akun Pengguna pada unit kerja yang sama yang ditampilkan.' }}</p>
            </div>
            <span class="status-draft tabular-nums">{{ number_format($users->total(), 0, ',', '.') }} akun</span>
        </div>

        @if ($users->isEmpty())
            <div class="card-inner mt-5 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada akun yang dapat ditampilkan.</p>
            </div>
        @else
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                        <tr>
                            <th class="pb-3 pr-5 font-semibold">Nama</th>
                            <th class="pb-3 pr-5 font-semibold">Email</th>
                            @if ($isSuperAdmin)
                                <th class="pb-3 pr-5 font-semibold">Unit kerja</th>
                            @endif
                            <th class="pb-3 font-semibold">Peran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="py-4 pr-5 font-semibold text-slate-800">{{ $user->name }}</td>
                                <td class="py-4 pr-5 text-slate-600">{{ $user->email }}</td>
                                @if ($isSuperAdmin)
                                    <td class="py-4 pr-5 text-slate-600">{{ $user->department?->name ?? 'Global' }}</td>
                                @endif
                                <td class="py-4"><span class="status-generated">{{ $user->roleLabel() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="mt-6">{{ $users->links() }}</div>
            @endif
        @endif
    </section>
@endsection
