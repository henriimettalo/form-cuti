<?php

namespace Tests\Feature;

use App\Models\CeremonySchedule;
use App\Models\Department;
use App\Models\DutyHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CeremonyScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_or_manage_schedules(): void
    {
        $this->get(route('ceremony-schedules.index'))->assertRedirect(route('login'));
        $this->post(route('ceremony-schedules.store'), $this->scheduleData())->assertRedirect(route('login'));
        $this->assertDatabaseCount('ceremony_schedules', 0);
    }

    public function test_all_roles_can_view_the_menu_and_agenda_but_only_super_admin_sees_management_actions(): void
    {
        $department = Department::query()->create(['name' => 'Unit Petugas', 'is_active' => true]);
        CeremonySchedule::query()->create($this->scheduleData(['department_id' => $department->id]));

        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN_UNIT, User::ROLE_PENGGUNA] as $role) {
            $user = User::factory()->create(['role' => $role, 'department_id' => $department->id]);
            $response = $this->actingAs($user)->get(route('ceremony-schedules.index', ['month' => '2026-10']));
            $response->assertOk()
                ->assertSee('data-workspace-title="Jadwal Kegiatan"', false)
                ->assertSee('Apel Senin')
                ->assertSee('Unit Petugas');

            if ($role === User::ROLE_SUPER_ADMIN) {
                $response->assertSee('Atur kalender apel 2026')->assertSee('Hapus jadwal');
            } else {
                $response->assertDontSee('Tambah jadwal')->assertDontSee('Hapus jadwal');
            }
        }
    }

    public function test_month_and_type_filters_return_only_matching_schedules_in_chronological_order(): void
    {
        CeremonySchedule::query()->create($this->scheduleData(['title' => 'Apel Selasa', 'event_date' => '2026-10-06']));
        CeremonySchedule::query()->create($this->scheduleData(['title' => 'Apel Senin']));
        CeremonySchedule::query()->create($this->scheduleData(['title' => 'Upacara Oktober', 'type' => 'upacara', 'event_date' => '2026-10-01']));
        CeremonySchedule::query()->create($this->scheduleData(['title' => 'Piket Pelayanan', 'type' => 'piket_loket', 'event_date' => '2026-10-02']));
        CeremonySchedule::query()->create($this->scheduleData(['title' => 'Apel November', 'event_date' => '2026-11-02']));

        $this->actingAs(User::factory()->create())
            ->get(route('ceremony-schedules.index', ['month' => '2026-10', 'type' => 'apel']))
            ->assertOk()
            ->assertSeeInOrder(['Upacara Oktober', 'Apel Senin', 'Apel Selasa'])
            ->assertDontSee('Piket Pelayanan')
            ->assertDontSee('Apel November');

        $this->get(route('ceremony-schedules.index', ['month' => '2026-10']))
            ->assertSeeInOrder(['Upacara Oktober', 'Apel Senin', 'Apel Selasa'])
            ->assertDontSee('Piket Pelayanan');
    }

    public function test_default_month_uses_pontianak_time_at_a_month_boundary(): void
    {
        $this->travelTo(Carbon::parse('2026-09-30 18:00:00', 'UTC'));

        $this->actingAs(User::factory()->create())
            ->get(route('ceremony-schedules.index'))
            ->assertOk()
            ->assertSee('Agenda Oktober 2026')
            ->assertSee('Belum ada jadwal kegiatan pada bulan ini.');

        $this->get(route('ceremony-schedules.create'))->assertSee('value="2026-10-01"', false);
        $this->travelBack();
    }

    public function test_filter_and_form_offer_combined_ceremony_type_and_counter_duty(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('ceremony-schedules.index'))
            ->assertOk()
            ->assertSee('Apel / Upacara')
            ->assertSee('Piket Loket')
            ->assertDontSee('<option value="upacara"', false);
        $this->get(route('ceremony-schedules.create', ['type' => 'piket_loket']))
            ->assertOk()
            ->assertSee('Tambah jadwal apel/upacara')
            ->assertSee('Apel / Upacara')
            ->assertDontSee('<option value="piket_loket"', false)
            ->assertDontSee('name="duty_group_id"', false);
    }

    public function test_legacy_ceremony_filter_and_edit_preserve_existing_schedules(): void
    {
        $schedule = CeremonySchedule::query()->create($this->scheduleData(['title' => 'Upacara Lama', 'type' => 'upacara']));
        CeremonySchedule::query()->create($this->scheduleData(['title' => 'Apel Lama']));
        CeremonySchedule::query()->create($this->scheduleData(['title' => 'Piket Pelayanan', 'type' => 'piket_loket']));
        $this->actingAs(User::factory()->create());

        $this->get(route('ceremony-schedules.index', ['month' => '2026-10', 'type' => 'upacara']))
            ->assertOk()
            ->assertViewHas('type', 'apel')
            ->assertSee('Upacara Lama')
            ->assertSee('Apel Lama')
            ->assertDontSee('Piket Pelayanan');
        $this->get(route('ceremony-schedules.edit', $schedule))
            ->assertOk()
            ->assertViewHas('selectedType', 'apel')
            ->assertSee('Apel / Upacara');
        $this->assertSame('upacara', $schedule->fresh()->type);
        $this->assertSame('Apel / Upacara', $schedule->typeLabel());

        $this->put(route('ceremony-schedules.update', $schedule), $this->scheduleData(['type' => 'upacara']))
            ->assertSessionHasNoErrors();
        $this->assertSame('apel', $schedule->fresh()->type);
    }

    public function test_super_admin_can_create_filter_and_edit_counter_duty(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('ceremony-schedules.store'), $this->scheduleData([
            'title' => 'Piket Pelayanan',
            'type' => 'piket_loket',
        ]))->assertRedirect(route('ceremony-schedules.index', ['month' => '2026-10']))
            ->assertSessionHas('status', 'Jadwal kegiatan berhasil ditambahkan.');
        $schedule = CeremonySchedule::query()->sole();
        $this->assertSame('piket_loket', $schedule->type);
        CeremonySchedule::query()->create($this->scheduleData());

        $this->get(route('ceremony-schedules.index', ['month' => '2026-10', 'type' => 'piket_loket']))
            ->assertOk()
            ->assertDontSee('Piket Pelayanan')
            ->assertDontSee('Apel Senin');
        $this->get(route('ceremony-schedules.edit', $schedule))
            ->assertOk()
            ->assertViewHas('selectedType', 'piket_loket');
        $this->put(route('ceremony-schedules.update', $schedule), $this->scheduleData([
            'title' => 'Piket Loket Sore',
            'type' => 'piket_loket',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ceremony_schedules', ['id' => $schedule->id, 'title' => 'Piket Loket Sore', 'type' => 'piket_loket']);
        $this->delete(route('ceremony-schedules.destroy', $schedule))->assertRedirect();
        $this->assertDatabaseMissing('ceremony_schedules', ['id' => $schedule->id]);
    }

    public function test_empty_agenda_matches_selected_activity_type(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['apel' => 'apel / upacara', 'piket_loket' => 'piket loket'] as $type => $label) {
            $this->get(route('ceremony-schedules.index', ['month' => '2026-10', 'type' => $type]))
                ->assertOk()
                ->assertSee("Belum ada jadwal {$label} pada bulan ini.");
        }
    }

    public function test_super_admin_can_create_edit_and_delete_a_schedule(): void
    {
        $department = Department::query()->create(['name' => 'Unit Petugas', 'is_active' => true]);
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('ceremony-schedules.create'))->assertOk()->assertSee('Simpan jadwal');
        $this->post(route('ceremony-schedules.store'), $this->scheduleData([
            'department_id' => $department->id,
            'notes' => 'Pakaian dinas.',
            'created_by' => 999,
        ]))->assertRedirect(route('ceremony-schedules.index', ['month' => '2026-10']));

        $schedule = CeremonySchedule::query()->sole();
        $this->assertSame($admin->id, $schedule->created_by);
        $this->assertNull($schedule->start_time);
        $this->assertNull($schedule->location);
        $this->assertNull($schedule->leader);
        $this->assertSame($department->id, $schedule->department_id);
        $this->get(route('ceremony-schedules.edit', $schedule))->assertOk()->assertSee('value="Apel Senin"', false);

        $this->put(route('ceremony-schedules.update', $schedule), $this->scheduleData([
            'title' => 'Upacara Hari Pahlawan',
            'type' => 'upacara',
            'event_date' => '2026-11-10',
            'department_id' => '',
            'notes' => '',
        ]))->assertRedirect(route('ceremony-schedules.index', ['month' => '2026-11']));

        $schedule->refresh();
        $this->assertSame('Upacara Hari Pahlawan', $schedule->title);
        $this->assertSame('apel', $schedule->type);
        $this->assertNull($schedule->department_id);
        $this->assertNull($schedule->leader);
        $this->assertNull($schedule->notes);
        $this->assertSame($admin->id, $schedule->created_by);
        $this->get(route('ceremony-schedules.index', ['month' => '2026-11']))
            ->assertSee('Upacara Hari Pahlawan')
            ->assertSee('data-confirm-title="Hapus jadwal?"', false);

        $this->delete(route('ceremony-schedules.destroy', $schedule))
            ->assertRedirect(route('ceremony-schedules.index', ['month' => '2026-11']));
        $this->assertDatabaseCount('ceremony_schedules', 0);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_ceremony_rotation_advances_weekly_from_the_selected_starting_village(): void
    {
        $villageA = Department::query()->create(['name' => 'Kelurahan A', 'department_type' => 'kelurahan', 'is_active' => true]);
        $villageB = Department::query()->create(['name' => 'Kelurahan B', 'department_type' => 'kelurahan', 'is_active' => true]);
        $villageC = Department::query()->create(['name' => 'Kelurahan C', 'department_type' => 'kelurahan', 'is_active' => true]);
        $start = $villageB->id;
        $this->actingAs(User::factory()->create());

        foreach (['2026-10-05' => $villageB, '2026-10-12' => $villageC, '2026-10-19' => $villageA] as $date => $expected) {
            $this->post(route('ceremony-schedules.store'), $this->scheduleData([
                'event_date' => $date,
                'rotation_start_department_id' => $start,
            ]))->assertSessionHasNoErrors();
            $this->assertSame($expected->id, CeremonySchedule::query()->latest('id')->value('department_id'));
        }
    }

    public function test_calendar_lists_all_mondays_and_saves_holiday_or_village_assignments(): void
    {
        $village = Department::query()->create(['name' => 'Kelurahan A', 'department_type' => 'kelurahan', 'is_active' => true]);
        $village->update(['ceremony_group_number' => 1]);
        $villages = [$village];
        foreach (['B', 'C', 'D'] as $suffix) {
            $villages[] = Department::query()->create(['name' => "Kelurahan {$suffix}", 'department_type' => 'kelurahan', 'is_active' => true]);
        }
        $groupDepartments = collect($villages)->mapWithKeys(fn ($department, $index) => [$index + 1 => $department->id])->all();
        DutyHoliday::query()->create(['holiday_date' => '2026-01-12', 'name' => 'Libur Bersama']);
        $this->actingAs(User::factory()->create());
        $this->get(route('ceremony-schedules.index', ['year' => 2026, 'tab' => 'kalender']))
            ->assertOk()->assertSee('Kalender apel 2026')->assertSee('Senin');

        $days = [];
        $date = Carbon::parse('2026-01-05');
        while ($date->year === 2026) {
            $days[$date->toDateString()] = ['group_number' => 1, 'department_id' => $village->id];
            $date->addWeek();
        }
        $days['2026-01-12'] = ['group_number' => '', 'department_id' => ''];
        $this->put(route('ceremony-schedules.calendar'), ['year' => 2026, 'days' => $days, 'group_count' => 4, 'group_departments' => $groupDepartments])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ceremony_schedules', 52);
        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-01-12', 'notes' => 'Libur', 'department_id' => null]);
        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-01-05', 'department_id' => $village->id]);
        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-01-05', 'ceremony_group_number' => 1]);
        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-01-19', 'ceremony_group_number' => 1]);
        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-01-12', 'ceremony_group_number' => null]);
    }

    public function test_group_configuration_is_saved_without_submitting_the_calendar(): void
    {
        $firstDepartment = Department::query()->create(['name' => 'Kelurahan A', 'department_type' => 'kelurahan', 'is_active' => true]);
        $secondDepartment = Department::query()->create(['name' => 'Kelurahan B', 'department_type' => 'kelurahan', 'is_active' => true]);
        CeremonySchedule::query()->create($this->scheduleData([
            'event_date' => '2026-12-07',
            'type' => 'apel',
            'department_id' => $firstDepartment->id,
            'ceremony_group_number' => 1,
        ]));
        $this->actingAs(User::factory()->create())
            ->postJson(route('ceremony-schedules.groups'), [
                'group_count' => 2,
                'group_departments' => [1 => $secondDepartment->id, 2 => $firstDepartment->id],
            ])
            ->assertOk()
            ->assertJson(['message' => 'Pengaturan kelompok berhasil disimpan.']);

        $this->assertDatabaseHas('departments', ['id' => $secondDepartment->id, 'ceremony_group_number' => 1]);
        $this->assertDatabaseHas('departments', ['id' => $firstDepartment->id, 'ceremony_group_number' => 2]);
        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-12-07', 'department_id' => $secondDepartment->id, 'ceremony_group_number' => 1]);
        $this->get(route('ceremony-schedules.index', ['year' => 2026, 'tab' => 'kalender']))
            ->assertOk()
            ->assertViewHas('groupCount', 2)
            ->assertSee('Kelurahan B');
    }

    public function test_selected_department_recalculates_group_instead_of_using_stale_hidden_group(): void
    {
        $oldDepartment = Department::query()->create(['name' => 'Kelurahan Lama', 'department_type' => 'kelurahan', 'is_active' => true, 'ceremony_group_number' => 1]);
        $newDepartment = Department::query()->create(['name' => 'Kelurahan Akcaya', 'department_type' => 'kelurahan', 'is_active' => true, 'ceremony_group_number' => 2]);
        $days = [];
        $date = Carbon::parse('2026-01-05');
        while ($date->year === 2026) {
            $days[$date->toDateString()] = ['group_number' => 1, 'department_id' => $oldDepartment->id];
            $date->addWeek();
        }
        $days['2026-01-05'] = ['group_number' => 1, 'department_id' => $newDepartment->id];
        $this->actingAs(User::factory()->create())
            ->put(route('ceremony-schedules.calendar'), [
                'year' => 2026,
                'days' => $days,
                'group_count' => 2,
                'group_departments' => [1 => $oldDepartment->id, 2 => $newDepartment->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-01-05', 'department_id' => $newDepartment->id, 'ceremony_group_number' => 2]);
    }

    public function test_non_super_admins_cannot_manage_schedules_even_via_direct_requests(): void
    {
        $department = Department::query()->create(['name' => 'Unit Aktif', 'is_active' => true]);
        $schedule = CeremonySchedule::query()->create($this->scheduleData());

        foreach ([User::ROLE_ADMIN_UNIT, User::ROLE_PENGGUNA, 'operator'] as $role) {
            $user = User::factory()->create(['role' => $role, 'department_id' => $department->id]);
            $this->actingAs($user)->get(route('ceremony-schedules.create'))->assertForbidden();
            $this->post(route('ceremony-schedules.store'), $this->scheduleData())->assertForbidden();
            $this->get(route('ceremony-schedules.edit', $schedule))->assertForbidden();
            $this->put(route('ceremony-schedules.update', $schedule), $this->scheduleData(['title' => 'Diubah']))->assertForbidden();
            $this->delete(route('ceremony-schedules.destroy', $schedule))->assertForbidden();
        }

        $this->assertDatabaseCount('ceremony_schedules', 1);
        $this->assertSame('Apel Senin', $schedule->fresh()->title);
    }

    public function test_removed_fields_are_absent_from_forms_and_ignored_when_submitted(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('ceremony-schedules.create'))
            ->assertOk()
            ->assertDontSee('name="start_time"', false)
            ->assertDontSee('name="location"', false)
            ->assertDontSee('name="leader"', false);

        foreach (array_keys(CeremonySchedule::TYPES) as $type) {
            $this->post(route('ceremony-schedules.store'), $this->scheduleData([
                'type' => $type,
                'start_time' => '25:00',
                'location' => ['invalid'],
                'leader' => str_repeat('A', 256),
            ]))->assertSessionHasNoErrors()
                ->assertRedirect(route('ceremony-schedules.index', ['month' => '2026-10']));

            $schedule = CeremonySchedule::query()->latest('id')->firstOrFail();
            $this->assertNull($schedule->start_time);
            $this->assertNull($schedule->location);
            $this->assertNull($schedule->leader);
        }
    }

    public function test_edit_and_agenda_hide_removed_details_without_erasing_existing_data(): void
    {
        $schedule = CeremonySchedule::query()->create($this->scheduleData([
            'start_time' => '07:30',
            'location' => 'Lokasi Lama',
            'leader' => 'Pembina Lama',
        ]));
        $this->actingAs(User::factory()->create());

        $this->get(route('ceremony-schedules.edit', $schedule))
            ->assertOk()
            ->assertDontSee('name="start_time"', false)
            ->assertDontSee('name="location"', false)
            ->assertDontSee('name="leader"', false);
        $this->put(route('ceremony-schedules.update', $schedule), $this->scheduleData(['title' => 'Jadwal Baru']))
            ->assertSessionHasNoErrors();
        $schedule->refresh();
        $this->assertSame('07:30', $schedule->start_time);
        $this->assertSame('Lokasi Lama', $schedule->location);
        $this->assertSame('Pembina Lama', $schedule->leader);

        $this->get(route('ceremony-schedules.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Jadwal Baru')
            ->assertDontSee('07:30')
            ->assertDontSee('Lokasi Lama')
            ->assertDontSee('Pembina Lama');
    }

    public function test_invalid_schedule_fields_and_inactive_or_missing_units_are_rejected(): void
    {
        $inactive = Department::query()->create(['name' => 'Unit Nonaktif', 'is_active' => false]);
        $this->actingAs(User::factory()->create());
        $this->post(route('ceremony-schedules.store'), $this->scheduleData([
            'title' => '',
            'type' => 'rapat',
            'event_date' => '2026-02-30',
            'department_id' => $inactive->id,
            'notes' => str_repeat('A', 3001),
        ]))->assertSessionHasErrors(['title', 'type', 'event_date', 'department_id', 'notes']);
        $this->post(route('ceremony-schedules.store'), $this->scheduleData(['department_id' => 999]))
            ->assertSessionHasErrors('department_id');
        $this->assertDatabaseCount('ceremony_schedules', 0);
    }

    public function test_existing_inactive_unit_can_be_retained_but_not_newly_assigned(): void
    {
        $inactive = Department::query()->create(['name' => 'Unit Lama', 'is_active' => false]);
        $otherInactive = Department::query()->create(['name' => 'Unit Nonaktif Lain', 'is_active' => false]);
        $schedule = CeremonySchedule::query()->create($this->scheduleData(['department_id' => $inactive->id]));
        $this->actingAs(User::factory()->create());
        $this->get(route('ceremony-schedules.edit', $schedule))
            ->assertOk()->assertSee('Unit Lama')->assertDontSee('Unit Nonaktif Lain');
        $this->put(route('ceremony-schedules.update', $schedule), $this->scheduleData(['department_id' => $inactive->id]))
            ->assertSessionHasNoErrors();
        $this->put(route('ceremony-schedules.update', $schedule), $this->scheduleData(['department_id' => $otherInactive->id]))
            ->assertSessionHasErrors('department_id');
        $this->assertSame($inactive->id, $schedule->fresh()->department_id);
    }

    public function test_invalid_filters_are_validated_and_pagination_preserves_filters(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('ceremony-schedules.index', ['month' => '2026-13', 'type' => 'invalid']))
            ->assertSessionHasErrors(['month', 'type']);
        for ($index = 1; $index <= 21; $index++) {
            CeremonySchedule::query()->create($this->scheduleData(['title' => "Apel {$index}"]));
        }
        $this->get(route('ceremony-schedules.index', ['month' => '2026-10', 'type' => 'apel']))
            ->assertOk()
            ->assertSee('month=2026-10&amp;type=apel&amp;page=2', false);
        $this->get(route('ceremony-schedules.index', ['month' => '2026-10', 'type' => 'apel', 'page' => 2]))
            ->assertOk()
            ->assertSee('Apel 21')
            ->assertDontSee('Apel 20');
    }

    public function test_agenda_escapes_user_content(): void
    {
        CeremonySchedule::query()->create($this->scheduleData(['title' => '<script>alert(1)</script>']));
        $this->actingAs(User::factory()->create())
            ->get(route('ceremony-schedules.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    private function scheduleData(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Apel Senin',
            'type' => 'apel',
            'event_date' => '2026-10-05',
        ], $overrides);
    }
}
