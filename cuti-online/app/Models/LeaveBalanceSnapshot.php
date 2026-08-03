<?php

namespace App\Models;

use Database\Factories\LeaveBalanceSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalanceSnapshot extends Model
{
    /** @use HasFactory<LeaveBalanceSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'leave_year',
        'n2_remaining',
        'n1_remaining',
        'current_year_entitlement',
        'current_year_used_before',
        'current_year_remaining_before',
        'requested_days',
        'current_year_remaining_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'leave_year' => 'integer',
            'n2_remaining' => 'integer',
            'n1_remaining' => 'integer',
            'current_year_entitlement' => 'integer',
            'current_year_used_before' => 'integer',
            'current_year_remaining_before' => 'integer',
            'requested_days' => 'integer',
            'current_year_remaining_after' => 'integer',
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
