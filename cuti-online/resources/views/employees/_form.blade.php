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
            <p class="section-description">Gunakan NIP sebagai identitas unik agar data tidak terduplikasi.</p>
        </div>
        <div>
            <label class="form-label" for="nip">NIP</label>
            <input class="form-input" id="nip" name="nip" value="{{ old('nip', $employee?->nip) }}" required>
            @error('nip') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="full_name">Nama lengkap</label>
            <input class="form-input" id="full_name" name="full_name" value="{{ old('full_name', $employee?->full_name) }}" required>
            @error('full_name') <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label" for="employment_status">Status kepegawaian</label>
            <select class="form-select" id="employment_status" name="employment_status" required>
                @foreach (['PNS', 'PPPK', 'Lainnya'] as $status)
                    <option value="{{ $status }}" @selected(old('employment_status', $employee?->employment_status ?? 'PNS') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="service_started_on">Mulai masa kerja</label>
            <input class="form-input" id="service_started_on" type="date" name="service_started_on" value="{{ old('service_started_on', $employee?->service_started_on?->toDateString()) }}">
            <p class="form-help">Dipakai untuk menghitung masa kerja pada formulir.</p>
        </div>
    </div>

    <div class="mt-8 grid gap-5 border-t border-slate-100 pt-7 md:grid-cols-2">
        <div class="md:col-span-2">
            <h2 class="section-heading">Data jabatan</h2>
            <p class="section-description">Jabatan dipakai ulang pada data pegawai berikutnya. Unit kerja mengikuti profil instansi.</p>
        </div>
        <div>
            <label class="form-label" for="organization_name">Unit kerja</label>
            <div class="form-input flex min-h-11 items-center bg-slate-50 text-slate-700" id="organization_name" role="status" aria-label="Unit kerja aplikasi">{{ $organizationProfile->name }}</div>
            <p class="form-help text-pretty">Diatur melalui <a class="font-semibold text-sky-700 underline underline-offset-2" href="{{ route('organization-profile.index') }}">Profil Instansi</a> dan berlaku untuk semua pegawai.</p>
        </div>
        <div>
            <label class="form-label" for="position_title">Jabatan</label>
            <input class="form-input" id="position_title" name="position_title" value="{{ old('position_title', $employee?->position?->name ?? $employee?->position_title) }}" placeholder="Contoh: Pengelola Layanan Operasional" required>
            @error('position_title') <p class="form-error">{{ $message }}</p> @enderror
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

    <div class="mt-8 grid gap-5 border-t border-slate-100 pt-7 md:grid-cols-2">
        <div class="md:col-span-2">
            <h2 class="section-heading">Kontak</h2>
            <p class="section-description">Opsional, tetapi berguna untuk melengkapi kontak cuti.</p>
        </div>
        <div>
            <label class="form-label" for="phone">Nomor telepon</label>
            <input class="form-input" id="phone" name="phone" value="{{ old('phone', $employee?->phone) }}">
        </div>
        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-input" id="email" type="email" name="email" value="{{ old('email', $employee?->email) }}">
        </div>
        <div class="md:col-span-2">
            <label class="form-label" for="address">Alamat</label>
            <textarea class="form-textarea" id="address" name="address">{{ old('address', $employee?->address) }}</textarea>
        </div>
    </div>

    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
        <a class="btn-secondary" href="{{ route('employees.index') }}">Batal</a>
        <button class="btn-primary" type="submit">{{ $submitLabel }}</button>
    </div>
</form>
