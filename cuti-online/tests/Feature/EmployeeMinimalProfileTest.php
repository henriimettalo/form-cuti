<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeMinimalProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_be_registered_with_only_nip_and_name(): void
    {
        $operator = User::factory()->create();

        $this->actingAs($operator)
            ->post(route('employees.store'), [
                'nip' => '202521',
                'full_name' => 'Pegawai Belum Lengkap',
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'nip' => '202521',
            'full_name' => 'Pegawai Belum Lengkap',
            'employment_status' => null,
            'position_title' => null,
            'position_id' => null,
        ]);

        $this->assertSame(0, Employee::query()->firstOrFail()->rankHistories()->count());
        $this->assertSame(0, Employee::query()->firstOrFail()->positionHistories()->count());
    }
}
