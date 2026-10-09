@extends('layouts.app')

@section('title', 'Jadwal Kegiatan')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title" data-sticky-page-title>Jadwal kegiatan</h1>
            <p class="page-description">Lihat tanggal dan unit petugas untuk apel/upacara serta piket loket.</p>
        </div>
        <nav class="flex shrink-0 flex-wrap gap-2 self-start sm:self-center" aria-label="Jenis jadwal">
            <a class="{{ request('tab') === 'kalender' || request()->routeIs('ceremony-schedules.*') && request('tab') !== 'piket' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('ceremony-schedules.index', ['year' => $calendarYear, 'tab' => 'kalender']) }}" @if (request('tab') !== 'piket') aria-current="page" @endif>Apel / Upacara</a>
            <a class="btn-secondary" href="{{ route('counter-duty.index', ['month' => $month->format('Y-m')]) }}">Piket Loket</a>
        </nav>
    </div>

    @if (request('tab') === 'kalender')
    <section class="card mt-7" aria-labelledby="calendar-heading">
        <h2 class="section-heading" id="calendar-heading">Kalender apel {{ $calendarYear }}</h2>
        <p class="section-description mt-1">Daftar Senin tahun {{ $calendarYear }}. Status libur mengikuti menu Hari Libur; atur kelompok dan kelurahan petugas di kalender ini.</p>
        @if ($canManage)
        <form class="mt-5 flex flex-col gap-4 border-t border-slate-100 pt-5 sm:flex-row sm:items-end sm:justify-between" method="GET" action="{{ route('ceremony-schedules.index') }}">
            <input type="hidden" name="tab" value="kalender">
            @php($calendarPickerMonth = $calendarMonth > 0 ? $calendarMonth : now('Asia/Pontianak')->month)
            @if ($calendarPickerMonth > 1)
                <a class="btn-secondary" href="{{ route('ceremony-schedules.index', ['year' => $calendarYear, 'tab' => 'kalender', 'calendar_month' => $calendarPickerMonth - 1]) }}">Bulan sebelumnya</a>
            @endif
            <div class="sm:w-44">
                <label class="form-label" for="calendar_month_picker-trigger">Bulan dan tahun</label>
                <x-month-picker id="calendar_month_picker" name="calendar_month_picker" value="{{ sprintf('%04d-%02d', $calendarYear, $calendarPickerMonth) }}" :years="$calendarYears" year-name="year" month-name="calendar_month" :auto-submit="true" />
            </div>
            @if ($calendarPickerMonth < 12)
                <a class="btn-secondary" href="{{ route('ceremony-schedules.index', ['year' => $calendarYear, 'tab' => 'kalender', 'calendar_month' => $calendarPickerMonth + 1]) }}">Bulan berikutnya</a>
            @endif
        </form>
        <form id="ceremony-calendar-form" method="POST" action="{{ route('ceremony-schedules.calendar') }}" class="mt-5" data-ceremony-calendar>
            @csrf
            @method('PUT')
            <input type="hidden" name="year" value="{{ $calendarYear }}">
            @php($visibleCalendarDays = collect($calendarDays)->filter(fn ($day) => ! $calendarMonth || \Carbon\CarbonImmutable::parse($day['date'])->month === $calendarMonth)->sortBy(fn ($day) => $day['date'], SORT_REGULAR, $calendarSort === 'desc')->values())
            @php($calendarSeedGroup = $visibleCalendarDays->isEmpty() ? 0 : ($calendarSchedules->filter(fn ($schedule) => $schedule->event_date->toDateString() < $visibleCalendarDays->first()['date'])->sortBy('event_date')->last()?->ceremony_group_number ?? 0))
            <input type="hidden" data-ceremony-seed-group value="{{ $calendarSeedGroup }}">
            @foreach ($calendarDays as $hiddenDay)
                @if (! $visibleCalendarDays->contains(fn ($day) => $day['date'] === $hiddenDay['date']))
                    @php($hiddenExisting = $calendarSchedules->get($hiddenDay['date']))
                    <input type="hidden" name="days[{{ $hiddenDay['date'] }}][group_number]" value="{{ $hiddenExisting?->ceremony_group_number }}">
                    <input type="hidden" name="days[{{ $hiddenDay['date'] }}][department_id]" value="{{ $hiddenExisting?->department_id }}">
                @endif
            @endforeach
            @error('days')<p class="form-error mb-3">{{ $message }}</p>@enderror
            <div class="mb-5 flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div><p class="font-semibold text-slate-800">Urutan kelompok</p><p class="mt-1 text-sm text-slate-600">Atur jumlah kelompok dan kelurahan petugas.</p></div>
                <button class="btn-secondary shrink-0" type="button" data-ceremony-groups-open>Atur kelompok</button>
            </div>
            <dialog class="m-auto max-h-[calc(100dvh-2rem)] w-[min(100%-2rem,42rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/50" data-ceremony-groups-dialog data-save-url="{{ route('ceremony-schedules.groups') }}" aria-labelledby="ceremony-groups-title">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4"><div><h3 class="text-lg font-semibold text-slate-900" id="ceremony-groups-title">Urutan kelompok apel</h3><p class="mt-1 text-sm text-slate-600">Setiap kelurahan hanya boleh dipakai satu kelompok.</p></div><button class="btn-secondary px-3" type="button" data-ceremony-groups-close aria-label="Tutup">×</button></div>
                    <div class="mt-5"><label class="form-label" for="group_count">Jumlah kelompok</label><div class="flex max-w-xs items-center gap-2"><button class="btn-secondary h-11 w-11 shrink-0 px-0 text-xl" type="button" data-ceremony-groups-decrement aria-label="Kurangi jumlah kelompok">−</button><input class="form-select text-center" id="group_count" name="group_count" type="number" value="{{ $groupCount }}" min="2" max="{{ max(2, $ceremonyDepartments->count()) }}" inputmode="numeric" readonly aria-live="polite"><button class="btn-secondary h-11 w-11 shrink-0 px-0 text-xl" type="button" data-ceremony-groups-increment aria-label="Tambah jumlah kelompok">+</button><span class="sr-only" data-ceremony-groups-count-label>{{ $groupCount }} kelompok</span></div><p class="form-help">Setelah selesai mengatur, tekan <strong>Simpan kelompok</strong>. Pengaturan akan tetap tersimpan saat menu dibuka kembali.</p></div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2" data-ceremony-group-fields data-department-options="@foreach ($ceremonyDepartments as $department){{ $department->id }}::{{ e(ucfirst($department->department_type).' — '.$department->name) }}||@endforeach">
                        @foreach (range(1, $groupCount) as $groupNumber)
                            <div><label class="form-label" for="group-{{ $groupNumber }}">Kelompok {{ $groupNumber }}</label><select class="form-select" id="group-{{ $groupNumber }}" name="group_departments[{{ $groupNumber }}]" required><option value="">Pilih kecamatan/kelurahan</option>@foreach ($ceremonyDepartments as $department)<option value="{{ $department->id }}" @selected((string) ($groupDepartments[$groupNumber]->id ?? '') === (string) $department->id)>{{ ucfirst($department->department_type) }} — {{ $department->name }}</option>@endforeach</select></div>
                        @endforeach
                    </div>
                    <p class="form-error mt-4 hidden" data-ceremony-groups-error></p><div class="mt-6 flex justify-end"><button class="btn-primary" type="button" data-ceremony-groups-save>Simpan kelompok</button></div>
                </div>
            </dialog>
            <?php if ($calendarMonth): ?>
                <?php
                    $calendarStart = \Carbon\CarbonImmutable::create($calendarYear, $calendarMonth, 1, 0, 0, 0, 'Asia/Pontianak')->startOfWeek(\Carbon\CarbonInterface::MONDAY);
                    $calendarEnd = \Carbon\CarbonImmutable::create($calendarYear, $calendarMonth, 1, 0, 0, 0, 'Asia/Pontianak')->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
                ?>
                <div class="mb-5 overflow-x-auto rounded-xl border border-slate-200">
                    <div class="grid min-w-[42rem] grid-cols-7 bg-emerald-50 text-center text-xs font-semibold text-slate-700">
                        <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday): ?>
                            <div class="border-b border-r border-slate-200 px-2 py-2">{{ $weekday }}</div>
                        <?php endforeach; ?>
                    </div>
                    <div class="grid min-w-[42rem] grid-cols-7">
                        <?php for ($date = $calendarStart; $date <= $calendarEnd; $date = $date->addDay()): ?>
                            <?php
                                $dateKey = $date->toDateString();
                                $inMonth = $date->month === $calendarMonth;
                                $schedule = $calendarSchedules->get($dateKey);
                                $holiday = $sharedHolidays->get($dateKey);
                            ?>
                            <div class="min-h-24 border-b border-r border-slate-200 p-2 {{ $inMonth ? 'bg-white' : 'bg-slate-50 text-slate-400' }}" data-calendar-date="{{ $dateKey }}">
                                <p class="text-xs font-semibold">{{ $date->day }}</p>
                                <?php if ($inMonth && $date->isMonday()): ?>
                                    <?php if ($holiday || $schedule?->notes === 'Libur'): ?>
                                        <p class="mt-2 text-xs font-medium text-rose-600">Libur</p>
                                    <?php elseif ($schedule?->ceremony_group_number): ?>
                                        <p class="mt-2 text-xs font-semibold text-sky-700" data-calendar-group>Kelompok {{ $schedule->ceremony_group_number }}</p>
                                        <p class="mt-1 line-clamp-2 text-xs text-slate-600" data-calendar-department>{{ $schedule->department?->name ?? 'Belum dipilih' }}</p>
                                    <?php else: ?>
                                        <p class="mt-2 text-xs font-semibold text-sky-700" data-calendar-group></p>
                                        <p class="mt-1 line-clamp-2 text-xs text-slate-600" data-calendar-department>Belum dipilih</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="overflow-x-auto">
                @php($assignedCeremonyDepartments = $ceremonyDepartments->whereNotNull('ceremony_group_number')->sortBy(['ceremony_group_number', 'name'])->values())
                <table class="min-w-full border-collapse text-left text-sm">
                    <thead class="bg-emerald-50 text-slate-800"><tr class="border border-slate-300"><th class="whitespace-nowrap border border-slate-300 px-3 py-2 font-semibold">Tanggal</th><th class="border border-slate-300 px-3 py-2 font-semibold">Kelompok / Petugas</th></tr></thead>
                    <tbody>
                    @if ($visibleCalendarDays->isEmpty())
                        <tr><td colspan="2" class="border border-slate-300 px-3 py-8 text-center text-slate-500">Tidak ada jadwal Senin pada bulan yang dipilih.</td></tr>
                    @endif
                    @foreach ($visibleCalendarDays as $day)
                        @php($existing = $calendarSchedules->get($day['date']))
                        @php($isHoliday = $existing?->notes === 'Libur' || $sharedHolidays->has($day['date']))
                        @php($isPast = $day['date'] < now('Asia/Pontianak')->toDateString())
                        <tr class="border border-slate-300 even:bg-slate-50">
                            <td class="whitespace-nowrap border border-slate-300 px-3 py-1.5 font-medium text-slate-800">{{ \Carbon\CarbonImmutable::parse($day['date'])->format('d/m/Y') }}</td>
                            <td class="border border-slate-300 px-3 py-1.5"><div class="grid gap-2"><span class="text-xs font-semibold text-slate-500" data-ceremony-group-display>{{ $existing?->ceremony_group_number ? 'Kelompok '.$existing->ceremony_group_number : 'Belum dipilih' }}</span><input type="hidden" name="days[{{ $day['date'] }}][group_number]" value="{{ $existing?->ceremony_group_number }}">@if ($isHoliday || $isPast)<input type="hidden" name="days[{{ $day['date'] }}][department_id]" value="{{ $existing?->department_id }}">@endif<select class="form-select min-w-52" name="days[{{ $day['date'] }}][department_id]" data-ceremony-department aria-label="Petugas {{ $day['label'] }}" @disabled($isHoliday || $isPast)><option value="">Pilih kelompok / petugas</option>@foreach ($assignedCeremonyDepartments as $department)<option value="{{ $department->id }}" data-group="{{ $department->ceremony_group_number }}" @selected((string) $existing?->department_id === (string) $department->id)>Kelompok {{ $department->ceremony_group_number }} — {{ ucfirst($department->department_type) }} — {{ $department->name }}</option>@endforeach</select></div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button class="btn-primary mt-5" type="submit">Simpan kalender {{ $calendarYear }}</button>
        </form>
        @endif
    </section>
    @else
    <section class="card mt-7" aria-labelledby="agenda-heading">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="section-heading" id="agenda-heading">Agenda {{ $month->locale('id')->translatedFormat('F Y') }}</h2>
            <div class="flex flex-wrap gap-2">
                @if ($canManage)<a class="btn-primary" href="{{ route('ceremony-schedules.index', ['year' => $calendarYear, 'tab' => 'kalender']) }}">Atur kalender apel {{ $calendarYear }}</a>@endif
            </div>
        </div>

        <form class="mt-5 flex flex-col gap-4 border-t border-slate-100 pt-5 sm:flex-row sm:flex-wrap sm:items-end" method="GET" action="{{ route('ceremony-schedules.index') }}">
            <div class="min-w-0 sm:w-44">
                <label class="form-label" for="month">Bulan</label>
                <x-month-picker id="month" name="month" value="{{ $month->format('Y-m') }}" :years="$calendarYears" />
            </div>
            <button class="btn-secondary" type="submit">Tampilkan</button>
            <div class="flex flex-wrap gap-2 sm:ml-auto">
                <a class="btn-secondary" href="{{ route('ceremony-schedules.index', ['month' => $month->subMonth()->format('Y-m'), 'type' => $type]) }}" aria-label="Lihat {{ $month->subMonth()->locale('id')->translatedFormat('F Y') }}">Bulan sebelumnya</a>
                <a class="btn-secondary" href="{{ route('ceremony-schedules.index', ['month' => $month->addMonth()->format('Y-m'), 'type' => $type]) }}" aria-label="Lihat {{ $month->addMonth()->locale('id')->translatedFormat('F Y') }}">Bulan berikutnya</a>
            </div>
        </form>

        @if ($schedules->isEmpty())
            <div class="card-inner mt-6 py-8 text-center">
                <p class="text-sm font-semibold text-slate-800">Belum ada jadwal {{ $type ? strtolower($types[$type]) : 'kegiatan' }} pada bulan ini.</p>
                <p class="mt-2 text-sm text-slate-600">{{ $canManage ? 'Tambahkan jadwal baru atau pilih bulan lain untuk melihat agenda.' : 'Pilih bulan lain atau hubungi Super Admin untuk informasi jadwal.' }}</p>
            </div>
        @else
            <ol class="mt-6 divide-y divide-slate-100">
                @foreach ($schedules as $schedule)
                    <li class="grid min-w-0 gap-4 py-5 sm:grid-cols-[5rem_minmax(0,1fr)]">
                        <div class="flex items-baseline gap-2 text-slate-600 sm:flex-col sm:items-center sm:gap-0" aria-label="{{ $schedule->event_date->locale('id')->translatedFormat('l, d F Y') }}">
                            <span class="text-2xl font-semibold tabular-nums text-slate-900 sm:text-3xl">{{ $schedule->event_date->format('d') }}</span>
                            <span class="text-sm">{{ $schedule->event_date->locale('id')->translatedFormat('M') }}</span>
                            <span class="text-sm">{{ $schedule->event_date->locale('id')->translatedFormat('l') }}</span>
                        </div>
                        <article class="grid min-w-0 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm text-slate-600">{{ $schedule->typeLabel() }}</p>
                                    <h3 class="mt-1 break-words text-base font-semibold text-slate-900">{{ $schedule->title }}</h3>
                                </div>
                                @if ($canManage)
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        <a class="btn-secondary" href="{{ route('ceremony-schedules.edit', $schedule) }}" aria-label="Edit jadwal {{ $schedule->title }}">Edit</a>
                                        <form method="POST" action="{{ route('ceremony-schedules.destroy', $schedule) }}" data-confirm-title="Hapus jadwal?" data-confirm-message="Jadwal {{ $schedule->title }} akan dihapus. Data pegawai tidak berubah." data-confirm-button="Hapus jadwal">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-danger" type="submit" aria-label="Hapus jadwal {{ $schedule->title }}">Hapus</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            @if ($schedule->type === 'piket_loket')
                                <dl class="min-w-0 self-start text-sm"><div><dt class="text-slate-500">Unit petugas</dt><dd class="mt-1 break-words text-slate-800">{{ $schedule->department?->name ?? 'Belum ditentukan' }}</dd></div></dl>
                            @endif
                            @if ($schedule->type === 'apel')
                                <p class="min-w-0 self-start text-sm"><span class="text-slate-500">Kelurahan petugas</span><span class="mt-1 block break-words font-medium text-slate-800">{{ $schedule->department?->name ?? 'Belum ditentukan' }}</span></p>
                            @endif
                            @if ($schedule->duty_roster)
                                <div class="min-w-0 self-start text-sm"><p class="font-semibold text-slate-800">Kelompok {{ $schedule->duty_roster['number'] }}</p><p class="mt-1 break-words text-slate-600">Penanggung jawab: {{ $schedule->duty_roster['coordinator'] }}</p><ul class="mt-2 grid gap-x-4 gap-y-1 text-slate-700 sm:grid-cols-2">@foreach ($schedule->duty_roster['members'] as $member)<li class="break-words">{{ $member }}</li>@endforeach</ul></div>
                            @endif
                            @if ($schedule->notes)
                                <div class="min-w-0 text-sm sm:col-span-2"><p class="text-slate-500">Catatan</p><p class="mt-1 whitespace-pre-line break-words text-slate-700">{{ $schedule->notes }}</p></div>
                            @endif
                        </article>
                    </li>
                @endforeach
            </ol>
            <div class="mt-5">{{ $schedules->links() }}</div>
        @endif
    </section>
    @endif
@endsection
