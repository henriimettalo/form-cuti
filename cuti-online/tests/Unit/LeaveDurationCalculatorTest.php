<?php

namespace Tests\Unit;

use App\Models\DutyHoliday;
use App\Models\Holiday;
use App\Services\LeaveDurationCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveDurationCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_annual_leave_counts_only_working_days(): void
    {
        Holiday::query()->create([
            'holiday_date' => '2026-07-30',
            'name' => 'Libur Uji',
            'is_national' => true,
        ]);

        $duration = app(LeaveDurationCalculator::class)->calculate(
            '2026-07-29',
            '2026-08-03',
            'day',
            true,
        );

        $this->assertSame(3, $duration);
    }

    public function test_non_annual_leave_counts_calendar_days(): void
    {
        $duration = app(LeaveDurationCalculator::class)->calculate(
            '2026-07-29',
            '2026-08-03',
            'day',
        );

        $this->assertSame(6, $duration);
    }

    public function test_annual_leave_excludes_shared_and_legacy_holidays_including_the_last_day(): void
    {
        DutyHoliday::query()->create(['holiday_date' => '2026-07-30', 'name' => 'Libur bersama']);
        Holiday::query()->create(['holiday_date' => '2026-07-31', 'name' => 'Libur lama']);

        $duration = app(LeaveDurationCalculator::class)->calculate('2026-07-29', '2026-07-31', 'day', true);

        $this->assertSame(1, $duration);
    }
}
