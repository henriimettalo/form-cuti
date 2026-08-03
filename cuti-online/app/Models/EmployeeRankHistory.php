<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRankHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'rank_name',
        'grade',
        'effective_on',
        'decree_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_on' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
