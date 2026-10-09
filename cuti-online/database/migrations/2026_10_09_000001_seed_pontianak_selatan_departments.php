<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $parent = DB::table('departments')
            ->where(function ($query): void {
                $query->where('department_type', 'kecamatan')
                    ->orWhereIn('name', ['Kecamatan Pontianak Selatan', 'Sekretariat Kecamatan Pontianak Selatan']);
            })
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        if ($parent === null) {
            throw new \RuntimeException('Kecamatan Pontianak Selatan belum tersedia untuk membuat data kelurahan.');
        }

        DB::table('departments')->where('id', $parent->id)->update([
            'department_type' => 'kecamatan',
            'parent_department_id' => null,
            'is_active' => true,
            'updated_at' => now(),
        ]);

        $departments = [
            ['name' => 'Kelurahan Kotabaru', 'code' => 'KEL-KB', 'simpeg_code' => '12.26.09.50.03.07.00', 'group' => 2],
            ['name' => 'Kelurahan Akcaya', 'code' => 'KEL-ACY', 'simpeg_code' => '12.26.09.50.03.06.00', 'group' => 3],
            ['name' => 'Kelurahan Parittokaya', 'code' => 'KEL-PT', 'simpeg_code' => '12.26.09.50.03.05.00', 'group' => 4],
            ['name' => 'Kelurahan Benuamelayu Darat', 'code' => 'KEL-BMD', 'simpeg_code' => '12.26.09.50.03.04.00', 'group' => 5],
            ['name' => 'Kelurahan Benuamelayu Laut', 'code' => 'KEL-BML', 'simpeg_code' => '12.26.09.50.03.03.00', 'group' => 6],
        ];

        foreach ($departments as $department) {
            $existing = DB::table('departments')
                ->where(function ($query) use ($department): void {
                    $query->where('name', $department['name'])
                        ->orWhere('simpeg_code', $department['simpeg_code']);
                })
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            $values = [
                'code' => $department['code'],
                'simpeg_code' => $department['simpeg_code'],
                'department_type' => 'kelurahan',
                'parent_department_id' => $parent->id,
                'name' => $department['name'],
                'is_active' => true,
                'ceremony_group_number' => $department['group'],
                'updated_at' => now(),
            ];

            if ($existing !== null) {
                DB::table('departments')->where('id', $existing->id)->update($values);
                continue;
            }

            DB::table('departments')->insert($values + ['created_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('departments')
            ->whereIn('simpeg_code', [
                '12.26.09.50.03.03.00',
                '12.26.09.50.03.04.00',
                '12.26.09.50.03.05.00',
                '12.26.09.50.03.06.00',
                '12.26.09.50.03.07.00',
            ])
            ->delete();
    }
};
