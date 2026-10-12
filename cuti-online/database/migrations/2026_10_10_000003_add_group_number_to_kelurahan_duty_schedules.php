<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelurahan_duty_schedules', function (Blueprint $table): void {
            $table->unsignedSmallInteger('group_number')->after('event_date');
        });
    }

    public function down(): void
    {
        Schema::table('kelurahan_duty_schedules', function (Blueprint $table): void {
            $table->dropColumn('group_number');
        });
    }
};
