<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_search_employees_by_name_nip_position_and_department(): void
    {
        $operator = User::factory()->create();
        $southDistrict = Department::query()->create(['name' => 'Kecamatan Pontianak Selatan', 'is_active' => true]);
        $educationOffice = Department::query()->create(['name' => 'Dinas Pendidikan', 'is_active' => true]);
        $serviceAnalyst = Position::query()->create(['name' => 'Analis Pelayanan', 'is_active' => true]);
        $administrator = Position::query()->create(['name' => 'Pengadministrasi', 'is_active' => true]);

        Employee::query()->create([
            'department_id' => $southDistrict->id,
            'position_id' => $serviceAnalyst->id,
            'nip' => '199001012020011101',
            'full_name' => 'Suri Lestari',
            'position_title' => $serviceAnalyst->name,
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        Employee::query()->create([
            'department_id' => $educationOffice->id,
            'position_id' => $administrator->id,
            'nip' => '199102022021021102',
            'full_name' => 'Budi Santoso',
            'position_title' => $administrator->name,
            'employment_status' => 'PPPK',
            'is_active' => true,
        ]);

        foreach (['Suri', '199001012020011101', 'Pelayanan', 'Kecamatan Pontianak Selatan'] as $search) {
            $this->actingAs($operator)
                ->get(route('employees.index', ['search' => $search]))
                ->assertOk()
                ->assertSee('Cari pegawai')
                ->assertSee('Suri Lestari')
                ->assertDontSee('Budi Santoso');
        }
    }

    public function test_operator_can_filter_employees_by_employment_department_and_active_status(): void
    {
        $operator = User::factory()->create();
        $southDistrict = Department::query()->create(['name' => 'Kecamatan Pontianak Selatan', 'is_active' => true]);
        $northDistrict = Department::query()->create(['name' => 'Kecamatan Pontianak Utara', 'is_active' => true]);
        $analyst = Position::query()->create(['name' => 'Analis', 'is_active' => true]);

        Employee::query()->create([
            'department_id' => $southDistrict->id,
            'position_id' => $analyst->id,
            'nip' => '199201012020011103',
            'full_name' => 'Pegawai Sesuai',
            'position_title' => $analyst->name,
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        Employee::query()->create([
            'department_id' => $southDistrict->id,
            'position_id' => $analyst->id,
            'nip' => '199301012020011104',
            'full_name' => 'Pegawai Nonaktif',
            'position_title' => $analyst->name,
            'employment_status' => 'PNS',
            'is_active' => false,
        ]);
        Employee::query()->create([
            'department_id' => $northDistrict->id,
            'position_id' => $analyst->id,
            'nip' => '199401012020011105',
            'full_name' => 'Pegawai PPPK',
            'position_title' => $analyst->name,
            'employment_status' => 'PPPK',
            'is_active' => true,
        ]);

        $this->actingAs($operator)
            ->get(route('employees.index', [
                'employment_status' => 'PNS',
                'department_id' => $southDistrict->id,
                'is_active' => '1',
            ]))
            ->assertOk()
            ->assertSee('Pegawai Sesuai')
            ->assertDontSee('Pegawai Nonaktif')
            ->assertDontSee('Pegawai PPPK')
            ->assertSee('Filter diterapkan');
    }

    public function test_employee_actions_are_grouped_in_a_dropdown_menu(): void
    {
        $operator = User::factory()->create();
        $employee = Employee::query()->create([
            'nip' => '199501012020011106',
            'full_name' => 'Pegawai Menu',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);

        $this->actingAs($operator)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('data-employee-actions', false)
            ->assertSee('data-employee-actions-toggle', false)
            ->assertSee('data-employee-actions-panel', false)
            ->assertSee('aria-label="Aksi untuk Pegawai Menu"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('inert', false)
            ->assertSee('Profil')
            ->assertSee('Edit')
            ->assertSee('Arsipkan');

        $employee->delete();

        $this->actingAs($operator)
            ->get(route('employees.index', ['archived' => 1]))
            ->assertOk()
            ->assertSee('aria-label="Aksi untuk Pegawai Menu"', false)
            ->assertSee('Pulihkan');
    }

    public function test_operator_can_choose_how_many_employee_rows_to_show_per_page(): void
    {
        $operator = User::factory()->create();

        for ($index = 1; $index <= 21; $index++) {
            Employee::query()->create([
                'nip' => sprintf('199601012020012%03d', $index),
                'full_name' => sprintf('Pegawai halaman %03d', $index),
                'employment_status' => 'PNS',
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($operator)->get(route('employees.index', [
            'search' => 'Pegawai halaman',
            'per_page' => 20,
        ]));

        $response->assertOk()
            ->assertSee('data-employee-per-page', false)
            ->assertSee('form="employee-filter-form"', false)
            ->assertSee('value="10"', false)
            ->assertSee('value="20" selected', false)
            ->assertSee('value="50"', false)
            ->assertSee('per_page=20', false)
            ->assertSee('search=Pegawai', false)
            ->assertSeeInOrder(['Menampilkan', 'data-employee-per-page', '<table'], false)
            ->assertViewHas('employees', static fn ($employees): bool => $employees->perPage() === 20
                && $employees->count() === 20
                && $employees->total() === 21);

        $this->actingAs($operator)
            ->get(route('employees.index', [
                'search' => 'Pegawai halaman',
                'per_page' => 20,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertViewHas('employees', static fn ($employees): bool => $employees->currentPage() === 2
                && $employees->perPage() === 20
                && $employees->count() === 1);
    }
}
