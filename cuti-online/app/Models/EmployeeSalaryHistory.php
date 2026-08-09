<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryHistory extends Model
{
    use HasFactory;

    public const REASONS = [
        'rank_change' => 'Kenaikan pangkat',
        'periodic_increase' => 'Kenaikan gaji berkala',
        'adjustment' => 'Penyesuaian',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'employee_id',
        'employee_rank_history_id',
        'basic_salary',
        'effective_on',
        'change_reason',
        'decree_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'integer',
            'effective_on' => 'date',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function reasonOptions(): array
    {
        return self::REASONS;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function rankHistory(): BelongsTo
    {
        return $this->belongsTo(EmployeeRankHistory::class, 'employee_rank_history_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
