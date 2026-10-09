<?php

namespace App\Support;

use App\Models\Department;
use Illuminate\Support\Collection;

class ImportDepartmentResolver
{
    public static function resolve(string $name, ?Collection $departments = null): ?Department
    {
        $departments ??= Department::query()->where('is_active', true)->get();
        $key = self::key($name);
        $matches = $departments->filter(fn ($department) => self::key($department->name) === $key
            || ($key === 'kecamatan' && $department->department_type === 'kecamatan'));
        $official = $matches->filter(fn ($department) => $department->department_type !== null);
        if ($official->isNotEmpty()) {
            $matches = $official;
        }

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private static function key(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/^kelurahan\s+/u', '', $name);

        return preg_replace('/\s+/u', '', $name);
    }
}
