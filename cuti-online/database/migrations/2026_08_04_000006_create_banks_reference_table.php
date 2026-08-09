<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('employee_bank_accounts', function (Blueprint $table): void {
            $table->foreignId('bank_id')->nullable()->after('employee_id')->constrained('banks')->nullOnDelete();
        });

        DB::table('employee_bank_accounts')
            ->select(['bank_code', 'bank_name'])
            ->whereNotNull('bank_code')
            ->whereNotNull('bank_name')
            ->distinct()
            ->orderBy('bank_code')
            ->get()
            ->each(function (object $account): void {
                $now = now();
                DB::table('banks')->updateOrInsert(
                    ['code' => $account->bank_code],
                    [
                        'name' => $account->bank_name,
                        'is_active' => true,
                        'updated_at' => $now,
                    ],
                );
                $bankId = DB::table('banks')
                    ->where('code', $account->bank_code)
                    ->value('id');

                DB::table('employee_bank_accounts')
                    ->where('bank_code', $account->bank_code)
                    ->update(['bank_id' => $bankId]);
            });
    }

    public function down(): void
    {
        Schema::table('employee_bank_accounts', function (Blueprint $table): void {
            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');
        });

        Schema::dropIfExists('banks');
    }
};
