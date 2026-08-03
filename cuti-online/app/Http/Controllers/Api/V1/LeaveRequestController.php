<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:32'],
            'employee_nip' => ['nullable', 'string', 'max:32'],
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('api.pagination.max')],
        ]);
        $leaveRequests = LeaveRequest::query()
            ->with(['employee.department', 'employee.position', 'leaveType'])
            ->when($data['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(
                $data['employee_nip'] ?? null,
                fn ($query, string $nip) => $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('nip', $nip)),
            )
            ->when($data['updated_since'] ?? null, fn ($query, string $updatedSince) => $query->where('updated_at', '>=', $updatedSince))
            ->latest('created_at')
            ->paginate($data['per_page'] ?? config('api.pagination.default'))
            ->withQueryString();

        return LeaveRequestResource::collection($leaveRequests);
    }

    public function show(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        return new LeaveRequestResource($leaveRequest->load([
            'employee.department',
            'employee.position',
            'leaveType',
        ]));
    }
}
