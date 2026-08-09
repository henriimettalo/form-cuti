<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_set_an_organization_default_without_changing_existing_employee_units(): void
    {
        $operator = User::factory()->create();
        $firstDepartment = Department::query()->create(['name' => 'Unit Lama Satu', 'is_active' => true]);
        $secondDepartment = Department::query()->create(['name' => 'Unit Lama Dua', 'is_active' => true]);
        $position = Position::query()->create(['name' => 'Jabatan Uji', 'is_active' => true]);
        $activeEmployee = Employee::query()->create([
            'department_id' => $firstDepartment->id,
            'position_id' => $position->id,
            'nip' => '199001012020011011',
            'full_name' => 'Pegawai Aktif',
            'position_title' => $position->name,
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        $archivedEmployee = Employee::query()->create([
            'department_id' => $secondDepartment->id,
            'position_id' => $position->id,
            'nip' => '199001012020011012',
            'full_name' => 'Pegawai Arsip',
            'position_title' => $position->name,
            'employment_status' => 'PNS',
            'is_active' => false,
        ]);
        $archivedEmployee->delete();
        EmployeePositionHistory::query()->create([
            'employee_id' => $activeEmployee->id,
            'department_id' => $firstDepartment->id,
            'position_id' => $position->id,
            'department_name' => $firstDepartment->name,
            'position_title' => $position->name,
            'effective_on' => '2026-07-01',
        ]);

        $this->actingAs($operator)
            ->get(route('organization-profile.index'))
            ->assertOk()
            ->assertSee('Profil instansi')
            ->assertSee(OrganizationProfile::current()->name);

        $this->actingAs($operator)
            ->post(route('organization-profile.store'), ['name' => 'Kantor Camat Pontianak Selatan'])
            ->assertRedirect(route('organization-profile.index'));

        $organizationDepartment = Department::query()
            ->where('name', 'Kantor Camat Pontianak Selatan')
            ->firstOrFail();

        $this->assertDatabaseHas('organization_profiles', [
            'id' => 1,
            'name' => 'Kantor Camat Pontianak Selatan',
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $activeEmployee->id,
            'department_id' => $firstDepartment->id,
        ]);
        $this->assertDatabaseHas('employees', [
            'id' => $archivedEmployee->id,
            'department_id' => $secondDepartment->id,
        ]);
        $this->assertDatabaseHas('employee_position_histories', [
            'employee_id' => $activeEmployee->id,
            'department_name' => 'Unit Lama Satu',
        ]);

        $this->actingAs($operator)
            ->get(route('employees.create'))
            ->assertOk()
            ->assertSee('Kantor Camat Pontianak Selatan')
            ->assertDontSee('name="department_name"', false);

        $this->actingAs($operator)->post(route('employees.store'), [
            'nip' => '199001012020011013',
            'full_name' => 'Pegawai Baru',
            'position_title' => $position->name,
            'employment_status' => 'PPPK',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'nip' => '199001012020011013',
            'department_id' => $organizationDepartment->id,
        ]);
    }
}
