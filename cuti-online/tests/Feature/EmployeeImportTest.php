<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeImport;
use App\Models\OrganizationProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_preview_and_confirm_a_csv_import_without_duplicate_entries(): void
    {
        $operator = User::factory()->create();

        $this->actingAs($operator)
            ->get(route('employees.import.create'))
            ->assertOk()
            ->assertSee('Impor pegawai');

        $file = UploadedFile::fake()->createWithContent('pegawai.csv', implode("\n", [
            'NIP,Nama Lengkap,Status Kepegawaian,Pangkat/Golongan,Jabatan,TMT Mulai Kerja,No. HP,Email,Alamat',
            '199001012020011001,Pegawai Impor,PNS,III/b,Jabatan Impor,01/01/2020,081234567890,pegawai@example.go.id,Pontianak',
        ]));

        $this->actingAs($operator)
            ->post(route('employees.import.upload'), ['file' => $file])
            ->assertRedirect();

        $employeeImport = EmployeeImport::query()->firstOrFail();

        $this->assertSame('previewed', $employeeImport->status);
        $this->assertSame(1, $employeeImport->total_rows);
        $this->get(route('employees.import.preview', $employeeImport))
            ->assertOk()
            ->assertSee('Pegawai siap impor')
            ->assertSee('Pegawai Impor');

        $this->actingAs($operator)
            ->post(route('employees.import.store', $employeeImport))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'nip' => '199001012020011001',
            'full_name' => 'Pegawai Impor',
            'rank_name' => 'Penata Muda Tingkat I',
            'grade' => 'III/b',
        ]);
        $employee = Employee::query()->where('nip', '199001012020011001')->firstOrFail();
        $this->assertSame('2020-01-01', $employee->service_started_on?->toDateString());
        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employee->id,
            'leave_year' => now()->year,
        ]);
        $this->assertDatabaseHas('employee_rank_histories', [
            'employee_id' => $employee->id,
            'grade' => 'III/b',
            'notes' => 'Data awal impor pegawai.',
        ]);
        $this->assertDatabaseHas('employee_position_histories', [
            'employee_id' => $employee->id,
            'department_name' => OrganizationProfile::current()->name,
            'position_title' => 'Jabatan Impor',
            'notes' => 'Data awal impor pegawai.',
        ]);
        $this->assertDatabaseHas('employee_imports', [
            'id' => $employeeImport->id,
            'status' => 'completed',
            'imported_rows' => 1,
        ]);

        $this->actingAs($operator)
            ->post(route('employees.import.store', $employeeImport))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseCount('employees', 1);
    }

    public function test_import_preserves_the_unit_kerja_provided_in_the_file(): void
    {
        $operator = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('pegawai-unit-kerja.csv', implode("\n", [
            'NIP,Nama Lengkap,Status Kepegawaian,Pangkat/Golongan,Unit Kerja,Jabatan',
            '199001012020011009,Pegawai Kelurahan,PNS,III/b,Kelurahan Akcaya,Jabatan Impor',
        ]));

        $this->actingAs($operator)
            ->post(route('employees.import.upload'), ['file' => $file])
            ->assertRedirect();

        $employeeImport = EmployeeImport::query()->firstOrFail();

        $this->actingAs($operator)
            ->post(route('employees.import.store', $employeeImport))
            ->assertRedirect(route('employees.index'));

        $department = Department::query()->where('name', 'Kelurahan Akcaya')->firstOrFail();
        $employee = Employee::query()->where('nip', '199001012020011009')->firstOrFail();

        $this->assertSame($department->id, $employee->department_id);
        $this->assertDatabaseHas('employee_position_histories', [
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'department_name' => 'Kelurahan Akcaya',
        ]);

        $this->actingAs($operator)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Kelurahan Akcaya');

        $this->actingAs($operator)
            ->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertSee('Kelurahan Akcaya');
    }

    public function test_import_rejects_duplicate_nips_before_any_data_is_created(): void
    {
        $operator = User::factory()->create();
        Employee::query()->create([
            'nip' => '199001012020011002',
            'full_name' => 'Pegawai Lama',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        $file = UploadedFile::fake()->createWithContent('pegawai.csv', implode("\n", [
            'NIP,Nama Lengkap,Status Kepegawaian,Pangkat/Golongan,Jabatan',
            '199001012020011002,Pegawai Sama,PNS,III/b,Jabatan Impor',
            '199001012020011003,Pegawai Baru,PNS,III/b,Jabatan Impor',
            '199001012020011003,Pegawai Ganda,PNS,III/b,Jabatan Impor',
        ]));

        $this->from(route('employees.import.create'))
            ->actingAs($operator)
            ->post(route('employees.import.upload'), ['file' => $file])
            ->assertRedirect(route('employees.import.create'))
            ->assertSessionHas('importErrors');

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('employee_imports', 0);
    }

    public function test_import_recognises_pppk_golongan_written_in_full(): void
    {
        $operator = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('pegawai-pppk.csv', implode("\n", [
            'NIP,Nama Lengkap,Status Kepegawaian,Pangkat/Golongan,Jabatan',
            '199001012020011005,Pegawai PPPK,PPPK,Golongan IX,Jabatan PPPK',
        ]));

        $this->actingAs($operator)
            ->post(route('employees.import.upload'), ['file' => $file])
            ->assertRedirect();

        $employeeImport = EmployeeImport::query()->firstOrFail();

        $this->actingAs($operator)
            ->post(route('employees.import.store', $employeeImport))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'nip' => '199001012020011005',
            'employment_status' => 'PPPK',
            'rank_name' => 'Golongan',
            'grade' => 'IX',
        ]);
        $this->assertDatabaseHas('employee_rank_histories', [
            'rank_name' => 'Golongan',
            'grade' => 'IX',
            'notes' => 'Data awal impor pegawai.',
        ]);
    }

    public function test_operator_can_paginate_all_import_preview_rows(): void
    {
        $operator = User::factory()->create();
        $rows = array_map(static fn (int $number): array => [
            'nip' => str_pad((string) $number, 18, '0', STR_PAD_LEFT),
            'full_name' => 'Pegawai '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'employment_status' => 'PNS',
            'rank_name' => 'Penata Muda Tingkat I',
            'grade' => 'III/b',
            'department_name' => 'Unit Uji',
            'position_title' => 'Jabatan Uji',
        ], range(1, 62));
        $employeeImport = EmployeeImport::query()->create([
            'token' => (string) Str::uuid(),
            'original_filename' => 'pegawai-62-baris.csv',
            'total_rows' => count($rows),
            'payload' => $rows,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator)
            ->get(route('employees.import.preview', $employeeImport))
            ->assertOk()
            ->assertSee('Menampilkan 1–10 dari 62 baris yang akan diproses.')
            ->assertSee('Pegawai 01')
            ->assertDontSee('Pegawai 11');

        $this->actingAs($operator)
            ->get(route('employees.import.preview', ['employeeImport' => $employeeImport, 'page' => 2]))
            ->assertOk()
            ->assertSee('Menampilkan 11–20 dari 62 baris yang akan diproses.')
            ->assertSee('Pegawai 11')
            ->assertDontSee('Pegawai 01');

        $this->actingAs($operator)
            ->get(route('employees.import.preview', ['employeeImport' => $employeeImport, 'page' => 7]))
            ->assertOk()
            ->assertSee('Menampilkan 61–62 dari 62 baris yang akan diproses.')
            ->assertSee('Pegawai 61')
            ->assertSee('Pegawai 62');
    }

    public function test_operator_can_preview_an_xlsx_import_and_download_the_template(): void
    {
        $operator = User::factory()->create();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['NIP', 'Nama Lengkap', 'Status Kepegawaian', 'Pangkat/Golongan', 'Jabatan'],
            ['199001012020011004', 'Pegawai Excel', 'PPPK', '', 'Jabatan Excel'],
        ], null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'employee-import-');

        if ($path === false) {
            self::fail('Tidak dapat membuat file sementara untuk pengujian Excel.');
        }

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $file = new UploadedFile(
            $path,
            'pegawai.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        try {
            $this->actingAs($operator)
                ->post(route('employees.import.upload'), ['file' => $file])
                ->assertRedirect();
        } finally {
            unlink($path);
        }

        $this->assertDatabaseHas('employee_imports', [
            'original_filename' => 'pegawai.xlsx',
            'total_rows' => 1,
            'status' => 'previewed',
        ]);
        $response = $this->actingAs($operator)
            ->get(route('employees.import.template'))
            ->assertOk()
            ->assertDownload('template-impor-pegawai.xlsx');

        $templateContents = $response->streamedContent();
        $this->assertStringStartsWith('PK', $templateContents);
        $templatePath = tempnam(sys_get_temp_dir(), 'employee-template-');

        if ($templatePath === false) {
            self::fail('Tidak dapat membuat file sementara untuk memeriksa template Excel.');
        }

        file_put_contents($templatePath, $templateContents);

        try {
            $template = IOFactory::load($templatePath);

            $this->assertSame('Data Pegawai', $template->getSheet(0)->getTitle());
            $this->assertSame('NIP', $template->getSheet(0)->getCell('A1')->getValue());
            $this->assertSame('Nama Lengkap', $template->getSheet(0)->getCell('B1')->getValue());
            $this->assertSame('Unit Kerja', $template->getSheet(0)->getCell('E1')->getValue());
            $template->disconnectWorksheets();
        } finally {
            unlink($templatePath);
        }
    }
}
