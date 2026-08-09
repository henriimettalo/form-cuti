<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('bank_code', 32);
            $table->string('bank_name');
            $table->string('account_number', 64);
            $table->string('account_holder_name')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'bank_code', 'account_number']);
            $table->index(['employee_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_accounts');
    }
};
