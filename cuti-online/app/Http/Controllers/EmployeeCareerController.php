<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\EmployeeSalaryHistory;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Services\EmployeeSalaryHistoryService;
use App\Support\EmployeeRankOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeCareerController extends Controller
{
    public function __construct(private readonly EmployeeSalaryHistoryService $salaryHistories) {}

    public function createRank(Employee $employee): View
    {
        $employee->load([
            'department',
            'position',
            'rankHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
            'salaryHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
        ]);

        return view('employees.rank-history-create', [
            'employee' => $employee,
            'currentSalary' => $employee->salaryHistories->first(),
            'rankGroups' => EmployeeRankOptions::groupsFor($employee->employment_status),
        ]);
    }

    public function storeRank(Request $request, Employee $employee): RedirectResponse
    {
        $rankOptions = EmployeeRankOptions::optionsFor($employee->employment_status);
        $data = $request->validate([
            'rank_grade' => ['required', Rule::in(array_keys($rankOptions))],
            'effective_on' => ['required', 'date'],
            'basic_salary' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $rank = $rankOptions[$data['rank_grade']];

        DB::transaction(function () use ($request, $employee, $data, $rank): void {
            $employee->update([
                'rank_name' => $rank['name'],
                'grade' => $rank['grade'],
            ]);

            $rankHistory = EmployeeRankHistory::query()->create([
                'employee_id' => $employee->id,
                'rank_name' => $rank['name'],
                'grade' => $rank['grade'],
                'effective_on' => $data['effective_on'],
                'decree_number' => $data['decree_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            if (($data['basic_salary'] ?? null) !== null) {
                $this->salaryHistories->record(
                    $employee,
                    [
                        'basic_salary' => $data['basic_salary'],
                        'effective_on' => $data['effective_on'],
                        'change_reason' => 'rank_change',
                        'decree_number' => $data['decree_number'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ],
                    $request->user()?->id,
                    $rankHistory,
                );
            }
        });

        $status = ($data['basic_salary'] ?? null) !== null
            ? 'Riwayat pangkat dan gaji pokok berhasil dicatat. Data pegawai diperbarui.'
            : 'Riwayat pangkat berhasil dicatat dan data pegawai diperbarui.';

        return to_route('employees.show', $employee)
            ->with('status', $status);
    }

    public function createSalary(Employee $employee): View
    {
        $employee->load([
            'department',
            'position',
            'rankHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
            'salaryHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
        ]);

        return view('employees.salary-history-create', [
            'employee' => $employee,
            'currentSalary' => $employee->salaryHistories->first(),
            'reasonOptions' => EmployeeSalaryHistory::reasonOptions(),
        ]);
    }

    public function storeSalary(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'basic_salary' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'effective_on' => ['required', 'date'],
            'change_reason' => ['required', Rule::in(array_keys(EmployeeSalaryHistory::reasonOptions()))],
            'employee_rank_history_id' => [
                'nullable',
                'integer',
                Rule::exists('employee_rank_histories', 'id')
                    ->where(fn ($query) => $query->where('employee_id', $employee->id)),
            ],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $employee, $data): void {
            $rankHistory = isset($data['employee_rank_history_id'])
                ? EmployeeRankHistory::query()->find($data['employee_rank_history_id'])
                : null;

            $this->salaryHistories->record(
                $employee,
                $data,
                $request->user()?->id,
                $rankHistory,
            );
        });

        return to_route('employees.show', $employee)
            ->with('status', 'Riwayat gaji pokok berhasil dicatat.');
    }

    public function createPosition(Employee $employee): View
    {
        $employee->load([
            'department',
            'position',
            'positionHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
        ]);
        $currentDepartmentName = $employee->positionHistories->firstWhere('department_name')?->department_name
            ?? $employee->department?->name;

        return view('employees.position-history-create', [
            'employee' => $employee,
            'currentDepartmentName' => $currentDepartmentName,
            'organizationProfile' => OrganizationProfile::current(),
        ]);
    }

    public function storePosition(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'position_title' => ['required', 'string', 'max:255'],
            'effective_on' => ['required', 'date'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $employee, $data): void {
            $department = $employee->department ?? OrganizationProfile::current()->resolveDepartment();
            $position = Position::query()->firstOrCreate(
                ['name' => trim($data['position_title'])],
                ['is_active' => true],
            );

            $employee->update([
                'department_id' => $department->id,
                'position_id' => $position->id,
                'position_title' => $position->name,
            ]);

            EmployeePositionHistory::query()->create([
                'employee_id' => $employee->id,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'department_name' => $department->name,
                'position_title' => $position->name,
                'effective_on' => $data['effective_on'],
                'decree_number' => $data['decree_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);
        });

        return to_route('employees.show', $employee)
            ->with('status', 'Riwayat jabatan berhasil dicatat dan data pegawai diperbarui.');
    }
}
