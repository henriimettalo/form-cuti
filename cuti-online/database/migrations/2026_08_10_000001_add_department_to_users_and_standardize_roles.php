<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('role')
                ->constrained()
                ->nullOnDelete()
                ->index();
            $table->string('role', 32)->default(User::ROLE_PENGGUNA)->change();
        });

        $organizationName = trim((string) DB::table('organization_profiles')
            ->where('id', 1)
            ->value('name'));
        $departmentId = $organizationName === ''
            ? null
            : DB::table('departments')->where('name', $organizationName)->value('id');

        if ($departmentId === null) {
            $departmentId = DB::table('departments')->orderByDesc('is_active')->orderBy('id')->value('id');
        }

        if ($departmentId === null) {
            $departmentId = DB::table('departments')->insertGetId([
                'name' => $organizationName !== '' ? $organizationName : 'Sekretariat Kecamatan Pontianak Selatan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $users = DB::table('users')->orderBy('id')->get(['id', 'role']);

        foreach ($users as $user) {
            $role = match (strtolower(trim((string) $user->role))) {
                User::ROLE_SUPER_ADMIN,
                'admin',
                'administrator',
                'pejabat',
                'simpeg_admin' => User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN_UNIT,
                'operator' => User::ROLE_ADMIN_UNIT,
                default => User::ROLE_PENGGUNA,
            };

            DB::table('users')->where('id', $user->id)->update(['role' => $role]);
        }

        if (! DB::table('users')->where('role', User::ROLE_SUPER_ADMIN)->exists()) {
            $firstUserId = DB::table('users')->orderBy('id')->value('id');

            if ($firstUserId !== null) {
                DB::table('users')->where('id', $firstUserId)->update([
                    'role' => User::ROLE_SUPER_ADMIN,
                    'department_id' => null,
                ]);
            }
        }

        DB::table('users')
            ->where('role', '!=', User::ROLE_SUPER_ADMIN)
            ->whereNull('department_id')
            ->update(['department_id' => $departmentId]);
        DB::table('users')
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->update(['department_id' => null]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->string('role', 32)->default('operator')->change();
        });
    }
};
