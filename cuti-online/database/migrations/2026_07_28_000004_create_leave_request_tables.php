<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('request_number', 64)->nullable()->unique();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->date('form_date');
            $table->text('reason')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('duration_value');
            $table->string('duration_unit', 16)->default('day');
            $table->text('address_during_leave')->nullable();
            $table->string('phone_during_leave', 32)->nullable();
            $table->json('employee_snapshot');
            $table->json('officials_snapshot')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['employee_id', 'start_date']);
            $table->index(['leave_type_id', 'status']);
        });

        Schema::create('leave_balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('leave_year');
            $table->unsignedSmallInteger('n2_remaining')->default(0);
            $table->unsignedSmallInteger('n1_remaining')->default(0);
            $table->unsignedSmallInteger('current_year_entitlement')->default(0);
            $table->unsignedSmallInteger('current_year_used_before')->default(0);
            $table->unsignedSmallInteger('current_year_remaining_before')->default(0);
            $table->unsignedSmallInteger('requested_days')->default(0);
            $table->unsignedSmallInteger('current_year_remaining_after')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('format', 10);
            $table->string('storage_path');
            $table->string('filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->string('template_version', 32)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->index(['leave_request_id', 'format']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 100);
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('leave_balance_snapshots');
        Schema::dropIfExists('leave_requests');
    }
};
