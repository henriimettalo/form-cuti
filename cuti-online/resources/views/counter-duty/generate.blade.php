@extends('layouts.app')
@section('title', 'Buat Jadwal Piket')
@section('content')
    <h1 class="page-title">Buat jadwal piket otomatis</h1>
    <p class="page-description">Rotasi melalui semua kelompok berlangsung setiap hari kerja, melewati akhir pekan dan hari libur. Jadwal lama tidak ditimpa.</p>
    <form class="card mt-7" action="{{ route('counter-duty.preview') }}" method="POST">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="min-w-0"><label class="form-label" for="start_date">Tanggal awal</label><input class="form-input" name="start_date" id="start_date" type="date" value="{{ old('start_date', $preview['input']['start_date'] ?? $today) }}" required></div>
            <div class="min-w-0"><label class="form-label" for="end_date">Tanggal akhir</label><input class="form-input" name="end_date" id="end_date" type="date" value="{{ old('end_date', $preview['input']['end_date'] ?? $today) }}" required></div>
            <div class="min-w-0"><label class="form-label" for="starting_group">Kelompok pertama</label><select class="form-select" name="starting_group" id="starting_group" required>@foreach ($groups as $group)<option value="{{ $group->number }}" @selected((int) old('starting_group', $preview['input']['starting_group'] ?? 1) === $group->number)>Kelompok {{ $group->number }}</option>@endforeach</select></div>
        </div>
        <p class="form-help mt-3">Kelompok pertama hanya dipakai jika belum ada jadwal sebelumnya. Jika sudah ada, giliran dilanjutkan otomatis. Maksimal satu tahun per pratinjau.</p>
        <button class="btn-primary mt-5" type="submit">Periksa jadwal</button>
    </form>
    @if ($preview)
        @php($newCount = collect($preview['rows'])->where('status', 'new')->count())
        <section class="card mt-5">
            <h2 class="section-heading">Pratinjau jadwal</h2>
            <p class="section-description">{{ $newCount }} jadwal baru · {{ collect($preview['rows'])->where('status', 'existing')->count() }} sudah tersimpan · {{ collect($preview['rows'])->where('status', 'holiday')->count() }} hari libur. {{ $preview['continued'] ? 'Rotasi melanjutkan jadwal sebelumnya.' : 'Rotasi memakai kelompok pertama yang dipilih.' }}</p>
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm"><thead class="border-b border-slate-200 text-slate-500"><tr><th class="py-3 pr-5" scope="col">Tanggal</th><th class="py-3 pr-5" scope="col">Kelompok</th><th class="py-3" scope="col">Keterangan</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">@foreach ($preview['rows'] as $row)<tr><td class="whitespace-nowrap py-3 pr-5">{{ \Carbon\CarbonImmutable::parse($row['date'])->locale('id')->translatedFormat('D, d M Y') }}</td><td class="py-3 pr-5">{{ isset($row['roster']) ? 'Kelompok '.$row['roster']['number'] : '—' }}</td><td class="py-3">{{ $row['status'] === 'new' ? 'Akan disimpan' : ($row['status'] === 'existing' ? 'Sudah tersimpan; tidak diubah' : $row['reason']) }}</td></tr>@endforeach</tbody>
                </table>
            </div>
            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <form method="POST" action="{{ route('counter-duty.cancel') }}">@csrf<button class="btn-secondary" type="submit">Batal</button></form>
                @if ($newCount)
                    <form method="POST" action="{{ route('counter-duty.store') }}" data-confirm-title="Simpan jadwal piket?" data-confirm-message="{{ $newCount }} jadwal baru akan disimpan. Jadwal yang sudah ada tidak diubah." data-confirm-button="Simpan jadwal">@csrf<button class="btn-primary" type="submit">Simpan {{ $newCount }} jadwal</button></form>
                @endif
            </div>
        </section>
    @else
        <a class="btn-secondary mt-5" href="{{ route('counter-duty.index') }}">Kembali ke piket</a>
    @endif
@endsection
