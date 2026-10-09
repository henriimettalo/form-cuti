<?php

use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'role:super_admin', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/employees', [EmployeeController::class, 'index'])
            ->middleware('abilities:employees:read')
            ->name('api.v1.employees.index');
        Route::get('/employees/{employee:nip}', [EmployeeController::class, 'show'])
            ->middleware('abilities:employees:read')
            ->name('api.v1.employees.show');
        Route::post('/employees', [EmployeeController::class, 'store'])
            ->middleware('abilities:employees:write')
            ->name('api.v1.employees.store');
        Route::get('/leave-requests', [LeaveRequestController::class, 'index'])
            ->middleware('abilities:leave-requests:read')
            ->name('api.v1.leave-requests.index');
        Route::get('/leave-requests/{leaveRequest:public_id}', [LeaveRequestController::class, 'show'])
            ->middleware('abilities:leave-requests:read')
            ->name('api.v1.leave-requests.show');
        Route::get('/ceremony-schedules', [ScheduleController::class, 'ceremonies'])
            ->middleware('abilities:ceremony-schedules:read')
            ->name('api.v1.ceremony-schedules.index');
        Route::get('/counter-duty-schedules', [ScheduleController::class, 'counterDuty'])
            ->middleware('abilities:counter-duty-schedules:read')
            ->name('api.v1.counter-duty-schedules.index');
    });
