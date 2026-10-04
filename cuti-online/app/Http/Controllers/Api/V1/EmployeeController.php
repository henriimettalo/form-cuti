<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEmployeeRequest;
use App\Http\Resources\Api\V1\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeOnboardingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'view' => ['nullable', Rule::in(['full', 'imported'])],
            'search' => ['nullable', 'string', 'max:100'],
            'nip' => ['nullable', 'string', 'max:32'],
            'active' => ['nullable', 'boolean'],
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('api.pagination.max')],
        ]);
        $relations = [
            'department',
            'position',
            'positionHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
        ];

        if ($request->user()?->tokenCan('employees:profile')) {
            $relations[] = 'bankAccounts';
        }

        $employees = Employee::query()
            ->with($relations)
            ->when($data['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->when($data['nip'] ?? null, fn ($query, string $nip) => $query->where('nip', $nip))
            ->when($request->has('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->when($data['updated_since'] ?? null, fn ($query, string $updatedSince) => $query->where('updated_at', '>=', $updatedSince))
            ->orderBy('full_name')
            ->paginate($data['per_page'] ?? config('api.pagination.default'))
            ->withQueryString();

        return EmployeeResource::collection($employees);
    }

    public function show(Employee $employee): EmployeeResource
    {
        request()->validate(['view' => ['nullable', Rule::in(['full', 'imported'])]]);
        $relations = [
            'department',
            'position',
            'positionHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
        ];

        if (request()->user()?->tokenCan('employees:profile')) {
            $relations[] = 'bankAccounts';
        }

        return new EmployeeResource($employee->load($relations));
    }

    public function store(StoreEmployeeRequest $request, EmployeeOnboardingService $onboarding): JsonResponse
    {
        try {
            $employee = $onboarding->create(
                $request->employeeData(),
                $request->user()?->id,
                'Data awal dari REST API.',
            );
        } catch (QueryException) {
            return response()->json([
                'message' => 'NIP sudah terdaftar.',
                'errors' => [
                    'nip' => ['NIP sudah terdaftar.'],
                ],
            ], Response::HTTP_CONFLICT);
        }

        $relations = ['department', 'position'];

        if ($request->user()?->tokenCan('employees:profile')) {
            $relations[] = 'bankAccounts';
        }

        return (new EmployeeResource($employee->load($relations)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
            ->header('Location', route('api.v1.employees.show', ['employee' => $employee->nip]));
    }
}
