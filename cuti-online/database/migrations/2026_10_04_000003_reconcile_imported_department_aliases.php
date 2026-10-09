<?php

use App\Models\Department;
use App\Support\ImportDepartmentResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $official = Department::query()->where('is_active', true)->whereNotNull('department_type')->get();
            foreach (Department::query()->whereNull('department_type')->where('is_active', true)->get() as $alias) {
                $target = ImportDepartmentResolver::resolve($alias->name, $official);
                if ($target === null) {
                    continue;
                }
                DB::table('employees')->where('department_id', $alias->id)->update(['department_id' => $target->id]);
                $alias->update(['is_active' => false]);
            }
        });
    }

    public function down(): void
    {
        // Relasi pegawai yang sudah diseragamkan tetap dipertahankan.
    }
};
