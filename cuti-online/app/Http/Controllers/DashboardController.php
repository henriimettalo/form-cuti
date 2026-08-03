<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\EmployeeRankHistory;
use App\Models\GeneratedDocument;
use App\Models\LeaveRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'employeeCount' => Employee::query()->where('is_active', true)->count(),
            'careerChangeCount' => EmployeeRankHistory::query()
                ->whereDate('effective_on', '>=', now()->startOfMonth())
                ->count() + EmployeePositionHistory::query()
                ->whereDate('effective_on', '>=', now()->startOfMonth())
                ->count(),
            'requestCount' => LeaveRequest::query()->count(),
            'documentCount' => GeneratedDocument::query()->count(),
            'recentRequests' => LeaveRequest::query()
                ->with(['employee', 'leaveType', 'generatedDocuments'])
                ->latest('created_at')
                ->take(6)
                ->get(),
        ]);
    }
}
