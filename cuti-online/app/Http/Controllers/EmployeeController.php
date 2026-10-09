<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\OrganizationProfile;
use App\Models\Position;
use App\Services\EmployeeOnboardingService;
use App\Support\EmployeeNipMetadata;
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
            'sort' => ['nullable', Rule::in(['name', 'rank', 'department', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $search = trim((string) ($input['search'] ?? ''));
        $employmentStatus = $input['employment_status'] ?? null;
        $departmentId = $input['department_id'] ?? null;
        $isActive = $showArchived ? null : ($input['is_active'] ?? null);
        $perPage = (int) ($input['per_page'] ?? self::PER_PAGE_OPTIONS[0]);
        $sort = $input['sort'] ?? null;
        $direction = $input['direction'] ?? 'asc';

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
                ->with([
                    'department',
                    'position',
                    'positionHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
                ])
                ->when($sort, function ($query) use ($sort, $direction, $showArchived): void {
                    if ($sort === 'rank') {
                        $grades = array_unique(array_merge(array_keys(EmployeeRankOptions::optionsFor('PNS')), array_keys(EmployeeRankOptions::optionsFor('PPPK'))));
                        $cases = [];
                        $bindings = [];
                        foreach (array_values($grades) as $index => $grade) {
                            $cases[] = 'WHEN grade = ? THEN ?';
                            array_push($bindings, $grade, $index);
                        }
                        $query->orderByRaw('CASE '.implode(' ', $cases).' ELSE 999 END '.$direction, $bindings)
                            ->orderBy('rank_name', $direction);
                    } elseif ($sort === 'department') {
                        $query->orderByRaw("LOWER(COALESCE((SELECT name FROM departments WHERE departments.id = employees.department_id), (SELECT department_name FROM employee_position_histories WHERE employee_position_histories.employee_id = employees.id AND department_name IS NOT NULL AND department_name <> '' ORDER BY effective_on DESC, id DESC LIMIT 1), '')) ".$direction);
                    } elseif ($sort === 'status') {
                        if (! $showArchived) {
                            $query->orderBy('is_active', $direction);
                        }
                    } else {
                        $query->orderByRaw('LOWER(full_name) '.$direction);
                    }
                    if ($sort !== 'name') {
                        $query->orderByRaw('LOWER(full_name) asc');
                    }
                    $query->orderBy('id');
                }, fn ($query) => $query->latest($showArchived ? 'deleted_at' : 'created_at')->latest('id'))
                ->paginate($perPage)
                ->withQueryString(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'perPage' => $perPage,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => [
                'search' => $search,
                'employment_status' => $employmentStatus,
                'department_id' => $departmentId,
                'is_active' => $isActive,
            ],
            'hasFilters' => $search !== '' || $employmentStatus !== null || $departmentId !== null || $isActive !== null,
            'showArchived' => $showArchived,
            'canBulkDelete' => $request->user()?->canViewSensitiveSimpegData() ?? false,
        ]);
    }

    public function create(Request $request): View
    {
        return view('employees.create', [
            'organizationProfile' => OrganizationProfile::current(),
            'rankGroups' => $this->rankGroups(),
            'canViewSensitive' => $request->user()?->canViewSensitiveSimpegData() ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEmployee($request);
        $this->onboarding->create($data, $request->user()?->id);

        return to_route('employees.index')->with('status', 'Data pegawai berhasil ditambahkan.');
    }

    public function show(Request $request, Employee $employee): View
    {
        $employee->load([
            'department',
            'position',
            'rankHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
            'positionHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
            'bankAccounts' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('id'),
        ]);

        $currentDepartmentName = $employee->department?->name
            ?? $employee->positionHistories->firstWhere('department_name')?->department_name;
        $canViewSensitive = $request->user()?->canViewSensitiveSimpegData() ?? false;

        return view('employees.show', compact('employee', 'currentDepartmentName', 'canViewSensitive'));
    }

    public function edit(Request $request, Employee $employee): View
    {
        $employee->load([
            'department',
            'position',
            'positionHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
        ]);
        $currentDepartmentName = $employee->department?->name
            ?? $employee->positionHistories->firstWhere('department_name')?->department_name;

        return view('employees.edit', [
            'employee' => $employee,
            'organizationProfile' => OrganizationProfile::current(),
            'currentDepartmentName' => $currentDepartmentName,
            'rankGroups' => $this->rankGroups(),
            'canViewSensitive' => $request->user()?->canViewSensitiveSimpegData() ?? false,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validateEmployee($request, $employee);

        if (! ($request->user()?->canViewSensitiveSimpegData() ?? false)) {
            $data['nik'] = $employee->nik;
            $data['npwp'] = $employee->npwp;
            $data['spouse_nip'] = $employee->spouse_nip;
        }

        [$department, $position] = $this->organizationFor($data, $employee);
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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canViewSensitiveSimpegData() ?? false, 403);

        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')],
        ]);

        $count = Employee::query()
            ->whereKey($data['employee_ids'])
            ->delete();

        $word = $count === 1 ? 'pegawai berhasil diarsipkan' : "{$count} pegawai berhasil diarsipkan";

        return to_route('employees.index')
            ->with('status', "{$word}. Data dan riwayat tetap tersimpan dan dapat dipulihkan.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        $request->merge([
            'nik' => $this->digitsOrNull($request->input('nik')),
            'npwp' => $this->digitsOrNull($request->input('npwp')),
            'spouse_nip' => $this->digitsOrNull($request->input('spouse_nip')),
        ]);
        $rankOptions = EmployeeRankOptions::optionsFor($request->input('employment_status'));
        $nipRule = Rule::unique('employees', 'nip');
        $nikRule = Rule::unique('employees', 'nik')->whereNotNull('nik');

        if ($employee !== null) {
            $nipRule->ignore($employee);
            $nikRule->ignore($employee);
        }

        $data = $request->validate([
            'nip' => ['required', 'string', 'max:32', $nipRule],
            'nik' => ['nullable', 'digits:16', $nikRule],
            'npwp' => ['nullable', 'digits_between:15,16'],
            'full_name' => ['required', 'string', 'max:255'],
            'position_title' => ['nullable', 'string', 'max:255'],
            'position_type' => ['nullable', 'integer', Rule::in(array_keys(Employee::POSITION_TYPES))],
            'eselon' => ['nullable', 'string', 'max:8', 'regex:/^[0-9A-Za-z\/-]+$/'],
            'rank_grade' => [
                'nullable',
                Rule::requiredIf($request->input('employment_status') === 'PNS'),
                Rule::in(array_keys($rankOptions)),
            ],
            'employment_status' => ['nullable', 'string', Rule::in(['PNS', 'PPPK', 'Lainnya'])],
            'marital_status' => ['nullable', 'integer', Rule::in([1, 2])],
            'spouse_count' => ['nullable', 'integer', 'min:0', 'max:255'],
            'child_count' => ['nullable', 'integer', 'min:0', 'max:255'],
            'spouse_is_pns' => ['nullable', 'boolean'],
            'spouse_nip' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date'],
            'service_started_on' => ['nullable', 'date'],
            'grade_service_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'grade_service_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'phone' => ['nullable', 'digits_between:8,15'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);
        $rank = isset($data['rank_grade']) ? $rankOptions[$data['rank_grade']] : null;

        $data['rank_name'] = $rank['name'] ?? null;
        $data['grade'] = $rank['grade'] ?? null;
        $derived = EmployeeNipMetadata::derive($data['nip']);
        $data['gender'] = $data['gender'] ?? $derived['gender'];
        $data['birth_date'] = $data['birth_date'] ?? $derived['birth_date'];
        $data['service_started_on'] = $data['service_started_on'] ?? $derived['service_started_on'];
        $data['nip_tmt_valid'] = $derived['tmt_valid'];
        $data['eselon'] = $data['eselon'] ?? '00';
        $data['spouse_count'] = (int) ($data['spouse_count'] ?? 0);
        $data['child_count'] = (int) ($data['child_count'] ?? 0);

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
     * @return array{Department, ?Position}
     */
    private function organizationFor(array $data, ?Employee $employee = null): array
    {
        $department = $employee?->department ?? OrganizationProfile::current()->resolveDepartment();
        $positionTitle = trim((string) ($data['position_title'] ?? ''));
        $position = $positionTitle === ''
            ? $employee?->position
            : Position::query()->firstOrCreate(
                ['name' => $positionTitle],
                ['is_active' => true],
            );

        return [$department, $position];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function employeeAttributes(array $data, Department $department, ?Position $position): array
    {
        return [
            'department_id' => $department->id,
            'position_id' => $position?->id,
            'nip' => $data['nip'],
            'nik' => $data['nik'] ?? null,
            'npwp' => $data['npwp'] ?? null,
            'full_name' => $data['full_name'],
            'position_title' => $position?->name,
            'position_type' => $data['position_type'] ?? null,
            'eselon' => $data['eselon'] ?? '00',
            'rank_name' => $data['rank_name'],
            'grade' => $data['grade'],
            'employment_status' => $data['employment_status'] ?? null,
            'marital_status' => $data['marital_status'] ?? null,
            'spouse_count' => $data['spouse_count'] ?? 0,
            'child_count' => $data['child_count'] ?? 0,
            'spouse_is_pns' => $data['spouse_is_pns'] ?? null,
            'spouse_nip' => $data['spouse_nip'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'service_started_on' => $data['service_started_on'] ?? null,
            'nip_tmt_valid' => $data['nip_tmt_valid'] ?? null,
            'grade_service_years' => $data['grade_service_years'] ?? null,
            'grade_service_months' => $data['grade_service_months'] ?? null,
            'gender' => $data['gender'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
        ];
    }

    private function digitsOrNull(mixed $value): ?string
    {
        $digits = EmployeeNipMetadata::digits($value === null ? null : (string) $value);

        return $digits === '' ? null : $digits;
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
