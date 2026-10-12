<?php

namespace Tests\Feature;

use App\Models\CeremonySchedule;
use App\Models\Department;
use App\Models\DutyGroup;
use App\Models\KelurahanDutySchedule;
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
        $this->getJson(route('api.v1.counter-duty-schedules.reminder'))->assertUnauthorized();
        $this->getJson(route('api.v1.ceremony-schedules.reminder'))->assertUnauthorized();
        $this->getJson(route('api.v1.kelurahan-duty-schedules.reminder'))->assertUnauthorized();
    }

    public function test_ceremony_and_kelurahan_reminders_support_dates_and_availability(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-12 00:30:00', 'Asia/Pontianak'));
        $department = Department::query()->create(['name' => 'Kelurahan Test', 'department_type' => 'kelurahan', 'is_active' => true]);
        CeremonySchedule::query()->create($this->scheduleData(['event_date' => '2026-10-12', 'ceremony_group_number' => 3, 'department_id' => $department->id]));
        CeremonySchedule::query()->create($this->scheduleData(['event_date' => '2026-10-13', 'notes' => 'Libur bersama']));
        foreach (['2026-10-12', '2026-10-14'] as $date) {
            KelurahanDutySchedule::query()->create(['department_id' => $department->id, 'event_date' => $date, 'group_number' => 2, 'coordinator' => 'Koordinator', 'members' => ['Anggota Satu', 'Anggota Dua']]);
        }
        KelurahanDutySchedule::query()->create(['department_id' => $department->id, 'group_number' => 1, 'coordinator' => 'Template', 'members' => ['Template']]);
        $user = User::factory()->create();
        $this->withToken($user->createToken('Reminder', ['ceremony-schedules:read', 'counter-duty-schedules:read'])->plainTextToken);
        $response = $this->getJson(route('api.v1.ceremony-schedules.reminder'))->assertOk()
            ->assertJsonPath('date', '2026-10-12')->assertJsonPath('isAvailable', 1)->assertJsonPath('has_schedule', true)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.group_number', 3)->assertJsonPath('data.0.department.name', 'Kelurahan Test');
        $this->assertStringContainsString('Reminder Jadwal Apel / Upacara Hari Ini', $response->json('message'));
        $this->assertStringContainsString('Pemimpin: Petugas Upacara', $response->json('message'));
        $response = $this->getJson(route('api.v1.kelurahan-duty-schedules.reminder'))->assertOk()
            ->assertJsonPath('date', '2026-10-12')->assertJsonPath('isAvailable', 1)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.coordinator', 'Koordinator')->assertJsonPath('data.0.members', ['Anggota Satu', 'Anggota Dua']);
        $this->assertStringContainsString("Petugas Piket:\nAnggota Satu\nAnggota Dua", $response->json('message'));
        $this->getJson(route('api.v1.kelurahan-duty-schedules.reminder', ['date' => '2026-10-14']))
            ->assertOk()->assertJsonPath('date', '2026-10-14')->assertJsonPath('isAvailable', 1);
        foreach (['api.v1.ceremony-schedules.reminder', 'api.v1.kelurahan-duty-schedules.reminder'] as $route) {
            $this->getJson(route($route, ['date' => '2026-10-13']))->assertOk()->assertJsonPath('isAvailable', 0)->assertJsonPath('has_schedule', false)->assertJsonCount(0, 'data');
            $this->getJson(route($route, ['date' => '2026-02-30']))->assertUnprocessable()->assertJsonValidationErrors('date');
        }
        $this->app['auth']->forgetGuards();
        $this->withToken($user->createToken('Salah', ['employees:read'])->plainTextToken);
        foreach (['api.v1.ceremony-schedules.reminder', 'api.v1.kelurahan-duty-schedules.reminder'] as $route) {
            $this->getJson(route($route))->assertForbidden();
        }
    }

    public function test_reminder_uses_pontianak_today_and_returns_ready_to_send_message(): void
    {
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-10-12 00:30:00', 'Asia/Pontianak'));
        $roster = ['number' => 3, 'coordinator' => 'Asra, S.Sos', 'members' => ['Widya Yulianti, S.IP', 'Arief Kurniawan, S.Kom', 'Alicia Destriani Sandea, S.Kom']];
        CeremonySchedule::query()->create($this->scheduleData(['type' => 'piket_loket', 'event_date' => '2026-10-12', 'duty_roster' => $roster]));
        CeremonySchedule::query()->create($this->scheduleData(['type' => 'piket_loket', 'event_date' => '2026-10-13', 'duty_roster' => ['number' => 1, 'coordinator' => 'Besok', 'members' => ['Anggota']]]));
        $token = User::factory()->create()->createToken('Telegram', ['counter-duty-schedules:read'])->plainTextToken;
        $message = "🔔 Reminder Jadwal Piket Hari Ini 🔔\n\nHari ini giliran kelompok 3:\nPenanggung Jawab: Asra, S.Sos\nPetugas Piket:\nWidya Yulianti, S.IP\nArief Kurniawan, S.Kom\nAlicia Destriani Sandea, S.Kom\n\nJangan lupa ya, terima kasih!";
        $this->withToken($token)->getJson(route('api.v1.counter-duty-schedules.reminder'))
            ->assertOk()->assertJsonPath('date', '2026-10-12')->assertJsonPath('timezone', 'Asia/Pontianak')
            ->assertJsonPath('has_schedule', true)->assertJsonPath('isAvailable', 1)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.group_number', 3)->assertJsonPath('data.0.members', $roster['members'])
            ->assertJsonPath('message', $message);
        $this->getJson(route('api.v1.counter-duty-schedules.reminder', ['date' => '2026-10-13']))
            ->assertOk()->assertJsonPath('date', '2026-10-13')->assertJsonPath('data.0.coordinator', 'Besok')
            ->assertJsonPath('message', "🔔 Reminder Jadwal Piket 13 Oktober 2026 🔔\n\nPada 13 Oktober 2026 giliran kelompok 1:\nPenanggung Jawab: Besok\nPetugas Piket:\nAnggota\n\nJangan lupa ya, terima kasih!");
        $this->getJson(route('api.v1.counter-duty-schedules.reminder', ['date' => '2026-02-30']))->assertUnprocessable()->assertJsonValidationErrors('date');
    }

    public function test_reminder_excludes_holidays_and_kelurahan_and_requires_ability(): void
    {
        $department = Department::query()->create(['name' => 'Kelurahan Test', 'is_active' => true]);
        foreach ([['notes' => 'Libur bersama'], ['department_id' => $department->id], ['type' => 'apel']] as $override) {
            CeremonySchedule::query()->create($this->scheduleData(array_merge(['type' => 'piket_loket', 'event_date' => '2026-10-12'], $override)));
        }
        $user = User::factory()->create();
        $this->withToken($user->createToken('Telegram', ['counter-duty-schedules:read'])->plainTextToken)
            ->getJson(route('api.v1.counter-duty-schedules.reminder', ['date' => '2026-10-12']))
            ->assertOk()->assertJsonPath('has_schedule', false)->assertJsonPath('isAvailable', 0)->assertJsonCount(0, 'data');
        $this->app['auth']->forgetGuards();
        $this->withToken($user->createToken('Salah', ['ceremony-schedules:read'])->plainTextToken)
            ->getJson(route('api.v1.counter-duty-schedules.reminder'))->assertForbidden();
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
