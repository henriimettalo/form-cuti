<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ceremony_schedules', function (Blueprint $table): void {
            $table->time('start_time')->nullable()->change();
            $table->string('location')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ceremony_schedules', function (Blueprint $table): void {
            $table->time('start_time')->nullable(false)->change();
            $table->string('location')->nullable(false)->change();
        });
    }
};
