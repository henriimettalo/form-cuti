<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $isSuperAdmin = $actor->isSuperAdmin();
        $users = User::query()
            ->with(['department.parent'])
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query
                    ->where('department_id', $actor->department_id)
                    ->where('role', User::ROLE_PENGGUNA),
            )
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'departments' => $isSuperAdmin
                ? Department::query()->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'parentDepartments' => $isSuperAdmin
                ? Department::query()
                    ->where('is_active', true)
                    ->where(function ($query): void {
                        $query->where('department_type', 'kecamatan')
                            ->orWhereNull('department_type');
                    })
                    ->orderBy('name')
                    ->get()
                : collect(),
            'allDepartments' => $isSuperAdmin
                ? Department::query()
                    ->with('parent')
                    ->withCount([
                        'users as admin_unit_count' => fn ($query) => $query->where('role', User::ROLE_ADMIN_UNIT),
                    ])
                    ->orderByDesc('is_active')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'currentDepartment' => $actor->department,
        ]);
    }

    public function deactivateDepartment(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        if ($department->is_active) {
            $department->update(['is_active' => false]);
        }

        return to_route('users.index')
            ->with('status', "Unit kerja {$department->name} dinonaktifkan. Data dan riwayat tetap tersimpan.")
            ->with('accountTab', 'units');
    }

    public function activateDepartment(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        if (! $department->is_active) {
            $department->update(['is_active' => true]);
        }

        return to_route('users.index')
            ->with('status', "Unit kerja {$department->name} diaktifkan kembali.")
            ->with('accountTab', 'units');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $request->merge([
            'department_code' => $this->normalizeDepartmentCode(
                $request->input('department_code'),
                $request->input('department_type'),
            ),
            'department_simpeg_code' => $this->normalizeSimpegCode($request->input('department_simpeg_code')),
        ]);

        $data = $request->validate($this->departmentRules());

        Department::query()->create([
            ...$this->departmentAttributes($data),
            'is_active' => true,
        ]);

        return to_route('users.index')
            ->with('status', 'Unit kerja berhasil ditambahkan tanpa membuat akun Admin Unit.')
            ->with('accountTab', 'units');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $request->merge([
            'department_code' => $this->normalizeDepartmentCode(
                $request->input('department_code'),
                $request->input('department_type'),
            ),
            'department_simpeg_code' => $this->normalizeSimpegCode($request->input('department_simpeg_code')),
        ]);

        $data = $request->validate($this->departmentRules($department));

        if ($department->children()->exists() && $data['department_type'] === 'kelurahan') {
            throw ValidationException::withMessages([
                'department_type' => 'Jenis unit tidak dapat diubah menjadi Kelurahan selama unit ini masih memiliki unit turunan.',
            ]);
        }

        $department->update($this->departmentAttributes($data));

        return to_route('users.index')
            ->with('status', "Unit kerja {$department->name} berhasil diperbarui.")
            ->with('accountTab', 'units');
    }

    public function storeUnitAdmin(Request $request): RedirectResponse
    {
        if (! in_array($request->input('department_source'), ['existing', 'new'], true)) {
            $request->merge([
                'department_source' => $request->filled('department_id') ? 'existing' : 'new',
            ]);
        }

        if ($request->input('department_source') === 'new' && ! $request->filled('department_type')) {
            $request->merge(['department_type' => 'kecamatan']);
        }

        $request->merge([
            'department_code' => $this->normalizeDepartmentCode(
                $request->input('department_code'),
                $request->input('department_type'),
            ),
            'department_simpeg_code' => $this->normalizeSimpegCode($request->input('department_simpeg_code')),
        ]);

        $data = $request->validate([
            'department_source' => ['required', Rule::in(['existing', 'new'])],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'department_type' => [
                'nullable',
                Rule::in(['kecamatan', 'kelurahan']),
                'required_if:department_source,new',
            ],
            'department_code' => ['nullable', 'string', 'max:32', Rule::unique('departments', 'code')],
            'department_simpeg_code' => ['nullable', 'string', 'max:32', Rule::unique('departments', 'simpeg_code')],
            'department_parent_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(function ($query): void {
                    $query->where('is_active', true)
                        ->where(function ($query): void {
                            $query->where('department_type', 'kecamatan')
                                ->orWhereNull('department_type');
                        });
                }),
            ],
            'department_name' => [
                'nullable',
                'string',
                'max:255',
                'required_without:department_id',
                Rule::unique('departments', 'name'),
            ],
            'department_address' => ['nullable', 'string', 'max:2000'],
            'department_phone' => ['nullable', 'string', 'max:32'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $departmentCode = $this->normalizeDepartmentCode(
            $data['department_code'] ?? null,
            $data['department_type'] ?? null,
        );
        $departmentSimpegCode = $this->normalizeSimpegCode($data['department_simpeg_code'] ?? null);
        $parentDepartmentId = $this->resolveParentDepartmentId($data);

        DB::transaction(function () use ($data, $departmentCode, $departmentSimpegCode, $parentDepartmentId): void {
            $department = isset($data['department_id'])
                ? Department::query()->findOrFail($data['department_id'])
                : Department::query()->create([
                    'code' => $departmentCode,
                    'simpeg_code' => $departmentSimpegCode,
                    'department_type' => $data['department_type'] ?? null,
                    'parent_department_id' => $parentDepartmentId,
                    'name' => $data['department_name'],
                    'address' => $data['department_address'] ?? null,
                    'phone' => $data['department_phone'] ?? null,
                    'is_active' => true,
                ]);

            User::query()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role' => User::ROLE_ADMIN_UNIT,
                'department_id' => $department->id,
            ]);
        });

        return to_route('users.index')
            ->with('status', 'Unit kerja dan akun Admin Unit berhasil dibuat.');
    }

    private function normalizeDepartmentCode(?string $code, ?string $type): ?string
    {
        $value = strtoupper(trim((string) $code));

        if ($value === '') {
            return null;
        }

        $prefix = match ($type) {
            'kecamatan' => 'KEC',
            'kelurahan' => 'KEL',
            default => null,
        };

        if ($prefix === null) {
            return $value;
        }

        $suffix = preg_replace('/^(?:KEC|KEL)(?:[-_\s]*)/i', '', $value) ?? $value;
        $suffix = preg_replace('/[^A-Z0-9]+/', '-', $suffix) ?? $suffix;
        $suffix = trim($suffix, '-');

        return $suffix === '' ? null : $prefix.'-'.$suffix;
    }

    private function normalizeSimpegCode(?string $code): ?string
    {
        $value = strtoupper(trim((string) $code));
        $value = preg_replace('/\s+/', '', $value) ?? $value;
        $value = preg_replace('/[^A-Z0-9.\-_]/', '', $value) ?? $value;

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function departmentRules(?Department $department = null): array
    {
        $ignoreId = $department?->getKey();
        $parentRules = [
            'nullable',
            'integer',
            'required_if:department_type,kelurahan',
            Rule::exists('departments', 'id')->where(function ($query): void {
                $query->where('is_active', true)
                    ->where(function ($query): void {
                        $query->where('department_type', 'kecamatan')
                            ->orWhereNull('department_type');
                    });
            }),
        ];

        if ($ignoreId !== null) {
            $parentRules[] = Rule::notIn([$ignoreId]);
        }

        return [
            'department_type' => ['required', Rule::in(['kecamatan', 'kelurahan'])],
            'department_code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('departments', 'code')->ignore($ignoreId),
            ],
            'department_simpeg_code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('departments', 'simpeg_code')->ignore($ignoreId),
            ],
            'department_parent_id' => $parentRules,
            'department_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($ignoreId),
            ],
            'department_address' => ['nullable', 'string', 'max:2000'],
            'department_phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function departmentAttributes(array $data): array
    {
        return [
            'code' => $this->normalizeDepartmentCode(
                $data['department_code'] ?? null,
                $data['department_type'] ?? null,
            ),
            'simpeg_code' => $this->normalizeSimpegCode($data['department_simpeg_code'] ?? null),
            'department_type' => $data['department_type'],
            'parent_department_id' => $data['department_type'] === 'kelurahan'
                ? ($data['department_parent_id'] ?? null)
                : null,
            'name' => $data['department_name'],
            'address' => $data['department_address'] ?? null,
            'phone' => $data['department_phone'] ?? null,
        ];
    }

    /**
     * Keep older clients working by using the only active Kecamatan as the
     * parent when a Kelurahan request does not send one explicitly.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveParentDepartmentId(array $data): ?int
    {
        if (($data['department_type'] ?? null) !== 'kelurahan') {
            return null;
        }

        if (! empty($data['department_parent_id'])) {
            return (int) $data['department_parent_id'];
        }

        $parentIds = Department::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('department_type', 'kecamatan')
                    ->orWhereNull('department_type');
            })
            ->pluck('id');

        return $parentIds->count() === 1 ? (int) $parentIds->first() : null;
    }

    public function storePengguna(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->isAdminUnit(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_PENGGUNA,
            'department_id' => $actor->department_id,
        ]);

        return to_route('users.index')
            ->with('status', 'Akun pengguna berhasil dibuat untuk unit kerja Anda.');
    }
}
