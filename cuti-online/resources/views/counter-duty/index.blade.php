@extends('layouts.app')
@section('title', 'Piket Loket')
@section('content')
    @php($standaloneHolidayPage = request()->routeIs('holidays.*'))
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title" data-sticky-page-title>{{ $standaloneHolidayPage ? 'Hari libur' : 'Piket loket' }}</h1>
            <p class="page-description">{{ $standaloneHolidayPage ? 'Kalender libur bersama untuk apel/upacara dan piket loket.' : 'Jadwal bergiliran melalui semua kelompok yang tersedia. Susunan kelompok dapat ditambah sesuai kebutuhan.' }}</p>
        </div>
        @unless ($standaloneHolidayPage)
        <nav class="flex shrink-0 flex-wrap gap-2 self-start sm:self-center" aria-label="Jenis jadwal">
            <a class="btn-secondary" href="{{ route('ceremony-schedules.index', ['year' => 2026, 'tab' => 'kalender']) }}">Apel / Upacara</a>
            <a class="btn-primary" href="{{ route('counter-duty.index', ['tab' => $tab, 'month' => $month->format('Y-m'), 'year' => $year]) }}" aria-current="page">Piket Loket</a>
        </nav>
        @endunless
    </div>
    @unless ($standaloneHolidayPage)
    <nav class="mt-7 flex flex-wrap gap-2" aria-label="Pengaturan piket loket">
        @foreach (['agenda' => 'Jadwal piket', 'groups' => 'Kelompok piket'] as $value => $label)
            <a class="{{ $tab === $value ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('counter-duty.index', ['tab' => $value, 'month' => $month->format('Y-m'), 'year' => $year]) }}" @if ($tab === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    @endunless

    @if ($tab === 'agenda')
        <section class="card mt-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="section-heading">Piket {{ $month->locale('id')->translatedFormat('F Y') }}</h2>
                @if ($canManage)
                    <div class="flex flex-wrap gap-2">
                        <a class="btn-primary" href="{{ route('counter-duty.generate') }}">Buat jadwal otomatis</a>
                    </div>
                @endif
            </div>
            <form class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between" action="{{ route('counter-duty.index') }}" method="GET">
                <a class="btn-secondary" href="{{ route('counter-duty.index', ['tab' => 'agenda', 'month' => $month->subMonth()->format('Y-m'), 'year' => $year]) }}" aria-label="Lihat {{ $month->subMonth()->locale('id')->translatedFormat('F Y') }}">Bulan sebelumnya</a>
                <div class="min-w-0 sm:w-56 sm:flex-none">
                    <label class="form-label" for="month-trigger">Bulan dan tahun</label>
                    <x-month-picker id="month" name="month" value="{{ $month->format('Y-m') }}" :years="range(2026, 2030)" :auto-submit="true" />
                </div>
                <a class="btn-secondary" href="{{ route('counter-duty.index', ['tab' => 'agenda', 'month' => $month->addMonth()->format('Y-m'), 'year' => $year]) }}" aria-label="Lihat {{ $month->addMonth()->locale('id')->translatedFormat('F Y') }}">Bulan berikutnya</a>
            </form>
            @if ($monthSchedules->isEmpty())
                <div class="card-inner mt-5">
                    <p class="font-semibold text-slate-800">Belum ada jadwal piket bulan ini.</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $canManage ? 'Tetapkan hari libur, lalu buat pratinjau jadwal otomatis.' : 'Pilih bulan lain atau hubungi Super Admin untuk informasi jadwal.' }}</p>
                </div>
            @else
                <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
                    <div class="min-w-[700px]">
                        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                            @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)<div class="px-2 py-3">{{ $weekday }}</div>@endforeach
                        </div>
                        <div class="divide-y divide-slate-200">
                            @foreach ($monthCalendarWeeks as $week)
                                <div class="grid grid-cols-7 divide-x divide-slate-200">
                                    @foreach ($week as $day)
                                        @php($dateKey = $day['date']->toDateString())
                                        @php($schedule = $day['inMonth'] ? $monthSchedules->get($dateKey) : null)
                                        <div class="min-h-32 min-w-0 p-2 {{ $day['inMonth'] ? 'bg-white' : 'bg-slate-50 text-slate-400' }}">
                                            <div class="flex items-start justify-between gap-1"><span class="grid size-7 shrink-0 place-items-center rounded-full text-sm {{ $schedule ? 'bg-sky-100 font-semibold text-sky-800' : 'font-medium text-slate-600' }}">{{ $day['date']->format('j') }}</span>
                                                @if ($schedule && $canManage)<a class="text-xs font-medium text-sky-700 underline decoration-slate-300 underline-offset-2" href="{{ route('ceremony-schedules.edit', $schedule) }}" aria-label="Edit piket {{ $day['date']->format('d/m/Y') }}">Edit</a>@endif
                                            </div>
                                            @if ($schedule)
                                                <div class="mt-2 space-y-1.5 text-xs leading-4">
                                                    @if ($schedule->duty_roster)<p class="font-semibold text-slate-800">Kelompok {{ $schedule->duty_roster['number'] }}</p><ol class="list-decimal space-y-0.5 pl-4 text-slate-500"><li class="break-words font-semibold text-slate-700"><strong>{{ $schedule->duty_roster['coordinator'] }}</strong></li>@foreach ($schedule->duty_roster['members'] as $member)<li class="break-words">{{ $member }}</li>@endforeach</ol>@else<p class="font-medium text-slate-600">{{ $schedule->notes ?: 'Belum ditentukan' }}</p>@endif
                                                </div>
                                            @elseif ($day['inMonth'] && $day['date']->isWeekend())
                                                <p class="mt-2 text-xs text-slate-400">Akhir pekan</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </section>
    @elseif ($tab === 'groups')
        <section class="card mt-5">
            <h2 class="section-heading">Susunan kelompok piket</h2>
            <p class="section-description">Kelompok dapat berjumlah berapa pun. Perubahan otomatis diterapkan pada jadwal piket yang akan datang; jadwal yang sudah lewat tetap menjadi arsip.</p>
            @if ($canManage)
                <datalist id="employee-names">@foreach ($employees as $employee)<option value="{{ $employee->full_name }}"></option>@endforeach</datalist>
                <button class="btn-primary mb-5" type="button" data-duty-group-create-open>Tambah kelompok {{ $groups->count() + 1 }}</button>
                <dialog class="m-auto max-h-[calc(100dvh-2rem)] w-[min(100%-2rem,40rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/50" data-duty-group-create-dialog aria-labelledby="duty-group-create-title">
                    <form method="POST" action="{{ route('counter-duty.groups.store') }}" class="p-6">
                        @csrf
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4"><div><h3 class="text-lg font-semibold text-slate-900" id="duty-group-create-title">Tambah kelompok {{ $groups->count() + 1 }}</h3><p class="mt-1 text-sm text-slate-600">Isi penanggung jawab dan petugas piket.</p></div><button class="btn-secondary px-3" type="button" data-duty-group-create-close aria-label="Tutup">×</button></div>
                        <div class="mt-5 grid gap-5"><div><label class="form-label" for="new-group-coordinator">Penanggung jawab</label><input class="form-input" id="new-group-coordinator" name="coordinator" placeholder="Nama penanggung jawab" maxlength="255" required></div><div><label class="form-label" for="new-group-members">Petugas piket</label><textarea class="form-input" id="new-group-members" name="members" rows="5" placeholder="Satu petugas per baris" maxlength="5000" required></textarea><p class="form-help">Masukkan satu nama pada setiap baris.</p></div></div>
                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end"><button class="btn-secondary" type="button" data-duty-group-create-close>Batal</button><button class="btn-primary" type="submit">Simpan kelompok</button></div>
                    </form>
                </dialog>
            @endif
            <div class="mt-5 divide-y divide-slate-100">
                @foreach ($groups as $group)
                    @if ($canManage)
                        <form class="py-5" method="POST" action="{{ route('counter-duty.groups.update', $group) }}">
                            @csrf
                            @method('PUT')
                            <h3 class="font-semibold text-slate-900">Kelompok {{ $group->number }}</h3>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div class="min-w-0">
                                    <label class="form-label" for="coordinator-{{ $group->id }}">Penanggung jawab</label>
                                    <input class="form-input" id="coordinator-{{ $group->id }}" name="coordinator" list="employee-names" value="{{ request()->session()->getOldInput('_group_id') == $group->id ? old('coordinator') : $group->coordinator }}" maxlength="255" required>
                                </div>
                                <div class="min-w-0">
                                    <label class="form-label" for="members-{{ $group->id }}">Petugas piket</label>
                                    <textarea class="form-input" id="members-{{ $group->id }}" name="members" rows="4" maxlength="5000" required>{{ request()->session()->getOldInput('_group_id') == $group->id ? old('members') : implode("\n", $group->members) }}</textarea>
                                    <p class="form-help">Satu nama per baris. Nama dari ref tetap bisa digunakan meskipun belum ada di master pegawai.</p>
                                </div>
                            </div>
                            <input type="hidden" name="_group_id" value="{{ $group->id }}">
                            <button class="btn-secondary mt-4" type="submit">Simpan kelompok {{ $group->number }}</button>
                        </form>
                    @else
                        <article class="py-5"><h3 class="font-semibold text-slate-900">Kelompok {{ $group->number }}</h3><p class="mt-2 text-sm">Penanggung jawab: {{ $group->coordinator }}</p><ul class="mt-3 space-y-1 text-sm text-slate-600">@foreach ($group->members as $member)<li>{{ $member }}</li>@endforeach</ul></article>
                    @endif
                @endforeach
            </div>
        </section>
    @else
        <section class="card mt-5">
            <h2 class="section-heading">Kalender libur piket {{ $year }}</h2>
            <p class="section-description">Tanggal 1 Januari sampai 31 Desember, tanpa Sabtu dan Minggu. Ketik ya atau 1 pada isLibur untuk libur tambahan; kosong, tidak, atau 0 berarti hari kerja. Libur nasional tidak ditandai otomatis.</p>
            <form class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end" method="GET" action="{{ $standaloneHolidayPage ? route('holidays.index') : route('counter-duty.index') }}">
                <input type="hidden" name="tab" value="holidays">
                <div><label class="form-label" for="calendar_year">Tahun</label><input class="form-input" id="calendar_year" name="year" type="number" min="1900" max="2100" value="{{ $year }}" required></div>
                <div class="sm:w-44"><label class="form-label" for="calendar_month">Bulan</label><select class="form-select" id="calendar_month" name="calendar_month"><option value="">Semua bulan</option>@foreach (range(1, 12) as $monthNumber)<option value="{{ $monthNumber }}" @selected($calendarMonth === $monthNumber)>{{ \Carbon\CarbonImmutable::create($year, $monthNumber, 1)->locale('id')->translatedFormat('F') }}</option>@endforeach</select></div>
                <label class="flex items-center gap-2 pb-2 text-sm text-slate-700"><input type="checkbox" name="monday_only" value="1" @checked($mondayOnly)>Hari Senin saja</label>
                <button class="btn-secondary" type="submit">Tampilkan</button>
            </form>
            @php($holidayMap = $holidays->keyBy(fn ($holiday) => $holiday->holiday_date->toDateString()))
            <form class="mt-5" method="POST" action="{{ route('counter-duty.holidays.calendar') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="year" value="{{ $year }}">
                @if ($calendarMonth)<input type="hidden" name="calendar_month" value="{{ $calendarMonth }}">@endif
                @if ($mondayOnly)<input type="hidden" name="monday_only" value="1">@endif
                @php($visibleCalendarDays = array_values(array_filter($calendarDays, fn ($day) => (!$calendarMonth || \Carbon\CarbonImmutable::parse($day['date'])->month === $calendarMonth) && (!$mondayOnly || $day['day'] === 'Senin'))))
                <p class="mb-3 text-sm text-slate-600">{{ count($visibleCalendarDays) }} {{ $mondayOnly ? 'hari Senin' : 'hari kerja' }} ditampilkan{{ $calendarMonth ? ' pada bulan '.\Carbon\CarbonImmutable::create($year, $calendarMonth, 1)->locale('id')->translatedFormat('F') : '' }}. {{ $canManage ? 'Perubahan berlaku setelah menekan Simpan kalender.' : 'Kalender hanya dapat diubah oleh Super Admin.' }}</p>
                @if ($canManage)<button class="btn-primary mb-4" type="submit">Simpan kalender {{ $year }}</button>@endif
                <div class="max-h-[32rem] overflow-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-left text-sm">
                        <caption class="sr-only">Tanggal hari kerja dan isLibur tahun {{ $year }}</caption>
                        <thead class="sticky top-0 bg-slate-100 text-slate-600"><tr><th scope="col" class="px-4 py-3">Tanggal</th><th scope="col" class="px-4 py-3">Hari</th><th scope="col" class="px-4 py-3">isLibur</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($calendarDays as $day)
                                @php($isHoliday = (string) (old('year') == $year ? old('days.'.$day['date'], $holidayMap->has($day['date']) ? '1' : '0') : ($holidayMap->has($day['date']) ? '1' : '0')))
                                @if (! in_array($day, $visibleCalendarDays, true))
                                    <input type="hidden" name="days[{{ $day['date'] }}]" value="{{ $isHoliday }}">
                                    @continue
                                @endif
                                <tr @if ($day['date'] === now('Asia/Pontianak')->toDateString() && in_array($day, $visibleCalendarDays, true)) data-calendar-today tabindex="-1" @endif><td class="whitespace-nowrap px-4 py-2">{{ $day['label'] }}</td><td class="px-4 py-2">{{ $day['day'] }}</td><td class="px-4 py-2">
                                    @if ($canManage)
                                        <input class="form-input min-w-28" type="text" name="days[{{ $day['date'] }}]" value="{{ $isHoliday }}" placeholder="ya / 1" aria-label="isLibur {{ $day['label'] }}" autocomplete="off" spellcheck="false">
                                    @else
                                        {{ $holidayMap->has($day['date']) ? 'Ya (1)' : 'Tidak (0)' }}
                                    @endif
                                </td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </section>
    @endif
@endsection
