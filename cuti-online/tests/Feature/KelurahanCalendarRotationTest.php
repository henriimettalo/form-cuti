<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KelurahanDutySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelurahanCalendarRotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rotation_wraps_in_both_directions_and_only_replaces_selected_month_and_department(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-01 08:00:00', 'Asia/Pontianak'));
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $department = Department::query()->create(['name' => 'Kelurahan Test', 'department_type' => 'kelurahan', 'is_active' => true]);
        $groups = collect([1, 2, 3])->map(fn ($number) => KelurahanDutySchedule::query()->create([
            'department_id' => $department->id, 'group_number' => $number,
            'coordinator' => 'Koordinator '.$number, 'members' => ['Anggota '.$number],
        ]));
        $otherDepartment = Department::query()->create(['name' => 'Kelurahan Lain', 'department_type' => 'kelurahan', 'is_active' => true]);
        foreach ([[$department->id, '2026-09-30'], [$otherDepartment->id, '2026-10-01'], [$department->id, '2026-10-01']] as [$departmentId, $date]) {
            KelurahanDutySchedule::query()->create(['department_id' => $departmentId, 'event_date' => $date, 'group_number' => 9, 'coordinator' => 'Lama', 'members' => ['Lama']]);
        }
        $this->actingAs($user)->put(route('counter-duty.kelurahan-calendar.rotate'), [
            'department_id' => $department->id, 'event_date' => '2026-10-02', 'group_id' => $groups[0]->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('counter-duty.kelurahan-calendar', ['month' => '2026-10']));
        foreach (['2026-10-01' => 3, '2026-10-02' => 1, '2026-10-03' => 2, '2026-10-04' => 3, '2026-10-05' => 1, '2026-10-31' => 3] as $date => $number) {
            $this->assertDatabaseHas('kelurahan_duty_schedules', ['department_id' => $department->id, 'event_date' => $date, 'group_number' => $number, 'coordinator' => 'Koordinator '.$number]);
        }
        $this->assertSame(31, KelurahanDutySchedule::query()->where('department_id', $department->id)->whereBetween('event_date', ['2026-10-01', '2026-10-31'])->count());
        $this->assertDatabaseHas('kelurahan_duty_schedules', ['department_id' => $department->id, 'event_date' => '2026-09-30', 'group_number' => 9]);
        $this->assertDatabaseHas('kelurahan_duty_schedules', ['department_id' => $otherDepartment->id, 'event_date' => '2026-10-01', 'group_number' => 9]);
        $this->get(route('counter-duty.kelurahan-calendar', ['month' => '2026-10']))->assertOk()->assertSee('Ganti kelompok')->assertSee('Anggota 1');
        $this->get(route('counter-duty.kelurahan-assignments'))->assertOk()->assertDontSee('Koordinator 9')->assertSee('3 kelompok dibuat');
        $this->put(route('counter-duty.kelurahan-calendar.rotate'), ['department_id' => $otherDepartment->id, 'event_date' => '2026-10-02', 'group_id' => $groups[0]->id])->assertSessionHasErrors('group_id');
        $this->assertDatabaseHas('kelurahan_duty_schedules', ['department_id' => $otherDepartment->id, 'event_date' => '2026-10-01', 'group_number' => 9]);
    }

    public function test_only_super_admin_can_save_rotation(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PENGGUNA]))
            ->put(route('counter-duty.kelurahan-calendar.rotate'), [])->assertForbidden();
    }

    public function test_past_dates_are_locked_and_rotation_preserves_history(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-11 00:30:00', 'Asia/Pontianak'));
        $department = Department::query()->create(['name' => 'Kelurahan Test', 'department_type' => 'kelurahan', 'is_active' => true]);
        $group = KelurahanDutySchedule::query()->create(['department_id' => $department->id, 'group_number' => 1, 'coordinator' => 'Baru', 'members' => ['Anggota']]);
        $history = KelurahanDutySchedule::query()->create(['department_id' => $department->id, 'event_date' => '2026-10-10', 'group_number' => 2, 'coordinator' => 'Lama', 'members' => ['Arsip']]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]));
        $this->put(route('counter-duty.kelurahan-calendar.rotate'), ['department_id' => $department->id, 'event_date' => '2026-10-10', 'group_id' => $group->id])->assertSessionHasErrors('event_date');
        $this->assertDatabaseCount('kelurahan_duty_schedules', 2);
        $this->get(route('counter-duty.kelurahan-calendar', ['month' => '2026-10']))
            ->assertOk()->assertSee('Tanggal sudah lewat · terkunci')
            ->assertSee('bg-slate-100 text-slate-500', false)
            ->assertSee('data-calendar-today tabindex="-1"', false)
            ->assertDontSee('name="event_date" value="2026-10-10"', false)
            ->assertSee('name="event_date" value="2026-10-11"', false);
        $this->put(route('counter-duty.kelurahan-calendar.rotate'), ['department_id' => $department->id, 'event_date' => '2026-10-11', 'group_id' => $group->id])->assertSessionHasNoErrors();
        $this->assertSame('Lama', $history->fresh()->coordinator);
        $this->assertDatabaseHas('kelurahan_duty_schedules', ['id' => $history->id, 'event_date' => '2026-10-10', 'group_number' => 2]);
        $this->assertDatabaseHas('kelurahan_duty_schedules', ['department_id' => $department->id, 'event_date' => '2026-10-11', 'group_number' => 1]);
        $this->assertDatabaseMissing('kelurahan_duty_schedules', ['department_id' => $department->id, 'event_date' => '2026-10-09']);
        $this->put(route('counter-duty.kelurahan-calendar.rotate'), ['department_id' => $department->id, 'event_date' => '2026-10-20', 'group_id' => $group->id])->assertSessionHasNoErrors();
        $this->assertSame('Lama', $history->fresh()->coordinator);
        $this->assertDatabaseCount('kelurahan_duty_schedules', 23);
    }
}
