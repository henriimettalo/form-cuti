<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'simpeg_code',
        'department_type',
        'parent_department_id',
        'name',
        'address',
        'phone',
        'is_active',
        'ceremony_group_number',
        'ceremony_group_number',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parent_department_id' => 'integer',
            'ceremony_group_number' => 'integer',
            'ceremony_group_number' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_department_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_department_id');
    }

    public function typeLabel(): string
    {
        return match ($this->department_type) {
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan',
            default => 'Belum ditentukan',
        };
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function officials(): HasMany
    {
        return $this->hasMany(Official::class);
    }
}
