<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('nik', 16)->nullable()->unique();
            $table->string('npwp', 16)->nullable();
            $table->string('gender', 1)->nullable();
            $table->unsignedTinyInteger('position_type')->nullable();
            $table->string('eselon', 8)->default('00');
            $table->unsignedTinyInteger('marital_status')->nullable();
            $table->unsignedTinyInteger('spouse_count')->default(0);
            $table->unsignedTinyInteger('child_count')->default(0);
            $table->boolean('spouse_is_pns')->nullable();
            $table->string('spouse_nip', 32)->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedSmallInteger('grade_service_years')->nullable();
            $table->unsignedTinyInteger('grade_service_months')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique(['nik']);
            $table->dropColumn([
                'nik',
                'npwp',
                'gender',
                'position_type',
                'eselon',
                'marital_status',
                'spouse_count',
                'child_count',
                'spouse_is_pns',
                'spouse_nip',
                'birth_date',
                'grade_service_years',
                'grade_service_months',
            ]);
        });
    }
};
