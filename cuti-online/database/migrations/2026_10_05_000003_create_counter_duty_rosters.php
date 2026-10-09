<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_groups', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->string('coordinator');
            $table->json('members');
            $table->timestamps();
        });

        Schema::create('duty_holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('ceremony_schedules', function (Blueprint $table): void {
            $table->foreignId('duty_group_id')->nullable()->constrained('duty_groups')->nullOnDelete();
            $table->json('duty_roster')->nullable();
            $table->date('duty_date')->nullable()->unique();
        });

        $groups = [
            ['Syaiful Rahman, S.IP, M.A.P', ['Suriadarna', 'Abu Bakar', 'Henri Mettaloa, S.Kom']],
            ['Al Ikhsan Imanullah, S.STP', ['Reny Haryani, SE', 'Maria Franciska Adhika Hapsari,A.Md', 'Ridho Nurrohcman, A.Md']],
            ['Asra, S.Sos', ['Widya Yulianti, S.IP', 'Arief Kurniawan, S.Kom', 'Alicia Destriani Sandea, S.Kom']],
            ['Drs. Ridwan', ['Mega Kartika Elly', 'Sudirianto', 'Harry Salistiwa, S.T']],
        ];

        foreach ($groups as $index => [$coordinator, $members]) {
            DB::table('duty_groups')->insert([
                'number' => $index + 1,
                'coordinator' => $coordinator,
                'members' => json_encode($members, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('ceremony_schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('duty_group_id');
            $table->dropColumn(['duty_roster', 'duty_date']);
        });

        Schema::dropIfExists('duty_holidays');
        Schema::dropIfExists('duty_groups');
    }
};
