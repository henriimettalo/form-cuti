@extends('layouts.app')
@section('title', 'Kelompok Piket Kelurahan')
@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="page-title">Kelompok piket kelurahan</h1><p class="page-description">Kelola penanggung jawab dan anggota kelompok piket setiap kelurahan.</p></div>
        <a class="btn-secondary" href="{{ route('counter-duty.kelurahan-calendar') }}">Lihat kalender kelurahan</a>
    </div>
    <section class="card mt-5">
        <div class="border-b border-slate-100 pb-5"><h2 class="section-heading">Status kelompok</h2><p class="section-description">Setiap kelurahan memiliki kelompoknya sendiri.</p></div>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            @foreach ($kelurahans as $kelurahan)
                @php($kelurahanGroups = $schedules->where('department_id', $kelurahan->id)->sortBy('group_number'))
                @php($madeGroups = $kelurahanGroups->pluck('group_number')->unique()->sort()->values())
                <article class="rounded-xl border border-slate-200 p-4"><div class="flex items-start justify-between gap-3"><h3 class="font-semibold text-slate-800">{{ $kelurahan->name }}</h3><span class="text-xs text-slate-500">{{ $madeGroups->count() }} kelompok dibuat</span></div>@if ($madeGroups->isEmpty())<p class="mt-4 text-sm text-amber-700">Belum ada kelompok.</p>@else<div class="mt-4 space-y-3 divide-y divide-slate-200">@foreach ($kelurahanGroups as $group)<div class="rounded-lg bg-emerald-50 px-3 py-2"><p class="text-xs font-semibold text-emerald-700">Kelompok {{ $group->group_number }}</p><p class="mt-1 text-sm font-medium text-slate-800">{{ $group->coordinator }}</p>@foreach ($group->members ?? [] as $member)<p class="mt-1 text-xs text-slate-600">{{ $member }}</p>@endforeach<div class="mt-2 flex gap-2"><a class="btn-secondary text-xs" href="{{ route('counter-duty.kelurahan-schedules.edit', $group) }}">Edit</a><form method="POST" action="{{ route('counter-duty.kelurahan-schedules.destroy', $group) }}" data-confirm-title="Hapus kelompok?" data-confirm-message="Kelompok {{ $group->group_number }} {{ $kelurahan->name }} dan susunan pegawainya akan dihapus." data-confirm-button="Hapus">@csrf @method('DELETE')<button class="btn-secondary text-xs text-rose-700" type="submit">Hapus</button></form></div></div>@endforeach</div>@endif<p class="mt-3 text-xs text-slate-500">Nomor kelompok berikutnya yang disarankan: {{ $madeGroups->isEmpty() ? 1 : $madeGroups->max() + 1 }}</p>@foreach (range(1, max(1, ($madeGroups->max() ?? 0) + 1)) as $groupNumber) @if (! $madeGroups->contains($groupNumber))<a class="btn-secondary mt-3 inline-flex text-xs" href="{{ route('counter-duty.kelurahan-schedules.create', ['department_id' => $kelurahan->id, 'group_number' => $groupNumber]) }}">Tambah Kelompok {{ $groupNumber }}</a>@break @endif @endforeach</article>
            @endforeach
        </div>
        
    </section>
@endsection
