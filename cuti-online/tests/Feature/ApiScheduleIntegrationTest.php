<?php

namespace Tests\Feature;

use App\Models\CeremonySchedule;
use App\Models\Department;
use App\Models\DutyGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiScheduleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_ceremony_schedules_with_month_filter(): void
    {
        $department = Department::query()->create(['name' => 'Unit Apel', 'is_active' => true]);
        CeremonySchedule::query()->create($this->scheduleData([
            'title' => 'Apel Oktober',
            'event_date' => '2026-10-05',
            'department_id' => $department->id,
        ]));
        CeremonySchedule::query()->create($this->scheduleData([
            'title' => 'Apel November',
            'event_date' => '2026-11-02',
        ]));
        CeremonySchedule::query()->create($this->scheduleData([
            'title' => 'Piket Oktober',
            'type' => 'piket_loket',
            'event_date' => '2026-10-06',
        ]));

        $token = User::factory()->create()->createToken('Jadwal', ['ceremony-schedules:read'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.v1.ceremony-schedules.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Apel Oktober')
            ->assertJsonPath('data.0.type', 'apel')
            ->assertJsonPath('data.0.department.name', 'Unit Apel');
    }

    public function test_api_returns_counter_duty_roster_and_requires_its_ability(): void
    {
        $group = DutyGroup::query()->firstOrFail();
        CeremonySchedule::query()->create($this->scheduleData([
            'title' => 'Piket Loket',
            'type' => 'piket_loket',
            'event_date' => '2026-10-07',
            'duty_group_id' => $group->id,
            'duty_date' => '2026-10-07',
            'duty_roster' => $group->roster(),
        ]));

        $user = User::factory()->create();
        $this->withToken($user->createToken('Jadwal', ['ceremony-schedules:read'])->plainTextToken)
            ->getJson(route('api.v1.counter-duty-schedules.index'))
            ->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->withToken($user->createToken('Piket', ['counter-duty-schedules:read'])->plainTextToken)
            ->getJson(route('api.v1.counter-duty-schedules.index'))
            ->assertOk()
            ->assertJsonPath('data.0.duty_group.number', $group->number)
            ->assertJsonPath('data.0.duty_roster.coordinator', $group->coordinator)
            ->assertJsonPath('data.0.duty_roster.members.0', $group->members[0]);
    }

    public function test_api_schedule_endpoints_require_a_bearer_token(): void
    {
        $this->getJson(route('api.v1.ceremony-schedules.index'))->assertUnauthorized();
        $this->getJson(route('api.v1.counter-duty-schedules.index'))->assertUnauthorized();
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function scheduleData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Apel Senin',
            'type' => 'apel',
            'event_date' => '2026-10-05',
            'start_time' => '07:30:00',
            'location' => 'Halaman Kantor',
            'leader' => 'Petugas Upacara',
        ], $overrides);
    }
}
