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
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'nip' => ['nullable', 'string', 'max:32'],
            'active' => ['nullable', 'boolean'],
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('api.pagination.max')],
        ]);
        $employees = Employee::query()
            ->with(['department', 'position'])
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
        return new EmployeeResource($employee->load(['department', 'position']));
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

        return (new EmployeeResource($employee->load(['department', 'position'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED)
            ->header('Location', route('api.v1.employees.show', ['employee' => $employee->nip]));
    }
}
