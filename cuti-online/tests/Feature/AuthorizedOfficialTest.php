<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Official;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizedOfficialTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_save_one_global_authorized_official(): void
    {
        $operator = User::factory()->create();

        $this->actingAs($operator)->post(route('authorized-official.store'), [
            'role' => 'authorized_official',
            'full_name' => 'Camat Uji',
            'nip' => '198601222004122001',
            'position_title' => 'Camat Pontianak Selatan',
        ])->assertRedirect(route('authorized-official.index'));

        $this->assertDatabaseHas('officials', [
            'department_id' => null,
            'signature_role' => 'authorized_official',
            'full_name' => 'Camat Uji',
            'position_title' => 'Camat Pontianak Selatan',
            'is_active' => true,
        ]);

        $this->actingAs($operator)->post(route('authorized-official.store'), [
            'role' => 'authorized_official',
            'full_name' => 'Camat Pembaruan',
            'nip' => '198601222004122001',
            'position_title' => 'Camat Pontianak Selatan',
        ]);

        $this->assertSame(1, Official::query()
            ->whereNull('department_id')
            ->where('signature_role', 'authorized_official')
            ->where('is_active', true)
            ->count());
    }

    public function test_global_authorized_official_is_available_to_new_leave_forms(): void
    {
        $operator = User::factory()->create();
        $department = Department::query()->create([
            'name' => 'Unit Uji',
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'name' => 'Jabatan Uji',
            'is_active' => true,
        ]);
        Employee::query()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => '199001012020011001',
            'full_name' => 'Pegawai Uji',
            'position_title' => $position->name,
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        Official::query()->create([
            'department_id' => null,
            'signature_role' => 'authorized_official',
            'full_name' => 'Camat Untuk Semua Formulir',
            'nip' => '198601222004122001',
            'position_title' => 'Camat Pontianak Selatan',
            'is_active' => true,
        ]);

        $this->actingAs($operator)->get(route('leave-requests.create'))
            ->assertOk()
            ->assertSee('id="authorized-official"', false)
            ->assertSee('id="official_name" name="official_name" data-plh-search data-combobox-search', false)
            ->assertSee('value="Camat Untuk Semua Formulir"', false)
            ->assertSee('readonly', false)
            ->assertSee('Camat Untuk Semua Formulir');
    }
}
