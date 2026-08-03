<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Official;
use App\Models\OrganizationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $organizationProfile = OrganizationProfile::current();
        $department = Department::query()
            ->where('name', $organizationProfile->name)
            ->first();

        return view('settings.index', [
            'currentYear' => now()->year,
            'employees' => Employee::query()
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get(),
            'organizationProfile' => $organizationProfile,
            'balances' => LeaveBalance::query()
                ->with('employee')
                ->where('leave_year', now()->year)
                ->orderByDesc('updated_at')
                ->get(),
            'supervisor' => $department === null ? null : Official::query()
                ->where('department_id', $department->id)
                ->where('is_active', true)
                ->where('signature_role', 'direct_supervisor')
                ->latest('updated_at')
                ->first(),
        ]);
    }

    public function storeBalance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'leave_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'current_year_entitlement' => ['required', 'integer', 'min:0', 'max:366'],
            'current_year_used' => ['required', 'integer', 'min:0', 'max:366'],
            'carryover_n1' => ['required', 'integer', 'min:0', 'max:366'],
            'carryover_n2' => ['required', 'integer', 'min:0', 'max:366'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        LeaveBalance::query()->updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'leave_year' => $data['leave_year'],
            ],
            [
                'current_year_entitlement' => $data['current_year_entitlement'],
                'current_year_used' => $data['current_year_used'],
                'carryover_n1' => $data['carryover_n1'],
                'carryover_n2' => $data['carryover_n2'],
                'notes' => $data['notes'] ?? null,
            ],
        );

        return to_route('settings.index')->with('status', 'Saldo cuti berhasil disimpan.');
    }

    public function storeSupervisor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:32'],
            'position_title' => ['required', 'string', 'max:255'],
        ]);
        $department = OrganizationProfile::current()->resolveDepartment();

        Official::query()->updateOrCreate(
            [
                'department_id' => $department->id,
                'signature_role' => 'direct_supervisor',
                'is_active' => true,
            ],
            [
                'full_name' => $data['full_name'],
                'nip' => $data['nip'] ?? null,
                'position_title' => $data['position_title'],
            ],
        );

        return to_route('settings.index')->with('status', 'Data atasan langsung berhasil disimpan.');
    }
}
