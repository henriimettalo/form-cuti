<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

class LeaveDurationCalculator
{
    public function calculate(
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
        string $unit,
        bool $workingDaysOnly = false,
    ): int {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($end->isBefore($start)) {
            throw new InvalidArgumentException('Tanggal selesai tidak boleh mendahului tanggal mulai.');
        }

        return match ($unit) {
            'day' => $workingDaysOnly
                ? $this->countWorkingDays($start, $end)
                : (int) $start->diffInDays($end) + 1,
            'month' => max(1, (int) $start->diffInMonths($end)),
            'year' => max(1, (int) $start->diffInYears($end)),
            default => throw new InvalidArgumentException('Satuan lama cuti tidak dikenal.'),
        };
    }

    private function countWorkingDays(CarbonImmutable $start, CarbonImmutable $end): int
    {
        $holidayDates = Holiday::query()
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('holiday_date')
            ->map(fn ($date): string => CarbonImmutable::parse($date)->toDateString())
            ->all();

        $total = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (! $date->isWeekend() && ! in_array($date->toDateString(), $holidayDates, true)) {
                $total++;
            }
        }

        return $total;
    }
}
