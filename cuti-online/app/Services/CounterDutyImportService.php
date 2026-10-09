<?php

namespace App\Services;

use App\Models\CeremonySchedule;
use App\Models\DutyGroup;
use App\Models\DutyHoliday;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

class CounterDutyImportService
{
    public function preview(UploadedFile $file, int $year): array
    {
        try {
            $reader = IOFactory::createReader('Xlsx');
            $sheets = $reader->listWorksheetInfo($file->getRealPath());
            $sheetInfo = collect($sheets)->firstWhere('worksheetName', 'jadwal_piket') ?? $sheets[0] ?? null;
            if (! $sheetInfo || $sheetInfo['totalRows'] > 10001 || $sheetInfo['totalColumns'] > 50) {
                throw ValidationException::withMessages(['file' => 'Sheet jadwal maksimal 10.000 baris data dan 50 kolom.']);
            }
            $reader->setLoadSheetsOnly($sheetInfo['worksheetName']);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'File Excel tidak dapat dibaca. Gunakan file .xlsx yang tidak rusak.']);
        }

        try {
            $sheet = $spreadsheet->getSheet(0);
            $columns = [];
            foreach ($sheet->getRowIterator(1, 1) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $header = preg_replace('/\s+/', '_', strtolower(trim((string) $this->cellValue($cell))));
                    if (in_array($header, ['tanggal_piket', 'tanggal', 'kelompok_yang_piket', 'kelompok'], true)) {
                        $field = str_starts_with($header, 'tanggal') ? 'date' : 'group';
                        if (isset($columns[$field])) {
                            throw ValidationException::withMessages(['file' => 'Kolom tanggal atau kelompok muncul lebih dari satu kali. Periksa header Excel.']);
                        }
                        $columns[$field] = $cell->getColumn();
                    }
                }
            }
            if (! isset($columns['date'], $columns['group'])) {
                throw ValidationException::withMessages(['file' => 'Baris pertama harus berisi kolom Tanggal Piket (atau Tanggal) dan Kelompok Yang Piket (atau Kelompok).']);
            }

            $sourceRows = [];
            $outsideYear = 0;
            for ($rowNumber = 2; $rowNumber <= $sheet->getHighestDataRow(); $rowNumber++) {
                $dateCell = $sheet->getCell($columns['date'].$rowNumber);
                $groupCell = $sheet->getCell($columns['group'].$rowNumber);
                $dateValue = $this->cellValue($dateCell);
                $groupValue = $this->cellValue($groupCell);
                if (($dateValue === null || $dateValue === '') && ($groupValue === null || $groupValue === '')
                    && $dateCell->getDataType() !== DataType::TYPE_FORMULA && $groupCell->getDataType() !== DataType::TYPE_FORMULA) {
                    continue;
                }
                $date = $this->parseDate($dateValue, $spreadsheet->getExcelCalendar());
                if ($date && $date->year !== $year) {
                    $outsideYear++;

                    continue;
                }
                $group = $this->parseGroup($groupValue);
                $sourceRows[] = [
                    'row_number' => $rowNumber,
                    'date' => $date?->toDateString(),
                    'group_number' => $group,
                    'error' => ! $date ? 'Tanggal tidak valid atau hasil formula belum tersimpan. Gunakan tanggal Excel atau teks dd/mm/yyyy.' : (! $group ? 'Kelompok harus berupa nomor 1–4 (atau Kelompok 1–4).' : null),
                ];
            }

            return $this->classify($sourceRows, $year, $file->getClientOriginalName(), $outsideYear, $sheet->getTitle());
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    public function store(array $preview, int $userId): int
    {
        try {
            return DB::transaction(function () use ($preview, $userId): int {
                DutyGroup::query()->orderBy('id')->lockForUpdate()->get();
                $current = $this->classify($preview['source_rows'], $preview['year'], $preview['file_name'], $preview['outside_year'], $preview['sheet_name']);
                if ($current !== $preview) {
                    throw ValidationException::withMessages(['preview' => 'Jadwal, hari libur, atau kelompok telah berubah. Periksa file ulang sebelum mengimpor.']);
                }
                if (collect($current['rows'])->contains('status', 'error')) {
                    throw ValidationException::withMessages(['preview' => 'Perbaiki semua baris bermasalah sebelum mengimpor. Tidak ada jadwal yang disimpan.']);
                }

                $count = 0;
                foreach ($current['rows'] as $row) {
                    if ($row['status'] !== 'new') {
                        continue;
                    }

                    CeremonySchedule::query()->create([
                        'title' => 'Piket Loket — Kelompok '.$row['group_number'],
                        'type' => 'piket_loket',
                        'event_date' => $row['date'],
                        'duty_date' => $row['date'],
                        'duty_group_id' => $row['group_id'],
                        'duty_roster' => $row['roster'],
                        'created_by' => $userId,
                    ]);
                    $count++;
                }

                return $count;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['preview' => 'Tanggal piket sudah disimpan oleh proses lain. Periksa file ulang; jadwal lama tidak ditimpa.']);
        }
    }

    private function classify(array $sourceRows, int $year, string $fileName, int $outsideYear, string $sheetName): array
    {
        $groups = DutyGroup::query()->get()->keyBy('number');
        $existing = CeremonySchedule::query()->where('type', 'piket_loket')->whereYear('event_date', $year)
            ->get()->groupBy(fn ($schedule) => $schedule->event_date->toDateString());
        $holidays = DutyHoliday::query()->whereYear('holiday_date', $year)
            ->get()->keyBy(fn ($holiday) => $holiday->holiday_date->toDateString());
        $seenDates = [];
        $rows = [];
        foreach ($sourceRows as $sourceRow) {
            $row = $sourceRow;
            $row['status'] = 'new';
            $row['message'] = 'Akan diimpor';
            if ($row['error']) {
                $row['status'] = 'error';
                $row['message'] = $row['error'];
            } elseif (isset($seenDates[$row['date']])) {
                $row['status'] = 'error';
                $row['message'] = 'Tanggal berulang; sudah ada pada baris '.$seenDates[$row['date']].'.';
            } else {
                $seenDates[$row['date']] = $row['row_number'];
                $group = $groups->get($row['group_number']);
                if (! $group) {
                    $row['status'] = 'error';
                    $row['message'] = 'Kelompok tidak tersedia di aplikasi.';
                } elseif ($existing->has($row['date'])) {
                    $row['status'] = 'existing';
                    $row['message'] = 'Sudah ada jadwal pada tanggal ini; tidak ditimpa.';
                } elseif ($holidays->has($row['date'])) {
                    $row['status'] = 'error';
                    $row['message'] = 'Tanggal ditetapkan sebagai hari libur: '.$holidays->get($row['date'])->name.'.';
                } else {
                    $row['group_id'] = $group->id;
                    $row['roster'] = $group->roster();
                    if (CarbonImmutable::parse($row['date'])->isWeekend()) {
                        $row['message'] = 'Akhir pekan: tetap diimpor sesuai jadwal Excel.';
                    }
                }
            }
            $rows[] = $row;
        }

        return [
            'file_name' => $fileName,
            'sheet_name' => $sheetName,
            'year' => $year,
            'outside_year' => $outsideYear,
            'source_rows' => $sourceRows,
            'rows' => $rows,
        ];
    }

    private function cellValue(Cell $cell): mixed
    {
        return $cell->getDataType() === DataType::TYPE_FORMULA ? $cell->getOldCalculatedValue() : $cell->getValue();
    }

    private function parseDate(mixed $value, int $calendar): ?CarbonImmutable
    {
        if (is_numeric($value) && (float) $value >= 1 && (float) $value < 2958466) {
            return CarbonImmutable::instance(Date::excelToDateTimeObject((float) $value, 'Asia/Pontianak', $calendar));
        }
        if (! is_string($value)) {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'j/n/Y', 'j-n-Y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, trim($value), 'Asia/Pontianak');
                if ($date && $date->format($format) === trim($value)) {
                    return $date;
                }
            } catch (Throwable) {
            }
        }

        return null;
    }

    private function parseGroup(mixed $value): ?int
    {
        if (is_string($value)) {
            $value = preg_replace('/^kelompok\s+/i', '', trim($value));
        }

        return is_numeric($value) && in_array((float) $value, [1.0, 2.0, 3.0, 4.0], true) ? (int) $value : null;
    }
}
