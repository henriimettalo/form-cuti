<?php

namespace App\Http\Controllers;

use App\Models\CeremonySchedule;
use App\Models\Department;
use App\Models\KelurahanDutySchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicScheduleController extends Controller
{
    public function kelurahan(): View
    {
        $today = CarbonImmutable::today('Asia/Pontianak');

        return view('public-schedules.kelurahan', [
            'today' => $today,
            'dates' => [$today->subDay(), $today, $today->addDay()],
            'kelurahans' => Department::query()->where('department_type', 'kelurahan')->where('is_active', true)->orderBy('name')->get(),
            'schedules' => KelurahanDutySchedule::query()
                ->whereBetween('event_date', [$today->subDay()->toDateString(), $today->addDay()->toDateString()])
                ->whereHas('department', fn ($query) => $query->where('department_type', 'kelurahan')->where('is_active', true))
                ->orderBy('group_number')->orderBy('id')->get()
                ->groupBy(fn (KelurahanDutySchedule $schedule) => $schedule->event_date->toDateString()),
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'in:apel,piket,kelurahan'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        if (! isset($filters['tab'])) {
            return view('public-schedules.choose');
        }
        if ($filters['tab'] === 'kelurahan') {
            return $this->kelurahan();
        }
        $month = CarbonImmutable::createFromFormat(
            '!Y-m',
            $filters['month'] ?? now('Asia/Pontianak')->format('Y-m'),
            'Asia/Pontianak',
        );
        $tab = $filters['tab'] ?? 'apel';

        $scheduleRecords = CeremonySchedule::query()
            ->with(['department', 'dutyGroup'])
            ->where('type', $tab === 'apel' ? 'apel' : 'piket_loket')
            ->when($tab === 'piket', fn ($query) => $query->whereNull('department_id'))
            ->whereDate('event_date', '>=', $month->toDateString())
            ->whereDate('event_date', '<=', $month->endOfMonth()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('notes')->orWhere('notes', '!=', 'Libur bersama');
            })
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();
        $schedules = $tab === 'piket'
            ? $scheduleRecords->groupBy(fn (CeremonySchedule $schedule) => $schedule->event_date->toDateString())
            : $scheduleRecords->keyBy(fn (CeremonySchedule $schedule) => $schedule->event_date->toDateString());

        $kelurahans = collect();

        return view('public-schedules.index', [
            'month' => $month,
            'tab' => $tab,
            'schedules' => $schedules,
            'kelurahans' => $kelurahans,
            'calendarWeeks' => $this->calendarWeeks($month),
            'years' => range(2026, 2030),
        ]);
    }

    private function calendarWeeks(CarbonImmutable $month): array
    {
        $start = $month->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $end = $month->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $weeks = [];

        for ($weekStart = $start; $weekStart->lte($end); $weekStart = $weekStart->addWeek()) {
            $week = [];
            for ($offset = 0; $offset < 7; $offset++) {
                $date = $weekStart->addDays($offset);
                $week[] = [
                    'date' => $date,
                    'inMonth' => $date->month === $month->month,
                ];
            }
            $weeks[] = $week;
        }

        return $weeks;
    }
}
