<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ceremony_schedules', function (Blueprint $table): void {
            $table->unsignedTinyInteger('ceremony_group_number')->nullable()->after('rotation_start_department_id');
        });
    }

    public function down(): void
    {
        Schema::table('ceremony_schedules', function (Blueprint $table): void {
            $table->dropColumn('ceremony_group_number');
        });
    }
};
