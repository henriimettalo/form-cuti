<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'position_id',
        'nip',
        'nik',
        'npwp',
        'gender',
        'full_name',
        'position_title',
        'position_type',
        'eselon',
        'rank_name',
        'grade',
        'employment_status',
        'marital_status',
        'spouse_count',
        'child_count',
        'spouse_is_pns',
        'spouse_nip',
        'birth_date',
        'service_started_on',
        'nip_tmt_valid',
        'grade_service_years',
        'grade_service_months',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position_type' => 'integer',
            'marital_status' => 'integer',
            'spouse_count' => 'integer',
            'child_count' => 'integer',
            'spouse_is_pns' => 'boolean',
            'birth_date' => 'date',
            'service_started_on' => 'date',
            'nip_tmt_valid' => 'boolean',
            'grade_service_years' => 'integer',
            'grade_service_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function rankHistories(): HasMany
    {
        return $this->hasMany(EmployeeRankHistory::class);
    }

    public function salaryHistories(): HasMany
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    /**
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('department_id', $user->department_id);
    }

    public function positionHistories(): HasMany
    {
        return $this->hasMany(EmployeePositionHistory::class);
    }
}
