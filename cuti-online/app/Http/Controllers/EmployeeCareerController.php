<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Support\EmployeeRankOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeCareerController extends Controller
{
    public function createRank(Employee $employee): View
    {
        return view('employees.rank-history-create', [
            'employee' => $employee->load(['department', 'position']),
            'rankGroups' => EmployeeRankOptions::groupsFor($employee->employment_status),
        ]);
    }

    public function storeRank(Request $request, Employee $employee): RedirectResponse
    {
        $rankOptions = EmployeeRankOptions::optionsFor($employee->employment_status);
        $data = $request->validate([
            'rank_grade' => ['required', Rule::in(array_keys($rankOptions))],
            'effective_on' => ['required', 'date'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $rank = $rankOptions[$data['rank_grade']];

        DB::transaction(function () use ($request, $employee, $data, $rank): void {
            $employee->update([
                'rank_name' => $rank['name'],
                'grade' => $rank['grade'],
            ]);

            EmployeeRankHistory::query()->create([
                'employee_id' => $employee->id,
                'rank_name' => $rank['name'],
                'grade' => $rank['grade'],
                'effective_on' => $data['effective_on'],
                'decree_number' => $data['decree_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);
        });

        return to_route('employees.show', $employee)
            ->with('status', 'Riwayat pangkat berhasil dicatat dan data pegawai diperbarui.');
    }

    public function createPosition(Employee $employee): View
    {
        return view('employees.position-history-create', [
            'employee' => $employee->load(['department', 'position']),
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
            $department = OrganizationProfile::current()->resolveDepartment();
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
