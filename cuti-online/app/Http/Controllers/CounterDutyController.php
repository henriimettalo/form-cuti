<?php

namespace App\Http\Controllers;

use App\Models\CeremonySchedule;
use App\Models\DutyGroup;
use App\Models\DutyHoliday;
use App\Models\Employee;
use App\Services\CounterDutyScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CounterDutyController extends Controller
{
    public function holidays(Request $request): View
    {
        $request->merge(['tab' => 'holidays']);

        return $this->index($request);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['agenda', 'groups', 'holidays'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'calendar_month' => ['nullable', 'integer', 'between:1,12'],
            'monday_only' => ['nullable', 'boolean'],
        ]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $filters['month'] ?? now('Asia/Pontianak')->format('Y-m'), 'Asia/Pontianak');
        $year = (int) ($filters['year'] ?? $month->year);

        return view('counter-duty.index', [
            'tab' => $filters['tab'] ?? 'agenda',
            'month' => $month,
            'year' => $year,
            'calendarMonth' => isset($filters['calendar_month']) ? (int) $filters['calendar_month'] : null,
            'mondayOnly' => (bool) ($filters['monday_only'] ?? false),
            'calendarDays' => $this->calendarDays($year),
            'groups' => DutyGroup::query()->orderBy('number')->get(),
            'employees' => $request->user()->isSuperAdmin() ? Employee::query()->where('is_active', true)->orderBy('full_name')->get(['full_name']) : collect(),
            'holidays' => DutyHoliday::query()->orderBy('holiday_date')->get(),
            'schedules' => CeremonySchedule::query()->where('type', 'piket_loket')
                ->where(function ($query): void {
                    $query->whereNull('notes')->orWhere('notes', '!=', 'Libur bersama');
                })
                ->whereDate('event_date', '>=', $month->toDateString())
                ->whereDate('event_date', '<=', $month->endOfMonth()->toDateString())
                ->orderBy('event_date')->orderBy('id')->paginate(31)->withQueryString(),
            'monthSchedules' => CeremonySchedule::query()->where('type', 'piket_loket')
                ->whereYear('event_date', $month->year)->whereMonth('event_date', $month->month)
                ->orderBy('event_date')->get()->keyBy(fn ($schedule) => $schedule->event_date->toDateString()),
            'monthCalendarWeeks' => $this->monthCalendarWeeks($month),
            'canManage' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function updateGroup(Request $request, DutyGroup $dutyGroup): RedirectResponse
    {
        $this->ensureCanManage($request);
        $input = $request->validate([
            'coordinator' => ['required', 'string', 'max:255'],
            'members' => ['required', 'string', 'max:5000'],
        ], [], ['coordinator' => 'penanggung jawab', 'members' => 'petugas piket']);
        $members = array_values(array_filter(array_map('trim', preg_split('/\R/u', $input['members']))));

        if ($members === [] || count($members) > 20 || count($members) !== count(array_unique($members)) || in_array($input['coordinator'], $members, true)) {
            throw ValidationException::withMessages(['members' => 'Isi 1–20 petugas yang berbeda, satu nama per baris. Penanggung jawab tidak perlu diulang.']);
        }
        foreach ($members as $member) {
            if (mb_strlen($member) > 255) {
                throw ValidationException::withMessages(['members' => 'Nama petugas maksimal 255 karakter.']);
            }
        }

        $dutyGroup->update(['coordinator' => $input['coordinator'], 'members' => $members]);
        CeremonySchedule::query()
            ->where('type', 'piket_loket')
            ->where('duty_group_id', $dutyGroup->id)
            ->whereDate('event_date', '>=', now('Asia/Pontianak')->toDateString())
            ->where(function ($query): void {
                $query->whereNull('notes')->orWhere('notes', '!=', 'Libur bersama');
            })
            ->get()
            ->each(function (CeremonySchedule $schedule) use ($dutyGroup): void {
                $schedule->update([
                    'title' => 'Piket Loket — Kelompok '.$dutyGroup->number,
                    'duty_roster' => $dutyGroup->roster(),
                ]);
            });

        return to_route('counter-duty.index', ['tab' => 'groups'])->with('status', 'Kelompok diperbarui. Jadwal piket yang akan datang ikut diperbarui.');
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $this->ensureCanManage($request);
        $input = $request->validate([
            'coordinator' => ['required', 'string', 'max:255'],
            'members' => ['required', 'string', 'max:5000'],
        ], [], ['coordinator' => 'penanggung jawab', 'members' => 'petugas piket']);
        $members = array_values(array_filter(array_map('trim', preg_split('/\R/u', $input['members']))));
        if ($members === [] || count($members) > 20 || count($members) !== count(array_unique($members)) || in_array($input['coordinator'], $members, true)) {
            throw ValidationException::withMessages(['members' => 'Isi 1–20 petugas yang berbeda dan penanggung jawab tidak perlu diulang.']);
        }
        $number = ((int) DutyGroup::query()->max('number')) + 1;
        DutyGroup::query()->create(['number' => $number, 'coordinator' => $input['coordinator'], 'members' => $members]);

        return to_route('counter-duty.index', ['tab' => 'groups'])->with('status', "Kelompok {$number} ditambahkan.");
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $this->ensureCanManage($request);
        $input = $request->validate([
            'holiday_date' => ['required', 'date_format:Y-m-d', 'unique:duty_holidays,holiday_date'],
            'name' => ['required', 'string', 'max:255'],
        ], [], ['holiday_date' => 'tanggal libur', 'name' => 'keterangan libur']);

        if (CeremonySchedule::query()->where('type', 'piket_loket')->whereDate('event_date', $input['holiday_date'])->exists()) {
            throw ValidationException::withMessages(['holiday_date' => 'Tanggal ini sudah memiliki jadwal piket. Hapus jadwal tersebut sebelum menetapkan hari libur bersama.']);
        }

        DutyHoliday::query()->create($input);

        return to_route($request->routeIs('holidays.*') ? 'holidays.index' : 'counter-duty.index', $request->routeIs('holidays.*') ? [] : ['tab' => 'holidays'])->with('status', 'Hari libur ditambahkan.');
    }

    public function destroyHoliday(Request $request, DutyHoliday $dutyHoliday): RedirectResponse
    {
        $this->ensureCanManage($request);
        $dutyHoliday->delete();

        return to_route('holidays.index')->with('status', 'Hari libur dihapus. Jadwal tersimpan tidak diubah.');
    }

    public function updateCalendar(Request $request): RedirectResponse
    {
        $this->ensureCanManage($request);
        $days = $request->input('days');
        if (is_array($days)) {
            $request->merge(['days' => array_map(fn ($value) => is_string($value) ? strtolower(trim($value)) : $value, $days)]);
        }
        $input = $request->validate([
            'year' => ['required', 'integer', 'between:1900,2100'],
            'days' => ['required', 'array', 'max:366'],
            'days.*' => ['nullable', Rule::in(['0', '1', 'ya', 'tidak'])],
        ], [], ['year' => 'tahun kalender', 'days' => 'tanggal kalender', 'days.*' => 'isLibur']);
        $year = (int) $input['year'];
        $dates = array_column($this->calendarDays($year), 'date');
        if (count($input['days']) !== count($dates) || array_diff(array_keys($input['days']), $dates) !== []) {
            throw ValidationException::withMessages(['days' => 'Kalender tidak lengkap atau berisi tanggal di luar hari kerja tahun yang dipilih. Muat ulang kalender.']);
        }
        $selected = array_keys(array_filter($input['days'], fn ($value) => in_array((string) $value, ['1', 'ya'], true)));

        DB::transaction(function () use ($selected, $dates): void {
            $existing = DutyHoliday::query()->whereIn('holiday_date', $dates)->lockForUpdate()->get()->keyBy(fn ($holiday) => $holiday->holiday_date->toDateString());
            $added = array_values(array_diff($selected, $existing->keys()->all()));
            $changedDates = array_values(array_unique([...$added, ...array_diff($existing->keys()->all(), $selected)]));
            $manualConflicts = CeremonySchedule::query()->where('type', 'piket_loket')->whereIn(DB::raw('date(event_date)'), $added)
                ->whereNull('duty_roster')->pluck('event_date');
            if ($manualConflicts->isNotEmpty()) {
                throw ValidationException::withMessages(['days' => 'Ada jadwal piket manual pada tanggal libur yang dipilih. Hapus atau perbaiki jadwal manual tersebut terlebih dahulu.']);
            }
            DutyHoliday::query()->whereIn('holiday_date', array_values(array_diff($dates, $selected)))->delete();
            foreach ($added as $date) {
                DutyHoliday::query()->create(['holiday_date' => $date, 'name' => 'Libur piket']);
            }
            $this->recalculateCounterDutySchedules($changedDates);
        });

        $routeParameters = $request->routeIs('holidays.*') ? ['year' => $year] : ['tab' => 'holidays', 'year' => $year];
        if ($request->boolean('monday_only')) {
            $routeParameters['monday_only'] = 1;
        }
        if ($request->filled('calendar_month')) {
            $routeParameters['calendar_month'] = (int) $request->input('calendar_month');
        }

        return to_route($request->routeIs('holidays.*') ? 'holidays.index' : 'counter-duty.index', $routeParameters)
            ->with('status', 'Kalender '.$year.' disimpan: '.count($selected).' hari kerja ditandai libur. Jadwal tersimpan tidak diubah.');
    }

    private function calendarDays(int $year): array
    {
        $days = [];
        for ($date = CarbonImmutable::create($year, 1, 1, 0, 0, 0, 'Asia/Pontianak'); $date->year === $year; $date = $date->addDay()) {
            if (! $date->isWeekend()) {
                $days[] = ['date' => $date->toDateString(), 'label' => $date->locale('id')->translatedFormat('d F Y'), 'day' => $date->locale('id')->translatedFormat('l')];
            }
        }

        return $days;
    }

    private function monthCalendarWeeks(CarbonImmutable $month): array
    {
        $firstDay = $month->startOfMonth();
        $lastDay = $month->endOfMonth();
        $calendarStart = $firstDay->startOfWeek(CarbonImmutable::MONDAY);
        $calendarEnd = $lastDay->endOfWeek(CarbonImmutable::SUNDAY);
        $weeks = [];

        for ($weekStart = $calendarStart; $weekStart->lte($calendarEnd); $weekStart = $weekStart->addWeek()) {
            $days = [];
            for ($offset = 0; $offset < 7; $offset++) {
                $date = $weekStart->addDays($offset);
                $days[] = [
                    'date' => $date,
                    'inMonth' => $date->month === $month->month,
                ];
            }
            $weeks[] = $days;
        }

        return $weeks;
    }

    private function recalculateCounterDutySchedules(array $changedDates): void
    {
        $today = now('Asia/Pontianak')->toDateString();
        $changedDates = array_values(array_filter($changedDates, fn ($date) => $date >= $today));
        if ($changedDates === []) {
            return;
        }

        sort($changedDates);
        $firstChangedDate = $changedDates[0];
        $groups = DutyGroup::query()->orderBy('number')->get();
        if ($groups->isEmpty()) {
            return;
        }
        $schedules = CeremonySchedule::query()->where('type', 'piket_loket')
            ->whereDate('event_date', '>=', $firstChangedDate)
            ->orderBy('event_date')->orderBy('id')->lockForUpdate()->get()
            ->keyBy(fn ($schedule) => $schedule->event_date->toDateString());
        $holidays = DutyHoliday::query()->whereDate('holiday_date', '>=', $firstChangedDate)
            ->pluck('holiday_date')->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())->flip();
        $previous = CeremonySchedule::query()->where('type', 'piket_loket')
            ->whereDate('event_date', '<', $firstChangedDate)->orderByDesc('event_date')->orderByDesc('id')->first();
        $nextNumber = $previous?->duty_roster
            ? (((int) $previous->duty_roster['number'] % $groups->count()) + 1)
            : (int) ($groups->first()->number);

        $lastDate = CarbonImmutable::parse($schedules->keys()->last() ?? $firstChangedDate, 'Asia/Pontianak');
        for ($date = CarbonImmutable::parse($firstChangedDate, 'Asia/Pontianak'); $date->lte($lastDate); $date = $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $dateKey = $date->toDateString();
            $schedule = $schedules->get($dateKey);
            if ($holidays->has($dateKey)) {
                if ($schedule) {
                    $schedule->update([
                        'title' => 'Piket Loket — Libur',
                        'duty_group_id' => null,
                        'duty_roster' => null,
                        'duty_date' => $dateKey,
                        'notes' => 'Libur bersama',
                    ]);
                }

                continue;
            }

            $group = $groups->firstWhere('number', $nextNumber);
            if ($schedule && $group) {
                $schedule->update([
                    'title' => 'Piket Loket — Kelompok '.$group->number,
                    'duty_group_id' => $group->id,
                    'duty_roster' => $group->roster(),
                    'duty_date' => $dateKey,
                ]);
            }
            $nextNumber = ($nextNumber % $groups->count()) + 1;
        }
    }

    public function generate(Request $request): View
    {
        $this->ensureCanManage($request);

        return view('counter-duty.generate', [
            'groups' => DutyGroup::query()->orderBy('number')->get(),
            'preview' => $request->session()->get('counter-duty-preview'),
            'today' => now('Asia/Pontianak')->toDateString(),
        ]);
    }

    public function preview(Request $request, CounterDutyScheduleService $generator): RedirectResponse
    {
        $this->ensureCanManage($request);
        $request->session()->forget('counter-duty-preview');
        $input = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'starting_group' => ['required', 'integer', 'exists:duty_groups,number'],
        ], [], ['start_date' => 'tanggal awal', 'end_date' => 'tanggal akhir', 'starting_group' => 'kelompok pertama']);

        if (CarbonImmutable::parse($input['start_date'])->diffInDays(CarbonImmutable::parse($input['end_date'])) > 366) {
            throw ValidationException::withMessages(['end_date' => 'Buat jadwal untuk rentang maksimal satu tahun.']);
        }
        $request->session()->put('counter-duty-preview', $generator->preview($input));

        return to_route('counter-duty.generate');
    }

    public function store(Request $request, CounterDutyScheduleService $generator): RedirectResponse
    {
        $this->ensureCanManage($request);
        $preview = $request->session()->get('counter-duty-preview');
        if (! $preview) {
            return to_route('counter-duty.generate')->withErrors(['preview' => 'Pratinjau tidak tersedia. Periksa rentang tanggal terlebih dahulu.']);
        }
        $count = $generator->store($preview, $request->user()->id);
        $request->session()->forget('counter-duty-preview');

        return to_route('counter-duty.index', ['month' => substr($preview['input']['start_date'], 0, 7)])
            ->with('status', $count.' jadwal piket disimpan. Jadwal lama tidak ditimpa.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->ensureCanManage($request);
        $request->session()->forget('counter-duty-preview');

        return to_route('counter-duty.index');
    }

    private function ensureCanManage(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
    }
}
