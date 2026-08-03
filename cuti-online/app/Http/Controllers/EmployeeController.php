<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Services\EmployeeOnboardingService;
use App\Support\EmployeeRankOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 20, 50];

    public function __construct(private readonly EmployeeOnboardingService $onboarding) {}

    public function index(Request $request): View
    {
        $showArchived = $request->boolean('archived');
        $input = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', Rule::in(['PNS', 'PPPK', 'Lainnya'])],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'is_active' => ['nullable', Rule::in(['0', '1'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ]);
        $search = trim((string) ($input['search'] ?? ''));
        $employmentStatus = $input['employment_status'] ?? null;
        $departmentId = $input['department_id'] ?? null;
        $isActive = $showArchived ? null : ($input['is_active'] ?? null);
        $perPage = (int) ($input['per_page'] ?? self::PER_PAGE_OPTIONS[0]);

        return view('employees.index', [
            'employees' => Employee::query()
                ->when($showArchived, static fn ($query) => $query->onlyTrashed())
                ->when($search !== '', static function ($query) use ($search): void {
                    $searchTerm = "%{$search}%";

                    $query->where(static function ($query) use ($searchTerm): void {
                        $query->where('full_name', 'like', $searchTerm)
                            ->orWhere('nip', 'like', $searchTerm)
                            ->orWhere('rank_name', 'like', $searchTerm)
                            ->orWhere('grade', 'like', $searchTerm)
                            ->orWhere('position_title', 'like', $searchTerm)
                            ->orWhereHas('department', static fn ($departmentQuery) => $departmentQuery->where('name', 'like', $searchTerm))
                            ->orWhereHas('position', static fn ($positionQuery) => $positionQuery->where('name', 'like', $searchTerm));
                    });
                })
                ->when($employmentStatus, static fn ($query, $employmentStatus) => $query->where('employment_status', $employmentStatus))
                ->when($departmentId, static fn ($query, $departmentId) => $query->where('department_id', $departmentId))
                ->when($isActive !== null, static fn ($query) => $query->where('is_active', $isActive === '1'))
                ->with(['department', 'position'])
                ->latest($showArchived ? 'deleted_at' : 'created_at')
                ->paginate($perPage)
                ->withQueryString(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'perPage' => $perPage,
            'filters' => [
                'search' => $search,
                'employment_status' => $employmentStatus,
                'department_id' => $departmentId,
                'is_active' => $isActive,
            ],
            'hasFilters' => $search !== '' || $employmentStatus !== null || $departmentId !== null || $isActive !== null,
            'showArchived' => $showArchived,
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'organizationProfile' => OrganizationProfile::current(),
            'rankGroups' => $this->rankGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEmployee($request);
        $this->onboarding->create($data, $request->user()?->id);

        return to_route('employees.index')->with('status', 'Data pegawai berhasil ditambahkan.');
    }

    public function show(Employee $employee): View
    {
        $employee->load([
            'department',
            'position',
            'rankHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
            'positionHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
        ]);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $employee->load(['department', 'position']);

        return view('employees.edit', [
            'employee' => $employee,
            'organizationProfile' => OrganizationProfile::current(),
            'rankGroups' => $this->rankGroups(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validateEmployee($request, $employee);
        [$department, $position] = $this->organizationFor($data);
        $attributes = $this->employeeAttributes($data, $department, $position);

        DB::transaction(function () use ($employee, $attributes, $request): void {
            $employee->fill($attributes);
            $rankChanged = $employee->isDirty(['rank_name', 'grade']);
            $positionChanged = $employee->isDirty(['department_id', 'position_id', 'position_title']);
            $employee->save();

            if ($rankChanged && $employee->rank_name !== null && $employee->grade !== null) {
                $this->recordRankHistory(
                    $employee,
                    now()->toDateString(),
                    null,
                    'Perubahan melalui edit data pegawai.',
                    $request->user()?->id,
                );
            }

            if ($positionChanged) {
                $this->recordPositionHistory(
                    $employee,
                    now()->toDateString(),
                    null,
                    'Perubahan melalui edit data pegawai.',
                    $request->user()?->id,
                );
            }
        });

        return to_route('employees.show', $employee)->with('status', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return to_route('employees.index')
            ->with('status', 'Pegawai berhasil diarsipkan. Data dan riwayatnya tetap tersimpan.');
    }

    public function restore(Employee $employee): RedirectResponse
    {
        if (! $employee->trashed()) {
            return to_route('employees.index')
                ->with('status', 'Pegawai tersebut sudah aktif.');
        }

        $employee->restore();

        return to_route('employees.index')
            ->with('status', 'Pegawai berhasil dipulihkan dan kembali tampil di daftar aktif.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $rankOptions = EmployeeRankOptions::optionsFor($request->input('employment_status'));
        $nipRule = Rule::unique('employees', 'nip');

        if ($employee !== null) {
            $nipRule->ignore($employee);
        }

        $data = $request->validate([
            'nip' => ['required', 'string', 'max:32', $nipRule],
            'full_name' => ['required', 'string', 'max:255'],
            'position_title' => ['required', 'string', 'max:255'],
            'rank_grade' => [
                'nullable',
                Rule::requiredIf($request->input('employment_status') === 'PNS'),
                Rule::in(array_keys($rankOptions)),
            ],
            'employment_status' => ['required', 'string', 'max:32'],
            'service_started_on' => ['nullable', 'date'],
            'phone' => ['nullable', 'digits_between:8,15'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);
        $rank = isset($data['rank_grade']) ? $rankOptions[$data['rank_grade']] : null;

        $data['rank_name'] = $rank['name'] ?? null;
        $data['grade'] = $rank['grade'] ?? null;

        return $data;
    }

    /**
     * @return array<string, list<array{label: string, ranks: list<array{name: string, grade: string, label?: string}>}>>
     */
    private function rankGroups(): array
    {
        return [
            'PNS' => EmployeeRankOptions::groupsFor('PNS'),
            'PPPK' => EmployeeRankOptions::groupsFor('PPPK'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{Department, Position}
     */
    private function organizationFor(array $data): array
    {
        $department = OrganizationProfile::current()->resolveDepartment();
        $position = Position::query()->firstOrCreate(
            ['name' => trim($data['position_title'])],
            ['is_active' => true],
        );

        return [$department, $position];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function employeeAttributes(array $data, Department $department, Position $position): array
    {
        return [
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => $data['nip'],
            'full_name' => $data['full_name'],
            'position_title' => $data['position_title'],
            'rank_name' => $data['rank_name'],
            'grade' => $data['grade'],
            'employment_status' => $data['employment_status'],
            'service_started_on' => $data['service_started_on'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
        ];
    }

    private function recordRankHistory(
        Employee $employee,
        string $effectiveOn,
        ?string $decreeNumber,
        ?string $notes,
        ?int $createdBy,
    ): void {
        EmployeeRankHistory::query()->create([
            'employee_id' => $employee->id,
            'rank_name' => $employee->rank_name,
            'grade' => $employee->grade,
            'effective_on' => $effectiveOn,
            'decree_number' => $decreeNumber,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }

    private function recordPositionHistory(
        Employee $employee,
        string $effectiveOn,
        ?string $decreeNumber,
        ?string $notes,
        ?int $createdBy,
    ): void {
        EmployeePositionHistory::query()->create([
            'employee_id' => $employee->id,
            'department_id' => $employee->department_id,
            'position_id' => $employee->position_id,
            'department_name' => $employee->department?->name ?? '-',
            'position_title' => $employee->position?->name ?? $employee->position_title ?? '-',
            'effective_on' => $effectiveOn,
            'decree_number' => $decreeNumber,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }
}
