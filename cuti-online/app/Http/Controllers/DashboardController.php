<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\GeneratedDocument;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('dashboard', [
            'employeeCount' => Employee::query()
                ->visibleTo($user)
                ->where('is_active', true)
                ->count(),
            'careerChangeCount' => EmployeeRankHistory::query()
                ->whereHas('employee', fn (Builder $query) => $query->visibleTo($user))
                ->whereDate('effective_on', '>=', now()->startOfMonth())
                ->count() + EmployeePositionHistory::query()
                ->whereHas('employee', fn (Builder $query) => $query->visibleTo($user))
                ->whereDate('effective_on', '>=', now()->startOfMonth())
                ->count(),
            'requestCount' => LeaveRequest::query()->visibleTo($user)->count(),
            'documentCount' => GeneratedDocument::query()
                ->whereHas('leaveRequest', fn (Builder $query) => $query->visibleTo($user))
                ->count(),
            'recentRequests' => LeaveRequest::query()
                ->visibleTo($user)
                ->with(['employee', 'leaveType', 'generatedDocuments'])
                ->latest('created_at')
                ->take(6)
                ->get(),
        ]);
    }
}
