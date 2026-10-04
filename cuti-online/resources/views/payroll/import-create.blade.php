@extends('layouts.app')

@section('title', 'Impor Payroll')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">Payroll</p>
            <h1 class="page-title mt-2">Impor data pegawai dari payroll</h1>
            <p class="page-description">Gunakan file payroll bulanan untuk memperbarui data pegawai. Nomor rekening ikut diimpor. Nominal gaji, tunjangan, potongan, dan transfer diabaikan.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('payroll.index') }}">Kembali ke payroll</a>
    </div>

    @if ($pendingImports->isNotEmpty())
        <section class="card mt-7 border-amber-200 bg-amber-50/70">
            <h2 class="section-heading">Pratinjau menunggu konfirmasi</h2>
            <p class="section-description">Buka untuk memeriksa perubahan. Pratinjau dari alur lama perlu dibuat ulang dengan mengunggah file yang sama.</p>
            <div class="mt-4 space-y-2">
                @foreach ($pendingImports as $pendingImport)
                    <a class="flex flex-col gap-1 rounded-xl bg-white px-4 py-3 text-sm shadow-[inset_0_0_0_1px_rgba(180,83,9,0.12)] transition-colors hover:bg-amber-100/60 sm:flex-row sm:items-center sm:justify-between" href="{{ route('payroll.import.preview', $pendingImport) }}">
                        <span class="font-semibold text-slate-800">{{ $pendingImport->sourceTypeLabel() }} {{ sprintf('%02d/%d', $pendingImport->month, $pendingImport->year) }} · {{ $pendingImport->original_filename }}</span>
                        <span class="text-amber-700">Buka pratinjau →</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <form class="card mt-7" method="POST" action="{{ route('payroll.import.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="year">Tahun</label>
                <input class="form-input" id="year" name="year" type="number" min="2000" max="2100" value="{{ old('year', 2026) }}" required>
            </div>
            <div>
                <label class="form-label" for="month">Bulan</label>
                <select class="form-select" id="month" name="month" required>
                    @foreach ($months as $number => $label)
                        <option value="{{ $number }}" @selected((int) old('month', 5) === $number)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="form-label" for="source_type">Jenis file</label>
                <select class="form-select" id="source_type" name="source_type" required>
                    <option value="primary" @selected(old('source_type', 'primary') === 'primary')>File payroll gaji</option>
                    <option value="tpp" @selected(old('source_type') === 'tpp')>File TPP SIPD</option>
                </select>
                <p class="form-help">Pilih jenis file sumber. Kedua jenis file hanya digunakan untuk memperbarui data pegawai.</p>
            </div>
            <div class="md:col-span-2">
                <label class="form-label" for="file">File sumber<span class="required-asterisk text-rose-500" aria-hidden="true"> *</span></label>
                <input class="form-input" id="file" name="file" type="file" accept=".xlsx,.csv,.txt" required>
                <p class="form-help">Kolom wajib: <code>nip_pegawai</code>, <code>nama_pegawai</code>, <code>status_asn</code>, dan <code>golongan</code>. Kolom nominal tidak diperlukan. Maksimal 1.000 baris.</p>
            </div>
        </div>

        <div class="card-inner mt-7 text-sm leading-6 text-slate-600">
            <p class="font-semibold text-slate-800">Yang dilakukan saat impor</p>
            <ul class="mt-2 list-inside list-disc">
                <li>NIP wajib sudah terdaftar di menu Pegawai; jika ada NIP yang belum terdaftar, seluruh impor dibatalkan.</li>
                <li>Perubahan identitas, pangkat, jabatan, dan masa kerja ditampilkan sebelum disimpan.</li>
                <li>Nomor rekening ikut disimpan jika tersedia. Gaji, tunjangan, potongan, dan transfer tidak diambil.</li>
                <li>Tahun dan bulan digunakan sebagai keterangan sumber perubahan.</li>
                <li>Perubahan data pegawai dicatat di Log perubahan.</li>
            </ul>
        </div>

        <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
            <a class="btn-secondary" href="{{ route('payroll.index') }}">Batal</a>
            <button class="btn-primary" type="submit">Tinjau impor</button>
        </div>
    </form>
@endsection
