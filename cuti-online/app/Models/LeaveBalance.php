<?php

namespace App\Models;

use Database\Factories\LeaveBalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    /** @use HasFactory<LeaveBalanceFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_year',
        'current_year_entitlement',
        'current_year_used',
        'carryover_n1',
        'carryover_n2',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'leave_year' => 'integer',
            'current_year_entitlement' => 'integer',
            'current_year_used' => 'integer',
            'carryover_n1' => 'integer',
            'carryover_n2' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
