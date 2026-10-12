<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->string('duty_coordinator')->nullable()->after('ceremony_group_number');
            $table->json('duty_members')->nullable()->after('duty_coordinator');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropColumn(['duty_coordinator', 'duty_members']);
        });
    }
};
