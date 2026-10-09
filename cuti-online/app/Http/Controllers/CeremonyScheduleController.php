<?php

namespace App\Http\Controllers;

use App\Models\CeremonySchedule;
use App\Models\Department;
use App\Models\DutyGroup;
use App\Models\DutyHoliday;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CeremonyScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $request->merge(['type' => CeremonySchedule::normalizeType($request->input('type'))]);

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'type' => ['nullable', Rule::in(array_keys(CeremonySchedule::TYPES))],
            'calendar_month' => ['nullable', 'integer', 'between:1,12'],
            'calendar_sort' => ['nullable', Rule::in(['asc', 'desc'])],
        ], [], ['month' => 'bulan', 'type' => 'jenis kegiatan']);

        $month = CarbonImmutable::createFromFormat('!Y-m', $filters['month'] ?? now('Asia/Pontianak')->format('Y-m'), 'Asia/Pontianak');
        $type = $filters['type'] ?? null;

        $schedules = CeremonySchedule::query()
            ->with('department')
            ->whereIn('type', ['apel', 'upacara'])
            ->whereDate('event_date', '>=', $month->toDateString())
            ->whereDate('event_date', '<=', $month->endOfMonth()->toDateString())
            ->when($type, fn ($query) => $query->whereIn('type', $type === 'apel' ? ['apel', 'upacara'] : [$type]))
            ->orderBy('event_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('ceremony-schedules.index', [
            'schedules' => $schedules,
            'month' => $month,
            'type' => $type,
            'types' => CeremonySchedule::TYPES,
            'canManage' => $request->user()->isSuperAdmin(),
            'rotationYears' => CeremonySchedule::query()->where('type', 'apel')->whereNotNull('rotation_start_department_id')->get()->groupBy(fn ($schedule) => $schedule->event_date->format('Y')),
            'calendarYear' => (int) ($request->input('year', $month->year)),
            'calendarYears' => range(now('Asia/Pontianak')->year, now('Asia/Pontianak')->year + 4),
            'calendarDays' => $this->mondayCalendar((int) ($request->input('year', $month->year))),
            'calendarMonth' => (int) ($request->input('calendar_month', ((int) $request->input('year', $month->year) === now('Asia/Pontianak')->year ? now('Asia/Pontianak')->month : 0))),
            'calendarSort' => $request->input('calendar_sort', 'asc'),
            'calendarSchedules' => CeremonySchedule::query()->where('type', 'apel')->whereYear('event_date', (int) ($request->input('year', $month->year)))->with('department')->get()->keyBy(fn ($schedule) => $schedule->event_date->toDateString()),
            'sharedHolidays' => DutyHoliday::query()->whereYear('holiday_date', (int) ($request->input('year', $month->year)))->get()->keyBy(fn ($holiday) => $holiday->holiday_date->toDateString()),
            'ceremonyDepartments' => $this->ceremonyDepartments(),
            'groupDepartments' => $this->ceremonyDepartments()->whereNotNull('ceremony_group_number')->keyBy('ceremony_group_number'),
            'groupCount' => max(2, (int) (Department::query()->whereIn('department_type', ['kecamatan', 'kelurahan'])->max('ceremony_group_number') ?? 4)),
        ]);
    }

    public function updateCalendar(Request $request): RedirectResponse
    {
        $this->ensureCanManage($request);
        $request->merge(['group_count' => $request->input('group_count', 4)]);
        $input = $request->validate([
            'year' => ['required', 'integer', 'between:2026,2030'],
            'days' => ['required', 'array', 'size:52'],
            'days.*.department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where(fn ($query) => $query->whereIn('department_type', ['kecamatan', 'kelurahan'])->where('is_active', true))],
            'days.*.group_number' => ['nullable', 'integer', 'between:1,255'],
            'group_count' => ['required', 'integer', 'between:2,255'],
            'group_departments' => ['required', 'array'],
            'group_departments.*' => ['required', 'integer', Rule::exists('departments', 'id')->where(fn ($query) => $query->whereIn('department_type', ['kecamatan', 'kelurahan'])->where('is_active', true))],
        ]);
        $groupCount = (int) $input['group_count'];
        $groupDepartments = array_filter($input['group_departments'], fn ($departmentId, $group) => (int) $group >= 1 && (int) $group <= $groupCount, ARRAY_FILTER_USE_BOTH);
        if (count($groupDepartments) !== $groupCount || count(array_unique($groupDepartments)) !== $groupCount) {
            throw ValidationException::withMessages(['group_departments' => 'Isi satu kecamatan atau kelurahan yang berbeda untuk setiap kelompok yang dipilih.']);
        }
        $year = (int) $input['year'];
        $expectedDates = array_column($this->mondayCalendar($year), 'date');
        $sharedHolidayDates = DutyHoliday::query()->whereYear('holiday_date', $year)->pluck('holiday_date')->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())->all();
        if (array_diff(array_keys($input['days']), $expectedDates) !== [] || array_diff($expectedDates, array_keys($input['days'])) !== []) {
            throw ValidationException::withMessages(['days' => 'Daftar tanggal Senin tidak lengkap. Muat ulang kalender sebelum menyimpan.']);
        }
        $today = now('Asia/Pontianak')->toDateString();
        $pastDates = array_filter($expectedDates, fn ($date) => $date < $today);
        foreach ($pastDates as $date) {
            $existing = CeremonySchedule::query()->where('type', 'apel')->whereDate('event_date', $date)->first();
            $submitted = $input['days'][$date];
            if ($existing && ((int) ($submitted['group_number'] ?? 0) !== (int) ($existing->ceremony_group_number ?? 0) || (int) ($submitted['department_id'] ?? 0) !== (int) ($existing->department_id ?? 0))) {
                throw ValidationException::withMessages(["days.{$date}.group_number" => 'Jadwal apel yang sudah lewat tidak dapat diubah.']);
            }
        }
        $groups = [];
        foreach ($expectedDates as $date) {
            $day = $input['days'][$date];
            $isSharedHoliday = in_array($date, $sharedHolidayDates, true);
            if (! $isSharedHoliday) {
                $group = ! empty($day['department_id'])
                    ? Department::query()->find($day['department_id'])?->ceremony_group_number
                    : ($day['group_number'] ?? null);
                $groups[$date] = $group ? (int) $group : null;
            } else {
                $groups[$date] = null;
            }
        }

        DB::transaction(function () use ($input, $request, $groups, $sharedHolidayDates, $groupDepartments): void {
            Department::query()->whereIn('department_type', ['kecamatan', 'kelurahan'])->update(['ceremony_group_number' => null]);
            foreach ($groupDepartments as $group => $departmentId) {
                Department::query()->whereKey($departmentId)->update(['ceremony_group_number' => $group]);
            }
            foreach ($input['days'] as $date => $day) {
                if ($groups[$date] === null && ! in_array($date, $sharedHolidayDates, true)) {
                    continue;
                }
                $schedule = CeremonySchedule::query()->firstOrNew(['type' => 'apel', 'event_date' => $date]);
                $schedule->fill([
                    'title' => 'Apel / Upacara',
                    'department_id' => in_array($date, $sharedHolidayDates, true) ? null : $groupDepartments[$groups[$date]],
                    'rotation_start_department_id' => null,
                    'notes' => in_array($date, $sharedHolidayDates, true) ? 'Libur' : null,
                    'ceremony_group_number' => $groups[$date],
                    'created_by' => $schedule->exists ? $schedule->created_by : $request->user()->id,
                ])->save();
            }
        });

        return to_route('ceremony-schedules.index', ['year' => $year, 'tab' => 'kalender'])
            ->with('status', "Kalender apel tahun {$year} berhasil disimpan.");
    }

    public function updateGroups(Request $request): JsonResponse
    {
        $this->ensureCanManage($request);
        $input = $request->validate([
            'group_count' => ['required', 'integer', 'between:2,255'],
            'group_departments' => ['required', 'array'],
            'group_departments.*' => ['required', 'integer', Rule::exists('departments', 'id')->where(fn ($query) => $query->whereIn('department_type', ['kecamatan', 'kelurahan'])->where('is_active', true))],
        ]);
        $groupCount = (int) $input['group_count'];
        $groupDepartments = array_filter($input['group_departments'], fn ($departmentId, $group) => (int) $group >= 1 && (int) $group <= $groupCount, ARRAY_FILTER_USE_BOTH);
        if (count($groupDepartments) !== $groupCount || count(array_unique($groupDepartments)) !== $groupCount) {
            throw ValidationException::withMessages(['group_departments' => 'Isi satu kecamatan atau kelurahan yang berbeda untuk setiap kelompok yang dipilih.']);
        }

        DB::transaction(function () use ($groupDepartments): void {
            Department::query()->whereIn('department_type', ['kecamatan', 'kelurahan'])->update(['ceremony_group_number' => null]);
            foreach ($groupDepartments as $group => $departmentId) {
                Department::query()->whereKey($departmentId)->update(['ceremony_group_number' => $group]);
            }

            CeremonySchedule::query()
                ->where('type', 'apel')
                ->whereDate('event_date', '>=', now('Asia/Pontianak')->toDateString())
                ->whereNotNull('ceremony_group_number')
                ->get()
                ->each(function (CeremonySchedule $schedule) use ($groupDepartments): void {
                    $schedule->update(['department_id' => $groupDepartments[$schedule->ceremony_group_number] ?? null]);
                });
        });

        return response()->json(['message' => 'Pengaturan kelompok berhasil disimpan.']);
    }

    private function mondayCalendar(int $year): array
    {
        $days = [];
        $date = CarbonImmutable::create($year, 1, 1, 0, 0, 0, 'Asia/Pontianak')->next(CarbonImmutable::MONDAY);
        if (CarbonImmutable::create($year, 1, 1, 0, 0, 0, 'Asia/Pontianak')->isMonday()) {
            $date = CarbonImmutable::create($year, 1, 1, 0, 0, 0, 'Asia/Pontianak');
        }
        while ($date->year === $year) {
            $days[] = ['date' => $date->toDateString(), 'label' => $date->locale('id')->translatedFormat('d F Y'), 'day' => $date->locale('id')->translatedFormat('l')];
            $date = $date->addWeek();
        }

        return $days;
    }

    public function create(Request $request): View
    {
        $this->ensureCanManage($request);

        return $this->form(new CeremonySchedule([
            'type' => 'apel',
            'event_date' => now('Asia/Pontianak')->toDateString(),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureCanManage($request);
        $schedule = CeremonySchedule::query()->create([
            ...$this->validatedData($request),
            'created_by' => $request->user()->id,
        ]);

        if ($schedule->type === 'apel' && $schedule->rotation_start_department_id) {
            $schedule->update(['department_id' => $this->departmentForSchedule($schedule)->id]);
        }

        return to_route('ceremony-schedules.index', ['month' => $schedule->event_date->format('Y-m')])
            ->with('status', 'Jadwal kegiatan berhasil ditambahkan.');
    }

    public function edit(Request $request, CeremonySchedule $ceremonySchedule): View
    {
        $this->ensureCanManage($request);

        return $this->form($ceremonySchedule);
    }

    public function update(Request $request, CeremonySchedule $ceremonySchedule): RedirectResponse
    {
        $this->ensureCanManage($request);
        $data = $this->validatedData($request, $ceremonySchedule);
        if ($ceremonySchedule->duty_roster && $data['type'] === 'piket_loket' && $data['duty_group_id'] === $ceremonySchedule->duty_group_id) {
            $data['duty_roster'] = $ceremonySchedule->duty_roster;
        }
        $ceremonySchedule->update($data);
        if ($ceremonySchedule->type === 'apel' && $ceremonySchedule->rotation_start_department_id) {
            $ceremonySchedule->update(['department_id' => $this->departmentForSchedule($ceremonySchedule)->id]);
        }

        return to_route('ceremony-schedules.index', ['month' => $ceremonySchedule->event_date->format('Y-m')])
            ->with('status', 'Jadwal kegiatan berhasil diperbarui.');
    }

    public function destroy(Request $request, CeremonySchedule $ceremonySchedule): RedirectResponse
    {
        $this->ensureCanManage($request);
        $month = $ceremonySchedule->event_date->format('Y-m');
        $ceremonySchedule->delete();

        return to_route('ceremony-schedules.index', ['month' => $month])
            ->with('status', 'Jadwal kegiatan berhasil dihapus.');
    }

    private function ensureCanManage(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
    }

    private function ceremonyDepartments()
    {
        return Department::query()
            ->whereIn('department_type', ['kecamatan', 'kelurahan'])
            ->where('is_active', true)
            ->orderBy('department_type')
            ->orderBy('name')
            ->get();
    }

    private function form(CeremonySchedule $schedule): View
    {
        return view('ceremony-schedules.form', [
            'schedule' => $schedule,
            'selectedType' => CeremonySchedule::normalizeType(old('type', $schedule->type)),
            'types' => $schedule->type === 'piket_loket'
                ? ['piket_loket' => CeremonySchedule::TYPES['piket_loket']]
                : ['apel' => CeremonySchedule::TYPES['apel']],
            'isCounterDuty' => $schedule->type === 'piket_loket',
            'dutyGroups' => $schedule->type === 'piket_loket' ? DutyGroup::query()->orderBy('number')->get() : collect(),
            'kelurahans' => Department::query()->where('department_type', 'kelurahan')->where('is_active', true)->orderBy('name')->get(),
            'rotationStart' => CeremonySchedule::query()->where('type', 'apel')->whereYear('event_date', $schedule->event_date?->year ?? now()->year)->whereNotNull('rotation_start_department_id')->orderBy('event_date')->first()?->rotation_start_department_id,
            'departments' => Department::query()
                ->where(function ($query) use ($schedule): void {
                    $query->where('is_active', true);
                    if ($schedule->department_id !== null) {
                        $query->orWhere('id', $schedule->department_id);
                    }
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function validatedData(Request $request, ?CeremonySchedule $schedule = null): array
    {
        $request->merge(['type' => CeremonySchedule::normalizeType($request->input('type'))]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::in(array_keys(CeremonySchedule::TYPES))],
            'event_date' => ['required', 'date_format:Y-m-d'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where(function ($query) use ($schedule): void {
                $query->where(function ($query) use ($schedule): void {
                    $query->where('is_active', true);
                    if ($schedule?->department_id !== null) {
                        $query->orWhere('id', $schedule->department_id);
                    }
                });
            })],
            'notes' => ['nullable', 'string', 'max:3000'],
            'duty_group_id' => ['nullable', 'integer', 'exists:duty_groups,id'],
            'rotation_start_department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where(fn ($query) => $query->where('department_type', 'kelurahan')->where('is_active', true))],
        ], [], [
            'title' => 'nama kegiatan',
            'type' => 'jenis kegiatan',
            'event_date' => 'tanggal kegiatan',
            'department_id' => 'unit petugas',
            'notes' => 'catatan',
            'duty_group_id' => 'kelompok piket',
        ]);

        $data['duty_group_id'] = $data['type'] === 'piket_loket'
            ? (array_key_exists('duty_group_id', $data) ? $data['duty_group_id'] : $schedule?->duty_group_id)
            : null;
        $data['duty_group_id'] = $data['duty_group_id'] ? (int) $data['duty_group_id'] : null;
        $data['duty_roster'] = $data['duty_group_id'] ? DutyGroup::query()->findOrFail($data['duty_group_id'])->roster() : null;
        $data['duty_date'] = $data['type'] === 'piket_loket' ? $data['event_date'] : null;
        $hasRotation = $data['type'] === 'apel' && (isset($data['rotation_start_department_id']) || $schedule?->rotation_start_department_id || $this->yearRotationStart((int) substr($data['event_date'], 0, 4)));
        $data['rotation_start_department_id'] = $hasRotation
            ? ($data['rotation_start_department_id'] ?? $schedule?->rotation_start_department_id ?? $this->yearRotationStart((int) substr($data['event_date'], 0, 4)))
            : null;
        if ($data['type'] === 'apel') {
            if ($hasRotation && CarbonImmutable::parse($data['event_date'])->dayOfWeekIso !== 1) {
                throw ValidationException::withMessages(['event_date' => 'Apel mingguan dijadwalkan pada hari Senin.']);
            }
            if ($hasRotation) {
                $data['rotation_start_department_id'] = $data['rotation_start_department_id'] ?: null;
                if (! $data['rotation_start_department_id']) {
                    throw ValidationException::withMessages(['rotation_start_department_id' => 'Pilih kelurahan petugas pertama untuk memulai rotasi tahun ini.']);
                }
                $data['department_id'] = $this->departmentForDate($data['event_date'], (int) $data['rotation_start_department_id'])->id;
            }
        }
        if ($data['type'] === 'piket_loket') {
            if (DutyHoliday::query()->whereDate('holiday_date', $data['event_date'])->exists()) {
                throw ValidationException::withMessages(['event_date' => 'Tanggal ini ditetapkan sebagai hari libur piket.']);
            }
            if (CeremonySchedule::query()->where('type', 'piket_loket')->whereDate('event_date', $data['event_date'])
                ->when($schedule, fn ($query) => $query->where('id', '!=', $schedule->id))->exists()) {
                throw ValidationException::withMessages(['event_date' => 'Sudah ada jadwal piket pada tanggal ini. Edit jadwal yang tersedia.']);
            }
        }

        return $data;
    }

    private function yearRotationStart(int $year): ?int
    {
        return CeremonySchedule::query()->where('type', 'apel')->whereYear('event_date', $year)
            ->whereNotNull('rotation_start_department_id')->orderBy('event_date')->value('rotation_start_department_id');
    }

    private function departmentForSchedule(CeremonySchedule $schedule): Department
    {
        return $this->departmentForDate($schedule->event_date->toDateString(), (int) $schedule->rotation_start_department_id);
    }

    private function departmentForDate(string $date, int $startDepartmentId): Department
    {
        $departments = Department::query()->where('department_type', 'kelurahan')->where('is_active', true)->orderBy('name')->get();
        $startIndex = $departments->search(fn ($department) => $department->id === $startDepartmentId);
        if ($departments->isEmpty() || $startIndex === false) {
            throw ValidationException::withMessages(['rotation_start_department_id' => 'Kelurahan pertama tidak tersedia. Periksa kembali data kelurahan aktif.']);
        }
        $year = (int) substr($date, 0, 4);
        $firstSchedule = CeremonySchedule::query()->where('type', 'apel')->whereYear('event_date', $year)
            ->whereNotNull('rotation_start_department_id')->orderBy('event_date')->first();
        $rotationDate = $firstSchedule?->event_date ?? CarbonImmutable::parse($date);
        $week = (int) floor($rotationDate->startOfWeek(CarbonImmutable::MONDAY)->diffInDays(CarbonImmutable::parse($date)->startOfWeek(CarbonImmutable::MONDAY)) / 7);

        return $departments[($startIndex + $week) % $departments->count()];
    }
}
