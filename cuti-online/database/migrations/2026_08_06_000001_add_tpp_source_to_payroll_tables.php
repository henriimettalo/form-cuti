<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_imports', function (Blueprint $table): void {
            $table->string('source_type', 16)->default('primary')->after('month');
            $table->index(['year', 'month', 'source_type', 'status']);
        });

        Schema::table('payroll_periods', function (Blueprint $table): void {
            $table->string('tpp_source_file')->nullable();
            $table->string('tpp_source_checksum', 64)->nullable();
            $table->timestamp('tpp_imported_at')->nullable();
            $table->foreignId('tpp_imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('tpp_total_rows')->default(0);
            $table->unsignedInteger('tpp_imported_rows')->default(0);
        });

        Schema::table('employee_payrolls', function (Blueprint $table): void {
            $table->string('tpp_bank_code', 32)->nullable();
            $table->string('tpp_bank_name')->nullable();
            $table->string('tpp_account_number', 64)->nullable();

            $table->unsignedBigInteger('tpp_workload')->default(0);
            $table->unsignedBigInteger('tpp_workplace')->default(0);
            $table->unsignedBigInteger('tpp_work_conditions')->default(0);
            $table->unsignedBigInteger('tpp_profession_scarcity')->default(0);
            $table->unsignedBigInteger('tpp_performance')->default(0);
            $table->unsignedBigInteger('tpp_pph_allowance')->default(0);
            $table->unsignedBigInteger('tpp_health_contribution')->default(0);
            $table->unsignedBigInteger('tpp_work_accident_contribution')->default(0);
            $table->unsignedBigInteger('tpp_death_contribution')->default(0);
            $table->unsignedBigInteger('tpp_tapera')->default(0);
            $table->unsignedBigInteger('tpp_pension_contribution')->default(0);
            $table->unsignedBigInteger('tpp_jht_allowance')->default(0);
            $table->unsignedBigInteger('tpp_iwp_deduction')->default(0);
            $table->unsignedBigInteger('tpp_pph21_deduction')->default(0);
            $table->unsignedBigInteger('tpp_zakat')->default(0);
            $table->unsignedBigInteger('tpp_bulog')->default(0);

            $table->unsignedBigInteger('tpp_total')->default(0);
            $table->unsignedBigInteger('tpp_total_deductions')->default(0);
            $table->bigInteger('tpp_transferred_amount')->default(0);
            $table->unsignedBigInteger('tpp_source_total')->nullable();
            $table->unsignedBigInteger('tpp_source_total_deductions')->nullable();
            $table->bigInteger('tpp_source_transferred_amount')->nullable();
            $table->string('tpp_reconciliation_status', 16)->default('pending');
            $table->unsignedInteger('tpp_source_row_number')->nullable();
            $table->string('tpp_source_row_checksum', 64)->nullable();

            $table->index('tpp_reconciliation_status');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table): void {
            $table->dropIndex(['tpp_reconciliation_status']);
            $table->dropColumn([
                'tpp_bank_code',
                'tpp_bank_name',
                'tpp_account_number',
                'tpp_workload',
                'tpp_workplace',
                'tpp_work_conditions',
                'tpp_profession_scarcity',
                'tpp_performance',
                'tpp_pph_allowance',
                'tpp_health_contribution',
                'tpp_work_accident_contribution',
                'tpp_death_contribution',
                'tpp_tapera',
                'tpp_pension_contribution',
                'tpp_jht_allowance',
                'tpp_iwp_deduction',
                'tpp_pph21_deduction',
                'tpp_zakat',
                'tpp_bulog',
                'tpp_total',
                'tpp_total_deductions',
                'tpp_transferred_amount',
                'tpp_source_total',
                'tpp_source_total_deductions',
                'tpp_source_transferred_amount',
                'tpp_reconciliation_status',
                'tpp_source_row_number',
                'tpp_source_row_checksum',
            ]);
        });

        Schema::table('payroll_periods', function (Blueprint $table): void {
            $table->dropForeign(['tpp_imported_by']);
            $table->dropColumn([
                'tpp_source_file',
                'tpp_source_checksum',
                'tpp_imported_at',
                'tpp_imported_by',
                'tpp_total_rows',
                'tpp_imported_rows',
            ]);
        });

        Schema::table('payroll_imports', function (Blueprint $table): void {
            $table->dropIndex(['year', 'month', 'source_type', 'status']);
            $table->dropColumn('source_type');
        });
    }
};
