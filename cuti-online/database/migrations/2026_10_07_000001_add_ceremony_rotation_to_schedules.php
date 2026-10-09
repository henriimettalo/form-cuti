<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ceremony_schedules', function (Blueprint $table): void {
            $table->foreignId('rotation_start_department_id')->nullable()->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ceremony_schedules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rotation_start_department_id');
        });
    }
};
