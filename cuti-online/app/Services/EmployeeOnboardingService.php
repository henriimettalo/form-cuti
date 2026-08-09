<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\LeaveBalance;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Support\EmployeeNipMetadata;
use Illuminate\Support\Facades\DB;

class EmployeeOnboardingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $createdBy = null, string $historyNote = 'Data awal pegawai.'): Employee
    {
        return DB::transaction(function () use ($data, $createdBy, $historyNote): Employee {
            $departmentName = trim((string) ($data['department_name'] ?? ''));
            $department = $departmentName === ''
                ? OrganizationProfile::current()->resolveDepartment()
                : Department::query()->firstOrCreate(
                    ['name' => $departmentName],
                    ['is_active' => true],
                );

            if (! $department->is_active) {
                $department->update(['is_active' => true]);
            }

            $positionTitle = trim((string) ($data['position_title'] ?? ''));
            $position = $positionTitle === ''
                ? null
                : Position::query()->firstOrCreate(
                    ['name' => $positionTitle],
                    ['is_active' => true],
                );
            $employee = Employee::query()->create(array_merge([
                'department_id' => $department->id,
                'position_id' => $position?->id,
                'nip' => $data['nip'],
                'full_name' => $data['full_name'],
                'position_title' => $position?->name,
                'rank_name' => $data['rank_name'] ?? null,
                'grade' => $data['grade'] ?? null,
                'employment_status' => $data['employment_status'] ?? null,
                'service_started_on' => $data['service_started_on'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
            ], $this->masterAttributes($data)));

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
        ?Position $position,
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

        if ($position !== null) {
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function masterAttributes(array $data): array
    {
        $derived = EmployeeNipMetadata::derive($data['nip'] ?? null);

        return [
            'nik' => $this->digitsOrNull($data['nik'] ?? null),
            'npwp' => $this->digitsOrNull($data['npwp'] ?? null),
            'position_type' => isset($data['position_type']) ? (int) $data['position_type'] : null,
            'eselon' => $data['eselon'] ?? '00',
            'marital_status' => isset($data['marital_status']) ? (int) $data['marital_status'] : null,
            'spouse_count' => (int) ($data['spouse_count'] ?? 0),
            'child_count' => (int) ($data['child_count'] ?? 0),
            'spouse_is_pns' => array_key_exists('spouse_is_pns', $data)
                ? $this->booleanOrNull($data['spouse_is_pns'])
                : null,
            'spouse_nip' => $this->digitsOrNull($data['spouse_nip'] ?? null),
            'birth_date' => $data['birth_date'] ?? $derived['birth_date'],
            'gender' => $data['gender'] ?? $derived['gender'],
            'service_started_on' => $data['service_started_on'] ?? $derived['service_started_on'],
            'nip_tmt_valid' => $derived['tmt_valid'],
            'grade_service_years' => isset($data['grade_service_years'])
                ? (int) $data['grade_service_years']
                : null,
            'grade_service_months' => isset($data['grade_service_months'])
                ? (int) $data['grade_service_months']
                : null,
        ];
    }

    private function digitsOrNull(mixed $value): ?string
    {
        $digits = EmployeeNipMetadata::digits($value === null ? null : (string) $value);

        return $digits === '' ? null : $digits;
    }

    private function booleanOrNull(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return match (strtoupper(trim($value))) {
                'YA', 'YES', 'TRUE', '1' => true,
                'TIDAK', 'NO', 'FALSE', '0' => false,
                default => null,
            };
        }

        return (bool) $value;
    }
}
