<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_a_bearer_token(): void
    {
        $this->getJson(route('api.v1.employees.index'))
            ->assertUnauthorized();
    }

    public function test_token_with_employee_read_ability_can_list_and_show_employees(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('199001012020011101', 'Pegawai API');
        $token = $this->tokenFor($user, ['employees:read']);

        $this->withToken($token)
            ->getJson(route('api.v1.employees.index', ['search' => 'API']))
            ->assertOk()
            ->assertJsonPath('data.0.nip', $employee->nip)
            ->assertJsonPath('data.0.position.title', 'Jabatan API')
            ->assertJsonPath('data.0.department.name', 'Unit API');

        $this->withToken($token)
            ->getJson(route('api.v1.employees.show', ['employee' => $employee->nip]))
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Pegawai API');
    }

    public function test_profile_ability_is_required_for_sensitive_employee_data(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('199001012020011107', 'Pegawai Profil API');
        $employee->update([
            'nik' => '6171052401900001',
            'npwp' => '123456789012345',
            'birth_date' => '1990-01-01',
        ]);
        EmployeeBankAccount::query()->create([
            'employee_id' => $employee->id,
            'bank_code' => '123',
            'bank_name' => 'Bank Contoh',
            'account_number' => '001234',
            'is_primary' => true,
        ]);

        $this->withToken($this->tokenFor($user, ['employees:read']))
            ->getJson(route('api.v1.employees.show', ['employee' => $employee->nip]))
            ->assertOk()
            ->assertJsonMissingPath('data.sensitive')
            ->assertJsonPath('data.birth_date', '1990-01-01');

        $this->app['auth']->forgetGuards();
        $this->withToken($this->tokenFor($user, ['employees:read', 'employees:profile']))
            ->getJson(route('api.v1.employees.show', ['employee' => $employee->nip]))
            ->assertOk()
            ->assertJsonPath('data.sensitive.nik', '6171052401900001')
            ->assertJsonPath('data.sensitive.bank_accounts.0.account_number', '001234');
    }

    public function test_token_without_the_required_ability_is_forbidden(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user, ['leave-requests:read']);

        $this->withToken($token)
            ->getJson(route('api.v1.employees.index'))
            ->assertForbidden();
    }

    public function test_employee_write_endpoint_creates_employee_with_leave_balance_and_history(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user, ['employees:write']);
        $payload = [
            'nip' => '199001012020011102',
            'full_name' => 'Pegawai Dari API',
            'employment_status' => 'PNS',
            'rank_grade' => 'III/b',
            'position_title' => 'Jabatan API Baru',
            'service_started_on' => '2020-01-01',
            'email' => 'pegawai.api@example.test',
        ];

        $this->withToken($token)
            ->postJson(route('api.v1.employees.store'), $payload)
            ->assertCreated()
            ->assertHeader('Location', route('api.v1.employees.show', ['employee' => $payload['nip']]))
            ->assertJsonPath('data.nip', $payload['nip'])
            ->assertJsonPath('data.rank.grade', 'III/b');

        $employee = Employee::query()->where('nip', $payload['nip'])->firstOrFail();

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_year' => now()->year,
        ]);
        $this->assertDatabaseHas('employee_rank_histories', [
            'employee_id' => $employee->id,
            'grade' => 'III/b',
            'notes' => 'Data awal dari REST API.',
        ]);
        $this->assertDatabaseHas('employee_position_histories', [
            'employee_id' => $employee->id,
            'department_name' => OrganizationProfile::current()->name,
            'position_title' => 'Jabatan API Baru',
            'notes' => 'Data awal dari REST API.',
        ]);
    }

    public function test_leave_request_read_endpoint_returns_filtered_leave_requests(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('199001012020011103', 'Pegawai Cuti API');
        $leaveType = LeaveType::query()->create([
            'code' => 'annual',
            'name' => 'Cuti Tahunan',
            'is_active' => true,
        ]);
        $leaveRequest = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'form_date' => '2026-07-28',
            'reason' => 'Keperluan keluarga.',
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-03',
            'duration_value' => 1,
            'duration_unit' => 'day',
            'employee_snapshot' => [],
            'officials_snapshot' => [],
            'status' => 'generated',
        ]);
        $token = $this->tokenFor($user, ['leave-requests:read']);

        $this->withToken($token)
            ->getJson(route('api.v1.leave-requests.index', ['employee_nip' => $employee->nip]))
            ->assertOk()
            ->assertJsonPath('data.0.public_id', $leaveRequest->public_id)
            ->assertJsonPath('data.0.employee.nip', $employee->nip)
            ->assertJsonPath('data.0.leave_type.code', 'annual');

        $this->withToken($token)
            ->getJson(route('api.v1.leave-requests.show', ['leaveRequest' => $leaveRequest->public_id]))
            ->assertOk()
            ->assertJsonPath('data.reason', 'Keperluan keluarga.');
    }

    public function test_employee_api_does_not_expose_salary_or_payroll_data(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('199001012020011104', 'Pegawai Data Master API');
        $token = $this->tokenFor($user, ['employees:read']);

        $this->withToken($token)
            ->getJson(route('api.v1.employees.show', ['employee' => $employee->nip]))
            ->assertOk()
            ->assertJsonMissingPath('data.basic_salary')
            ->assertJsonMissingPath('data.tunjangan_keluarga')
            ->assertJsonMissingPath('data.payroll');

        $this->withToken($token)
            ->getJson('/api/v1/employees/'.$employee->nip.'/salary-history')
            ->assertNotFound();
    }

    public function test_operator_can_create_and_revoke_only_their_own_api_token(): void
    {
        $operator = User::factory()->create();
        $otherOperator = User::factory()->create();

        $this->actingAs($operator)
            ->get(route('api-tokens.index'))
            ->assertOk()
            ->assertSee('Integrasi API');

        $this->actingAs($operator)
            ->post(route('api-tokens.store'), [
                'name' => 'Sistem Absensi',
                'abilities' => ['employees:read'],
            ])
            ->assertRedirect(route('api-tokens.index'))
            ->assertSessionHas('apiTokenPlainText');

        $token = PersonalAccessToken::query()->where('tokenable_id', $operator->id)->firstOrFail();

        $this->assertSame(['employees:read'], $token->abilities);
        $this->assertNotSame('Sistem Absensi', $token->token);

        $otherToken = $otherOperator->createToken('Milik Operator Lain', ['employees:read'])->accessToken;

        $this->actingAs($operator)
            ->delete(route('api-tokens.destroy', ['personalAccessToken' => $otherToken]))
            ->assertNotFound();

        $this->actingAs($operator)
            ->delete(route('api-tokens.destroy', ['personalAccessToken' => $token]))
            ->assertRedirect(route('api-tokens.index'));

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    /**
     * @param  list<string>  $abilities
     */
    private function tokenFor(User $user, array $abilities): string
    {
        return $user->createToken('Token Uji', $abilities)->plainTextToken;
    }

    private function createEmployee(string $nip, string $fullName): Employee
    {
        $department = Department::query()->create([
            'name' => 'Unit API',
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'name' => 'Jabatan API',
            'is_active' => true,
        ]);

        return Employee::query()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => $nip,
            'full_name' => $fullName,
            'position_title' => $position->name,
            'rank_name' => 'Penata',
            'grade' => 'III/c',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
    }
}
