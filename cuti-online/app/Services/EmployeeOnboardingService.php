<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\LeaveBalance;
use App\Models\OrganizationProfile;
use App\Models\Position;
use Illuminate\Support\Facades\DB;

class EmployeeOnboardingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy = null, string $historyNote = 'Data awal pegawai.'): Employee
    {
        return DB::transaction(function () use ($data, $createdBy, $historyNote): Employee {
            $department = OrganizationProfile::current()->resolveDepartment();
            $position = Position::query()->firstOrCreate(
                ['name' => trim((string) $data['position_title'])],
                ['is_active' => true],
            );
            $employee = Employee::query()->create([
                'department_id' => $department->id,
                'position_id' => $position->id,
                'nip' => $data['nip'],
                'full_name' => $data['full_name'],
                'position_title' => $position->name,
                'rank_name' => $data['rank_name'] ?? null,
                'grade' => $data['grade'] ?? null,
                'employment_status' => $data['employment_status'],
                'service_started_on' => $data['service_started_on'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
            ]);

            LeaveBalance::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_year' => now()->year,
                ],
                [
                    'current_year_entitlement' => 12,
                    'current_year_used' => 0,
                    'carryover_n1' => 0,
                    'carryover_n2' => 0,
                ],
            );

            $this->recordInitialCareerHistory($employee, $department, $position, $createdBy, $historyNote);

            return $employee;
        });
    }

    private function recordInitialCareerHistory(
        Employee $employee,
        Department $department,
        Position $position,
        ?int $createdBy,
        string $historyNote,
    ): void {
        $effectiveOn = now()->toDateString();

        if ($employee->rank_name !== null && $employee->grade !== null) {
            EmployeeRankHistory::query()->create([
                'employee_id' => $employee->id,
                'rank_name' => $employee->rank_name,
                'grade' => $employee->grade,
                'effective_on' => $effectiveOn,
                'notes' => $historyNote,
                'created_by' => $createdBy,
            ]);
        }

        EmployeePositionHistory::query()->create([
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'department_name' => $department->name,
            'position_title' => $position->name,
            'effective_on' => $effectiveOn,
            'notes' => $historyNote,
            'created_by' => $createdBy,
        ]);
    }
}
