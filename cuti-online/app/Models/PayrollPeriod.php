<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'year',
        'month',
        'status',
        'source_file',
        'source_checksum',
        'tpp_source_file',
        'tpp_source_checksum',
        'imported_at',
        'tpp_imported_at',
        'locked_at',
        'imported_by',
        'tpp_imported_by',
        'total_rows',
        'imported_rows',
        'tpp_total_rows',
        'tpp_imported_rows',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'tpp_imported_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function records(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function label(): string
    {
        return sprintf('%02d/%d', $this->month, $this->year);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_LOCKED => 'Terkunci',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Draf',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_LOCKED => 'status-generated',
            self::STATUS_REJECTED => 'status-void',
            default => 'status-draft',
        };
    }

    public function hasTpp(): bool
    {
        return (int) $this->tpp_imported_rows > 0;
    }
}
