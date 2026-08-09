<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status', 16)->default('draft');
            $table->string('source_file')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
            $table->index('status');
        });

        Schema::create('employee_payrolls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_nip', 32);
            $table->string('employee_name');
            $table->string('skpd_snapshot')->nullable();
            $table->foreignId('employee_bank_account_id')->nullable()->constrained('employee_bank_accounts')->nullOnDelete();
            $table->string('bank_code', 32)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number', 64)->nullable();

            $table->unsignedBigInteger('basic_salary')->default(0);
            $table->unsignedBigInteger('spouse_allowance')->default(0);
            $table->unsignedBigInteger('child_allowance')->default(0);
            $table->unsignedBigInteger('family_allowance')->default(0);
            $table->unsignedBigInteger('position_allowance')->default(0);
            $table->unsignedBigInteger('functional_allowance')->default(0);
            $table->unsignedBigInteger('general_functional_allowance')->default(0);
            $table->unsignedBigInteger('rice_allowance')->default(0);
            $table->unsignedBigInteger('pph_allowance')->default(0);
            $table->unsignedBigInteger('rounding')->default(0);
            $table->unsignedBigInteger('health_contribution')->default(0);
            $table->unsignedBigInteger('work_accident_contribution')->default(0);
            $table->unsignedBigInteger('death_contribution')->default(0);
            $table->unsignedBigInteger('tapera')->default(0);
            $table->unsignedBigInteger('pension_contribution')->default(0);
            $table->unsignedBigInteger('papua_special_allowance')->default(0);
            $table->unsignedBigInteger('jht_allowance')->default(0);
            $table->unsignedBigInteger('iwp_deduction')->default(0);
            $table->unsignedBigInteger('pph21_deduction')->default(0);
            $table->unsignedBigInteger('zakat')->default(0);
            $table->unsignedBigInteger('bulog')->default(0);

            $table->unsignedBigInteger('total_earnings')->default(0);
            $table->unsignedBigInteger('total_deductions')->default(0);
            $table->bigInteger('transferred_amount')->default(0);
            $table->unsignedBigInteger('source_total_earnings')->nullable();
            $table->unsignedBigInteger('source_total_deductions')->nullable();
            $table->bigInteger('source_transferred_amount')->nullable();
            $table->string('reconciliation_status', 16)->default('matched');
            $table->unsignedInteger('source_row_number')->nullable();
            $table->string('source_row_checksum', 64)->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_nip']);
            $table->index(['employee_id', 'payroll_period_id']);
            $table->index('reconciliation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payrolls');
        Schema::dropIfExists('payroll_periods');
    }
};
