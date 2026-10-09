@extends('layouts.app')
@section('title', 'Impor Jadwal Piket')
@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="page-title">Impor jadwal piket</h1>
            <p class="page-description">Ambil jadwal existing dari Excel untuk tahun yang dipilih. Tanggal dan nomor kelompok mengikuti file, bukan dibuat ulang.</p>
        </div>
        <a class="btn-secondary shrink-0" href="{{ route('counter-duty.index') }}">Kembali ke piket</a>
    </div>
    <form class="card mt-7" action="{{ route('counter-duty.import.preview') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]">
            <div class="min-w-0"><label class="form-label" for="year">Tahun impor</label><input class="form-input" id="year" name="year" type="number" min="1900" max="2100" value="{{ old('year', $preview['year'] ?? $currentYear) }}" required>@error('year')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="min-w-0"><label class="form-label" for="file">File Excel jadwal</label><input class="form-input" id="file" name="file" type="file" accept=".xlsx" required><p class="form-help">Format .xlsx, maksimal 5 MB. Sheet jadwal_piket dibaca jika tersedia; jika tidak, sheet pertama digunakan.</p>@error('file')<p class="form-error">{{ $message }}</p>@enderror</div>
        </div>
        <div class="card-inner mt-4 text-sm text-slate-600">
            <p>Kolom wajib: <strong>Tanggal Piket</strong> dan <strong>Kelompok Yang Piket</strong> (1–4). Header Tanggal dan Kelompok juga diterima.</p>
            <p class="mt-2">Tahun hanya menyaring tanggal asli, tidak mengubah tahunnya. Nama penanggung jawab dan petugas di Excel tidak digunakan: aplikasi memakai susunan kelompok ref yang sudah dikonfirmasi.</p>
            <p class="mt-2">Jadwal pada akhir pekan tetap diimpor jika tertulis di Excel. Konflik dengan hari libur yang ditetapkan harus diperiksa. Hari libur nasional tidak ditambahkan otomatis.</p>
        </div>
        <button class="btn-primary mt-5" type="submit">Periksa file</button>
    </form>
    @if ($preview)
        @php
            $newCount = collect($preview['rows'])->where('status', 'new')->count();
            $errorCount = collect($preview['rows'])->where('status', 'error')->count();
        @endphp
        <section class="card mt-5">
            <h2 class="section-heading">Pratinjau tahun {{ $preview['year'] }}</h2>
            <p class="section-description">{{ $preview['file_name'] }} · sheet {{ $preview['sheet_name'] }}</p>
            <p class="mt-3 text-sm text-slate-700">{{ $newCount }} jadwal baru · {{ collect($preview['rows'])->where('status', 'existing')->count() }} sudah ada · {{ $preview['outside_year'] }} baris di luar tahun · {{ $errorCount }} bermasalah.</p>
            @if ($errorCount)<p class="form-error mt-3">Perbaiki baris bermasalah lalu unggah ulang. Belum ada jadwal yang disimpan.</p>@endif
            @if ($preview['rows'] === [])
                <div class="card-inner mt-5 text-sm">Tidak ada tanggal pada tahun {{ $preview['year'] }}. Pilih tahun yang tercantum dalam file lalu periksa ulang.</div>
            @else
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm"><caption class="sr-only">Pratinjau impor jadwal piket tahun {{ $preview['year'] }}</caption><thead class="border-b border-slate-200 text-slate-500"><tr><th scope="col" class="py-3 pr-5">Baris</th><th scope="col" class="py-3 pr-5">Tanggal</th><th scope="col" class="py-3 pr-5">Kelompok & petugas</th><th scope="col" class="py-3">Hasil pemeriksaan</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">@foreach ($preview['rows'] as $row)<tr class="align-top">
                            <td class="py-3 pr-5">{{ $row['row_number'] }}</td><td class="whitespace-nowrap py-3 pr-5">{{ $row['date'] ? \Carbon\CarbonImmutable::parse($row['date'])->format('d/m/Y') : 'Tidak valid' }}</td>
                            <td class="min-w-56 py-3 pr-5"><p class="font-semibold">{{ $row['group_number'] ? 'Kelompok '.$row['group_number'] : 'Tidak valid' }}</p>@if (isset($row['roster']))<p class="mt-1 text-slate-600">{{ $row['roster']['coordinator'] }}</p><ul class="mt-2 space-y-1">@foreach ($row['roster']['members'] as $member)<li>{{ $member }}</li>@endforeach</ul>@endif</td>
                            <td class="min-w-48 py-3 {{ $row['status'] === 'error' ? 'text-rose-700' : 'text-slate-600' }}">{{ $row['message'] }}</td>
                        </tr>@endforeach</tbody>
                    </table>
                </div>
            @endif
            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <form method="POST" action="{{ route('counter-duty.import.cancel') }}">@csrf<button class="btn-secondary" type="submit">Batal</button></form>
                @if ($newCount && ! $errorCount)
                    <form method="POST" action="{{ route('counter-duty.import.store') }}" data-confirm-title="Impor jadwal tahun {{ $preview['year'] }}?" data-confirm-message="{{ $newCount }} jadwal baru akan diimpor sesuai tanggal dan kelompok Excel. Jadwal lama tidak diubah." data-confirm-button="Impor jadwal">@csrf<button class="btn-primary" type="submit">Impor {{ $newCount }} jadwal</button></form>
                @endif
            </div>
        </section>
    @endif
@endsection
