<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_archive_and_restore_an_employee_without_losing_leave_history(): void
    {
        $operator = User::factory()->create();
        $employee = Employee::query()->create([
            'nip' => '199001012020011007',
            'full_name' => 'Pegawai Arsip',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        $leaveType = LeaveType::query()->create([
            'code' => 'annual',
            'name' => 'Cuti Tahunan',
            'is_active' => true,
        ]);
        $leaveRequest = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'form_date' => '2026-07-28',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'duration_value' => 1,
            'employee_snapshot' => ['full_name' => $employee->full_name],
        ]);

        $this->actingAs($operator)
            ->delete(route('employees.destroy', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertDatabaseCount('employees', 1);
        $this->assertSame($employee->id, $leaveRequest->fresh()->employee?->id);

        $this->actingAs($operator)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertDontSee('Pegawai Arsip');
        $this->actingAs($operator)
            ->get(route('employees.index', ['archived' => 1]))
            ->assertOk()
            ->assertSee('Arsip pegawai')
            ->assertSee('Pegawai Arsip')
            ->assertSee('Diarsipkan');

        $this->actingAs($operator)
            ->post(route('employees.restore', $employee))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'deleted_at' => null,
        ]);
        $this->actingAs($operator)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Pegawai Arsip');
    }
}
