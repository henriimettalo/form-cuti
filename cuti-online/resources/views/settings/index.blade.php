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
                        <textarea class="form-textarea" id="balance_notes" name="notes" maxlength="1000"></textarea>
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
                    <input class="form-input" id="full_name" name="full_name" value="{{ old('full_name', $supervisor?->full_name) }}" maxlength="255" required>
                </div>
                <div>
                    <label class="form-label" for="nip">NIP</label>
                    <input class="form-input" id="nip" name="nip" value="{{ old('nip', $supervisor?->nip) }}" maxlength="32">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="position_title">Jabatan</label>
                    <input class="form-input" id="position_title" name="position_title" value="{{ old('position_title', $supervisor?->position_title) }}" maxlength="255" required>
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

    @if ($canViewSensitive ?? false)
        <section class="card mt-6">
            <h2 class="section-heading">Daftar backup</h2>
            <p class="section-description">Salinan database yang dibuat otomatis sebelum setiap reset data. Unduh untuk penyimpanan manual.</p>

            @if (empty($backups))
                <div class="card-inner mt-5 text-sm text-slate-600">Belum ada backup. Backup dibuat otomatis saat reset data dijalankan.</div>
            @else
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs uppercase tracking-[0.08em] text-slate-400">
                            <tr>
                                <th class="pb-2 pr-5 font-semibold">Nama file</th>
                                <th class="pb-2 pr-5 font-semibold">Ukuran</th>
                                <th class="pb-2 pr-5 font-semibold">Dibuat</th>
                                <th class="pb-2 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($backups as $backup)
                                <tr class="transition-colors duration-150 hover:bg-slate-50">
                                    <td class="py-3 pr-5 font-mono text-xs text-slate-600">{{ $backup['name'] }}</td>
                                    <td class="py-3 pr-5 tabular-nums text-slate-600">{{ $backup['size_human'] }}</td>
                                    <td class="py-3 pr-5 tabular-nums text-slate-600">{{ $backup['modified_at'] }}</td>
                                    <td class="py-3 text-right">
                                        <a class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-white px-2.5 text-xs font-semibold text-sky-700 shadow-[inset_0_0_0_1px_rgba(14,165,233,0.3)] transition-colors duration-150 hover:bg-sky-50" href="{{ route('settings.backups.download', ['file' => $backup['name']]) }}">
                                            <svg aria-hidden="true" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                            Unduh
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card mt-6 border-rose-200">
            <h2 class="section-heading">Reset data transaksional</h2>
            <p class="section-description">Hapus semua data pegawai (termasuk arsip), cuti, payroll, riwayat, dan log. Konfigurasi (profil instansi, pejabat, jenis cuti, template dokumen, akun) dipertahankan.</p>

            <div class="card-inner mt-5 rounded-xl bg-rose-50 text-sm leading-6 text-rose-800 shadow-[inset_0_0_0_1px_rgba(225,29,72,0.14)]">
                <p class="font-semibold">Peringatan — tidak dapat dibatalkan.</p>
                <ul class="mt-1 list-inside list-disc">
                    <li>Salinan database dibuat otomatis ke <code class="font-mono text-xs">storage/app/backups/</code> sebelum penghapusan.</li>
                    <li>Data pegawai, cuti, payroll, riwayat, dan log <strong>dihapus permanen</strong>.</li>
                    <li>Anda tetap masuk, dan dapat mengimpor ulang data dari awal.</li>
                </ul>
            </div>

            <form id="reset-data-form" class="mt-5" method="POST" action="{{ route('settings.data.reset') }}">
                @csrf
                @error('confirm_text') <p class="form-error">{{ $message }}</p> @enderror
                <button class="btn-danger" type="button" data-reset-open>Reset data</button>
            </form>
        </section>
    @endif

    @if ($canViewSensitive ?? false)
        <div id="reset-confirm-overlay" class="hidden fixed inset-0 z-[100] items-center justify-center bg-slate-950/60 p-4" style="backdrop-filter: blur(2px);">
            <div id="reset-confirm-dialog" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.24),0_4px_16px_rgba(15,23,42,0.12)]">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-rose-100 text-rose-700">
                    <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">Hapus semua data transaksional?</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Tindakan ini <strong>tidak dapat dibatalkan</strong>. Semua data pegawai, cuti, payroll, riwayat, dan log akan dihapus permanen. Salinan database tetap dibuat otomatis ke <code class="font-mono text-xs">storage/app/backups/</code> sebelum penghapusan.</p>
                </div>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">
                <label class="form-label" for="confirm_text">Ketik <strong>RESET</strong> untuk konfirmasi</label>
                <input class="form-input" id="confirm_text" name="confirm_text" type="text" autocomplete="off" maxlength="10" required form="reset-data-form">
                <p class="form-help">Mengetik kata kunci memastikan penghapusan disengaja.</p>
            </div>

            <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button class="btn-secondary" type="button" data-reset-cancel>Batal</button>
                <button class="btn-danger" type="button" data-reset-confirm>
                    <svg aria-hidden="true" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.14-2.032-2.172a48.114 48.114 0 0 0-3.736 0C8.16 2.338 7.25 3.297 7.25 4.477v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    Ya, hapus semua
                </button>
            </div>
            </div>
        </div>

        <script>
            (function () {
                const openBtn = document.querySelector('[data-reset-open]');
                const overlay = document.getElementById('reset-confirm-overlay');
                const confirmBtn = document.querySelector('[data-reset-confirm]');
                const form = document.getElementById('reset-data-form');

                if (!openBtn || !overlay || !confirmBtn || !form) {
                    return;
                }

                const openDialog = () => {
                    overlay.classList.remove('hidden');
                    overlay.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };

                const closeDialog = () => {
                    overlay.classList.add('hidden');
                    overlay.classList.remove('flex');
                    document.body.style.overflow = '';
                };

                openBtn.addEventListener('click', openDialog);

                confirmBtn.addEventListener('click', () => {
                    const input = document.getElementById('confirm_text');

                    if (input.value.trim() !== 'RESET') {
                        input.setCustomValidity('Ketik RESET untuk mengonfirmasi.');
                        input.reportValidity();
                        return;
                    }

                    input.setCustomValidity('');
                    form.submit();
                });

                overlay.querySelector('[data-reset-cancel]')?.addEventListener('click', closeDialog);

                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        closeDialog();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !overlay.classList.contains('hidden')) {
                        closeDialog();
                    }
                });
            })();
        </script>
    @endif
@endsection
