<?php

namespace App\Models;

use Database\Factories\EmployeeImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeImport extends Model
{
    /** @use HasFactory<EmployeeImportFactory> */
    use HasFactory;

    protected $fillable = [
        'token',
        'original_filename',
        'total_rows',
        'imported_rows',
        'status',
        'payload',
        'created_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
