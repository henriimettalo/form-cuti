<?php

namespace App\Services;

use App\Models\EmployeePayroll;
use App\Models\PayrollPeriod;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollExportService
{
    /** @var list<string> */
    public const HEADERS = [
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
        'jumlah_tanggungan',
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
    ];

    /** @var list<string> */
    public const TPP_HEADERS = [
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
    ];

    /** @var list<string> */
    private const TEXT_COLUMNS = [
        'A',
        'C',
        'D',
        'E',
        'G',
        'H',
        'I',
        'J',
        'L',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'AT',
    ];

    /** @var list<string> */
    private const TPP_TEXT_COLUMNS = ['A', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'L', 'M', 'N', 'O'];

    public function spreadsheet(PayrollPeriod $period): Spreadsheet
    {
        $records = $period->records()
            ->with([
                'employee' => fn ($query) => $query->withTrashed()->with('position'),
                'bankAccount',
            ])
            ->orderBy('employee_name')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');
        $sheet->fromArray([self::HEADERS], null, 'A1');
        $sheet->setShowGridLines(false);
        $sheet->freezePane('A2');

        $dataRow = 2;

        foreach ($records as $record) {
            foreach ($this->rowValues($record) as $index => $value) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $cell = $column.$dataRow;

                if (in_array($column, self::TEXT_COLUMNS, true)) {
                    $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $value);
                }
            }

            $dataRow++;
        }

        $lastDataRow = $dataRow - 1;

        if ($lastDataRow >= 2) {
            for ($index = 22; $index <= 45; $index++) {
                $column = Coordinate::stringFromColumnIndex($index);
                $sheet->setCellValue($column.$dataRow, "=SUM({$column}2:{$column}{$lastDataRow})");
            }

            $sheet->getStyle("A{$dataRow}:AT{$dataRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$dataRow}:AT{$dataRow}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFE0F2FE');
        }

        $lastVisibleRow = max(1, $lastDataRow);
        $sheet->setAutoFilter("A1:AT{$lastVisibleRow}");
        $sheet->getStyle('A1:AT1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0369A1'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle("A1:AT{$lastVisibleRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new Color('FFD1D5DB'));

        if ($lastDataRow >= 2) {
            foreach (['A', 'C', 'D', 'R', 'U'] as $column) {
                $sheet->getStyle("{$column}2:{$column}{$lastDataRow}")
                    ->getNumberFormat()
                    ->setFormatCode('@');
            }
        }

        $sheet->getDefaultColumnDimension()->setWidth(16);

        foreach ([
            'A' => 24,
            'B' => 32,
            'C' => 20,
            'D' => 20,
            'E' => 18,
            'G' => 30,
            'L' => 36,
            'T' => 22,
            'U' => 26,
            'AT' => 36,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $spreadsheet;
    }

    public function download(PayrollPeriod $period): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet($period);
        $filename = sprintf('payroll-%d-%02d.xlsx', $period->year, $period->month);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function tppSpreadsheet(PayrollPeriod $period): Spreadsheet
    {
        $records = $period->records()
            ->whereNotNull('tpp_source_row_checksum')
            ->with([
                'employee' => fn ($query) => $query->withTrashed()->with('position'),
            ])
            ->orderBy('employee_name')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');
        $sheet->fromArray([self::TPP_HEADERS], null, 'A1');
        $sheet->setShowGridLines(false);
        $sheet->freezePane('A2');

        $dataRow = 2;

        foreach ($records as $record) {
            foreach ($this->tppRowValues($record) as $index => $value) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $cell = $column.$dataRow;

                if (in_array($column, self::TPP_TEXT_COLUMNS, true)) {
                    $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $value);
                }
            }

            $dataRow++;
        }

        $lastDataRow = $dataRow - 1;

        if ($lastDataRow >= 2) {
            foreach ([32, 33, 34] as $index) {
                $column = Coordinate::stringFromColumnIndex($index);
                $sheet->setCellValue($column.$dataRow, "=SUM({$column}2:{$column}{$lastDataRow})");
            }

            $sheet->getStyle("A{$dataRow}:AH{$dataRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$dataRow}:AH{$dataRow}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFE0F2FE');
        }

        $lastVisibleRow = max(1, $lastDataRow);
        $sheet->setAutoFilter("A1:AH{$lastVisibleRow}");
        $sheet->getStyle('A1:AH1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0369A1'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle("A1:AH{$lastVisibleRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new Color('FFD1D5DB'));

        if ($lastDataRow >= 2) {
            foreach (['A', 'C', 'D', 'E', 'M', 'O'] as $column) {
                $sheet->getStyle("{$column}2:{$column}{$lastDataRow}")
                    ->getNumberFormat()
                    ->setFormatCode('@');
            }
        }

        $sheet->getDefaultColumnDimension()->setWidth(16);

        foreach ([
            'A' => 24,
            'B' => 32,
            'C' => 20,
            'D' => 20,
            'E' => 18,
            'G' => 30,
            'L' => 36,
            'N' => 22,
            'O' => 26,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $spreadsheet;
    }

    public function downloadTpp(PayrollPeriod $period): StreamedResponse
    {
        $spreadsheet = $this->tppSpreadsheet($period);
        $filename = sprintf('tpp-sipd-%d-%02d.xlsx', $period->year, $period->month);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @return list<mixed> */
    private function rowValues(EmployeePayroll $record): array
    {
        $employee = $record->employee;

        return [
            $record->employee_nip,
            $record->employee_name,
            $employee?->nik,
            $employee?->npwp,
            $employee?->birth_date?->format('d-m-Y'),
            $employee?->position_type,
            $employee?->position?->name ?? $employee?->position_title,
            $employee?->eselon ?? '00',
            $this->employmentStatusCode($employee?->employment_status),
            $employee?->grade,
            $this->gradeService($employee),
            $employee?->address,
            $employee?->marital_status,
            $employee?->spouse_count ?? 0,
            $employee?->child_count ?? 0,
            ($employee?->spouse_count ?? 0) + ($employee?->child_count ?? 0),
            $this->spouseStatus($employee?->spouse_is_pns),
            $employee?->spouse_nip,
            $record->bank_code,
            $record->bank_name,
            $record->account_number ?: '-',
            $record->basic_salary,
            $record->spouse_allowance,
            $record->child_allowance,
            $record->family_allowance,
            $record->position_allowance,
            $record->functional_allowance,
            $record->general_functional_allowance,
            $record->rice_allowance,
            $record->pph_allowance,
            $record->rounding,
            $record->health_contribution,
            $record->work_accident_contribution,
            $record->death_contribution,
            $record->tapera,
            $record->pension_contribution,
            $record->papua_special_allowance,
            $record->jht_allowance,
            $record->iwp_deduction,
            $record->pph21_deduction,
            $record->zakat,
            $record->bulog,
            $record->total_earnings,
            $record->total_deductions,
            $record->transferred_amount,
            $record->skpd_snapshot,
        ];
    }

    /** @return list<mixed> */
    private function tppRowValues(EmployeePayroll $record): array
    {
        $employee = $record->employee;

        return [
            $record->employee_nip,
            $record->employee_name,
            $employee?->nik,
            $employee?->npwp,
            $employee?->birth_date?->format('d-m-Y'),
            $employee?->position_type,
            $employee?->position?->name ?? $employee?->position_title,
            $employee?->eselon ?? '00',
            $this->employmentStatusCode($employee?->employment_status),
            $employee?->grade,
            $this->gradeService($employee),
            $employee?->address,
            $record->tpp_bank_code,
            $record->tpp_bank_name,
            $record->tpp_account_number ?: '-',
            $record->tpp_workload,
            $record->tpp_workplace,
            $record->tpp_work_conditions,
            $record->tpp_profession_scarcity,
            $record->tpp_performance,
            $record->tpp_pph_allowance,
            $record->tpp_health_contribution,
            $record->tpp_work_accident_contribution,
            $record->tpp_death_contribution,
            $record->tpp_tapera,
            $record->tpp_pension_contribution,
            $record->tpp_jht_allowance,
            $record->tpp_iwp_deduction,
            $record->tpp_pph21_deduction,
            $record->tpp_zakat,
            $record->tpp_bulog,
            $record->tpp_total,
            $record->tpp_total_deductions,
            $record->tpp_transferred_amount,
        ];
    }

    private function employmentStatusCode(?string $status): ?string
    {
        return match ($status) {
            'PNS' => '1',
            'PPPK' => '2',
            'Lainnya' => '3',
            default => null,
        };
    }

    private function spouseStatus(?bool $isPns): ?string
    {
        return match ($isPns) {
            true => 'YA',
            false => 'TIDAK',
            default => null,
        };
    }

    private function gradeService(?object $employee): int|string|null
    {
        if ($employee?->grade_service_years === null) {
            return null;
        }

        $years = $employee->grade_service_years;
        $months = $employee->grade_service_months ?? 0;

        return $months === 0 ? $years : "{$years} tahun {$months} bulan";
    }
}
