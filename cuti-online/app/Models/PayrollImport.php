<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollImport extends Model
{
    use HasFactory;

    public const STATUS_PREVIEWED = 'previewed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_PRIMARY = 'primary';

    public const SOURCE_TPP = 'tpp';

    protected $fillable = [
        'token',
        'year',
        'month',
        'source_type',
        'original_filename',
        'source_checksum',
        'total_rows',
        'imported_rows',
        'status',
        'payload',
        'master_changes',
        'created_by',
        'payroll_period_id',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'master_changes' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function sourceTypeLabel(): string
    {
        return match ($this->source_type) {
            self::SOURCE_TPP => 'Pelengkap TPP SIPD',
            default => 'Payroll gaji utama',
        };
    }

    public function isTpp(): bool
    {
        return $this->source_type === self::SOURCE_TPP;
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
