<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_imports', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('original_filename');
            $table->string('source_checksum', 64)->nullable();
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('imported_rows')->default(0);
            $table->string('status', 24)->default('previewed');
            $table->json('payload');
            $table->json('master_changes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained('payroll_periods')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'month', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_imports');
    }
};
