<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departments = DB::table('departments')->pluck('name', 'id');
        $positions = DB::table('positions')->pluck('name', 'id');

        DB::table('employees')->orderBy('id')->each(function (object $employee) use ($departments, $positions): void {
            $recordedAt = $employee->created_at ?? now();
            $effectiveOn = substr((string) $recordedAt, 0, 10);

            if ($employee->rank_name !== null && $employee->grade !== null) {
                DB::table('employee_rank_histories')->insert([
                    'employee_id' => $employee->id,
                    'rank_name' => $employee->rank_name,
                    'grade' => $employee->grade,
                    'effective_on' => $effectiveOn,
                    'notes' => 'Data awal dari data master pegawai.',
                    'created_at' => $recordedAt,
                    'updated_at' => $recordedAt,
                ]);
            }

            DB::table('employee_position_histories')->insert([
                'employee_id' => $employee->id,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'department_name' => $departments[$employee->department_id] ?? '-',
                'position_title' => $positions[$employee->position_id] ?? $employee->position_title ?? '-',
                'effective_on' => $effectiveOn,
                'notes' => 'Data awal dari data master pegawai.',
                'created_at' => $recordedAt,
                'updated_at' => $recordedAt,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('employee_position_histories')
            ->where('notes', 'Data awal dari data master pegawai.')
            ->delete();
        DB::table('employee_rank_histories')
            ->where('notes', 'Data awal dari data master pegawai.')
            ->delete();
    }
};
