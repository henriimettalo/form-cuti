<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeIdentityImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeIdentityImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_modal_confirmation_shows_completion_and_clears_preview(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $this->post(route('employees.identity-import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('modal.csv', "NIP,Nama Lengkap\n199001012020011001,Pegawai Modal"),
        ])->assertRedirect(route('employees.identity-import.create'));

        $this->post(route('employees.identity-import.store'), ['modal' => '1'])
            ->assertOk()
            ->assertViewIs('employees.identity-import-complete')
            ->assertSee('data-import-completed', false)
            ->assertSee('1 data identitas pegawai berhasil diimpor.')
            ->assertSessionMissing('identity-import-preview')
            ->assertSessionMissing('identity-import-path');
        $this->assertDatabaseHas('employees', ['full_name' => 'Pegawai Modal']);
    }

    public function test_generated_excel_template_can_be_read_with_name_and_phone(): void
    {
        $service = app(EmployeeIdentityImportService::class);
        $spreadsheet = $service->template();
        $path = tempnam(sys_get_temp_dir(), 'identity-template-');

        try {
            (new Xlsx($spreadsheet))->save($path);
            $preview = $service->preview(new UploadedFile($path, 'identitas.xlsx', null, null, true));

            $this->assertSame([], $preview['errors']);
            $this->assertCount(2, $preview['valid_rows']);
            $this->assertNotEmpty($preview['valid_rows'][0]['full_name']);
            $this->assertNotEmpty($preview['valid_rows'][0]['phone']);
            $this->assertSame('Unit Kerja', $spreadsheet->getActiveSheet()->getCell('F1')->getValue());
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($path);
        }
    }

    public function test_import_adds_and_updates_employees_from_provided_columns(): void
    {
        Storage::fake('local');
        $department = Department::query()->create(['name' => 'Unit Lama', 'is_active' => true]);
        $employee = Employee::query()->create(['nip' => '199001012020011001', 'full_name' => 'Nama Tetap', 'department_id' => $department->id, 'phone' => '081111111111', 'email' => 'lama@example.test', 'is_active' => true]);
        $employee->positionHistories()->create(['position_title' => 'Jabatan Lama', 'department_name' => 'Unit Lama', 'effective_on' => '2025-01-01']);
        $this->actingAs(User::factory()->create());
        $content = "NIP,Nama Lengkap,Unit Kerja,Nomor Telepon,Email\n199001012020011001,Nama dari File,Unit Baru,082222222222,baru@example.test\n199202022021012002,Pegawai Baru,Unit Baru,083333333333,new@example.test";
        $this->post(route('employees.identity-import.preview'), ['file' => UploadedFile::fake()->createWithContent('kontak.csv', $content)])
            ->assertSessionHas('identity-import-preview', fn ($preview) => count($preview['valid_rows']) === 2 && $preview['valid_rows'][0]['action'] === 'update');
        $this->assertSame('081111111111', $employee->fresh()->phone);
        $this->get(route('employees.identity-import.create'))->assertOk()->assertSee('Unit Baru')->assertSee('Perbarui');
        $this->post(route('employees.identity-import.store'))->assertRedirect(route('employees.index'));
        $this->assertDatabaseCount('employees', 2);
        $this->assertSame('Nama dari File', $employee->fresh()->full_name);
        $this->assertSame('Unit Baru', $employee->fresh()->department->name);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'phone' => '082222222222', 'email' => 'baru@example.test']);
        $this->assertSame(1, Department::query()->where('name', 'Unit Baru')->count());
        $this->get(route('employees.show', $employee))->assertOk()->assertViewHas('currentDepartmentName', 'Unit Baru')->assertSee('082222222222')->assertSee('baru@example.test');
        $this->assertSame('Unit Lama', $employee->positionHistories()->first()->department_name);
        $this->post(route('employees.identity-import.preview'), ['file' => UploadedFile::fake()->createWithContent('kosong.csv', "NIP,Nama Lengkap,Unit Kerja,Nomor Telepon,Email\n199001012020011001,,,,")])
            ->assertSessionHas('identity-import-preview', fn ($preview) => $preview['valid_rows'] === [] && count($preview['skipped_existing']) === 1);
        $this->post(route('employees.identity-import.store'));
        $this->assertSame('Unit Baru', $employee->fresh()->department->name);
        $this->assertSame('082222222222', $employee->fresh()->phone);
        $this->assertSame('baru@example.test', $employee->fresh()->email);
    }

    public function test_csv_preview_confirmation_and_repeat_import_preserve_two_employees(): void
    {
        Storage::fake('local');
        $this->withoutExceptionHandling();
        $this->actingAs(User::factory()->create());
        $content = implode("\n", [
            'NIP,Nama Lengkap,Jabatan,Nomor Telepon,Email',
            '199001012020011001,Pegawai Uji A,Staf Uji,081234567890,uji-a@example.test',
            '199202022021012002,Pegawai Uji B,Staf Uji,081234567891,uji-b@example.test',
        ]);

        $this->post(route('employees.identity-import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('identitas.csv', $content),
        ])->assertRedirect(route('employees.identity-import.create'))
            ->assertSessionHas('identity-import-preview', fn ($preview) => count($preview['valid_rows']) === 2 && $preview['errors'] === []);

        $this->assertDatabaseCount('employees', 0);
        $this->post(route('employees.identity-import.store'))
            ->assertRedirect(route('employees.index'));
        $this->assertDatabaseCount('employees', 2);
        $this->assertDatabaseHas('employees', ['full_name' => 'Pegawai Uji A', 'phone' => '081234567890']);

        $this->post(route('employees.identity-import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('identitas.csv', $content),
        ])->assertSessionHas('identity-import-preview', fn ($preview) => $preview['valid_rows'] === [] && count($preview['skipped_existing']) === 2);
        $this->post(route('employees.identity-import.store'))->assertRedirect(route('employees.index'));
        $this->assertSame(2, Employee::query()->count());
    }

    public function test_partial_upsert_updates_position_inserts_new_employee_and_preserves_missing_contacts(): void
    {
        Storage::fake('local');
        $employee = Employee::query()->create(['nip' => '199001012020011001', 'full_name' => 'Nama Lama', 'position_title' => 'Jabatan Lama', 'phone' => '081234567890', 'email' => 'tetap@example.test', 'is_active' => true]);
        $this->actingAs(User::factory()->create());
        $content = "NIP,Nama Lengkap,Jabatan\n199001012020011001,,Jabatan Baru\n199202022021012002,Pegawai Baru,Staf";
        $this->post(route('employees.identity-import.preview'), ['file' => UploadedFile::fake()->createWithContent('upsert.csv', $content)])
            ->assertSessionHas('identity-import-preview', fn ($preview) => $preview['valid_rows'][0]['changes']['position_title']['old'] === 'Jabatan Lama' && $preview['valid_rows'][1]['action'] === 'create');
        $this->get(route('employees.identity-import.create'))->assertOk()->assertSee('1 ditambahkan')->assertSee('1 diperbarui')->assertSee('Jabatan Lama')->assertSee('Jabatan Baru');
        $this->assertSame('Jabatan Lama', $employee->fresh()->position_title);
        $this->post(route('employees.identity-import.store'))->assertRedirect(route('employees.index'));
        $employee->refresh();
        $this->assertSame('Nama Lama', $employee->full_name);
        $this->assertSame('Jabatan Baru', $employee->position_title);
        $this->assertSame('Jabatan Baru', $employee->position->name);
        $this->assertSame('081234567890', $employee->phone);
        $this->assertSame('tetap@example.test', $employee->email);
        $this->assertDatabaseCount('employees', 2);
        $this->post(route('employees.identity-import.preview'), ['file' => UploadedFile::fake()->createWithContent('upsert.csv', $content)])
            ->assertSessionHas('identity-import-preview', fn ($preview) => $preview['valid_rows'] === [] && count($preview['skipped_existing']) === 2);
        $this->post(route('employees.identity-import.store'));
        $this->assertDatabaseCount('employees', 2);
    }

    public function test_partial_upsert_accepts_nip_and_email_only_but_requires_name_for_new_employee(): void
    {
        $service = app(EmployeeIdentityImportService::class);
        $employee = Employee::query()->create(['nip' => '199001012020011001', 'full_name' => 'Nama Tetap', 'is_active' => true]);
        $file = UploadedFile::fake()->createWithContent('email.csv', "NIP,Email\n199001012020011001,new@example.test\n199202022021012002,other@example.test");
        $preview = $service->preview($file);
        $this->assertCount(1, $preview['valid_rows']);
        $this->assertCount(1, $preview['errors']);
        $this->assertSame(['Nama lengkap wajib diisi untuk pegawai baru.'], $preview['errors'][0]['messages']);
        $this->assertSame(1, $service->store($file));
        $this->assertSame('new@example.test', $employee->fresh()->email);
        $this->assertSame('Nama Tetap', $employee->fresh()->full_name);
        $this->assertDatabaseCount('employees', 1);
    }
}
