<?php

namespace App\Http\Controllers;

use App\Models\CeremonySchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'in:apel,piket'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = CarbonImmutable::createFromFormat(
            '!Y-m',
            $filters['month'] ?? now('Asia/Pontianak')->format('Y-m'),
            'Asia/Pontianak',
        );
        $tab = $filters['tab'] ?? 'apel';

        $schedules = CeremonySchedule::query()
            ->with(['department', 'dutyGroup'])
            ->where('type', $tab === 'apel' ? 'apel' : 'piket_loket')
            ->whereDate('event_date', '>=', $month->toDateString())
            ->whereDate('event_date', '<=', $month->endOfMonth()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('notes')->orWhere('notes', '!=', 'Libur bersama');
            })
            ->orderBy('event_date')
            ->orderBy('id')
            ->get()
            ->keyBy(fn (CeremonySchedule $schedule) => $schedule->event_date->toDateString());

        return view('public-schedules.index', [
            'month' => $month,
            'tab' => $tab,
            'schedules' => $schedules,
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
