<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_save_balance_and_an_organization_supervisor(): void
    {
        $operator = User::factory()->create();
        $department = Department::query()->create([
            'name' => 'Unit Uji',
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'name' => 'Jabatan Uji',
            'is_active' => true,
        ]);
        $employee = Employee::query()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => '199001012020011001',
            'full_name' => 'Pegawai Uji',
            'position_title' => $position->name,
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);

        $this->actingAs($operator)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('data-leave-balance-form', false)
            ->assertSee('data-balance-employee-combobox', false)
            ->assertSee('data-balance-employee-option-index', false)
            ->assertSee('Cari nama atau NIP, mis. suri*008', false)
            ->assertDontSee('<select class="form-select" id="employee_id"', false);

        $this->actingAs($operator)->post(route('settings.balances.store'), [
            'employee_id' => $employee->id,
            'leave_year' => 2026,
            'current_year_entitlement' => 12,
            'current_year_used' => 2,
            'carryover_n1' => 3,
            'carryover_n2' => 0,
        ])->assertRedirect(route('settings.index'));

        $this->actingAs($operator)->post(route('settings.supervisors.store'), [
            'full_name' => 'Pejabat Uji',
            'nip' => '198601222004122001',
            'position_title' => 'Camat Uji',
        ])->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_year' => 2026,
            'current_year_used' => 2,
        ]);
        $this->assertDatabaseHas('officials', [
            'department_id' => OrganizationProfile::current()->resolveDepartment()->id,
            'signature_role' => 'direct_supervisor',
            'full_name' => 'Pejabat Uji',
        ]);
    }
}
