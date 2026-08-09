<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEmployeeSalaryHistoryRequest;
use App\Http\Resources\Api\V1\EmployeeSalaryHistoryResource;
use App\Models\Employee;
use App\Models\EmployeeRankHistory;
use App\Services\EmployeeSalaryHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeSalaryHistoryController extends Controller
{
    public function index(Employee $employee): AnonymousResourceCollection
    {
        $histories = $employee->salaryHistories()
            ->with(['employee', 'rankHistory', 'createdBy'])
            ->latest('effective_on')
            ->latest('id')
            ->paginate(config('api.pagination.default'))
            ->withQueryString();

        return EmployeeSalaryHistoryResource::collection($histories);
    }

    public function store(
        StoreEmployeeSalaryHistoryRequest $request,
        Employee $employee,
        EmployeeSalaryHistoryService $salaryHistories,
    ): JsonResponse {
        $data = $request->validated();
        $rankHistory = isset($data['employee_rank_history_id'])
            ? EmployeeRankHistory::query()->find($data['employee_rank_history_id'])
            : null;
        $history = $salaryHistories->record(
            $employee,
            $data,
            $request->user()?->id,
            $rankHistory,
        );

        return (new EmployeeSalaryHistoryResource($history->load(['employee', 'rankHistory', 'createdBy'])))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.v1.employees.salary-histories.index', ['employee' => $employee->nip]));
    }
}
