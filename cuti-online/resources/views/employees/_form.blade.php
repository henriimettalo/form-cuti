@php($canViewSensitive = $canViewSensitive ?? false)
@php($importManaged = 'data-import-managed')

<form class="card mt-7" method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($employee)
        <div class="card-inner mb-7 text-sm leading-6 text-slate-600">
            Untuk mencatat tanggal berlaku dan nomor SK kenaikan pangkat atau mutasi secara lengkap, gunakan menu <a class="font-semibold text-sky-700 underline underline-offset-2" href="{{ route('employees.show', $employee) }}">Profil Pegawai</a>.
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <h2 class="section-heading">Identitas pegawai</h2>
            <p class="section-description">NIP, nama, jabatan, dan kontak adalah data minimal. Detail lainnya dapat dilengkapi dari impor payroll.</p>
        </div>
        <div>
            <label class="form-label" for="nip">NIP</label>
            <input class="form-input" id="nip" name="nip" value="{{ old('nip', $employee?->nip) }}" maxlength="32" required>
            @error('nip') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="full_name">Nama lengkap</label>
            <input class="form-input" id="full_name" name="full_name" value="{{ old('full_name', $employee?->full_name) }}" maxlength="255" required>
            @error('full_name') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="form-label" for="position_title">Jabatan</label>
            <input class="form-input" id="position_title" name="position_title" value="{{ old('position_title', $employee?->position?->name ?? $employee?->position_title) }}" maxlength="255" placeholder="Contoh: Sekretaris Camat Pontianak Selatan">
            <p class="form-help">Isi nama jabatan spesifik. Tipe jabatan (Fungsional Umum/Struktural) dari impor payroll.</p>
            @error('position_title') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="phone">Nomor telepon</label>
            <input class="form-input" id="phone" type="tel" inputmode="numeric" name="phone" value="{{ old('phone', $employee?->phone) }}" maxlength="15">
            @error('phone') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-input" id="email" type="email" name="email" value="{{ old('email', $employee?->email) }}" maxlength="255">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <details class="mt-8 border-t border-slate-100 pt-7" {{ $importManaged }}>
        <summary class="cursor-pointer select-none text-sm font-semibold text-slate-800">Biodata dan administrasi <span class="font-normal text-slate-500">(opsional, dilengkapi dari impor Gaji PNS)</span></summary>
        <p class="section-description mt-2">Jika dibiarkan kosong, tanggal lahir, jenis kelamin, dan TMT dapat diisi otomatis dari NIP yang valid.</p>
    <div class="mt-5 grid gap-5 md:grid-cols-2">
        @if ($canViewSensitive)
            <div>
                <label class="form-label" for="nik">NIK</label>
                <input class="form-input" id="nik" name="nik" value="{{ old('nik', $employee?->nik) }}" inputmode="numeric" maxlength="16">
                <p class="form-help">16 digit. Data sensitif, tampilkan hanya kepada petugas berwenang.</p>
                @error('nik') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="npwp">NPWP</label>
                <input class="form-input" id="npwp" name="npwp" value="{{ old('npwp', $employee?->npwp) }}" inputmode="numeric" maxlength="16">
                <p class="form-help">Mendukung NPWP lama 15 digit atau format baru 16 digit.</p>
                @error('npwp') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        @endif
        <div>
            <label class="form-label" for="employment_status">Status kepegawaian</label>
            <select class="form-select" id="employment_status" name="employment_status">
                <option value="">Belum dilengkapi</option>
                @foreach (['PNS', 'PPPK', 'Lainnya'] as $status)
                    <option value="{{ $status }}" @selected(old('employment_status', $employee?->employment_status) === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <p class="form-help">Boleh dikosongkan saat pendaftaran awal. PNS wajib memilih pangkat pada bagian Data jabatan.</p>
        </div>
        <div>
            <label class="form-label" for="service_started_on">Mulai masa kerja</label>
            <input class="form-input" id="service_started_on" type="date" name="service_started_on" value="{{ old('service_started_on', $employee?->service_started_on?->toDateString()) }}">
            <p class="form-help">Masa kerja dari NIP berbeda dengan masa kerja golongan. Isi manual bila TMT NIP tidak valid.</p>
            @if ($employee?->nip_tmt_valid === false)
                <p class="form-help text-amber-700">Fragmen TMT pada NIP tidak valid.</p>
            @endif
        </div>
        <div>
            <label class="form-label" for="birth_date">Tanggal lahir</label>
            <input class="form-input" id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $employee?->birth_date?->toDateString()) }}">
            @error('birth_date') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="gender">Jenis kelamin</label>
            <select class="form-select" id="gender" name="gender">
                <option value="">Pilih</option>
                <option value="L" @selected(old('gender', $employee?->gender) === 'L')>Laki-laki</option>
                <option value="P" @selected(old('gender', $employee?->gender) === 'P')>Perempuan</option>
            </select>
            @error('gender') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="position_type">Tipe jabatan</label>
            <select class="form-select" id="position_type" name="position_type">
                <option value="">Pilih</option>
                <option value="1" @selected((string) old('position_type', $employee?->position_type) === '1')>1 · Struktural</option>
                <option value="3" @selected((string) old('position_type', $employee?->position_type) === '3')>3 · Fungsional Umum</option>
            </select>
            @error('position_type') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="eselon">Eselon</label>
            <input class="form-input" id="eselon" name="eselon" value="{{ old('eselon', $employee?->eselon ?? '00') }}" maxlength="8">
            <p class="form-help">Gunakan 00 untuk pegawai tanpa eselon.</p>
            @error('eselon') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="grade_service_years">Masa kerja golongan (tahun)</label>
            <input class="form-input" id="grade_service_years" type="number" name="grade_service_years" min="0" max="100" value="{{ old('grade_service_years', $employee?->grade_service_years) }}">
            @error('grade_service_years') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="grade_service_months">Masa kerja golongan (bulan)</label>
            <input class="form-input" id="grade_service_months" type="number" name="grade_service_months" min="0" max="11" value="{{ old('grade_service_months', $employee?->grade_service_months) }}">
            @error('grade_service_months') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="marital_status">Status pernikahan</label>
            <select class="form-select" id="marital_status" name="marital_status">
                <option value="">Pilih</option>
                <option value="1" @selected((string) old('marital_status', $employee?->marital_status) === '1')>1 · Menikah</option>
                <option value="2" @selected((string) old('marital_status', $employee?->marital_status) === '2')>2 · Belum menikah</option>
            </select>
            @error('marital_status') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="spouse_count">Jumlah istri/suami</label>
            <input class="form-input" id="spouse_count" type="number" name="spouse_count" min="0" max="255" value="{{ old('spouse_count', $employee?->spouse_count ?? 0) }}">
            @error('spouse_count') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="child_count">Jumlah anak</label>
            <input class="form-input" id="child_count" type="number" name="child_count" min="0" max="255" value="{{ old('child_count', $employee?->child_count ?? 0) }}">
            @error('child_count') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="spouse_is_pns">Pasangan PNS</label>
            <select class="form-select" id="spouse_is_pns" name="spouse_is_pns">
                <option value="">Belum diisi</option>
                <option value="1" @selected((string) old('spouse_is_pns', $employee?->spouse_is_pns === null ? '' : ($employee->spouse_is_pns ? '1' : '0')) === '1')>YA</option>
                <option value="0" @selected((string) old('spouse_is_pns', $employee?->spouse_is_pns === null ? '' : ($employee->spouse_is_pns ? '1' : '0')) === '0')>TIDAK</option>
            </select>
            @error('spouse_is_pns') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        @if ($canViewSensitive)
            <div class="md:col-span-2">
                <label class="form-label" for="spouse_nip">NIP pasangan</label>
                <input class="form-input" id="spouse_nip" name="spouse_nip" value="{{ old('spouse_nip', $employee?->spouse_nip) }}" maxlength="32">
                @error('spouse_nip') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        @endif
    </div>
    </details>

    <details class="mt-8 border-t border-slate-100 pt-7" {{ $importManaged }}>
        <summary class="cursor-pointer select-none text-sm font-semibold text-slate-800">Data jabatan <span class="font-normal text-slate-500">(opsional, dilengkapi dari impor Gaji PNS)</span></summary>
        <p class="section-description mt-2">Jabatan dipakai ulang pada data pegawai berikutnya. Unit kerja dari impor tetap dipertahankan.</p>
    <div class="mt-5 grid gap-5 md:grid-cols-2">
        <div>
            <label class="form-label" for="organization_name">Unit kerja</label>
            <div class="form-input flex min-h-11 items-center bg-slate-50 text-slate-700" id="organization_name" role="status" aria-label="Unit kerja pegawai">{{ $currentDepartmentName ?? $employee?->department?->name ?? $organizationProfile->name }}</div>
            <p class="form-help text-pretty">Pegawai baru memakai <a class="font-semibold text-sky-700 underline underline-offset-2" href="{{ route('organization-profile.index') }}">Profil Instansi</a> sebagai bawaan. Data dari kolom Unit Kerja pada impor tetap dipertahankan.</p>
        </div>
        <div class="md:col-span-2">
            <label class="form-label" for="rank_grade">Pangkat / golongan</label>
            <select class="form-select" id="rank_grade" name="rank_grade">
                <option value="">Pilih pangkat / golongan</option>
                @foreach ($rankGroups as $rankStatus => $groups)
                    @foreach ($groups as $rankGroup)
                        <optgroup label="{{ $rankStatus }} · {{ $rankGroup['label'] }}">
                            @foreach ($rankGroup['ranks'] as $rank)
                                <option value="{{ $rank['grade'] }}" @selected(old('rank_grade', $employee?->grade) === $rank['grade'])>
                                    {{ $rank['label'] ?? ($rank['name'].' ('.$rank['grade'].')') }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                @endforeach
            </select>
            <p class="form-help">Wajib untuk PNS. Untuk PPPK, pilih Golongan I sampai Golongan XX atau biarkan kosong bila belum ditetapkan.</p>
            @error('rank_grade') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>
    </details>

    <details class="mt-8 border-t border-slate-100 pt-7" {{ $importManaged }}>
        <summary class="cursor-pointer select-none text-sm font-semibold text-slate-800">Alamat <span class="font-normal text-slate-500">(opsional, dilengkapi dari impor Gaji PNS)</span></summary>
        <p class="section-description mt-2">Dapat dilengkapi dari impor payroll.</p>
    <div class="mt-5 grid gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="form-label" for="address">Alamat</label>
            <textarea class="form-textarea" id="address" name="address">{{ old('address', $employee?->address) }}</textarea>
        </div>
    </div>
    </details>

    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
        <a class="btn-secondary" href="{{ route('employees.index') }}">Batal</a>
        <button class="btn-primary" type="submit">{{ $submitLabel }}</button>
    </div>
</form>
