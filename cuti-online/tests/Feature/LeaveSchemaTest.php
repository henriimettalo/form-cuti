<?php

namespace Tests\Feature;

use Database\Seeders\LeaveTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_generator_schema_is_available(): void
    {
        foreach ([
            'departments',
            'positions',
            'employees',
            'officials',
            'leave_types',
            'leave_balances',
            'holidays',
            'document_templates',
            'leave_requests',
            'leave_balance_snapshots',
            'generated_documents',
            'audit_logs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected {$table} table to exist.");
        }

        foreach ([
            'public_id',
            'employee_snapshot',
            'officials_snapshot',
            'status',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('leave_requests', $column));
        }
    }

    public function test_leave_types_can_be_seeded(): void
    {
        $this->seed(LeaveTypeSeeder::class);

        $this->assertDatabaseCount('leave_types', 6);
        $this->assertDatabaseHas('leave_types', [
            'code' => 'annual',
            'name' => 'Cuti Tahunan',
        ]);
    }
}
