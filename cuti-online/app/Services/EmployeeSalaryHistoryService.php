<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeRankHistory;
use App\Models\EmployeeSalaryHistory;

class EmployeeSalaryHistoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(
        Employee $employee,
        array $data,
        ?int $createdBy = null,
        ?EmployeeRankHistory $rankHistory = null,
    ): EmployeeSalaryHistory {
        return EmployeeSalaryHistory::query()->create([
            'employee_id' => $employee->id,
            'employee_rank_history_id' => $rankHistory?->id ?? ($data['employee_rank_history_id'] ?? null),
            'basic_salary' => $data['basic_salary'],
            'effective_on' => $data['effective_on'],
            'change_reason' => $data['change_reason'] ?? 'other',
            'decree_number' => $data['decree_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $createdBy,
        ]);
    }
}
