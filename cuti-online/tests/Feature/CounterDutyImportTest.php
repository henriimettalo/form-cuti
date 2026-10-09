<?php

namespace Tests\Feature;

use App\Models\CeremonySchedule;
use App\Models\Department;
use App\Models\DutyGroup;
use App\Models\DutyHoliday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CounterDutyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_access_import_actions(): void
    {
        $this->get(route('counter-duty.import.create'))->assertRedirect(route('login'));
        $department = Department::query()->create(['name' => 'Unit Aktif', 'is_active' => true]);
        foreach ([User::ROLE_ADMIN_UNIT, User::ROLE_PENGGUNA, 'operator'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'department_id' => $department->id]));
            $this->get(route('counter-duty.import.create'))->assertForbidden();
            $this->post(route('counter-duty.import.preview'))->assertForbidden();
            $this->post(route('counter-duty.import.store'))->assertForbidden();
            $this->post(route('counter-duty.import.cancel'))->assertForbidden();
        }
        $this->actingAs(User::factory()->create())->get(route('counter-duty.index'))
            ->assertOk()->assertSee('Impor Excel tahun N');
        $this->get(route('counter-duty.import.create'))->assertOk()->assertSee('Tahun impor');
    }

    public function test_import_filters_original_dates_by_year_and_uses_ref_roster(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);
        $file = $this->workbook([
            ['2025-12-31', 4, 'Nama dari summary yang tidak digunakan'],
            [Date::dateTimeToExcel(new \DateTimeImmutable('2026-01-02')), 2, 'Petugas dari Excel'],
            ['05/01/2026', 'Kelompok 3', 'Penanggung dari Excel'],
            ['2027-01-01', 1, 'Lainnya'],
        ]);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026])
            ->assertRedirect(route('counter-duty.import.create'));
        $preview = session('counter-duty-import-preview');
        $this->assertSame(2, $preview['outside_year']);
        $this->assertSame(['2026-01-02', '2026-01-05'], array_column($preview['rows'], 'date'));
        $this->assertSame([2, 3], array_column($preview['rows'], 'group_number'));
        $this->assertSame('Asra, S.Sos', $preview['rows'][1]['roster']['coordinator']);
        $this->assertContains('Widya Yulianti, S.IP', $preview['rows'][1]['roster']['members']);
        $this->assertDatabaseCount('ceremony_schedules', 0);
        $this->get(route('counter-duty.import.create'))->assertOk()
            ->assertSee('2 jadwal baru')->assertSee('2 baris di luar tahun')->assertSee('Asra, S.Sos');
        $this->post(route('counter-duty.import.store'))
            ->assertRedirect(route('counter-duty.index', ['month' => '2026-01']))
            ->assertSessionMissing('counter-duty-import-preview');
        $this->assertDatabaseCount('ceremony_schedules', 2);
        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-01-02', 'created_by' => $admin->id, 'duty_group_id' => 2]);
        $this->assertDatabaseMissing('ceremony_schedules', ['event_date' => '2025-12-31']);
        $this->assertDatabaseMissing('ceremony_schedules', ['event_date' => '2027-01-01']);
        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('duty_holidays', 0);
    }

    public function test_cached_formula_results_and_mac_excel_calendar_are_read_without_recalculation(): void
    {
        $this->actingAs(User::factory()->create());
        $file = $this->workbook([['2026-01-05', 1]], Date::CALENDAR_MAC_1904, true);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026])->assertSessionHasNoErrors();
        $row = session('counter-duty-import-preview.rows.0');
        $this->assertSame('2026-01-05', $row['date']);
        $this->assertSame(3, $row['group_number']);
        $this->assertSame('new', $row['status']);
    }

    public function test_repeated_import_and_existing_dates_never_overwrite_records(): void
    {
        $this->actingAs(User::factory()->create());
        $existing = CeremonySchedule::query()->create([
            'title' => 'Jadwal Lama', 'type' => 'piket_loket', 'event_date' => '2026-01-02',
            'duty_roster' => DutyGroup::query()->findOrFail(4)->roster(), 'duty_group_id' => 4, 'duty_date' => '2026-01-02',
        ]);
        $original = $existing->fresh()->getAttributes();
        $file = $this->workbook([['2026-01-02', 1], ['2026-01-05', 2]]);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026]);
        $this->assertSame(['existing', 'new'], array_column(session('counter-duty-import-preview.rows'), 'status'));
        $this->post(route('counter-duty.import.store'))->assertSessionHasNoErrors();
        $this->assertSame($original, $existing->fresh()->getAttributes());
        $this->assertDatabaseCount('ceremony_schedules', 2);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026]);
        $this->assertSame(['existing', 'existing'], array_column(session('counter-duty-import-preview.rows'), 'status'));
        $this->post(route('counter-duty.import.store'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ceremony_schedules', 2);
    }

    public function test_invalid_dates_groups_duplicates_and_holiday_conflicts_block_the_entire_import(): void
    {
        DutyHoliday::query()->create(['holiday_date' => '2026-01-08', 'name' => 'Libur Uji']);
        $this->actingAs(User::factory()->create());
        $file = $this->workbook([
            ['2026-01-05', 1], ['2026-01-05', 2], ['30/02/2026', 3],
            ['2026-01-06', 5], ['2026-01-08', 4],
        ]);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026])->assertSessionHasNoErrors();
        $this->assertSame(['new', 'error', 'error', 'error', 'error'], array_column(session('counter-duty-import-preview.rows'), 'status'));
        $this->get(route('counter-duty.import.create'))->assertOk()->assertSee('4 bermasalah')
            ->assertSee('Libur Uji')->assertDontSee('data-confirm-title="Impor jadwal', false);
        $this->post(route('counter-duty.import.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 0);
    }

    public function test_explicit_weekend_schedule_is_preserved_and_empty_year_has_no_import_button(): void
    {
        $this->actingAs(User::factory()->create());
        $file = $this->workbook([['2026-01-03', 1]]);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026]);
        $this->assertStringContainsString('Akhir pekan', session('counter-duty-import-preview.rows.0.message'));
        $this->post(route('counter-duty.import.store'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ceremony_schedules', ['event_date' => '2026-01-03']);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2027]);
        $this->assertSame([], session('counter-duty-import-preview.rows'));
        $this->get(route('counter-duty.import.create'))->assertOk()->assertSee('Tidak ada tanggal pada tahun 2027.')
            ->assertDontSee('data-confirm-title="Impor jadwal', false);
    }

    public function test_changed_groups_holidays_and_existing_schedules_invalidate_preview(): void
    {
        $this->actingAs(User::factory()->create());
        $file = $this->workbook([['2026-01-05', 1], ['2026-01-06', 2]]);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026]);
        DutyGroup::query()->findOrFail(1)->update(['coordinator' => 'Nama Baru']);
        $this->post(route('counter-duty.import.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 0);

        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026]);
        DutyHoliday::query()->create(['holiday_date' => '2026-01-06', 'name' => 'Libur Baru']);
        $this->post(route('counter-duty.import.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 0);
        DutyHoliday::query()->delete();

        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026]);
        CeremonySchedule::query()->create(['title' => 'Manual', 'type' => 'piket_loket', 'event_date' => '2026-01-06']);
        $this->post(route('counter-duty.import.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 1);
    }

    public function test_cancel_and_invalid_upload_clear_preview_and_store_requires_confirmation(): void
    {
        $this->actingAs(User::factory()->create());
        $file = $this->workbook([['2026-01-05', 1]]);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026]);
        $this->post(route('counter-duty.import.cancel'))->assertRedirect(route('counter-duty.index'))
            ->assertSessionMissing('counter-duty-import-preview');
        $this->post(route('counter-duty.import.store'))->assertSessionHasErrors('preview');
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026]);
        $this->post(route('counter-duty.import.preview'), ['year' => 'N'])
            ->assertSessionHasErrors(['file', 'year'])->assertSessionMissing('counter-duty-import-preview');
        $this->post(route('counter-duty.import.preview'), ['file' => UploadedFile::fake()->createWithContent('broken.xlsx', 'bad'), 'year' => 2026])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('ceremony_schedules', 0);
    }

    public function test_first_sheet_fallback_and_required_headers(): void
    {
        $this->actingAs(User::factory()->create());
        $file = $this->workbook([['2026-01-05', 1]], sheetName: 'Data');
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026])
            ->assertSessionHasNoErrors()->assertSessionHas('counter-duty-import-preview.sheet_name', 'Data');
        $file = $this->workbook([['2026-01-05', 1]], headers: ['Tanggal', 'Kelompok', 'Nama']);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026])->assertSessionHasNoErrors();
        $file = $this->workbook([['2026-01-05', 1]], headers: ['Tanggal', 'Tidak Ada Kelompok']);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026])->assertSessionHasErrors('file');
    }

    public function test_auto_generation_continues_from_last_imported_group(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('counter-duty.import.preview'), ['file' => $this->workbook([['2026-01-30', 3]]), 'year' => 2026]);
        $this->post(route('counter-duty.import.store'))->assertSessionHasNoErrors();
        $this->post(route('counter-duty.preview'), ['start_date' => '2026-02-02', 'end_date' => '2026-02-03', 'starting_group' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame([4, 1], collect(session('counter-duty-preview.rows'))->pluck('roster.number')->all());
    }

    public function test_formulas_without_saved_results_are_not_silently_skipped(): void
    {
        $this->actingAs(User::factory()->create());
        $file = $this->workbook([['=MissingSheet!A1', '=MissingSheet!B1']]);
        $this->post(route('counter-duty.import.preview'), ['file' => $file, 'year' => 2026])->assertSessionHasNoErrors();
        $this->assertSame('error', session('counter-duty-import-preview.rows.0.status'));
        $this->assertStringContainsString('hasil formula belum tersimpan', session('counter-duty-import-preview.rows.0.message'));
        $this->post(route('counter-duty.import.store'))->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('ceremony_schedules', 0);
    }

    private function workbook(array $rows, int $calendar = Date::CALENDAR_WINDOWS_1900, bool $formula = false, string $sheetName = 'jadwal_piket', array $headers = ['Tanggal Piket', 'Kelompok Yang Piket', 'Penanggung Jawab']): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('summary');
        $sheet->setCellValue('A1', 'Daftar lain yang tidak dibaca');
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($sheetName);
        $spreadsheet->setExcelCalendar($calendar);
        $sheet->fromArray([$headers, ...$rows], null, 'A1');
        if ($formula) {
            $sheet->getCell('A2')->setValueExplicit('=MissingSheet!A1', DataType::TYPE_FORMULA)
                ->setCalculatedValue(Date::dateTimeToExcel(new \DateTimeImmutable('2026-01-05'), $calendar));
            $sheet->getCell('B2')->setValueExplicit('=MissingSheet!B1', DataType::TYPE_FORMULA)->setCalculatedValue(3);
        }
        if ($sheetName !== 'jadwal_piket') {
            $spreadsheet->removeSheetByIndex(0);
        }
        $path = tempnam(sys_get_temp_dir(), 'counter-import-');
        try {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($path);
            if ($formula) {
                $archive = new \ZipArchive;
                $archive->open($path);
                $document = new \DOMDocument;
                $document->loadXML($archive->getFromName('xl/worksheets/sheet2.xml'));
                $xpath = new \DOMXPath($document);
                $xpath->registerNamespace('sheet', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach (['A2' => Date::dateTimeToExcel(new \DateTimeImmutable('2026-01-05'), $calendar), 'B2' => 3] as $coordinate => $cachedValue) {
                    $cell = $xpath->query('//sheet:c[@r="'.$coordinate.'"]')->item(0);
                    foreach ($xpath->query('sheet:v', $cell) as $valueNode) {
                        $cell->removeChild($valueNode);
                    }
                    $cell->appendChild($document->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main', 'v', (string) $cachedValue));
                }
                $archive->addFromString('xl/worksheets/sheet2.xml', $document->saveXML());
                $archive->close();
            }

            return UploadedFile::fake()->createWithContent('piket.xlsx', file_get_contents($path));
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($path);
        }
    }
}
