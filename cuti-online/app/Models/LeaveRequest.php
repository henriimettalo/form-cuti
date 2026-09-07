<?php

namespace App\Models;

use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_number',
        'idempotency_key',
        'employee_id',
        'leave_type_id',
        'created_by',
        'document_template_id',
        'form_date',
        'reason',
        'start_date',
        'end_date',
        'duration_value',
        'duration_unit',
        'address_during_leave',
        'phone_during_leave',
        'employee_snapshot',
        'officials_snapshot',
        'status',
        'generated_at',
        'voided_at',
        'void_reason',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $leaveRequest): void {
            $leaveRequest->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'form_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_value' => 'integer',
            'employee_snapshot' => 'array',
            'officials_snapshot' => 'array',
            'generated_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documentTemplate(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class);
    }

    public function leaveBalanceSnapshot(): HasOne
    {
        return $this->hasOne(LeaveBalanceSnapshot::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    /**
     * @param  Builder<LeaveRequest>  $query
     * @return Builder<LeaveRequest>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas(
            'employee',
            fn (Builder $employeeQuery) => $employeeQuery->where('department_id', $user->department_id),
        );
    }
}
