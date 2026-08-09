<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRankTest extends TestCase
{
    use RefreshDatabase;

    public function test_pns_rank_selection_is_saved_as_rank_name_and_grade(): void
    {
        $operator = User::factory()->create();

        $this->actingAs($operator)->post(route('employees.store'), [
            'nip' => '199001012020011001',
            'full_name' => 'Pegawai Uji',
            'position_title' => 'Jabatan Uji',
            'employment_status' => 'PNS',
            'rank_grade' => 'III/b',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'nip' => '199001012020011001',
            'rank_name' => 'Penata Muda Tingkat I',
            'grade' => 'III/b',
        ]);
        $this->assertDatabaseHas('employee_rank_histories', [
            'rank_name' => 'Penata Muda Tingkat I',
            'grade' => 'III/b',
            'notes' => 'Data awal pegawai.',
        ]);

        $this->actingAs($operator)->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Penata Muda Tingkat I (III/b)')
            ->assertDontSee('Penata Muda Tingkat I III/b');
    }

    public function test_pns_must_choose_a_rank_and_grade(): void
    {
        $operator = User::factory()->create();

        $this->from(route('employees.create'))->actingAs($operator)->post(route('employees.store'), [
            'nip' => '199001012020011002',
            'full_name' => 'Pegawai Tanpa Pangkat',
            'position_title' => 'Jabatan Uji',
            'employment_status' => 'PNS',
        ])->assertRedirect(route('employees.create'))
            ->assertSessionHasErrors('rank_grade');
    }

    public function test_pppk_golongan_selection_is_saved_as_rank_name_and_grade(): void
    {
        $operator = User::factory()->create();

        $this->actingAs($operator)->post(route('employees.store'), [
            'nip' => '199001012020011006',
            'full_name' => 'Pegawai PPPK Uji',
            'position_title' => 'Jabatan Uji',
            'employment_status' => 'PPPK',
            'rank_grade' => 'IX',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'nip' => '199001012020011006',
            'rank_name' => 'Golongan',
            'grade' => 'IX',
        ]);
        $this->assertDatabaseHas('employee_rank_histories', [
            'rank_name' => 'Golongan',
            'grade' => 'IX',
            'notes' => 'Data awal pegawai.',
        ]);

        $this->actingAs($operator)->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Golongan IX')
            ->assertDontSee('Golongan (IX)');
    }

    public function test_operator_can_update_an_employee_rank_and_position(): void
    {
        $operator = User::factory()->create();
        $department = Department::query()->create([
            'name' => 'Unit Lama',
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'name' => 'Jabatan Lama',
            'is_active' => true,
        ]);
        $employee = Employee::query()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => '199001012020011003',
            'full_name' => 'Pegawai Uji',
            'position_title' => $position->name,
            'rank_name' => 'Penata',
            'grade' => 'III/c',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);

        $this->actingAs($operator)->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertSee('Edit pegawai')
            ->assertSee('Jabatan Lama');

        $this->actingAs($operator)->put(route('employees.update', $employee), [
            'nip' => $employee->nip,
            'full_name' => $employee->full_name,
            'position_title' => 'Jabatan Baru',
            'employment_status' => 'PNS',
            'rank_grade' => 'IV/a',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'department_id' => $department->id,
            'position_title' => 'Jabatan Baru',
            'rank_name' => 'Pembina',
            'grade' => 'IV/a',
        ]);
        $this->assertDatabaseHas('positions', ['name' => 'Jabatan Baru']);
        $this->assertDatabaseHas('employee_rank_histories', [
            'employee_id' => $employee->id,
            'grade' => 'IV/a',
            'notes' => 'Perubahan melalui edit data pegawai.',
        ]);
        $this->assertDatabaseHas('employee_position_histories', [
            'employee_id' => $employee->id,
            'department_name' => 'Unit Lama',
            'position_title' => 'Jabatan Baru',
            'notes' => 'Perubahan melalui edit data pegawai.',
        ]);
    }
}
