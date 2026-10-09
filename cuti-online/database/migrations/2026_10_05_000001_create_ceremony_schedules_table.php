<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceremony_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('type', 20);
            $table->date('event_date');
            $table->time('start_time');
            $table->string('location');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('leader')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['event_date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceremony_schedules');
    }
};
