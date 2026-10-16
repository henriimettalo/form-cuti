@extends('layouts.app')
@section('title', 'Tambah Jadwal Kelurahan')
@section('content')
    <div class="flex items-end justify-between gap-4"><div><h1 class="page-title">{{ isset($schedule) ? 'Edit' : 'Buat' }} kelompok piket kelurahan {{ $selectedDepartment->name }}</h1><p class="page-description">Atur kelompok dan pegawai untuk {{ $selectedDepartment->name }}. Tanggal ditentukan nanti saat penjadwalan.</p></div><a class="btn-secondary" href="{{ route('counter-duty.kelurahan-assignments', ['month' => $month->format('Y-m')]) }}">Kembali ke dashboard</a></div>
    <section class="card mt-5"><form class="grid gap-4 sm:grid-cols-2" method="POST" action="{{ isset($schedule) ? route('counter-duty.kelurahan-schedules.update', $schedule) : route('counter-duty.kelurahan-schedules.store') }}" autocomplete="off" data-confirm-title="{{ isset($schedule) ? 'Simpan perubahan kelompok?' : 'Simpan kelompok kelurahan?' }}" data-confirm-message="Periksa kembali penanggung jawab dan anggota yang dipilih." data-confirm-button="Simpan">@csrf @if (isset($schedule)) @method('PUT') @endif
        <input id="department_id" name="department_id" type="hidden" value="{{ old('department_id', $selectedDepartmentId) }}">
        <div><label class="form-label" for="group_number_display">Nomor kelompok kelurahan</label><input class="form-input" id="group_number_display" value="{{ old('group_number', $selectedGroupNumber ?? 1) }}" readonly><input id="group_number" name="group_number" type="hidden" value="{{ old('group_number', $selectedGroupNumber) }}"><p class="form-help">Kelompok dipilih dari dashboard.</p></div>
        <div><label class="form-label" for="coordinator_employee_id">Penanggung jawab</label><select class="form-select" id="coordinator_employee_id" name="coordinator_employee_id" required><option value="">Pilih pegawai</option>@foreach ($employees as $employee)<option value="{{ $employee->id }}" @selected(isset($schedule) && $schedule->coordinator === $employee->full_name) data-department-id="{{ $employee->department_id }}">{{ $employee->full_name }}</option>@endforeach</select></div>
        <fieldset class="sm:col-span-2"><legend class="form-label">Anggota <span class="text-rose-600">*</span></legend><div id="member_employee_ids" class="grid max-h-48 gap-2 overflow-y-auto rounded-xl border border-slate-200 p-3 sm:grid-cols-2">@foreach ($employees as $employee)<label class="member-option flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50" data-department-id="{{ $employee->department_id }}"><input class="size-4" type="checkbox" name="member_employee_ids[]" value="{{ $employee->id }}" @checked(isset($schedule) && in_array($employee->full_name, $schedule->members ?? [], true)) data-department-id="{{ $employee->department_id }}">{{ $employee->full_name }}</label>@endforeach</div><p id="member-help" class="form-help">Belum ada anggota yang dipilih. Centang pegawai yang ditugaskan.</p></fieldset>
        <div class="sm:col-span-2"><button class="btn-primary" type="submit">{{ isset($schedule) ? 'Simpan perubahan' : 'Simpan kelompok kelurahan' }}</button></div>
    </form></section>
    <script>
        (() => {
            const department = document.querySelector('#department_id');
            const coordinator = document.querySelector('#coordinator_employee_id');
            const memberOptions = document.querySelectorAll('.member-option');
            const memberHelp = document.querySelector('#member-help');
            const sync = () => {
                memberOptions.forEach((option) => {
                    const visible = option.dataset.departmentId === department.value;
                    const checkbox = option.querySelector('input');
                    const isCoordinator = checkbox.value === coordinator.value;
                    option.hidden = !visible;
                    option.classList.toggle('opacity-50', isCoordinator);
                    option.classList.toggle('bg-slate-100', isCoordinator);
                    checkbox.disabled = !visible || isCoordinator;
                });

                const hasSelectedMember = [...memberOptions].some((option) => {
                    const checkbox = option.querySelector('input');
                    return !option.hidden && checkbox.checked && !checkbox.disabled;
                });
                memberHelp.hidden = hasSelectedMember;
            };
            @unless (isset($schedule)) coordinator.value = ''; @endunless
            coordinator.addEventListener('change', sync);
            memberOptions.forEach((option) => option.querySelector('input').addEventListener('change', sync));
            sync();
        })();
    </script>
@endsection
