<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCareerHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_record_rank_and_position_history(): void
    {
        $operator = User::factory()->create();
        $department = Department::query()->create([
            'name' => 'Unit Lama',
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'name' => 'Jabatan Lama',
            'is_active' => true,
        ]);
        $employee = Employee::query()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => '199001012020011004',
            'full_name' => 'Pegawai Riwayat',
            'position_title' => $position->name,
            'rank_name' => 'Penata',
            'grade' => 'III/c',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);

        $this->actingAs($operator)->get(route('employees.rank-histories.create', $employee))
            ->assertOk()
            ->assertSee('Catat riwayat pangkat');
        $this->actingAs($operator)->get(route('employees.position-histories.create', $employee))
            ->assertOk()
            ->assertSee('Catat riwayat jabatan');

        $this->actingAs($operator)->post(route('employees.rank-histories.store', $employee), [
            'rank_grade' => 'IV/a',
            'effective_on' => '2026-08-01',
            'decree_number' => 'SK-001/2026',
            'notes' => 'Kenaikan pangkat reguler.',
        ])->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertSame('Pembina', $employee->rank_name);
        $this->assertSame('IV/a', $employee->grade);
        $this->assertDatabaseHas('employee_rank_histories', [
            'employee_id' => $employee->id,
            'rank_name' => 'Pembina',
            'grade' => 'IV/a',
            'decree_number' => 'SK-001/2026',
        ]);
        $this->assertSame('2026-08-01', EmployeeRankHistory::query()->firstOrFail()->effective_on->toDateString());

        $this->actingAs($operator)->post(route('employees.position-histories.store', $employee), [
            'position_title' => 'Jabatan Baru',
            'effective_on' => '2026-08-02',
            'decree_number' => 'SK-002/2026',
            'notes' => 'Mutasi internal.',
        ])->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertSame('Jabatan Baru', $employee->position_title);
        $this->assertDatabaseHas('employee_position_histories', [
            'employee_id' => $employee->id,
            'department_name' => OrganizationProfile::current()->name,
            'position_title' => 'Jabatan Baru',
            'decree_number' => 'SK-002/2026',
        ]);
        $this->assertSame('2026-08-02', EmployeePositionHistory::query()->firstOrFail()->effective_on->toDateString());

        $this->actingAs($operator)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Riwayat pangkat dan golongan')
            ->assertSee('Riwayat jabatan');
    }
}
