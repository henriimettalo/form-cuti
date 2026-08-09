@extends('layouts.app')

@section('title', 'Pejabat Berwenang')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Master penandatangan</p>
        <h1 class="page-title mt-2">Pejabat berwenang</h1>
        <p class="page-description">Simpan pejabat yang akan diisikan otomatis pada formulir cuti: pejabat berwenang, Sekda, dan Wali Kota.</p>
    </div>

    <div class="mt-7 grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="card">
            <h2 class="section-heading">Data pejabat</h2>
            <p class="section-description">Pilih peran di atas, lalu isi datanya. Perubahan berlaku untuk formulir baru; data pada formulir yang sudah dibuat tetap menjadi arsip.</p>

            @foreach ($roleLabels as $role => $label)
                @php($official = $officials->get($role))
                <form class="mt-6 grid gap-5 sm:grid-cols-2 border-t border-slate-100 pt-6 first:border-t-0 first:mt-4 first:pt-0" method="POST" action="{{ route('authorized-official.store') }}">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role }}">
                    <div class="sm:col-span-2">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $label }}</h3>
                        @if ($role === 'authorized_official')
                            <p class="mt-0.5 text-xs text-slate-500">Pejabat berwenang standar untuk formulir cuti biasa.</p>
                        @elseif ($role === 'sekda')
                            <p class="mt-0.5 text-xs text-slate-500">Atasan langsung pemohon berstatus Camat.</p>
                        @else
                            <p class="mt-0.5 text-xs text-slate-500">Pejabat berwenang saat pemohon berstatus Camat.</p>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label" for="full_name_{{ $role }}">Nama lengkap</label>
                        <input class="form-input" id="full_name_{{ $role }}" name="full_name" value="{{ old('full_name', $official?->full_name) }}" maxlength="255" required>
                        @error('full_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="nip_{{ $role }}">NIP</label>
                        <input class="form-input" id="nip_{{ $role }}" name="nip" value="{{ old('nip', $official?->nip) }}" maxlength="32">
                        @error('nip') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="position_title_{{ $role }}">Jabatan</label>
                        <input class="form-input" id="position_title_{{ $role }}" name="position_title" value="{{ old('position_title', $official?->position_title) }}" maxlength="255" required>
                        @error('position_title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button class="btn-primary" type="submit">Simpan {{ $label }}</button>
                    </div>
                </form>
            @endforeach
        </section>

        <aside class="card h-fit">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-sky-700">Dipakai pada formulir</p>
            <ul class="mt-3 space-y-4">
                @foreach ($roleLabels as $role => $label)
                    <li class="border-t border-slate-100 pt-3 first:border-t-0 first:pt-0">
                        <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                        @if ($officials->has($role))
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $officials[$role]->full_name }}</p>
                            <p class="text-sm text-slate-600">{{ $officials[$role]->position_title }}</p>
                            @if ($officials[$role]->nip)
                                <p class="text-sm tabular-nums text-slate-500">NIP {{ $officials[$role]->nip }}</p>
                            @endif
                        @else
                            <p class="mt-1 text-sm leading-6 text-slate-600">Belum diisi.</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
@endsection
