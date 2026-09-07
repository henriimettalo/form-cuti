<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->string('simpeg_code', 32)->nullable()->unique()->after('code');
            $table->string('department_type', 16)->nullable()->after('simpeg_code');
            $table->foreignId('parent_department_id')
                ->nullable()
                ->after('department_type')
                ->constrained('departments')
                ->nullOnDelete();
            $table->index('department_type');
        });

        $root = $this->findDepartment([
            'Sekretariat Kecamatan Pontianak Selatan',
            'Kecamatan Pontianak Selatan',
        ]);

        if ($root !== null) {
            $this->applyOfficialMetadata(
                $root,
                '12.26.09.50.03',
                'KEC-PONSEL',
                'kecamatan',
                null,
            );
        }

        $children = [
            [
                'names' => ['Kelurahan Akcaya', 'Kelurahan Akcaya, Kecamatan Pontianak Selatan'],
                'simpeg_code' => '12.26.09.50.03.06.00',
                'code' => 'KEL-ACY',
            ],
            [
                'names' => ['Kelurahan Parittokaya', 'Kelurahan Parittokaya, Kecamatan Pontianak Selatan'],
                'simpeg_code' => '12.26.09.50.03.05.00',
                'code' => 'KEL-PT',
            ],
            [
                'names' => ['Kelurahan Kotabaru', 'Kelurahan Kotabaru, Kecamatan Pontianak Selatan'],
                'simpeg_code' => '12.26.09.50.03.07.00',
                'code' => 'KEL-KB',
            ],
            [
                'names' => ['Kelurahan Benuamelayu Darat', 'Benuamelayu Darat'],
                'simpeg_code' => '12.26.09.50.03.04.00',
                'code' => 'KEL-BMD',
            ],
            [
                'names' => ['Kelurahan Benuamelayu Laut', 'Benuamelayu Laut'],
                'simpeg_code' => '12.26.09.50.03.03.00',
                'code' => 'KEL-BML',
            ],
        ];

        foreach ($children as $child) {
            $department = $this->findDepartment($child['names']);

            if ($department === null) {
                continue;
            }

            $this->applyOfficialMetadata(
                $department,
                $child['simpeg_code'],
                $child['code'],
                'kelurahan',
                $root?->id,
            );
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropForeign(['parent_department_id']);
            $table->dropIndex(['department_type']);
            $table->dropUnique(['simpeg_code']);
            $table->dropColumn([
                'simpeg_code',
                'department_type',
                'parent_department_id',
            ]);
        });
    }

    /**
     * @param  array<int, string>  $names
     */
    private function findDepartment(array $names): ?object
    {
        foreach ($names as $name) {
            $department = DB::table('departments')
                ->where('name', $name)
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->first();

            if ($department !== null) {
                return $department;
            }
        }

        return null;
    }

    private function applyOfficialMetadata(
        object $department,
        string $simpegCode,
        string $code,
        string $type,
        ?int $parentId,
    ): void {
        $updates = [
            'department_type' => $type,
            'parent_department_id' => $parentId,
            'updated_at' => now(),
        ];

        $codeOwnerExists = DB::table('departments')
            ->where('code', $code)
            ->where('id', '!=', $department->id)
            ->exists();

        if (! $codeOwnerExists) {
            $updates['code'] = $code;
        }

        $simpegCodeOwnerExists = DB::table('departments')
            ->where('simpeg_code', $simpegCode)
            ->where('id', '!=', $department->id)
            ->exists();

        if (! $simpegCodeOwnerExists) {
            $updates['simpeg_code'] = $simpegCode;
        }

        DB::table('departments')->where('id', $department->id)->update($updates);
    }
};
