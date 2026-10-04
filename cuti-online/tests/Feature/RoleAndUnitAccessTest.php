<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Official;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndUnitAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_a_unit_and_its_admin(): void
    {
        $superAdmin = User::factory()->create();

        $this->actingAs($superAdmin)
            ->post(route('users.unit-admin.store'), [
                'department_source' => 'new',
                'department_type' => 'kecamatan',
                'department_code' => 'PS',
                'department_name' => 'Sekretariat Kecamatan Pontianak Selatan',
                'department_phone' => '0561-123456',
                'admin_name' => 'Admin Sekretariat',
                'admin_email' => 'admin-sekretariat@example.test',
                'admin_password' => 'password-admin',
                'admin_password_confirmation' => 'password-admin',
            ])
            ->assertRedirect(route('users.index'));

        $department = Department::query()
            ->where('code', 'KEC-PS')
            ->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => 'admin-sekretariat@example.test',
            'role' => User::ROLE_ADMIN_UNIT,
            'department_id' => $department->id,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Admin Sekretariat')
            ->assertSee('Sekretariat Kecamatan Pontianak Selatan')
            ->assertSee('Kelola admin')
            ->assertSee('Administrasi Unit')
            ->assertSee('Seluruh Akun');
    }

    public function test_super_admin_can_deactivate_and_reactivate_a_unit_without_deleting_it(): void
    {
        $superAdmin = User::factory()->create();
        $department = $this->department('Unit yang akan diarsipkan');

        $this->actingAs($superAdmin)
            ->post(route('users.departments.deactivate', $department))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Unit yang akan diarsipkan',
            'is_active' => false,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Unit yang akan diarsipkan')
            ->assertSee('Aktifkan kembali');

        $this->actingAs($superAdmin)
            ->post(route('users.departments.activate', $department))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_add_a_unit_without_creating_an_admin(): void
    {
        $superAdmin = User::factory()->create();
        $parent = Department::query()->where('department_type', 'kecamatan')->firstOrFail();

        $this->actingAs($superAdmin)
            ->post(route('users.departments.store'), [
                'form_context' => 'department',
                'department_type' => 'kelurahan',
                'department_code' => 'PT',
                'department_simpeg_code' => '12.26.09.50.03.05.00',
                'department_parent_id' => $parent->id,
                'department_name' => 'Kelurahan Parittokaya',
                'department_address' => 'Jalan Parittokaya',
                'department_phone' => '0561-222333',
            ])
            ->assertRedirect(route('users.index'));

        $department = Department::query()->where('code', 'KEL-PT')->firstOrFail();

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Kelurahan Parittokaya',
            'simpeg_code' => '12.26.09.50.03.05.00',
            'department_type' => 'kelurahan',
            'parent_department_id' => $parent->id,
            'address' => 'Jalan Parittokaya',
            'phone' => '0561-222333',
        ]);
        $this->assertDatabaseMissing('users', [
            'department_id' => $department->id,
            'role' => User::ROLE_ADMIN_UNIT,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Kelurahan Parittokaya')
            ->assertSee('Belum ada Admin Unit');
    }

    public function test_super_admin_can_edit_all_unit_metadata(): void
    {
        $superAdmin = User::factory()->create();
        $newParent = Department::query()->create([
            'name' => 'Kecamatan Induk Baru',
            'code' => 'KEC-BARU',
            'simpeg_code' => '99.99.99.99.99',
            'department_type' => 'kecamatan',
            'is_active' => true,
        ]);
        $department = Department::query()->create([
            'name' => 'Unit Lama',
            'code' => 'KEC-LAMA',
            'simpeg_code' => '99.99.99.99.98',
            'department_type' => 'kecamatan',
            'address' => 'Alamat lama',
            'phone' => '0561-000000',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->put(route('users.departments.update', $department), [
                'form_context' => 'department-edit',
                'department_id' => $department->id,
                'department_type' => 'kelurahan',
                'department_code' => 'KEL-BARU',
                'department_simpeg_code' => '12.26.09.50.03.05.00',
                'department_parent_id' => $newParent->id,
                'department_name' => 'Kelurahan Parittokaya',
                'department_address' => 'Jalan Baru Parittokaya',
                'department_phone' => '0561-333444',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Kelurahan Parittokaya',
            'code' => 'KEL-BARU',
            'simpeg_code' => '12.26.09.50.03.05.00',
            'department_type' => 'kelurahan',
            'parent_department_id' => $newParent->id,
            'address' => 'Jalan Baru Parittokaya',
            'phone' => '0561-333444',
        ]);
    }

    public function test_super_admin_prefixes_a_kelurahan_code_with_kel(): void
    {
        $superAdmin = User::factory()->create();
        $parent = Department::query()->where('department_type', 'kecamatan')->firstOrFail();

        $this->actingAs($superAdmin)
            ->post(route('users.unit-admin.store'), [
                'department_source' => 'new',
                'department_type' => 'kelurahan',
                'department_code' => 'AKCAYA',
                'department_simpeg_code' => '12.26.09.50.03.06.00',
                'department_parent_id' => $parent->id,
                'department_name' => 'Kelurahan Akcaya, Kecamatan Pontianak Selatan',
                'admin_name' => 'Admin Akcaya',
                'admin_email' => 'admin-akcaya@example.test',
                'admin_password' => 'password-admin',
                'admin_password_confirmation' => 'password-admin',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('departments', [
            'code' => 'KEL-AKCAYA',
            'simpeg_code' => '12.26.09.50.03.06.00',
            'department_type' => 'kelurahan',
            'parent_department_id' => $parent->id,
            'name' => 'Kelurahan Akcaya, Kecamatan Pontianak Selatan',
        ]);
    }

    public function test_migration_backfills_the_official_parent_unit_metadata(): void
    {
        $department = Department::query()->where('name', 'Kecamatan Pontianak Selatan')->firstOrFail();

        $this->assertSame('KEC-PONSEL', $department->code);
        $this->assertSame('12.26.09.50.03', $department->simpeg_code);
        $this->assertSame('kecamatan', $department->department_type);
        $this->assertNull($department->parent_department_id);
    }

    public function test_role_and_unit_access_matrix_is_enforced(): void
    {
        $department = $this->department('Unit aktif untuk matriks akses');
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'department_id' => null,
        ]);
        $adminUnit = User::factory()->create([
            'role' => User::ROLE_ADMIN_UNIT,
            'department_id' => $department->id,
        ]);
        $pengguna = User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'department_id' => $department->id,
        ]);

        foreach ([$superAdmin, $adminUnit, $pengguna] as $user) {
            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('leave-requests.index'))
                ->assertOk();
        }

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk();
        $this->actingAs($superAdmin)
            ->get(route('employees.index'))
            ->assertOk();

        $this->actingAs($adminUnit)
            ->get(route('users.index'))
            ->assertOk();
        $this->actingAs($adminUnit)
            ->get(route('employees.index'))
            ->assertForbidden();

        $this->actingAs($pengguna)
            ->get(route('users.index'))
            ->assertForbidden();
        $this->actingAs($pengguna)
            ->get(route('employees.index'))
            ->assertForbidden();
    }

    public function test_non_super_account_without_a_unit_is_blocked(): void
    {
        $adminUnit = User::factory()->create([
            'role' => User::ROLE_ADMIN_UNIT,
            'department_id' => null,
        ]);

        $this->actingAs($adminUnit)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_units_are_not_offered_when_creating_a_new_admin(): void
    {
        $superAdmin = User::factory()->create();
        $activeDepartment = $this->department('Unit aktif untuk pilihan admin');
        $inactiveDepartment = $this->department('Unit nonaktif untuk pilihan admin');
        $inactiveDepartment->update(['is_active' => false]);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('value="'.$activeDepartment->id.'"', false)
            ->assertDontSee('value="'.$inactiveDepartment->id.'"', false);

        $this->actingAs($superAdmin)
            ->post(route('users.unit-admin.store'), [
                'department_source' => 'existing',
                'department_id' => $inactiveDepartment->id,
                'admin_name' => 'Admin Unit Nonaktif',
                'admin_email' => 'admin-unit-nonaktif@example.test',
                'admin_password' => 'password-admin',
                'admin_password_confirmation' => 'password-admin',
            ])
            ->assertSessionHasErrors('department_id');

        $this->assertDatabaseMissing('users', [
            'email' => 'admin-unit-nonaktif@example.test',
        ]);
    }

    public function test_admin_unit_cannot_change_department_status(): void
    {
        $department = $this->department('Unit Admin');
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_UNIT,
            'department_id' => $department->id,
        ]);

        $this->actingAs($admin)
            ->post(route('users.departments.deactivate', $department))
            ->assertForbidden();

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'is_active' => true,
        ]);
    }

    public function test_account_in_an_inactive_unit_cannot_login(): void
    {
        $department = $this->department('Unit Nonaktif');
        $department->update(['is_active' => false]);
        $user = User::factory()->create([
            'email' => 'blocked@example.test',
            'password' => 'password-user',
            'role' => User::ROLE_ADMIN_UNIT,
            'department_id' => $department->id,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-user',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_session_is_forced_out_when_its_unit_becomes_inactive(): void
    {
        $department = $this->department('Unit yang dinonaktifkan saat sesi aktif');
        $user = User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'department_id' => $department->id,
        ]);

        $this->actingAs($user);
        $department->update(['is_active' => false]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_unit_creates_pengguna_in_its_own_unit_only(): void
    {
        $ownDepartment = $this->department('Sekretariat Kecamatan Pontianak Selatan');
        $otherDepartment = $this->department('Kecamatan Pontianak Timur');
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN_UNIT,
            'department_id' => $ownDepartment->id,
        ]);

        $this->actingAs($admin)
            ->post(route('users.pengguna.store'), [
                'name' => 'Pengguna Sekretariat',
                'email' => 'pengguna-sekretariat@example.test',
                'password' => 'password-pengguna',
                'password_confirmation' => 'password-pengguna',
                'department_id' => $otherDepartment->id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'pengguna-sekretariat@example.test',
            'role' => User::ROLE_PENGGUNA,
            'department_id' => $ownDepartment->id,
        ]);

        $this->actingAs($admin)
            ->post(route('users.unit-admin.store'), [
                'department_name' => 'Unit Tidak Diizinkan',
                'admin_name' => 'Admin Baru',
                'admin_email' => 'admin-baru@example.test',
                'admin_password' => 'password-admin',
                'admin_password_confirmation' => 'password-admin',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('employees.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Pengguna Sekretariat')
            ->assertDontSee('Kecamatan Pontianak Timur');
    }

    public function test_pengguna_can_only_see_and_create_leave_requests_for_its_unit(): void
    {
        $ownDepartment = $this->department('Sekretariat Kecamatan Pontianak Selatan');
        $otherDepartment = $this->department('Kecamatan Pontianak Timur');
        $pengguna = User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'department_id' => $ownDepartment->id,
        ]);
        $ownEmployee = $this->employee($ownDepartment, '198001012010011001', 'Pegawai Sekretariat');
        $otherEmployee = $this->employee($otherDepartment, '198101012010011002', 'Pegawai Unit Lain');
        $leaveType = LeaveType::query()->create([
            'code' => 'annual',
            'name' => 'Cuti Tahunan',
            'is_active' => true,
        ]);
        $ownRequest = $this->leaveRequest($ownEmployee, $leaveType);
        $otherRequest = $this->leaveRequest($otherEmployee, $leaveType);

        $this->actingAs($pengguna)
            ->get(route('leave-requests.index'))
            ->assertOk()
            ->assertSee($ownEmployee->full_name)
            ->assertDontSee($otherEmployee->full_name);

        $this->actingAs($pengguna)
            ->get(route('leave-requests.create'))
            ->assertOk()
            ->assertSee($ownEmployee->nip)
            ->assertDontSee($otherEmployee->nip);

        $this->actingAs($pengguna)
            ->get(route('leave-requests.show', $otherRequest))
            ->assertNotFound();

        Official::query()->create([
            'signature_role' => 'authorized_official',
            'full_name' => 'Pejabat Berwenang',
            'position_title' => 'Sekretaris',
            'is_active' => true,
        ]);

        $this->actingAs($pengguna)
            ->post(route('leave-requests.store'), [
                'idempotency_key' => 'b7ea178c-7745-43f2-b4d7-c6ebeaad593f',
                'employee_id' => $otherEmployee->id,
                'leave_type_id' => $leaveType->id,
                'form_date' => '2026-08-10',
                'reason' => 'Keperluan keluarga.',
                'start_date' => '2026-08-12',
                'end_date' => '2026-08-12',
                'duration_unit' => 'day',
                'phone_during_leave' => '081234567890',
                'supervisor_employee_id' => $ownEmployee->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('leave_requests', ['id' => $ownRequest->id]);
        $this->assertDatabaseMissing('leave_requests', [
            'idempotency_key' => 'b7ea178c-7745-43f2-b4d7-c6ebeaad593f',
        ]);

        $this->actingAs($pengguna)
            ->get(route('users.index'))
            ->assertForbidden();

        $token = $pengguna->createToken('Tidak diizinkan', ['employees:read'])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.v1.employees.index'))
            ->assertForbidden();
    }

    private function department(string $name): Department
    {
        return Department::query()->create([
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function employee(Department $department, string $nip, string $fullName): Employee
    {
        return Employee::query()->create([
            'department_id' => $department->id,
            'nip' => $nip,
            'full_name' => $fullName,
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
    }

    private function leaveRequest(Employee $employee, LeaveType $leaveType): LeaveRequest
    {
        return LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'form_date' => '2026-08-10',
            'reason' => 'Keperluan keluarga.',
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'duration_value' => 1,
            'duration_unit' => 'day',
            'employee_snapshot' => [],
            'officials_snapshot' => [],
            'status' => 'generated',
        ]);
    }
}
