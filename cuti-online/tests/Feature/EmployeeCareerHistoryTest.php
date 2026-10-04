<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\EmployeeSalaryHistory;
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
            ->assertSee('Catat riwayat pangkat')
            ->assertSee('Gaji pokok baru');
        $this->actingAs($operator)->get(route('employees.position-histories.create', $employee))
            ->assertOk()
            ->assertSee('Catat riwayat jabatan');

        $this->actingAs($operator)->post(route('employees.rank-histories.store', $employee), [
            'rank_grade' => 'IV/a',
            'effective_on' => '2026-08-01',
            'basic_salary' => 4346200,
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
        $this->assertDatabaseHas('employee_salary_histories', [
            'employee_id' => $employee->id,
            'basic_salary' => 4346200,
            'change_reason' => 'rank_change',
            'decree_number' => 'SK-001/2026',
        ]);
        $this->assertSame(
            '2026-08-01',
            EmployeeSalaryHistory::query()->firstOrFail()->effective_on->toDateString(),
        );
        $this->assertSame(
            EmployeeRankHistory::query()->firstOrFail()->id,
            EmployeeSalaryHistory::query()->firstOrFail()->employee_rank_history_id,
        );

        $this->actingAs($operator)->put(route('employees.update', $employee), [
            'nip' => $employee->nip,
            'full_name' => $employee->full_name,
            'position_title' => $employee->position_title,
            'rank_grade' => 'IV/a',
            'employment_status' => 'PNS',
        ])->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertSame($department->id, $employee->department_id);

        $this->actingAs($operator)->post(route('employees.position-histories.store', $employee), [
            'position_title' => 'Jabatan Baru',
            'effective_on' => '2026-08-02',
            'decree_number' => 'SK-002/2026',
            'notes' => 'Mutasi internal.',
        ])->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertSame('Jabatan Baru', $employee->position_title);
        $this->assertSame($department->id, $employee->department_id);
        $this->assertDatabaseHas('employee_position_histories', [
            'employee_id' => $employee->id,
            'department_name' => 'Unit Lama',
            'position_title' => 'Jabatan Baru',
            'decree_number' => 'SK-002/2026',
        ]);
        $this->assertSame('2026-08-02', EmployeePositionHistory::query()->firstOrFail()->effective_on->toDateString());

        $this->actingAs($operator)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Riwayat pangkat dan golongan')
            ->assertDontSee('Riwayat gaji pokok')
            ->assertDontSee('Rp 4.346.200')
            ->assertSee('Riwayat jabatan');
    }

    public function test_operator_can_record_a_salary_change_without_a_rank_change(): void
    {
        $operator = User::factory()->create();
        $department = Department::query()->create([
            'name' => 'Unit Gaji',
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'name' => 'Jabatan Gaji',
            'is_active' => true,
        ]);
        $employee = Employee::query()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => '199001012020011005',
            'full_name' => 'Pegawai Gaji',
            'position_title' => $position->name,
            'rank_name' => 'Penata',
            'grade' => 'III/c',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);

        $this->actingAs($operator)->post(route('employees.salary-histories.store', $employee), [
            'basic_salary' => 4500000,
            'effective_on' => '2026-09-01',
            'change_reason' => 'periodic_increase',
            'notes' => 'Kenaikan gaji berkala.',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_salary_histories', [
            'employee_id' => $employee->id,
            'basic_salary' => 4500000,
            'change_reason' => 'periodic_increase',
            'employee_rank_history_id' => null,
        ]);
    }
}
