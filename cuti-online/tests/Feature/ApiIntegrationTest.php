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
use App\Services\EmployeeIdentityImportService;
use App\Services\PayrollImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $token = $this->tokenFor($user, ['employees:write', 'employees:profile']);
        $payload = [
            'nip' => '199001012020011102',
            'full_name' => 'Pegawai Dari API',
            'employment_status' => 'PNS',
            'rank_grade' => 'III/b',
            'position_title' => 'Jabatan API Baru',
            'position_type' => 2,
            'service_started_on' => '2020-01-01',
            'email' => 'pegawai.api@example.test',
        ];

        $this->withToken($token)
            ->postJson(route('api.v1.employees.store'), $payload)
            ->assertCreated()
            ->assertHeader('Location', route('api.v1.employees.show', ['employee' => $payload['nip']]))
            ->assertJsonPath('data.nip', $payload['nip'])
            ->assertJsonPath('data.rank.grade', 'III/b')
            ->assertJsonPath('data.position_type', 2)
            ->assertJsonPath('data.position_type_label', 'Fungsional');

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

    public function test_imported_view_returns_only_headers_from_latest_identity_import_without_filtering_employees(): void
    {
        $employee = $this->createEmployee('199001012020011101', 'Pegawai API');
        $manual = $this->createEmployee('199001012020011102', 'Pegawai Manual');
        $service = app(EmployeeIdentityImportService::class);
        $service->store(UploadedFile::fake()->createWithContent('kontak.csv', "NIP,Nomor Telepon,Email\n{$employee->nip},081234567890,import@example.test"));
        $token = $this->tokenFor(User::factory()->create(), ['employees:read']);
        $this->withToken($token)->getJson(route('api.v1.employees.show', ['employee' => $employee->nip, 'view' => 'imported']))
            ->assertOk()->assertExactJson(['data' => ['nip' => $employee->nip, 'phone' => '081234567890', 'email' => 'import@example.test']]);
        $this->withToken($token)->getJson(route('api.v1.employees.index', ['view' => 'imported']))
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonMissingPath('data.0.id')->assertJsonMissingPath('data.0.full_name');
        $this->withToken($token)->getJson(route('api.v1.employees.show', ['employee' => $manual->nip, 'view' => 'imported']))
            ->assertOk()->assertExactJson(['data' => ['nip' => $manual->nip]]);
        $service->store(UploadedFile::fake()->createWithContent('nama.csv', "NIP,Nama Lengkap\n{$employee->nip},Nama Baru"));
        $this->withToken($token)->getJson(route('api.v1.employees.show', ['employee' => $employee->nip, 'view' => 'imported']))
            ->assertOk()->assertExactJson(['data' => ['nip' => $employee->nip, 'full_name' => 'Nama Baru']]);
        $this->withToken($token)->getJson(route('api.v1.employees.show', ['employee' => $employee->nip]))
            ->assertOk()->assertJsonPath('data.email', 'import@example.test')->assertJsonPath('data.rank.grade', 'III/c');
    }

    public function test_payroll_import_projection_excludes_derived_and_financial_fields_and_respects_profile_permission(): void
    {
        $employee = $this->createEmployee('199001012020011101', 'Nama Lama');
        $service = app(PayrollImportService::class);
        $preview = $service->preview(UploadedFile::fake()->createWithContent('payroll.csv', "nip_pegawai,nama_pegawai,status_asn,golongan,nik_pegawai,nomor_rekening,gaji_pokok\n{$employee->nip},Nama Impor,1,III/a,6171052401900001,000123456,9000000"), 2026, 5, employeeDataOnly: true);
        $this->assertNull($employee->fresh()->imported_api_fields);
        $service->confirmEmployeeData($preview);
        $data = ['nip' => $employee->nip, 'full_name' => 'Nama Impor', 'employment_status' => 'PNS', 'rank' => ['grade' => 'III/a']];
        $user = User::factory()->create();
        $this->withToken($this->tokenFor($user, ['employees:read']))
            ->getJson(route('api.v1.employees.show', ['employee' => $employee->nip, 'view' => 'imported']))
            ->assertOk()->assertExactJson(['data' => $data]);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->tokenFor($user, ['employees:read', 'employees:profile']))
            ->getJson(route('api.v1.employees.show', ['employee' => $employee->nip, 'view' => 'imported']))
            ->assertOk()->assertExactJson(['data' => $data + ['sensitive' => ['nik' => '6171052401900001', 'bank_accounts' => [['account_number' => '000123456']]]]]);
    }

    public function test_imported_view_still_requires_read_permission_and_rejects_unknown_views(): void
    {
        $employee = $this->createEmployee('199001012020011101', 'Pegawai API');
        $user = User::factory()->create();
        $this->withToken($this->tokenFor($user, ['leave-requests:read']))
            ->getJson(route('api.v1.employees.index', ['view' => 'imported']))->assertForbidden();
        $this->app['auth']->forgetGuards();
        $token = $this->tokenFor($user, ['employees:read']);
        $this->withToken($token)->getJson(route('api.v1.employees.index', ['view' => 'other']))->assertUnprocessable()->assertJsonValidationErrors('view');
        $this->withToken($token)->getJson(route('api.v1.employees.show', ['employee' => $employee->nip, 'view' => 'other']))->assertUnprocessable()->assertJsonValidationErrors('view');
    }

    public function test_reimport_can_record_api_columns_without_changing_existing_employee_values(): void
    {
        $employee = $this->createEmployee('199001012020011101', 'Nama Tetap');
        $service = app(EmployeeIdentityImportService::class);
        $file = UploadedFile::fake()->createWithContent('sama.csv', "NIP,Nama Lengkap\n{$employee->nip},Nama Tetap");
        $preview = $service->preview($file);
        $this->assertTrue($preview['valid_rows'][0]['record_columns_only']);
        $this->assertSame([], $preview['valid_rows'][0]['changes']);
        $this->assertNull($employee->fresh()->imported_api_fields);
        $this->assertSame(1, $service->store($file));
        $this->assertSame('Nama Tetap', $employee->fresh()->full_name);
        $this->assertSame(['nip', 'full_name'], $employee->fresh()->imported_api_fields);
        $this->assertDatabaseCount('employees', 1);
        $this->assertSame([], $service->preview($file)['valid_rows']);
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
