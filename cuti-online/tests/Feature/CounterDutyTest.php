<?php

namespace Tests\Feature;

use App\Models\CeremonySchedule;
use App\Models\Department;
use App\Models\DutyGroup;
use App\Models\DutyHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterDutyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ref_roster_is_seeded_exactly_with_four_groups(): void
    {
        $this->assertDatabaseCount('duty_groups', 4);
        $groups = DutyGroup::query()->orderBy('number')->get();
        $this->assertSame([
            'Syaiful Rahman, S.IP, M.A.P',
            'Al Ikhsan Imanullah, S.STP',
            'Asra, S.Sos',
            'Drs. Ridwan',
        ], $groups->pluck('coordinator')->all());
        $this->assertSame([
            ['Suriadarna', 'Abu Bakar', 'Henri Mettaloa, S.Kom'],
            ['Reny Haryani, SE', 'Maria Franciska Adhika Hapsari,A.Md', 'Ridho Nurrohcman, A.Md'],
            ['Widya Yulianti, S.IP', 'Arief Kurniawan, S.Kom', 'Alicia Destriani Sandea, S.Kom'],
            ['Mega Kartika Elly', 'Sudirianto', 'Harry Salistiwa, S.T'],
        ], $groups->pluck('members')->all());
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_all_users_can_view_rosters_but_only_super_admin_can_change_them(): void
    {
        $this->get(route('counter-duty.index'))->assertRedirect(route('login'));
        $department = Department::query()->create(['name' => 'Unit Aktif', 'is_active' => true]);
        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN_UNIT, User::ROLE_PENGGUNA] as $role) {
            $user = User::factory()->create(['role' => $role, 'department_id' => $department->id]);
            $this->actingAs($user)->get(route('counter-duty.index', ['tab' => 'groups']))
                ->assertOk()->assertSee('Syaiful Rahman')->assertSee('Harry Salistiwa');
            $this->get(route('counter-duty.index', ['tab' => 'holidays']))->assertOk();
            $this->get(route('counter-duty.index'))->assertOk();
            if ($role !== User::ROLE_SUPER_ADMIN) {
                $this->get(route('counter-duty.generate'))->assertForbidden();
                $this->post(route('counter-duty.preview'), $this->range())->assertForbidden();
                $this->post(route('counter-duty.store'))->assertForbidden();
                $this->post(route('counter-duty.cancel'))->assertForbidden();
                $this->put(route('counter-duty.groups.update', 1), ['coordinator' => 'Ubah', 'members' => 'Petugas'])->assertForbidden();
                $this->post(route('counter-duty.holidays.store'), ['holiday_date' => '2026-10-06', 'name' => 'Libur'])->assertForbidden();
            }
        }
        $this->assertSame('Syaiful Rahman, S.IP, M.A.P', DutyGroup::query()->first()->coordinator);
    }

    public function test_holiday_calendar_lists_weekdays_for_selected_year(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('counter-duty.index', ['tab' => 'holidays', 'year' => 2028]))
            ->assertOk()
            ->assertSee('Kalender libur piket 2028')
            ->assertSee('name="days[2028-02-29]"', false)
            ->assertSee('name="days[2028-01-03]"', false)
            ->assertDontSee('name="days[2028-01-01]"', false)
            ->assertDontSee('name="days[2028-01-02]"', false)
            ->assertDontSee('name="days[2027-12-31]"', false)
            ->assertSee('Simpan kalender 2028');
    }

    public function test_calendar_bulk_save_marks_and_unmarks_holidays_without_touching_other_years(): void
    {
        $this->actingAs(User::factory()->create());
        $old = DutyHoliday::query()->create(['holiday_date' => '2026-01-01', 'name' => 'Tahun Baru']);
        DutyHoliday::query()->create(['holiday_date' => '2026-01-02', 'name' => 'Libur Lama']);
        DutyHoliday::query()->create(['holiday_date' => '2025-12-25', 'name' => 'Arsip']);
        $response = $this->get(route('counter-duty.index', ['tab' => 'holidays', 'year' => 2026]));
        $days = array_fill_keys(array_column($response->viewData('calendarDays'), 'date'), '0');
        $days['2026-01-01'] = '1';
        $days['2026-01-05'] = 'ya';
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026, 'days' => $days])
            ->assertSessionHasNoErrors()->assertRedirect(route('counter-duty.index', ['tab' => 'holidays', 'year' => 2026]));
        $this->assertSame('Tahun Baru', $old->fresh()->name);
        $this->assertDatabaseHas('duty_holidays', ['holiday_date' => '2026-01-05']);
        $this->assertDatabaseMissing('duty_holidays', ['holiday_date' => '2026-01-02']);
        $this->assertDatabaseHas('duty_holidays', ['holiday_date' => '2025-12-25']);
        $this->post(route('counter-duty.preview'), ['start_date' => '2026-01-05', 'end_date' => '2026-01-06', 'starting_group' => 1]);
        $this->assertSame(['holiday', 'new'], array_column(session('counter-duty-preview.rows'), 'status'));
    }

    public function test_calendar_accepts_free_text_with_case_whitespace_and_empty_workdays(): void
    {
        $this->actingAs(User::factory()->create());
        DutyHoliday::query()->create(['holiday_date' => '2026-01-02', 'name' => 'Libur Lama']);
        $response = $this->get(route('counter-duty.index', ['tab' => 'holidays', 'year' => 2026]));
        $response->assertSee('placeholder="ya / 1"', false)
            ->assertDontSee('<select class="form-select min-w-28"', false);
        $days = array_fill_keys(array_column($response->viewData('calendarDays'), 'date'), '');
        $days['2026-01-01'] = ' YA ';
        $days['2026-01-05'] = '1';
        $days['2026-01-06'] = ' Tidak ';
        $days['2026-01-07'] = '0';
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026, 'days' => $days])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('duty_holidays', 2);
        $this->assertDatabaseHas('duty_holidays', ['holiday_date' => '2026-01-01']);
        $this->assertDatabaseHas('duty_holidays', ['holiday_date' => '2026-01-05']);
        $this->assertDatabaseMissing('duty_holidays', ['holiday_date' => '2026-01-02']);
    }

    public function test_calendar_conflict_blocks_all_changes_and_invalid_data_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->get(route('counter-duty.index', ['tab' => 'holidays', 'year' => 2026]));
        $days = array_fill_keys(array_column($response->viewData('calendarDays'), 'date'), '0');
        CeremonySchedule::query()->create(['title' => 'Existing', 'type' => 'piket_loket', 'event_date' => '2026-01-05']);
        $days['2026-01-05'] = '1';
        $days['2026-01-06'] = '1';
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026, 'days' => $days])->assertSessionHasErrors('days');
        $this->assertDatabaseCount('duty_holidays', 0);
        $this->assertDatabaseCount('ceremony_schedules', 1);
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026, 'days' => ['2026-01-05' => '1']])->assertSessionHasErrors('days');
        $days['2027-01-01'] = '1';
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026, 'days' => $days])->assertSessionHasErrors('days');
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026, 'days' => ['2026-01-05' => 'invalid']])->assertSessionHasErrors('days.2026-01-05');
        $this->get(route('counter-duty.index', ['tab' => 'holidays', 'year' => 3000]))->assertSessionHasErrors('year');
    }

    public function test_marking_a_scheduled_day_as_holiday_removes_it_and_recalculates_following_groups(): void
    {
        $this->actingAs(User::factory()->create());
        $groups = DutyGroup::query()->orderBy('number')->get();
        foreach (['2026-10-07' => 1, '2026-10-08' => 2, '2026-10-09' => 3, '2026-10-12' => 4] as $date => $number) {
            $group = $groups[$number - 1];
            CeremonySchedule::query()->create([
                'title' => 'Piket Loket — Kelompok '.$group->number,
                'type' => 'piket_loket',
                'event_date' => $date,
                'duty_date' => $date,
                'duty_group_id' => $group->id,
                'duty_roster' => $group->roster(),
            ]);
        }

        $response = $this->get(route('holidays.index', ['year' => 2026]));
        $days = array_fill_keys(array_column($response->viewData('calendarDays'), 'date'), '0');
        $days['2026-10-09'] = '1';
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026, 'days' => $days])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ceremony_schedules', ['type' => 'piket_loket', 'event_date' => '2026-10-09', 'duty_group_id' => null, 'duty_roster' => null]);
        $this->assertDatabaseHas('ceremony_schedules', ['type' => 'piket_loket', 'event_date' => '2026-10-07', 'duty_group_id' => $groups[0]->id]);
        $this->assertDatabaseHas('ceremony_schedules', ['type' => 'piket_loket', 'event_date' => '2026-10-08', 'duty_group_id' => $groups[1]->id]);
        $this->assertDatabaseHas('ceremony_schedules', ['type' => 'piket_loket', 'event_date' => '2026-10-12', 'duty_group_id' => $groups[2]->id]);
        $this->get(route('counter-duty.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertDontSee('Jum, 09 Okt 2026')
            ->assertSee('Kelompok 3');
    }

    public function test_backdated_holiday_changes_do_not_modify_past_schedule_snapshots(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15', 'Asia/Pontianak'));
        $this->actingAs(User::factory()->create());
        $group = DutyGroup::query()->findOrFail(1);
        $schedule = CeremonySchedule::query()->create([
            'title' => 'Piket Loket — Kelompok 1',
            'type' => 'piket_loket',
            'event_date' => '2026-10-09',
            'duty_date' => '2026-10-09',
            'duty_group_id' => $group->id,
            'duty_roster' => $group->roster(),
        ]);
        $response = $this->get(route('holidays.index', ['year' => 2026]));
        $days = array_fill_keys(array_column($response->viewData('calendarDays'), 'date'), '0');
        $days['2026-10-09'] = '1';
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026, 'days' => $days])->assertSessionHasNoErrors();

        $this->assertSame($schedule->id, $schedule->fresh()->id);
        $this->assertSame($group->id, $schedule->fresh()->duty_group_id);
        $this->assertSame('Piket Loket — Kelompok 1', $schedule->fresh()->title);
        $this->travelBack();
    }

    public function test_non_admin_calendar_is_read_only_and_cannot_be_updated(): void
    {
        $department = Department::query()->create(['name' => 'Unit Aktif', 'is_active' => true]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_PENGGUNA, 'department_id' => $department->id]));
        $this->get(route('counter-duty.index', ['tab' => 'holidays', 'year' => 2026]))
            ->assertOk()->assertSee('isLibur')->assertDontSee('Simpan kalender')->assertDontSee('name="days[', false);
        $this->put(route('counter-duty.holidays.calendar'), ['year' => 2026])->assertForbidden();
    }

    public function test_rotation_skips_weekends_and_holidays_and_persists_only_after_confirmation(): void
    {
        DutyHoliday::query()->create(['holiday_date' => '2026-10-06', 'name' => 'Libur Uji']);
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('counter-duty.generate'))->assertOk();
        $this->post(route('counter-duty.preview'), $this->range(['start_date' => '2026-10-02', 'end_date' => '2026-10-09', 'starting_group' => 4]))
            ->assertRedirect(route('counter-duty.generate'));
        $this->assertDatabaseCount('ceremony_schedules', 0);
        $preview = session('counter-duty-preview');
        $newRows = collect($preview['rows'])->where('status', 'new');
        $this->assertSame(['2026-10-02', '2026-10-05', '2026-10-07', '2026-10-08', '2026-10-09'], $newRows->pluck('date')->all());
        $this->assertSame([4, 1, 2, 3, 4], $newRows->pluck('roster.number')->all());
        $this->get(route('counter-duty.generate'))->assertOk()->assertSee('5 jadwal baru')->assertSee('Libur Uji');
        $this->post(route('counter-duty.store'))->assertRedirect()->assertSessionMissing('counter-duty-preview');
        $this->assertDatabaseCount('ceremony_schedules', 5);
        $first = CeremonySchedule::query()->orderBy('event_date')->first();
        $this->assertSame($admin->id, $first->created_by);
        $this->assertSame('2026-10-02', $first->duty_date->toDateString());
        $this->assertSame('Drs. Ridwan', $first->duty_roster['coordinator']);
        $this->get(route('counter-duty.index', ['month' => '2026-10']))->assertOk()->assertSee('Harry Salistiwa')->assertSee('Kelompok 4')->assertSee('Akhir pekan');
        $this->get(route('ceremony-schedules.index', ['month' => '2026-10']))->assertOk()->assertDontSee('Kelompok 4')->assertDontSee('Drs. Ridwan');
    }

    public function test_month_calendar_shows_shared_holiday_instead_of_an_empty_day(): void
    {
        CeremonySchedule::query()->create([
            'title' => 'Piket Loket — Libur',
            'type' => 'piket_loket',
            'event_date' => '2026-10-09',
            'duty_date' => '2026-10-09',
            'notes' => 'Libur bersama',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('counter-duty.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Libur bersama');
    }

    public function test_rotation_continues_across_months_and_does_not_overwrite_existing_dates(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('counter-duty.preview'), $this->range(['start_date' => '2026-10-30', 'end_date' => '2026-10-30', 'starting_group' => 2]));
        $this->post(route('counter-duty.store'))->assertSessionHasNoErrors();
        $first = CeremonySchedule::query()->sole();
        $original = $first->getAttributes();
        $this->post(route('counter-duty.preview'), $this->range(['start_date' => '2026-11-02', 'end_date' => '2026-11-03', 'starting_group' => 1]));
        $this->assertTrue(session('counter-duty-preview.continued'));
        $this->assertSame([3, 4], collect(session('counter-duty-preview.rows'))->pluck('roster.number')->all());
        $this->post(route('counter-duty.store'))->assertSessionHasNoErrors();
        $this->post(route('counter-duty.preview'), $this->range(['start_date' => '2026-10-30', 'end_date' => '2026-11-04', 'starting_group' => 2]));
        $this->assertSame(3, collect(session('counter-duty-preview.rows'))->where('status', 'existing')->count());
        $this->assertSame(1, collect(session('counter-duty-preview.rows'))->where('status', 'new')->count());
        $this->post(route('counter-duty.store'))->assertSessionHasNoErrors();
        $this->assertSame($original, $first->fresh()->getAttributes());
        $this->assertDatabaseCount('ceremony_schedules', 4);
        $this->post(route('counter-duty.preview'), $this->range(['start_date' => '2026-11-04', 'end_date' => '2026-11-04']));
        $this->post(route('counter-duty.store'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ceremony_schedules', 4);
    }

    public function test_changing_groups_does_not_change_saved_roster_and_invalidates_preview(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('counter-duty.preview'), $this->range());
        $this->post(route('counter-duty.store'))->assertSessionHasNoErrors();
        $schedule = CeremonySchedule::query()->sole();
        $original = $schedule->duty_roster;
        $this->post(route('counter-duty.preview'), $this->range(['start_date' => '2026-10-06', 'end_date' => '2026-10-06']));
        $this->put(route('counter-duty.groups.update', 2), ['coordinator' => 'Penanggung Baru', 'members' => "Petugas Baru\nPetugas Kedua"])->assertRedirect();
        $this->post(route('counter-duty.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 1);
        $this->put(route('counter-duty.groups.update', 1), ['coordinator' => 'Koordinator Baru', 'members' => 'Anggota Baru'])->assertRedirect();
        $this->put(route('ceremony-schedules.update', $schedule), ['title' => $schedule->title, 'type' => 'piket_loket', 'event_date' => '2026-10-05', 'duty_group_id' => (string) $schedule->duty_group_id])->assertSessionHasNoErrors();
        $this->assertSame($original, $schedule->fresh()->duty_roster);
    }

    public function test_changing_groups_updates_future_saved_rosters(): void
    {
        $this->actingAs(User::factory()->create());
        $group = DutyGroup::query()->findOrFail(2);
        $schedule = CeremonySchedule::query()->create([
            'title' => 'Piket Loket — Kelompok 2',
            'type' => 'piket_loket',
            'event_date' => '2026-10-12',
            'duty_date' => '2026-10-12',
            'duty_group_id' => $group->id,
            'duty_roster' => $group->roster(),
        ]);

        $this->put(route('counter-duty.groups.update', $group), [
            'coordinator' => 'Koordinator Baru',
            'members' => "Petugas Baru\nPetugas Kedua",
        ])->assertRedirect();

        $schedule = $schedule->fresh();
        $this->assertSame('Koordinator Baru', $schedule->duty_roster['coordinator']);
        $this->assertSame(['Petugas Baru', 'Petugas Kedua'], $schedule->duty_roster['members']);
    }

    public function test_holiday_or_new_schedule_after_preview_prevents_stale_save(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('counter-duty.preview'), $this->range());
        $this->post(route('counter-duty.holidays.store'), ['holiday_date' => '2026-10-05', 'name' => 'Libur Tambahan'])->assertRedirect();
        $this->post(route('counter-duty.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 0);
        $this->delete(route('counter-duty.holidays.destroy', DutyHoliday::query()->sole()))->assertRedirect();
        $this->post(route('counter-duty.preview'), $this->range());
        $this->post(route('ceremony-schedules.store'), ['title' => 'Piket Manual', 'type' => 'piket_loket', 'event_date' => '2026-10-05', 'duty_group_id' => 1])->assertRedirect();
        $this->post(route('counter-duty.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 1);
        $this->assertSame('Piket Manual', CeremonySchedule::query()->sole()->title);
    }

    public function test_cancel_clears_preview_and_repeated_store_cannot_duplicate_schedule(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('counter-duty.preview'), $this->range());
        $this->post(route('counter-duty.cancel'))->assertRedirect()->assertSessionMissing('counter-duty-preview');
        $this->post(route('counter-duty.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 0);
        $this->post(route('counter-duty.preview'), $this->range());
        $this->post(route('counter-duty.store'))->assertSessionHasNoErrors();
        $this->post(route('counter-duty.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 1);
    }

    public function test_invalid_ranges_groups_and_filters_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('counter-duty.preview'), $this->range(['end_date' => '2026-10-01', 'starting_group' => 5]))->assertSessionHasErrors(['end_date', 'starting_group']);
        $this->post(route('counter-duty.preview'), $this->range(['end_date' => '2028-10-05']))->assertSessionHasErrors('end_date');
        $this->get(route('counter-duty.index', ['tab' => 'invalid', 'month' => '2026-13']))->assertSessionHasErrors(['tab', 'month']);
        $this->put(route('counter-duty.groups.update', 1), ['coordinator' => 'Test', 'members' => "Nomor Satu\nNomor Satu"])->assertSessionHasErrors('members');
        $this->put(route('counter-duty.groups.update', 1), ['coordinator' => 'Test', 'members' => 'Test'])->assertSessionHasErrors('members');
        $this->post(route('counter-duty.preview'), $this->range());
        $this->post(route('counter-duty.preview'), $this->range(['starting_group' => 5]))->assertSessionMissing('counter-duty-preview');
    }

    public function test_holidays_cannot_replace_existing_schedule_or_be_duplicated(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('counter-duty.preview'), $this->range());
        $this->post(route('counter-duty.store'));
        $this->post(route('counter-duty.holidays.store'), ['holiday_date' => '2026-10-05', 'name' => 'Libur'])->assertSessionHasErrors('holiday_date');
        $this->post(route('counter-duty.holidays.store'), ['holiday_date' => '2026-10-06', 'name' => 'Libur'])->assertSessionHasNoErrors();
        $this->post(route('counter-duty.holidays.store'), ['holiday_date' => '2026-10-06', 'name' => 'Libur'])->assertSessionHasErrors('holiday_date');
        $this->post(route('ceremony-schedules.store'), ['title' => 'Piket', 'type' => 'piket_loket', 'event_date' => '2026-10-06', 'duty_group_id' => 1])->assertSessionHasErrors('event_date');
        $this->assertDatabaseCount('ceremony_schedules', 1);
    }

    public function test_unassigned_or_future_schedules_require_review_before_generating(): void
    {
        $this->actingAs(User::factory()->create());
        $schedule = CeremonySchedule::query()->create(['title' => 'Piket Lama', 'type' => 'piket_loket', 'event_date' => '2026-10-05']);
        $this->post(route('counter-duty.preview'), $this->range(['start_date' => '2026-10-06', 'end_date' => '2026-10-07']))->assertSessionHasErrors('start_date');
        $this->post(route('counter-duty.preview'), $this->range())->assertSessionHasErrors('start_date');
        $schedule->update(['duty_group_id' => 1, 'duty_roster' => DutyGroup::query()->first()->roster()]);
        $this->post(route('counter-duty.preview'), $this->range(['start_date' => '2026-10-01', 'end_date' => '2026-10-02']))->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('ceremony_schedules', 1);
    }

    public function test_manual_edit_can_change_or_clear_the_assigned_group(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('counter-duty.preview'), $this->range());
        $this->post(route('counter-duty.store'));
        $schedule = CeremonySchedule::query()->sole();
        $input = ['title' => $schedule->title, 'type' => 'piket_loket', 'event_date' => '2026-10-05', 'duty_group_id' => 2];
        $this->put(route('ceremony-schedules.update', $schedule), $input)->assertSessionHasNoErrors();
        $this->assertSame(2, $schedule->fresh()->duty_roster['number']);
        $this->put(route('ceremony-schedules.update', $schedule), array_replace($input, ['duty_group_id' => '']))->assertSessionHasNoErrors();
        $this->assertNull($schedule->fresh()->duty_roster);
        $this->assertNull($schedule->fresh()->duty_group_id);
    }

    private function range(array $overrides = []): array
    {
        return array_replace(['start_date' => '2026-10-05', 'end_date' => '2026-10-05', 'starting_group' => 1], $overrides);
    }
}
