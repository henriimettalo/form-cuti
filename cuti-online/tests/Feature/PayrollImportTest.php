<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeePayroll;
use App\Models\PayrollImport;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\EmployeeOnboardingService;
use App\Services\PayrollImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class PayrollImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(EmployeeOnboardingService::class)->create([
            'nip' => '199001012020011102',
            'full_name' => 'Pegawai Terdaftar',
            'position_title' => 'Fungsional Umum',
            'rank_name' => 'Penata Muda',
            'grade' => 'III/a',
            'employment_status' => 'PNS',
            'department_name' => 'Unit Payroll',
        ]);
    }

    public function test_payroll_import_updates_registered_master_profile_bank_account_and_calculated_totals(): void
    {
        $operator = User::factory()->create(['role' => 'admin']);
        $service = app(PayrollImportService::class);
        $content = $this->csvContent();

        $period = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $content),
            2026,
            5,
            $operator->id,
        );

        $employee = Employee::query()->where('nip', '199001012020011102')->firstOrFail();
        $record = EmployeePayroll::query()
            ->where('payroll_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertSame('1990-01-01', $employee->birth_date?->toDateString());
        $this->assertSame('2020-01-01', $employee->service_started_on?->toDateString());
        $this->assertSame('L', $employee->gender);
        $this->assertTrue($employee->nip_tmt_valid);
        $this->assertSame(12, $employee->grade_service_years);
        $this->assertSame(3, $employee->grade_service_months);
        $this->assertSame(3, $employee->spouse_count + $employee->child_count);

        $this->assertDatabaseHas('employee_bank_accounts', [
            'employee_id' => $employee->id,
            'bank_code' => '123',
            'account_number' => '001234',
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('banks', [
            'code' => '123',
            'name' => 'Bank Contoh',
        ]);
        $this->assertInstanceOf(EmployeeBankAccount::class, $record->bankAccount);
        $this->assertSame(150000, $record->family_allowance);
        $this->assertSame(1210138, $record->total_earnings);
        $this->assertSame(210, $record->total_deductions);
        $this->assertSame(1209928, $record->transferred_amount);
        $this->assertSame(EmployeePayroll::RECONCILIATION_MATCHED, $record->reconciliation_status);
        $this->assertSame(1, $period->imported_rows);
        $this->assertSame(PayrollPeriod::STATUS_DRAFT, $period->status);

        $this->actingAs($operator)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Administrasi kepegawaian')
            ->assertDontSee('Payroll terbaru')
            ->assertSee('6171052401900001')
            ->assertDontSee('Rp 1.209.928');

        $this->actingAs(User::factory()->create(['role' => 'operator']))
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertDontSee('6171052401900001')
            ->assertSee('hanya ditampilkan untuk admin/pejabat berwenang');

    }

    public function test_import_rejects_unregistered_nip_and_does_not_import_any_data(): void
    {
        $operator = User::factory()->create();
        $lines = explode("\n", $this->csvContent());
        $unknownNipRow = str_replace('199001012020011102', '199001012020011103', $lines[1]);
        $content = implode("\n", [$lines[0], $lines[1], $unknownNipRow]);

        try {
            app(PayrollImportService::class)->import(
                UploadedFile::fake()->createWithContent('mei-2026.csv', $content),
                2026,
                5,
                $operator->id,
            );

            self::fail('Import seharusnya ditolak karena ada NIP yang belum terdaftar.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Baris 3', $exception->getMessage());
            $this->assertStringContainsString('199001012020011103', $exception->getMessage());
            $this->assertStringContainsString('Tambahkan pegawai terlebih dahulu', $exception->getMessage());
        }

        $this->assertDatabaseCount('payroll_periods', 0);
        $this->assertDatabaseCount('employee_payrolls', 0);
    }

    public function test_import_rejects_nip_that_is_not_18_digits(): void
    {
        $operator = User::factory()->create();
        $content = str_replace('199001012020011102', '19900101202001110', $this->csvContent());

        try {
            app(PayrollImportService::class)->import(
                UploadedFile::fake()->createWithContent('mei-2026-invalid-nip.csv', $content),
                2026,
                5,
                $operator->id,
            );

            self::fail('Import seharusnya ditolak karena NIP tidak terdiri dari 18 digit.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('NIP harus terdiri dari 18 digit', $exception->getMessage());
        }

        $this->assertDatabaseCount('payroll_periods', 0);
        $this->assertDatabaseCount('employee_payrolls', 0);
    }

    public function test_preview_ignores_empty_excel_rows_with_zero_salary_formulas(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $rows = array_map('str_getcsv', explode("\n", trim($this->csvContent())));
        $sheet->fromArray($rows);
        $sheet->setCellValue('U3', '=0');
        $sheet->setCellValue('AS3', '=SUM(U3:AR3)');
        $path = tempnam(sys_get_temp_dir(), 'payroll-empty-');

        try {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
            $preview = app(PayrollImportService::class)->preview(
                new UploadedFile($path, 'payroll.xlsx', null, null, true), 2026, 5,
            );
            $this->assertSame(1, $preview->total_rows);
            $this->assertDatabaseCount('employee_payrolls', 0);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($path);
        }
    }

    public function test_preview_rejects_unidentified_salary_and_preserves_original_row_numbers(): void
    {
        $lines = explode("\n", trim($this->csvContent()));
        $orphan = array_fill(0, count(str_getcsv($lines[0])), '');
        $orphan[20] = '1000000';
        $content = implode("\n", [$lines[0], $lines[1], '', implode(',', $orphan)]);

        try {
            app(PayrollImportService::class)->preview(
                UploadedFile::fake()->createWithContent('payroll.csv', $content), 2026, 5,
            );
            self::fail('Nominal gaji tanpa identitas harus ditolak.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Baris 4: NIP wajib diisi.', $exception->getMessage());
        }
        $this->assertDatabaseCount('payroll_imports', 0);
        $this->assertDatabaseCount('employee_payrolls', 0);
    }

    public function test_web_import_shows_unregistered_nip_row_error_to_the_user(): void
    {
        $operator = User::factory()->create();
        $lines = explode("\n", $this->csvContent());
        $unknownNipRow = str_replace('199001012020011102', '199001012020011103', $lines[1]);
        $content = implode("\n", [$lines[0], $lines[1], $unknownNipRow]);

        $response = $this->from(route('payroll.import.create'))
            ->actingAs($operator)
            ->post(route('payroll.import.store'), [
                'year' => 2026,
                'month' => 5,
                'file' => UploadedFile::fake()->createWithContent('mei-2026.csv', $content),
            ])
            ->assertRedirect(route('payroll.import.create'));

        $this->assertTrue($response->getSession()->has('errors'));

        $this->actingAs($operator)
            ->get(route('payroll.import.create'))
            ->assertOk()
            ->assertSee('Baris 3')
            ->assertSee('199001012020011103')
            ->assertSee('Tambahkan pegawai terlebih dahulu');
    }

    public function test_web_import_only_creates_a_preview_until_the_user_confirms(): void
    {
        $operator = User::factory()->create();

        $this->actingAs($operator)
            ->post(route('payroll.import.store'), [
                'year' => 2026,
                'month' => 5,
                'file' => UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('payroll_periods', 0);
        $this->assertDatabaseCount('employee_payrolls', 0);
        $this->assertSame('Pegawai Terdaftar', Employee::query()->firstOrFail()->full_name);

        $preview = PayrollImport::query()->firstOrFail();

        $this->assertTrue($preview->employee_data_only);
        $this->assertArrayNotHasKey('basic_salary', $preview->payload[0]);
        $this->assertArrayNotHasKey('source_transferred_amount', $preview->payload[0]);
        $this->assertArrayNotHasKey('tpp_workload', $preview->payload[0]);
        $this->assertSame('001234', $preview->payload[0]['account_number']);
        $this->actingAs($operator)->get(route('payroll.import.preview', $preview))
            ->assertOk()->assertDontSee('Gaji pokok')->assertDontSee('Ditransfer')
            ->assertSee('Simpan data pegawai');

        $this->actingAs($operator)
            ->post(route('payroll.import.confirm', $preview))
            ->assertRedirect();

        $this->assertDatabaseCount('payroll_periods', 0);
        $this->assertDatabaseCount('employee_payrolls', 0);
        $this->assertDatabaseHas('employee_bank_accounts', ['account_number' => '001234', 'is_primary' => true]);
        $this->assertSame('Pegawai Payroll', Employee::query()->firstOrFail()->full_name);
    }

    public function test_web_import_displays_each_row_error_as_a_separate_list_item(): void
    {
        $operator = User::factory()->create();
        $content = "nip_pegawai,nama_pegawai,status_asn,golongan\n199001012020011103,Pegawai A,1,III/a\n199001012020011104,Pegawai B,1,III/a";
        $page = $this->followingRedirects()->from(route('payroll.import.create'))->actingAs($operator)
            ->post(route('payroll.import.store'), [
                'year' => 2026, 'month' => 5,
                'file' => UploadedFile::fake()->createWithContent('belum-terdaftar.csv', $content),
            ])->assertOk()->assertSee('Ditemukan 2 error.');
        $page->assertSee('<li>Baris 2: NIP 199001012020011103 belum terdaftar di menu Pegawai. Tambahkan pegawai terlebih dahulu.</li>', false)
            ->assertSee('<li>Baris 3: NIP 199001012020011104 belum terdaftar di menu Pegawai. Tambahkan pegawai terlebih dahulu.</li>', false);
        $this->assertDatabaseCount('payroll_imports', 0);
    }

    public function test_web_employee_import_accepts_file_without_financial_columns(): void
    {
        $operator = User::factory()->create();
        $content = "nip_pegawai,nama_pegawai,status_asn,golongan,nama_jabatan\n199001012020011102,Nama Baru,1,III/a,Jabatan Baru";
        $this->actingAs($operator)->post(route('payroll.import.store'), [
            'year' => 2026, 'month' => 5, 'source_type' => 'tpp',
            'file' => UploadedFile::fake()->createWithContent('pegawai.csv', $content),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $preview = PayrollImport::query()->firstOrFail();
        $this->actingAs($operator)->post(route('payroll.import.confirm', $preview))
            ->assertRedirect(route('employees.index'));
        $this->assertSame('Nama Baru', Employee::query()->firstOrFail()->full_name);
        $this->assertDatabaseCount('employee_payrolls', 0);
        $this->assertDatabaseCount('employee_bank_accounts', 0);
        $this->assertDatabaseCount('payroll_periods', 0);
    }

    public function test_employee_import_accepts_functional_position_type_two(): void
    {
        $operator = User::factory()->create();
        $content = "nip_pegawai,nama_pegawai,status_asn,golongan,tipe_jabatan\n199001012020011102,Pegawai Fungsional,1,III/a,2";
        $this->actingAs($operator)->post(route('payroll.import.store'), [
            'year' => 2026, 'month' => 5,
            'file' => UploadedFile::fake()->createWithContent('fungsional.csv', $content),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $preview = PayrollImport::query()->firstOrFail();
        $this->assertSame(2, $preview->payload[0]['position_type']);
        $changes = $preview->master_changes['items'][0]['changes'];
        $typeChange = collect($changes)->firstWhere('attribute', 'position_type');
        $this->assertSame('Fungsional', $typeChange['new']);
        $this->actingAs($operator)->post(route('payroll.import.confirm', $preview))
            ->assertSessionHasNoErrors()->assertRedirect(route('employees.index'));
        $employee = Employee::query()->firstOrFail();
        $this->assertSame(2, $employee->position_type);
        $this->actingAs($operator)->get(route('employees.show', $employee))
            ->assertOk()->assertSee('Fungsional');
        $this->actingAs($operator)->get(route('employees.edit', $employee))
            ->assertOk()->assertSee('2 · Fungsional');
        $this->actingAs($operator)->get(route('employees.change-logs.index'))
            ->assertOk()->assertSee('Fungsional');
    }

    public function test_web_cannot_confirm_old_preview_containing_salary(): void
    {
        $preview = app(PayrollImportService::class)->preview(
            UploadedFile::fake()->createWithContent('lama.csv', $this->csvContent()), 2026, 5,
        );
        $this->actingAs(User::factory()->create())->post(route('payroll.import.confirm', $preview))
            ->assertSessionHasErrors('import');
        $this->assertDatabaseCount('employee_payrolls', 0);
        $this->assertSame('Pegawai Terdaftar', Employee::query()->firstOrFail()->full_name);
    }

    public function test_employee_only_preview_keeps_bank_but_discards_financial_values_including_footer(): void
    {
        $lines = explode("\n", trim($this->csvContent()));
        $row = str_getcsv($lines[1]);
        $row[19] = '000123456';
        $row[20] = 'GAJI_TIDAK_DISIMPAN';
        $footer = array_fill(0, count($row), '');
        $footer[20] = '9000000';
        $content = implode("\n", [$lines[0], implode(',', $row), implode(',', $footer)]);
        $preview = app(PayrollImportService::class)->preview(
            UploadedFile::fake()->createWithContent('pegawai.csv', $content), 2026, 5,
            employeeDataOnly: true,
        );
        $this->assertSame(1, $preview->total_rows);
        $this->assertStringNotContainsString('GAJI_TIDAK_DISIMPAN', json_encode($preview->payload));
        $this->assertSame('000123456', $preview->payload[0]['account_number']);
        $this->assertArrayNotHasKey('basic_salary', $preview->payload[0]);
        app(PayrollImportService::class)->confirmEmployeeData($preview);
        $this->assertDatabaseCount('employee_payrolls', 0);
        $this->assertDatabaseHas('employee_bank_accounts', ['account_number' => '000123456']);
        $this->assertDatabaseCount('payroll_periods', 0);
        $audit = AuditLog::query()->latest('id')->firstOrFail();
        $this->assertArrayNotHasKey('basic_salary', $audit->new_values);
        $this->assertArrayHasKey('account_number', $audit->new_values);
    }

    public function test_employee_import_accepts_account_number_without_bank_metadata_and_keeps_it_when_omitted(): void
    {
        $operator = User::factory()->create();
        $header = 'nip_pegawai,nama_pegawai,status_asn,golongan';
        foreach ([
            $header.",nomor_rekening\n199001012020011102,Pegawai Terdaftar,1,III/a,000987654",
            $header."\n199001012020011102,Pegawai Terdaftar,1,III/a",
        ] as $content) {
            $this->actingAs($operator)->post(route('payroll.import.store'), [
                'year' => 2026, 'month' => 5,
                'file' => UploadedFile::fake()->createWithContent('rekening.csv', $content),
            ])->assertSessionHasNoErrors()->assertRedirect();
            $preview = PayrollImport::query()->latest('id')->firstOrFail();
            $this->actingAs($operator)->post(route('payroll.import.confirm', $preview))
                ->assertSessionHasNoErrors()->assertRedirect(route('employees.index'));
            $this->assertDatabaseHas('employee_bank_accounts', ['account_number' => '000987654', 'is_primary' => true]);
            $this->assertDatabaseCount('employee_bank_accounts', 1);
        }
        $this->assertDatabaseCount('employee_payrolls', 0);
        $this->assertDatabaseCount('payroll_periods', 0);
        $employee = Employee::query()->firstOrFail();
        $this->actingAs($operator)->get(route('employees.show', $employee))
            ->assertOk()->assertSee('000987654')->assertDontSee(' · 000987654');
    }

    public function test_employee_import_preserves_bank_metadata_when_only_the_same_account_number_is_provided(): void
    {
        $service = app(PayrollImportService::class);
        foreach ([$this->csvContent(), "nip_pegawai,nama_pegawai,status_asn,golongan,nomor_rekening\n199001012020011102,Pegawai Payroll,1,III/a,001234"] as $content) {
            $preview = $service->preview(UploadedFile::fake()->createWithContent('rekening.csv', $content), 2026, 5, employeeDataOnly: true);
            $service->confirmEmployeeData($preview);
        }
        $this->assertDatabaseCount('employee_bank_accounts', 1);
        $this->assertDatabaseHas('employee_bank_accounts', ['account_number' => '001234', 'bank_code' => '123', 'is_primary' => true]);
    }

    public function test_tpp_import_complements_primary_period_without_replacing_salary_data(): void
    {
        $operator = User::factory()->create();
        $service = app(PayrollImportService::class);
        $period = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );

        $recordBefore = EmployeePayroll::query()->where('payroll_period_id', $period->id)->firstOrFail();

        $tppPeriod = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026-tpp.csv', $this->tppCsvContent()),
            2026,
            5,
            $operator->id,
            PayrollImport::SOURCE_TPP,
        );

        $record = $recordBefore->fresh();

        $this->assertSame($period->id, $tppPeriod->id);
        $this->assertSame(1000000, $record->basic_salary);
        $this->assertSame(711300, $record->tpp_total);
        $this->assertSame(2270, $record->tpp_total_deductions);
        $this->assertSame(709030, $record->tpp_transferred_amount);
        $this->assertSame(EmployeePayroll::TPP_RECONCILIATION_MATCHED, $record->tpp_reconciliation_status);
        $this->assertSame('123', $record->tpp_bank_code);
        $this->assertSame('001234', $record->tpp_account_number);
        $this->assertSame(1, $tppPeriod->tpp_imported_rows);
        $this->assertTrue($tppPeriod->hasTpp());
        $this->assertDatabaseCount('employee_payrolls', 1);
    }

    public function test_tpp_import_requires_primary_payroll(): void
    {
        $operator = User::factory()->create();
        $service = app(PayrollImportService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('gaji utama harus diimpor terlebih dahulu');

        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026-tpp.csv', $this->tppCsvContent()),
            2026,
            5,
            $operator->id,
            PayrollImport::SOURCE_TPP,
        );
    }

    public function test_tpp_import_cannot_be_repeated_for_the_same_period(): void
    {
        $operator = User::factory()->create();
        $service = app(PayrollImportService::class);

        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );
        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026-tpp.csv', $this->tppCsvContent()),
            2026,
            5,
            $operator->id,
            PayrollImport::SOURCE_TPP,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pelengkap TPP periode');

        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026-tpp-ulangan.csv', $this->tppCsvContent()),
            2026,
            5,
            $operator->id,
            PayrollImport::SOURCE_TPP,
        );
    }

    public function test_operator_can_export_tpp_period_with_the_sipd_column_structure(): void
    {
        $operator = User::factory()->create(['role' => 'admin']);
        $service = app(PayrollImportService::class);
        $period = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );
        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026-tpp.csv', $this->tppCsvContent()),
            2026,
            5,
            $operator->id,
            PayrollImport::SOURCE_TPP,
        );

        $response = $this->actingAs($operator)
            ->get(route('payroll.export-tpp', $period))
            ->assertOk()
            ->assertDownload('tpp-sipd-2026-05.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'tpp-export-');

        if ($path === false) {
            self::fail('Tidak dapat membuat file sementara untuk memeriksa export TPP.');
        }

        file_put_contents($path, $response->streamedContent());

        try {
            $workbook = IOFactory::load($path);
            $sheet = $workbook->getActiveSheet();

            $this->assertSame('NIP Pegawai', $sheet->getCell('A1')->getValue());
            $this->assertSame('Jumlah TPP', $sheet->getCell('AF1')->getValue());
            $this->assertSame('Jumlah Ditransfer', $sheet->getCell('AH1')->getValue());
            $this->assertSame('199001012020011102', $sheet->getCell('A2')->getValue());
            $this->assertSame('=SUM(AF2:AF2)', $sheet->getCell('AF3')->getValue());
        } finally {
            unlink($path);
        }
    }

    public function test_payroll_period_cannot_be_imported_twice_unless_rejected(): void
    {
        $operator = User::factory()->create();
        $service = app(PayrollImportService::class);
        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah memiliki data');

        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026-revisi.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );
    }

    public function test_import_reports_master_changes_by_nip_and_name(): void
    {
        $operator = User::factory()->create();
        $service = app(PayrollImportService::class);
        $period = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );
        $period->update(['status' => PayrollPeriod::STATUS_REJECTED]);

        Employee::query()->where('nip', '199001012020011102')->update([
            'full_name' => 'Nama Lama',
            'address' => 'Alamat Lama',
            'child_count' => 1,
            'grade' => 'II/a',
        ]);

        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026-revisi.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );

        $report = $service->lastMasterChanges();
        $this->assertSame(1, $report['total']);
        $this->assertSame('199001012020011102', $report['items'][0]['nip']);
        $this->assertSame('Pegawai Payroll', $report['items'][0]['name']);

        $changes = collect($report['items'][0]['changes'])->keyBy('field');
        $this->assertSame('Nama Lama', $changes['Nama']['old']);
        $this->assertSame('Pegawai Payroll', $changes['Nama']['new']);
        $this->assertSame('Alamat Lama', $changes['Alamat rumah']['old']);
        $this->assertSame('Pontianak', $changes['Alamat rumah']['new']);
        $this->assertSame('1', $changes['Jumlah anak']['old']);
        $this->assertSame('2', $changes['Jumlah anak']['new']);
        $this->assertSame('II/a', $changes['Golongan']['old']);
        $this->assertSame('III/a', $changes['Golongan']['new']);
    }

    public function test_import_redirect_shows_master_change_notice_to_the_user(): void
    {
        $operator = User::factory()->create();
        $service = app(PayrollImportService::class);
        $period = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );
        $period->update(['status' => PayrollPeriod::STATUS_REJECTED]);

        Employee::query()->where('nip', '199001012020011102')->update([
            'full_name' => 'Nama Lama',
        ]);

        $auditCountBefore = AuditLog::query()->count();

        $this->actingAs($operator)
            ->post(route('payroll.import.store'), [
                'year' => 2026,
                'month' => 5,
                'file' => UploadedFile::fake()->createWithContent('mei-2026-revisi.csv', $this->csvContent()),
            ])
            ->assertRedirect();

        $preview = PayrollImport::query()->latest('id')->firstOrFail();

        $this->actingAs($operator)
            ->get(route('payroll.import.preview', $preview))
            ->assertOk()
            ->assertSee('Perubahan data master dari file')
            ->assertSee('199001012020011102')
            ->assertSee('Nama Lama')
            ->assertSee('Pegawai Payroll');

        $this->assertSame($auditCountBefore, AuditLog::query()->count());

        $this->actingAs($operator)
            ->post(route('payroll.import.confirm', $preview))
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'employee.master_updated_from_payroll_import',
            'auditable_id' => Employee::query()->where('nip', '199001012020011102')->value('id'),
        ]);
        $this->assertSame($auditCountBefore + 1, AuditLog::query()->count());

        $this->actingAs($operator)
            ->get(route('employees.change-logs.index'))
            ->assertOk()
            ->assertSee('Pegawai Payroll')
            ->assertSee('Nama Lama')
            ->assertSee('mei-2026-revisi.csv');
    }

    public function test_change_log_search_shows_a_visible_empty_state_when_no_employee_matches(): void
    {
        $operator = User::factory()->create();
        $employee = Employee::query()->firstOrFail();

        AuditLog::query()->create([
            'user_id' => $operator->id,
            'event' => 'employee.master_updated_from_payroll_import',
            'auditable_type' => Employee::class,
            'auditable_id' => $employee->id,
            'old_values' => ['grade' => 'II/a'],
            'new_values' => ['grade' => 'III/a'],
            'metadata' => [],
        ]);

        $this->actingAs($operator)
            ->get(route('employees.change-logs.index', ['search' => 'aaa']))
            ->assertOk()
            ->assertSee('Tidak ada hasil pencarian.')
            ->assertSee('aaa')
            ->assertSee('Hapus filter')
            ->assertDontSee('Pegawai Terdaftar');
    }

    public function test_rejected_payroll_period_can_be_reimported_without_duplicate_rows(): void
    {
        $operator = User::factory()->create();
        $service = app(PayrollImportService::class);
        $period = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );

        $this->actingAs($operator)
            ->post(route('payroll.reject', $period))
            ->assertRedirect(route('payroll.show', $period));

        $this->assertDatabaseHas('payroll_periods', [
            'id' => $period->id,
            'status' => PayrollPeriod::STATUS_REJECTED,
        ]);

        $reimported = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026-revisi.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );

        $this->assertSame($period->id, $reimported->id);
        $this->assertSame(PayrollPeriod::STATUS_DRAFT, $reimported->status);
        $this->assertSame('mei-2026-revisi.csv', $reimported->source_file);
        $this->assertDatabaseCount('employee_payrolls', 1);
    }

    public function test_locked_payroll_period_cannot_be_imported_again(): void
    {
        $operator = User::factory()->create();
        $service = app(PayrollImportService::class);
        $period = $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );
        $period->update(['status' => PayrollPeriod::STATUS_LOCKED]);

        $this->expectException(InvalidArgumentException::class);

        $service->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );
    }

    public function test_operator_can_open_a_detailed_payroll_slip(): void
    {
        $operator = User::factory()->create();
        $period = app(PayrollImportService::class)->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );
        $record = EmployeePayroll::query()->where('payroll_period_id', $period->id)->firstOrFail();

        $this->actingAs($operator)
            ->get(route('payroll.slip', [$period, $record]))
            ->assertOk()
            ->assertSee('Slip gaji')
            ->assertSee('Tunjangan khusus Papua')
            ->assertSee('Rp 1.209.928');
    }

    public function test_operator_can_export_a_payroll_period_with_the_bkad_column_structure(): void
    {
        $operator = User::factory()->create(['role' => 'admin']);
        $period = app(PayrollImportService::class)->import(
            UploadedFile::fake()->createWithContent('mei-2026.csv', $this->csvContent()),
            2026,
            5,
            $operator->id,
        );

        $response = $this->actingAs($operator)
            ->get(route('payroll.export', $period))
            ->assertOk()
            ->assertDownload('payroll-2026-05.xlsx');

        $contents = $response->streamedContent();
        $this->assertStringStartsWith('PK', $contents);
        $path = tempnam(sys_get_temp_dir(), 'payroll-export-');

        if ($path === false) {
            self::fail('Tidak dapat membuat file sementara untuk memeriksa export payroll.');
        }

        file_put_contents($path, $contents);

        try {
            $workbook = IOFactory::load($path);
            $sheet = $workbook->getActiveSheet();

            $this->assertSame('Sheet1', $sheet->getTitle());
            $this->assertSame('nip_pegawai', $sheet->getCell('A1')->getValue());
            $this->assertSame('jumlah_ditransfer', $sheet->getCell('AS1')->getValue());
            $this->assertSame('skpd', $sheet->getCell('AT1')->getValue());
            $this->assertSame('199001012020011102', $sheet->getCell('A2')->getValue());
            $this->assertSame('Unit Payroll', $sheet->getCell('AT2')->getValue());
            $this->assertSame('=SUM(AQ2:AQ2)', $sheet->getCell('AQ3')->getValue());
            $this->assertSame('=SUM(AS2:AS2)', $sheet->getCell('AS3')->getValue());
        } finally {
            unlink($path);
        }
    }

    private function csvContent(): string
    {
        return implode("\n", [
            implode(',', [
                'nip_pegawai',
                'nama_pegawai',
                'nik_pegawai',
                'npwp_pegawai',
                'tanggal_lahir_pegawai',
                'tipe_jabatan',
                'nama_jabatan',
                'eselon',
                'status_asn',
                'golongan',
                'masa_kerja_golongan',
                'alamat',
                'status_pernikahan',
                'jumlah_istri_suami',
                'jumlah_anak',
                'pasangan_pns',
                'nip_pasangan',
                'kode_bank',
                'nama_bank',
                'nomor_rekening_bank_pegawai',
                'gaji_pokok',
                'perhitungan_suami_istri',
                'perhitungan_anak',
                'tunjangan_keluarga',
                'tunjangan_jabatan',
                'tunjangan_fungsional',
                'tunjangan_fungsional_umum',
                'tunjangan_beras',
                'tunjangan_pph',
                'pembulatan_gaji',
                'iuran_jaminan_kesehatan',
                'iuran_jaminan_kecelakaan_kerja',
                'iuran_jaminan_kematian',
                'iuran_simpanan_tapera',
                'iuran_pensiun',
                'tunjangan_khusus_papua',
                'tunjangan_jaminan_hari_tua',
                'potongan_iwp',
                'potongan_pph21',
                'zakat',
                'bulog',
                'jumlah_gaji_dan_tunjangan',
                'jumlah_potongan',
                'jumlah_ditransfer',
                'skpd',
            ]),
            implode(',', [
                '199001012020011102',
                'Pegawai Payroll',
                '6171052401900001',
                '123456789012345',
                '01-01-1990',
                '3',
                'Fungsional Umum',
                '00',
                '1',
                'III/a',
                '12 tahun 3 bulan',
                'Pontianak',
                '1',
                '1',
                '2',
                'YA',
                '',
                '123',
                'Bank Contoh',
                '001234',
                '1000000',
                '100000',
                '50000',
                '150000',
                '25000',
                '0',
                '10000',
                '20000',
                '5000',
                '1',
                '100',
                '10',
                '20',
                '3',
                '4',
                '7',
                '30',
                '40',
                '2',
                '1',
                '0',
                '1210138',
                '210',
                '1209928',
                'Unit Payroll',
            ]),
        ]);
    }

    private function tppCsvContent(): string
    {
        return implode("\n", [
            implode(',', [
                'NIP Pegawai',
                'Nama Pegawai',
                'NIK Pegawai',
                'NPWP Pegawai',
                'Tanggal Lahir Pegawai',
                'Tipe Jabatan',
                'Nama Jabatan',
                'Eselon',
                'Status ASN',
                'golongan',
                'Masa Kerja Golongan',
                'Alamat',
                'Kode Bank',
                'Nama Bank',
                'Nomor Rekening Bank Pegawai',
                'TPP Beban Kerja',
                'TPP Tempat Bertugas',
                'TPP Kondisi kerja',
                'TPP Kelangkaan Profesi',
                'TPP Prestasi Kerja',
                'Tunjangan PPh',
                'Iuran Jaminan Kesehatan',
                'Iuran Jaminan Kecelakaan Kerja',
                'Iuran Jaminan Kematian',
                'Iuran Simpanan Tapera',
                'Iuran Pensiun',
                'Tunjangan Jaminan Hari Tua',
                'Potongan IWP',
                'Potongan PPh 21',
                'Zakat',
                'Bulog',
                'Jumlah TPP',
                'Jumlah Potongan',
                'Jumlah Ditransfer',
            ]),
            implode(',', [
                '199001012020011102',
                'Pegawai Payroll',
                '6171052401900001',
                '123456789012345',
                '01-01-1990',
                '3',
                'Fungsional Umum',
                '00',
                '1',
                'III/a',
                '12 tahun 3 bulan',
                'Pontianak',
                '123',
                'Bank Contoh',
                '001234',
                '500000',
                '0',
                '0',
                '0',
                '200000',
                '10000',
                '1000',
                '100',
                '200',
                '50',
                '300',
                '20',
                '500',
                '100',
                '0',
                '0',
                '711300',
                '2270',
                '709030',
            ]),
        ]);
    }
}
