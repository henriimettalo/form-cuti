<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KelurahanDutySchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicKelurahanScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_only_sees_three_days_even_with_date_filters_across_month_boundary(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-01 00:30:00', 'Asia/Pontianak'));
        $department = Department::query()->create(['name' => 'Kelurahan Test', 'department_type' => 'kelurahan', 'is_active' => true]);
        foreach (['2026-10-30', '2026-10-31', '2026-11-01', '2026-11-02', '2026-11-03'] as $date) {
            KelurahanDutySchedule::query()->create(['department_id' => $department->id, 'event_date' => $date, 'group_number' => 1, 'coordinator' => 'Petugas '.$date, 'members' => ['Anggota '.$date]]);
        }
        $response = $this->get(route('public-schedules.kelurahan', ['month' => '2026-10', 'date' => '2026-10-30']));
        $response->assertOk()->assertSee('Kemarin')->assertSee('Hari ini')->assertSee('Besok')
            ->assertSee('Petugas 2026-10-31')->assertSee('Petugas 2026-11-01')->assertSee('Anggota 2026-11-02')
            ->assertDontSee('Petugas 2026-10-30')->assertDontSee('Petugas 2026-11-03')
            ->assertDontSee('Ganti kelompok')->assertDontSee('<form', false);
        $this->assertCount(3, $response->viewData('dates'));
        $this->assertCount(3, $response->viewData('schedules'));
    }

    public function test_guest_can_view_empty_schedule(): void
    {
        $this->get(route('public-schedules.kelurahan'))->assertOk()->assertSee('Belum ada kelurahan aktif.');
        Department::query()->create(['name' => 'Kelurahan Test', 'department_type' => 'kelurahan', 'is_active' => true]);
        $this->get(route('public-schedules.kelurahan'))->assertOk()->assertSee('Belum ada jadwal.');
    }
}
