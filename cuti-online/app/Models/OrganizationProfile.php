<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationProfile extends Model
{
    /** @use HasFactory<OrganizationProfile> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public static function current(): self
    {
        return static::query()->findOrFail(1);
    }

    public function resolveDepartment(): Department
    {
        $department = Department::query()->firstOrCreate(
            ['name' => trim($this->name)],
            ['is_active' => true],
        );

        if (! $department->is_active) {
            $department->update(['is_active' => true]);
        }

        return $department;
    }
}
