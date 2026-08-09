<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Bank;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeePayroll;
use App\Models\PayrollImport;
use App\Models\PayrollPeriod;
use App\Models\Position;
use App\Support\EmployeeNipMetadata;
use App\Support\EmployeeRankOptions;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class PayrollImportService
{
    public const MAX_ROWS = 1000;

    private bool $preserveExistingMaster = false;

    private const MAX_REPORTED_CHANGES = 100;

    private const MASTER_CHANGE_FIELDS = [
        'full_name' => 'Nama',
        'nik' => 'NIK',
        'npwp' => 'NPWP',
        'birth_date' => 'Tanggal lahir',
        'gender' => 'Jenis kelamin',
        'position_type' => 'Tipe jabatan',
        'position_title' => 'Jabatan',
        'eselon' => 'Eselon',
        'employment_status' => 'Status kepegawaian',
        'rank_name' => 'Pangkat',
        'grade' => 'Golongan',
        'marital_status' => 'Status pernikahan',
        'spouse_count' => 'Jumlah pasangan',
        'child_count' => 'Jumlah anak',
        'spouse_is_pns' => 'Pasangan PNS',
        'spouse_nip' => 'NIP pasangan',
        'service_started_on' => 'Mulai masa kerja',
        'grade_service_years' => 'Masa kerja golongan (tahun)',
        'grade_service_months' => 'Masa kerja golongan (bulan)',
        'address' => 'Alamat rumah',
        'bank_code' => 'Kode bank',
        'bank_name' => 'Nama bank',
        'account_number' => 'Nomor rekening',
    ];

    private const SENSITIVE_MASTER_FIELDS = ['nik', 'npwp', 'spouse_nip', 'account_number'];

    /** @var array{total: int, items: list<array<string, mixed>>} */
    private array $lastMasterChanges = [
        'total' => 0,
        'items' => [],
    ];

    /** @var array<string, list<string>> */
    private const HEADER_ALIASES = [
        'nip' => ['nip_pegawai', 'nip'],
        'employee_name' => ['nama_pegawai', 'nama_lengkap', 'nama', 'full_name'],
        'nik' => ['nik_pegawai', 'nik'],
        'npwp' => ['npwp_pegawai', 'npwp'],
        'birth_date' => ['tanggal_lahir_pegawai', 'tanggal_lahir', 'birth_date'],
        'position_type' => ['tipe_jabatan', 'position_type'],
        'position_title' => ['nama_jabatan', 'jabatan', 'position_title'],
        'eselon' => ['eselon'],
        'employment_status' => ['status_asn', 'status_kepegawaian', 'employment_status'],
        'grade' => ['golongan', 'pangkat_golongan', 'grade'],
        'grade_service_years' => ['masa_kerja_golongan', 'grade_service_years'],
        'address' => ['alamat', 'address'],
        'marital_status' => ['status_pernikahan', 'marital_status'],
        'spouse_count' => ['jumlah_istri_suami', 'jumlah_pasangan', 'spouse_count'],
        'child_count' => ['jumlah_anak', 'child_count'],
        'spouse_is_pns' => ['pasangan_pns', 'spouse_is_pns'],
        'spouse_nip' => ['nip_pasangan', 'spouse_nip'],
        'bank_code' => ['kode_bank', 'bank_code'],
        'bank_name' => ['nama_bank', 'bank_name'],
        'account_number' => ['nomor_rekening_bank_pegawai', 'nomor_rekening', 'account_number'],
        'basic_salary' => ['gaji_pokok', 'basic_salary'],
        'spouse_allowance' => ['perhitungan_suami_istri', 'tunjangan_pasangan', 'spouse_allowance'],
        'child_allowance' => ['perhitungan_anak', 'tunjangan_anak', 'child_allowance'],
        'family_allowance' => ['tunjangan_keluarga', 'family_allowance'],
        'position_allowance' => ['tunjangan_jabatan', 'position_allowance'],
        'functional_allowance' => ['tunjangan_fungsional', 'functional_allowance'],
        'general_functional_allowance' => ['tunjangan_fungsional_umum', 'general_functional_allowance'],
        'rice_allowance' => ['tunjangan_beras', 'rice_allowance'],
        'pph_allowance' => ['tunjangan_pph', 'pph_allowance'],
        'rounding' => ['pembulatan_gaji', 'pembulatan', 'rounding'],
        'health_contribution' => ['iuran_jaminan_kesehatan', 'iuran_kesehatan', 'health_contribution'],
        'work_accident_contribution' => ['iuran_jaminan_kecelakaan_kerja', 'iuran_kecelakaan_kerja', 'work_accident_contribution'],
        'death_contribution' => ['iuran_jaminan_kematian', 'iuran_kematian', 'death_contribution'],
        'tapera' => ['iuran_simpanan_tapera', 'tapera'],
        'pension_contribution' => ['iuran_pensiun', 'pension_contribution'],
        'papua_special_allowance' => ['tunjangan_khusus_papua', 'papua_special_allowance'],
        'jht_allowance' => ['tunjangan_jaminan_hari_tua', 'tunjangan_jht', 'jht_allowance'],
        'iwp_deduction' => ['potongan_iwp', 'iwp_deduction'],
        'pph21_deduction' => ['potongan_pph21', 'pph21_deduction'],
        'zakat' => ['zakat'],
        'bulog' => ['bulog'],
        'source_total_earnings' => ['jumlah_gaji_dan_tunjangan', 'total_gaji_dan_tunjangan', 'source_total_earnings'],
        'source_total_deductions' => ['jumlah_potongan', 'total_potongan', 'source_total_deductions'],
        'source_transferred_amount' => ['jumlah_ditransfer', 'jumlah_diterima', 'source_transferred_amount'],
        'skpd' => ['skpd', 'unit_kerja', 'department_name'],
        'tpp_workload' => ['tpp_beban_kerja', 'tpp_workload'],
        'tpp_workplace' => ['tpp_tempat_bertugas', 'tpp_workplace'],
        'tpp_work_conditions' => ['tpp_kondisi_kerja', 'tpp_work_conditions'],
        'tpp_profession_scarcity' => ['tpp_kelangkaan_profesi', 'tpp_profession_scarcity'],
        'tpp_performance' => ['tpp_prestasi_kerja', 'tpp_performance'],
        'tpp_pph_allowance' => ['tunjangan_pph', 'tpp_tunjangan_pph', 'tpp_pph_allowance'],
        'tpp_health_contribution' => ['iuran_jaminan_kesehatan', 'tpp_iuran_jaminan_kesehatan', 'tpp_health_contribution'],
        'tpp_work_accident_contribution' => ['iuran_jaminan_kecelakaan_kerja', 'tpp_iuran_jaminan_kecelakaan_kerja', 'tpp_work_accident_contribution'],
        'tpp_death_contribution' => ['iuran_jaminan_kematian', 'tpp_iuran_jaminan_kematian', 'tpp_death_contribution'],
        'tpp_tapera' => ['iuran_simpanan_tapera', 'tpp_iuran_simpanan_tapera', 'tpp_tapera'],
        'tpp_pension_contribution' => ['iuran_pensiun', 'tpp_iuran_pensiun', 'tpp_pension_contribution'],
        'tpp_jht_allowance' => ['tunjangan_jaminan_hari_tua', 'tpp_tunjangan_jaminan_hari_tua', 'tpp_jht_allowance'],
        'tpp_iwp_deduction' => ['potongan_iwp', 'tpp_potongan_iwp', 'tpp_iwp_deduction'],
        'tpp_pph21_deduction' => ['potongan_pph_21', 'potongan_pph21', 'tpp_potongan_pph_21', 'tpp_potongan_pph21', 'tpp_pph21_deduction'],
        'tpp_zakat' => ['zakat', 'tpp_zakat'],
        'tpp_bulog' => ['bulog', 'tpp_bulog'],
        'tpp_total' => ['jumlah_tpp', 'total_tpp', 'tpp_total'],
        'tpp_total_deductions' => ['jumlah_potongan', 'total_potongan', 'tpp_total_deductions'],
        'tpp_transferred_amount' => ['jumlah_ditransfer', 'jumlah_diterima', 'tpp_transferred_amount'],
    ];

    /** @var list<string> */
    private const REQUIRED_COLUMNS = [
        'nip',
        'employee_name',
        'employment_status',
        'grade',
        'basic_salary',
        'source_total_earnings',
        'source_total_deductions',
        'source_transferred_amount',
    ];

    /** @var list<string> */
    private const TPP_REQUIRED_COLUMNS = [
        'nip',
        'employee_name',
        'employment_status',
        'grade',
        'tpp_workload',
        'tpp_total',
        'tpp_total_deductions',
        'tpp_transferred_amount',
    ];

    /** @return array{total: int, items: list<array<string, mixed>>} */
    public function lastMasterChanges(): array
    {
        return $this->lastMasterChanges;
    }

    public function import(
        UploadedFile $file,
        int $year,
        int $month,
        ?int $importedBy = null,
        string $sourceType = PayrollImport::SOURCE_PRIMARY,
    ): PayrollPeriod {
        $payrollImport = $this->preview($file, $year, $month, $importedBy, $sourceType);

        return $this->confirm($payrollImport, $importedBy);
    }

    public function preview(
        UploadedFile $file,
        int $year,
        int $month,
        ?int $createdBy = null,
        string $sourceType = PayrollImport::SOURCE_PRIMARY,
    ): PayrollImport {
        $this->lastMasterChanges = [
            'total' => 0,
            'items' => [],
        ];
        $this->preserveExistingMaster = $sourceType === PayrollImport::SOURCE_TPP;

        $this->assertPeriodAvailable($year, $month, $sourceType);
        $path = $file->getRealPath();
        $checksum = $path === false ? null : (hash_file('sha256', $path) ?: null);
        [$rows, $normalizedRows, $errors] = $this->prepareRows($file, $sourceType);

        $registeredEmployees = $this->registeredEmployees();
        $seenNips = [];

        foreach ($normalizedRows as $row) {
            if (! $registeredEmployees->has($row['nip_lookup'])) {
                $errors[] = "Baris {$row['source_row_number']}: NIP {$row['employee_nip']} belum terdaftar di menu Pegawai. Tambahkan pegawai terlebih dahulu.";
            }

            if (isset($seenNips[$row['nip_lookup']])) {
                $errors[] = "Baris {$row['source_row_number']}: NIP {$row['employee_nip']} duplikat dengan baris {$seenNips[$row['nip_lookup']]} pada file.";
            } else {
                $seenNips[$row['nip_lookup']] = $row['source_row_number'];
            }
        }

        if ($sourceType === PayrollImport::SOURCE_TPP) {
            $period = PayrollPeriod::query()
                ->where('year', $year)
                ->where('month', $month)
                ->first();
            $primaryNips = $period?->records()
                ->pluck('employee_nip')
                ->mapWithKeys(fn (string $nip): array => [EmployeeNipMetadata::digits($nip) => true])
                ->all() ?? [];

            foreach ($normalizedRows as $row) {
                if (! isset($primaryNips[$row['nip_lookup']])) {
                    $errors[] = "Baris {$row['source_row_number']}: NIP {$row['employee_nip']} belum ada pada payroll gaji utama periode {$month}/{$year}. Impor gaji utama terlebih dahulu.";
                }
            }
        }

        $this->throwIfErrors($errors);

        $masterChanges = $this->masterChangesForRows($normalizedRows, $registeredEmployees);

        return PayrollImport::query()->create([
            'token' => (string) Str::uuid(),
            'year' => $year,
            'month' => $month,
            'source_type' => $sourceType,
            'original_filename' => $file->getClientOriginalName(),
            'source_checksum' => $checksum,
            'total_rows' => count($rows),
            'status' => PayrollImport::STATUS_PREVIEWED,
            'payload' => $normalizedRows,
            'master_changes' => $this->changeReport($masterChanges, false),
            'created_by' => $createdBy,
        ]);
    }

    public function confirm(
        PayrollImport $payrollImport,
        ?int $importedBy = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): PayrollPeriod {
        $this->lastMasterChanges = [
            'total' => 0,
            'items' => [],
        ];

        [$period, $masterChanges] = DB::transaction(function () use ($payrollImport, $importedBy, $ipAddress, $userAgent): array {
            $lockedImport = PayrollImport::query()
                ->lockForUpdate()
                ->findOrFail($payrollImport->getKey());

            $this->preserveExistingMaster = $lockedImport->source_type === PayrollImport::SOURCE_TPP;

            if ($lockedImport->status === PayrollImport::STATUS_COMPLETED) {
                $period = $lockedImport->payrollPeriod;

                if ($period === null) {
                    throw new LogicException('Pratinjau payroll sudah selesai tetapi periode hasilnya tidak ditemukan.');
                }

                return [$period, $lockedImport->master_changes['items'] ?? []];
            }

            if ($lockedImport->status !== PayrollImport::STATUS_PREVIEWED) {
                throw new LogicException('Pratinjau payroll ini tidak dapat diproses lagi. Unggah file baru untuk melanjutkan.');
            }

            $sourceType = $lockedImport->source_type ?: PayrollImport::SOURCE_PRIMARY;

            $period = PayrollPeriod::query()
                ->where('year', $lockedImport->year)
                ->where('month', $lockedImport->month)
                ->lockForUpdate()
                ->first();

            if ($period?->status === PayrollPeriod::STATUS_LOCKED) {
                throw new InvalidArgumentException('Periode payroll tersebut sudah terkunci dan tidak dapat diimpor ulang.');
            }

            $hasPrimaryRecords = $period?->records()->exists() ?? false;
            $hasTppRecords = $period?->records()->whereNotNull('tpp_source_row_checksum')->exists() ?? false;

            if ($sourceType === PayrollImport::SOURCE_PRIMARY) {
                if ($period !== null && $period->status !== PayrollPeriod::STATUS_REJECTED && ($hasPrimaryRecords || $hasTppRecords)) {
                    throw new InvalidArgumentException("Periode payroll {$period->label()} sudah memiliki data. Impor ulang hanya diperbolehkan setelah periode tersebut ditolak.");
                }
            } else {
                if (! $hasPrimaryRecords) {
                    throw new InvalidArgumentException('File gaji utama harus diimpor terlebih dahulu sebelum pelengkap TPP SIPD.');
                }

                if ($period?->status !== PayrollPeriod::STATUS_REJECTED && $hasTppRecords) {
                    throw new InvalidArgumentException("Pelengkap TPP periode {$period->label()} sudah memiliki data. Impor ulang hanya diperbolehkan setelah periode tersebut ditolak.");
                }
            }

            $normalizedRows = $lockedImport->payload ?? [];
            $employees = $this->registeredEmployees();
            $masterChanges = $this->masterChangesForRows($normalizedRows, $employees);

            if ($this->changeSignature($masterChanges) !== $this->changeSignature($lockedImport->master_changes['items'] ?? [])) {
                throw new LogicException('Data master berubah setelah pratinjau dibuat. Unggah ulang file untuk mendapatkan perbandingan terbaru.');
            }

            if ($sourceType === PayrollImport::SOURCE_PRIMARY) {
                $period ??= PayrollPeriod::query()->create([
                    'year' => $lockedImport->year,
                    'month' => $lockedImport->month,
                ]);

                if ($period->status === PayrollPeriod::STATUS_REJECTED) {
                    $period->records()->delete();
                }
            } elseif ($period === null) {
                throw new LogicException('Periode payroll utama untuk pelengkap TPP tidak ditemukan.');
            } elseif ($period->status === PayrollPeriod::STATUS_REJECTED) {
                $this->clearTppRecords($period);
            }

            foreach ($normalizedRows as $row) {
                $employee = $employees->get($row['nip_lookup']);

                if ($employee === null) {
                    throw new InvalidArgumentException("Baris {$row['source_row_number']}: NIP {$row['employee_nip']} belum terdaftar di menu Pegawai. Tambahkan pegawai terlebih dahulu.");
                }

                $changes = $this->masterFieldChanges($employee, $row);

                if ($changes !== []) {
                    $this->recordMasterChangeAudit(
                        $employee,
                        $changes,
                        $lockedImport,
                        $period,
                        (int) $row['source_row_number'],
                        $importedBy,
                        $ipAddress,
                        $userAgent,
                    );
                }

                $this->updateExistingEmployee($employee, $row);

                $bankAccount = $this->upsertBankAccount($employee, $row);

                if ($sourceType === PayrollImport::SOURCE_PRIMARY) {
                    $totals = EmployeePayroll::calculatedTotals($row);
                    $sourceTotals = [
                        'source_total_earnings' => $row['source_total_earnings'],
                        'source_total_deductions' => $row['source_total_deductions'],
                        'source_transferred_amount' => $row['source_transferred_amount'],
                    ];
                    $reconciliationStatus = $totals['total_earnings'] === $sourceTotals['source_total_earnings']
                        && $totals['total_deductions'] === $sourceTotals['source_total_deductions']
                        && $totals['transferred_amount'] === $sourceTotals['source_transferred_amount']
                        ? EmployeePayroll::RECONCILIATION_MATCHED
                        : EmployeePayroll::RECONCILIATION_MISMATCH;

                    $period->records()->create(array_merge([
                        'employee_id' => $employee->id,
                        'employee_nip' => $row['employee_nip'],
                        'employee_name' => $row['employee_name'],
                        'skpd_snapshot' => $row['skpd'],
                        'employee_bank_account_id' => $bankAccount?->id,
                        'bank_code' => $row['bank_code'],
                        'bank_name' => $row['bank_name'],
                        'account_number' => $row['account_number'],
                    ], $this->componentAttributes($row), $totals, $sourceTotals, [
                        'reconciliation_status' => $reconciliationStatus,
                        'source_row_number' => $row['source_row_number'],
                        'source_row_checksum' => $row['source_row_checksum'],
                    ]));

                    continue;
                }

                $record = $period->records()
                    ->where('employee_nip', $row['employee_nip'])
                    ->first();

                if ($record === null) {
                    throw new InvalidArgumentException("Baris {$row['source_row_number']}: NIP {$row['employee_nip']} belum ada pada payroll gaji utama periode {$period->label()}.");
                }

                $tppTotals = EmployeePayroll::calculatedTppTotals($row);
                $tppSourceTotals = [
                    'tpp_source_total' => $row['tpp_source_total'],
                    'tpp_source_total_deductions' => $row['tpp_source_total_deductions'],
                    'tpp_source_transferred_amount' => $row['tpp_source_transferred_amount'],
                ];
                $tppReconciliationStatus = $tppTotals['tpp_total'] === $tppSourceTotals['tpp_source_total']
                    && $tppTotals['tpp_total_deductions'] === $tppSourceTotals['tpp_source_total_deductions']
                    && $tppTotals['tpp_transferred_amount'] === $tppSourceTotals['tpp_source_transferred_amount']
                    ? EmployeePayroll::TPP_RECONCILIATION_MATCHED
                    : EmployeePayroll::TPP_RECONCILIATION_MISMATCH;

                $record->fill(array_merge(
                    $this->tppComponentAttributes($row),
                    $tppTotals,
                    $tppSourceTotals,
                    [
                        'tpp_bank_code' => $row['bank_code'],
                        'tpp_bank_name' => $row['bank_name'],
                        'tpp_account_number' => $row['account_number'],
                        'tpp_reconciliation_status' => $tppReconciliationStatus,
                        'tpp_source_row_number' => $row['source_row_number'],
                        'tpp_source_row_checksum' => $row['source_row_checksum'],
                    ],
                ));
                $record->save();
            }

            $period->update($sourceType === PayrollImport::SOURCE_PRIMARY
                ? [
                    'status' => PayrollPeriod::STATUS_DRAFT,
                    'source_file' => $lockedImport->original_filename,
                    'source_checksum' => $lockedImport->source_checksum,
                    'imported_at' => now(),
                    'locked_at' => null,
                    'imported_by' => $importedBy,
                    'total_rows' => $lockedImport->total_rows,
                    'imported_rows' => count($normalizedRows),
                    'tpp_source_file' => null,
                    'tpp_source_checksum' => null,
                    'tpp_imported_at' => null,
                    'tpp_imported_by' => null,
                    'tpp_total_rows' => 0,
                    'tpp_imported_rows' => 0,
                ]
                : [
                    'status' => PayrollPeriod::STATUS_DRAFT,
                    'tpp_source_file' => $lockedImport->original_filename,
                    'tpp_source_checksum' => $lockedImport->source_checksum,
                    'tpp_imported_at' => now(),
                    'tpp_imported_by' => $importedBy,
                    'tpp_total_rows' => $lockedImport->total_rows,
                    'tpp_imported_rows' => count($normalizedRows),
                ]);

            $lockedImport->update([
                'status' => PayrollImport::STATUS_COMPLETED,
                'imported_rows' => count($normalizedRows),
                'payroll_period_id' => $period->id,
                'processed_at' => now(),
            ]);

            return [$period->fresh(), $masterChanges];
        });

        $this->lastMasterChanges = $this->changeReport($masterChanges);

        return $period;
    }

    private function assertPeriodAvailable(int $year, int $month, string $sourceType): void
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Bulan payroll harus berada di antara Januari dan Desember.');
        }

        if (! in_array($sourceType, [PayrollImport::SOURCE_PRIMARY, PayrollImport::SOURCE_TPP], true)) {
            throw new InvalidArgumentException('Jenis file payroll tidak dikenali. Pilih payroll gaji utama atau pelengkap TPP SIPD.');
        }

        $period = PayrollPeriod::query()
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($period?->status === PayrollPeriod::STATUS_LOCKED) {
            throw new InvalidArgumentException('Periode payroll tersebut sudah terkunci dan tidak dapat diimpor ulang.');
        }

        $hasPrimaryRecords = $period?->records()->exists() ?? false;
        $hasTppRecords = $period?->records()->whereNotNull('tpp_source_row_checksum')->exists() ?? false;

        if ($sourceType === PayrollImport::SOURCE_PRIMARY) {
            if ($period !== null && $period->status !== PayrollPeriod::STATUS_REJECTED && ($hasPrimaryRecords || $hasTppRecords)) {
                throw new InvalidArgumentException("Periode payroll {$period->label()} sudah memiliki data. Impor ulang hanya diperbolehkan setelah periode tersebut ditolak.");
            }
        } else {
            if (! $hasPrimaryRecords) {
                throw new InvalidArgumentException('File gaji utama harus diimpor terlebih dahulu sebelum pelengkap TPP SIPD.');
            }

            if ($period?->status !== PayrollPeriod::STATUS_REJECTED && $hasTppRecords) {
                throw new InvalidArgumentException("Pelengkap TPP periode {$period->label()} sudah memiliki data. Impor ulang hanya diperbolehkan setelah periode tersebut ditolak.");
            }
        }

        if (PayrollImport::query()
            ->where('year', $year)
            ->where('month', $month)
            ->where('source_type', $sourceType)
            ->where('status', PayrollImport::STATUS_PREVIEWED)
            ->exists()) {
            throw new InvalidArgumentException("Periode payroll {$month}/{$year} sudah memiliki pratinjau {$sourceType} yang menunggu konfirmasi. Selesaikan atau batalkan pratinjau tersebut terlebih dahulu.");
        }
    }

    /**
     * @return array{0: list<array<int, mixed>>, 1: list<array<string, mixed>>, 2: list<string>}
     */
    private function prepareRows(UploadedFile $file, string $sourceType): array
    {
        $rows = $this->readRows($file);

        if ($rows === []) {
            throw new InvalidArgumentException('File payroll tidak memiliki data yang dapat dibaca.');
        }

        $headers = array_shift($rows);
        $headerMap = $this->headerMap((array) $headers);
        $requiredColumns = $sourceType === PayrollImport::SOURCE_TPP
            ? self::TPP_REQUIRED_COLUMNS
            : self::REQUIRED_COLUMNS;
        $missingColumns = array_values(array_filter(
            $requiredColumns,
            static fn (string $column): bool => $headerMap[$column] === null,
        ));

        if ($missingColumns !== []) {
            $template = $sourceType === PayrollImport::SOURCE_TPP ? 'TPP SIPD' : 'payroll gaji utama';
            throw new InvalidArgumentException('Kolom wajib untuk template '.$template.' tidak ditemukan: '.implode(', ', $missingColumns).'. Periksa jenis file yang dipilih.');
        }

        $rows = array_values(array_filter($rows, fn (array $row): bool => ! $this->isBlankRow($row)));

        if ($rows === []) {
            throw new InvalidArgumentException('File payroll belum memiliki baris pegawai.');
        }

        if (count($rows) > self::MAX_ROWS) {
            throw new InvalidArgumentException('Maksimal '.number_format(self::MAX_ROWS, 0, ',', '.').' pegawai per file payroll.');
        }

        $normalizedRows = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            [$data, $messages] = $this->normalizeRow($row, $headerMap);

            if ($messages !== []) {
                $errors[] = 'Baris '.$rowNumber.': '.implode(' ', $messages);

                continue;
            }

            $data['source_row_number'] = $rowNumber;
            $data['source_row_checksum'] = hash('sha256', json_encode($row, JSON_UNESCAPED_UNICODE));
            $normalizedRows[] = $data;
        }

        return [$rows, $normalizedRows, $errors];
    }

    private function throwIfErrors(array $errors): void
    {
        if ($errors === []) {
            return;
        }

        $reportedErrors = array_slice($errors, 0, 30);
        $remainingErrors = count($errors) - count($reportedErrors);

        if ($remainingErrors > 0) {
            $reportedErrors[] = "... dan {$remainingErrors} masalah lainnya.";
        }

        throw new InvalidArgumentException("Data payroll tidak lolos validasi:\n- ".implode("\n- ", $reportedErrors));
    }

    /** @return Collection<string, Employee> */
    private function registeredEmployees()
    {
        return Employee::query()->with('bankAccounts')->get()->keyBy(
            fn (Employee $employee): string => EmployeeNipMetadata::digits($employee->nip),
        );
    }

    /** @return list<array<string, mixed>> */
    private function masterChangesForRows(array $rows, $employees): array
    {
        $changes = [];

        foreach ($rows as $row) {
            $employee = $employees->get($row['nip_lookup']);

            if ($employee === null) {
                continue;
            }

            $fieldChanges = $this->masterFieldChanges($employee, $row);

            if ($fieldChanges !== []) {
                $changes[] = [
                    'type' => 'updated',
                    'nip' => $row['employee_nip'],
                    'name' => $row['employee_name'],
                    'changes' => $fieldChanges,
                ];
            }
        }

        return $changes;
    }

    /** @param list<array<string, mixed>> $items */
    private function changeReport(array $items, bool $limit = true): array
    {
        return [
            'total' => count($items),
            'items' => $limit ? array_slice($items, 0, self::MAX_REPORTED_CHANGES) : $items,
        ];
    }

    /** @param list<array<string, mixed>> $items */
    private function changeSignature(array $items): string
    {
        return hash('sha256', (string) json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @param list<array<string, mixed>> $changes */
    private function recordMasterChangeAudit(
        Employee $employee,
        array $changes,
        PayrollImport $payrollImport,
        PayrollPeriod $period,
        int $sourceRowNumber,
        ?int $importedBy,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        $oldValues = [];
        $newValues = [];

        foreach ($changes as $change) {
            $oldValues[$change['attribute']] = $change['old_value'];
            $newValues[$change['attribute']] = $change['new_value'];
        }

        AuditLog::query()->create([
            'user_id' => $importedBy ?? $payrollImport->created_by,
            'event' => 'employee.master_updated_from_payroll_import',
            'auditable_type' => Employee::class,
            'auditable_id' => $employee->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'source' => 'payroll_import',
                'payroll_import_id' => $payrollImport->id,
                'payroll_period_id' => $period->id,
                'payroll_period' => $period->label(),
                'source_file' => $payrollImport->original_filename,
                'source_row_number' => $sourceRowNumber,
                'uploaded_at' => $payrollImport->created_at?->toIso8601String(),
                'confirmed_at' => now()->toIso8601String(),
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /** @return array{0: array<string, mixed>, 1: list<string>} */
    private function normalizeRow(array $row, array $headerMap): array
    {
        $sourceNip = $this->cellValue($row, $headerMap['nip']);
        $employeeNip = EmployeeNipMetadata::digits($sourceNip);
        $employmentStatus = $this->employmentStatus($this->cellValue($row, $headerMap['employment_status']));
        $gradeValue = $this->cellValue($row, $headerMap['grade']);
        $rank = EmployeeRankOptions::find($employmentStatus, $gradeValue);
        $derived = EmployeeNipMetadata::derive($sourceNip);
        $employeeName = $this->cellValue($row, $headerMap['employee_name']);
        $birthDate = $this->normaliseDate($this->cellValue($row, $headerMap['birth_date'])) ?? $derived['birth_date'];
        $data = [
            'employee_nip' => $employeeNip,
            'nip_lookup' => $employeeNip,
            'employee_name' => $employeeName,
            'nik' => $this->digitsOrNull($this->cellValue($row, $headerMap['nik'])),
            'npwp' => $this->digitsOrNull($this->cellValue($row, $headerMap['npwp'])),
            'birth_date' => $birthDate,
            'gender' => $derived['gender'],
            'position_type' => $this->integerValue($this->cellValue($row, $headerMap['position_type'])),
            'position_title' => $this->cellValue($row, $headerMap['position_title']),
            'eselon' => $this->cellValue($row, $headerMap['eselon']),
            'employment_status' => $employmentStatus,
            'rank_name' => $rank['name'] ?? null,
            'grade' => $rank['grade'] ?? $gradeValue,
            'nip_tmt_valid' => $derived['tmt_valid'],
            'address' => $this->cellValue($row, $headerMap['address']),
            'marital_status' => $this->integerValue($this->cellValue($row, $headerMap['marital_status'])),
            'spouse_count' => $this->integerValue($this->cellValue($row, $headerMap['spouse_count'])),
            'child_count' => $this->integerValue($this->cellValue($row, $headerMap['child_count'])),
            'spouse_is_pns' => $this->booleanValue($this->cellValue($row, $headerMap['spouse_is_pns'])),
            'spouse_nip' => EmployeeNipMetadata::digits($this->cellValue($row, $headerMap['spouse_nip'])) ?: null,
            'service_started_on' => $derived['service_started_on'],
            'bank_code' => $this->cellValue($row, $headerMap['bank_code']),
            'bank_name' => $this->cellValue($row, $headerMap['bank_name']),
            'account_number' => $this->cellValue($row, $headerMap['account_number']),
            'skpd' => $this->cellValue($row, $headerMap['skpd']),
        ];
        [$data['grade_service_years'], $data['grade_service_months']] = $this->gradeServiceDuration(
            $this->cellValue($row, $headerMap['grade_service_years']),
        );

        $providedMasterFields = ['full_name'];

        foreach ([
            'nik',
            'npwp',
            'birth_date',
            'gender',
            'position_type',
            'position_title',
            'eselon',
            'employment_status',
            'rank_name',
            'grade',
            'marital_status',
            'spouse_count',
            'child_count',
            'spouse_is_pns',
            'spouse_nip',
            'service_started_on',
            'grade_service_years',
            'grade_service_months',
            'address',
            'bank_code',
            'bank_name',
            'account_number',
        ] as $field) {
            if ($data[$field] !== null) {
                $providedMasterFields[] = $field;
            }
        }

        $data['_provided_master_fields'] = array_values(array_unique($providedMasterFields));

        foreach (EmployeePayroll::COMPONENTS as $component) {
            $data[$component] = $this->moneyValue($this->cellValue($row, $headerMap[$component]));
        }

        $data['source_total_earnings'] = $this->moneyValue($this->cellValue($row, $headerMap['source_total_earnings']));
        $data['source_total_deductions'] = $this->moneyValue($this->cellValue($row, $headerMap['source_total_deductions']));
        $data['source_transferred_amount'] = $this->moneyValue($this->cellValue($row, $headerMap['source_transferred_amount']));

        foreach (EmployeePayroll::TPP_COMPONENTS as $component) {
            $data[$component] = $this->moneyValue($this->cellValue($row, $headerMap[$component]));
        }

        $data['tpp_source_total'] = $this->moneyValue($this->cellValue($row, $headerMap['tpp_total']));
        $data['tpp_source_total_deductions'] = $this->moneyValue($this->cellValue($row, $headerMap['tpp_total_deductions']));
        $data['tpp_source_transferred_amount'] = $this->moneyValue($this->cellValue($row, $headerMap['tpp_transferred_amount']));

        $messages = [];

        if ($employeeNip === '') {
            $messages[] = 'NIP wajib diisi.';
        } elseif (strlen($employeeNip) !== 18) {
            $messages[] = 'NIP harus terdiri dari 18 digit.';
        }

        if ($data['employee_name'] === null) {
            $messages[] = 'Nama pegawai wajib diisi.';
        }

        if ($employmentStatus === null) {
            $messages[] = 'Status ASN tidak dikenali.';
        }

        if ($employmentStatus === 'PNS' && $rank === null) {
            $messages[] = 'Golongan PNS tidak dikenali: '.($gradeValue ?: '(kosong)').'.';
        }

        if ($data['position_type'] !== null && ! in_array($data['position_type'], [1, 3], true)) {
            $messages[] = 'Tipe jabatan hanya boleh 1 atau 3.';
        }

        if ($data['marital_status'] !== null && ! in_array($data['marital_status'], [1, 2], true)) {
            $messages[] = 'Status pernikahan hanya boleh 1 atau 2.';
        }

        return [$data, $messages];
    }

    private function updateExistingEmployee(Employee $employee, array $row): void
    {
        $position = ! $this->masterFieldIsProvided($row, 'position_title')
            ? $employee->position
            : Position::query()->firstOrCreate(['name' => trim($row['position_title'])], ['is_active' => true]);

        $employee->fill([
            'position_id' => $position?->id,
            'position_title' => $this->incomingMasterValue($employee, $row, 'position_title'),
            'nip' => $employee->nip,
            'nik' => $this->incomingMasterValue($employee, $row, 'nik'),
            'npwp' => $this->incomingMasterValue($employee, $row, 'npwp'),
            'full_name' => $row['employee_name'],
            'birth_date' => $this->incomingMasterValue($employee, $row, 'birth_date'),
            'position_type' => $this->incomingMasterValue($employee, $row, 'position_type'),
            'eselon' => $this->incomingMasterValue($employee, $row, 'eselon'),
            'rank_name' => $this->incomingMasterValue($employee, $row, 'rank_name'),
            'grade' => $this->incomingMasterValue($employee, $row, 'grade'),
            'employment_status' => $this->incomingMasterValue($employee, $row, 'employment_status'),
            'marital_status' => $this->incomingMasterValue($employee, $row, 'marital_status'),
            'spouse_count' => $this->incomingMasterValue($employee, $row, 'spouse_count'),
            'child_count' => $this->incomingMasterValue($employee, $row, 'child_count'),
            'spouse_is_pns' => $this->incomingMasterValue($employee, $row, 'spouse_is_pns'),
            'spouse_nip' => $this->incomingMasterValue($employee, $row, 'spouse_nip'),
            'gender' => $this->incomingMasterValue($employee, $row, 'gender'),
            'nip_tmt_valid' => $row['nip_tmt_valid'] ?? $employee->nip_tmt_valid,
            'service_started_on' => $this->incomingMasterValue($employee, $row, 'service_started_on'),
            'grade_service_years' => $this->incomingMasterValue($employee, $row, 'grade_service_years'),
            'grade_service_months' => $this->incomingMasterValue($employee, $row, 'grade_service_months'),
            'address' => $this->incomingMasterValue($employee, $row, 'address'),
        ]);
        $employee->save();
    }

    /** @return list<array<string, mixed>> */
    private function masterFieldChanges(Employee $employee, array $row): array
    {
        $changes = [];

        foreach (self::MASTER_CHANGE_FIELDS as $field => $label) {
            if (! $this->masterFieldIsProvided($row, $field)) {
                continue;
            }

            $oldValue = $this->currentMasterValue($employee, $field);
            $newValue = $this->incomingMasterValue($employee, $row, $field);

            if ($this->comparableValue($oldValue) === $this->comparableValue($newValue)) {
                continue;
            }

            $changes[] = [
                'attribute' => $field,
                'field' => $label,
                'old' => $this->reportValue($oldValue, $field),
                'new' => $this->reportValue($newValue, $field),
                'old_value' => $this->auditValue($oldValue),
                'new_value' => $this->auditValue($newValue),
            ];
        }

        return $changes;
    }

    private function incomingMasterValue(Employee $employee, array $row, string $field): mixed
    {
        if (! $this->masterFieldIsProvided($row, $field)) {
            return $this->currentMasterValue($employee, $field);
        }

        // Source tambahan (TPP) tidak menimpa field master yang sudah terisi dari source utama.
        if ($this->preserveExistingMaster) {
            $current = $this->currentMasterValue($employee, $field);

            if ($this->comparableValue($current) !== $this->comparableValue(null)
                && (string) $current !== '') {
                return $current;
            }
        }

        $incoming = match ($field) {
            'full_name' => $row['employee_name'],
            default => $row[$field] ?? $employee->getAttribute($field),
        };

        // Kolom nama_jabatan pada file payroll hanya memuat tipe jabatan generik
        // ("FUNGSIONAL UMUM"/"STRUKTURAL"), bukan nama jabatan spesifik. Jangan
        // menimpa position_title manual dengan nilai generik tersebut.
        if ($field === 'position_title' && $this->isGenericPositionType($incoming)) {
            return $this->currentMasterValue($employee, $field);
        }

        return $incoming;
    }

    private function isGenericPositionType(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $normalized = preg_replace('/\s+/', ' ', strtoupper(trim($value)));

        return in_array($normalized, ['FUNGSIONAL UMUM', 'STRUKTURAL', 'FUNGSIONAL'], true);
    }

    private function currentMasterValue(Employee $employee, string $field): mixed
    {
        if (! in_array($field, ['bank_code', 'bank_name', 'account_number'], true)) {
            return $employee->getAttribute($field);
        }

        $account = $employee->relationLoaded('bankAccounts')
            ? $employee->bankAccounts->firstWhere('is_primary', true)
            : $employee->bankAccounts()->where('is_primary', true)->first();

        return match ($field) {
            'bank_code' => $account?->bank_code,
            'bank_name' => $account?->bank_name,
            'account_number' => $account?->account_number,
        };
    }

    private function masterFieldIsProvided(array $row, string $field): bool
    {
        return in_array($field, $row['_provided_master_fields'] ?? [], true);
    }

    private function auditValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    private function comparableValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) ($value ?? ''));
    }

    private function reportValue(mixed $value, string $field): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (in_array($field, self::SENSITIVE_MASTER_FIELDS, true)) {
            return '[disembunyikan]';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return match ($field) {
            'position_type' => [
                1 => 'Struktural',
                3 => 'Fungsional Umum',
            ][(int) $value] ?? (string) $value,
            'marital_status' => [
                1 => 'Menikah',
                2 => 'Belum menikah',
            ][(int) $value] ?? (string) $value,
            'spouse_is_pns' => ((bool) $value) ? 'Ya' : 'Tidak',
            default => (string) $value,
        };
    }

    private function upsertBankAccount(Employee $employee, array $row): ?EmployeeBankAccount
    {
        if ($this->preserveExistingMaster) {
            return null;
        }

        if ($row['bank_code'] === null || $row['bank_name'] === null || $row['account_number'] === null) {
            return null;
        }

        $bank = Bank::query()->updateOrCreate(
            ['code' => $row['bank_code']],
            [
                'name' => $row['bank_name'],
                'is_active' => true,
            ],
        );

        $account = $employee->bankAccounts()->updateOrCreate(
            [
                'bank_code' => $row['bank_code'],
                'account_number' => $row['account_number'],
            ],
            [
                'bank_id' => $bank->id,
                'bank_name' => $row['bank_name'],
                'is_primary' => true,
            ],
        );

        $employee->bankAccounts()
            ->where($account->getKeyName(), '!=', $account->getKey())
            ->update(['is_primary' => false]);

        return $account;
    }

    private function clearTppRecords(PayrollPeriod $period): void
    {
        $period->records()->update(array_merge(
            array_fill_keys(EmployeePayroll::TPP_COMPONENTS, 0),
            [
                'tpp_bank_code' => null,
                'tpp_bank_name' => null,
                'tpp_account_number' => null,
                'tpp_total' => 0,
                'tpp_total_deductions' => 0,
                'tpp_transferred_amount' => 0,
                'tpp_source_total' => null,
                'tpp_source_total_deductions' => null,
                'tpp_source_transferred_amount' => null,
                'tpp_reconciliation_status' => EmployeePayroll::TPP_RECONCILIATION_PENDING,
                'tpp_source_row_number' => null,
                'tpp_source_row_checksum' => null,
            ],
        ));
    }

    /** @return array<string, mixed> */
    private function componentAttributes(array $row): array
    {
        return array_intersect_key($row, array_flip(EmployeePayroll::COMPONENTS));
    }

    /** @return array<string, mixed> */
    private function tppComponentAttributes(array $row): array
    {
        return array_intersect_key($row, array_flip(EmployeePayroll::TPP_COMPONENTS));
    }

    /** @return array<string, int|null> */
    private function headerMap(array $headers): array
    {
        $normalized = array_map(fn (mixed $header): string => $this->normalizeHeader($header), $headers);
        $map = [];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            $map[$field] = null;

            foreach ($aliases as $alias) {
                $index = array_search($alias, $normalized, true);

                if ($index !== false) {
                    $map[$field] = $index;

                    break;
                }
            }
        }

        return $map;
    }

    private function normalizeHeader(mixed $value): string
    {
        return Str::of((string) $value)
            ->replace("\u{FEFF}", '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function cellValue(array $row, ?int $index): ?string
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return null;
        }

        $value = trim((string) $row[$index]);

        return in_array($value, ['', '-', '—', 'N/A', 'NA'], true) ? null : $value;
    }

    private function moneyValue(?string $value): int
    {
        if ($value === null) {
            return 0;
        }

        $negative = str_contains($value, '-');
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        $amount = (int) ($digits === '' ? 0 : $digits);

        return $negative ? -$amount : $amount;
    }

    private function integerValue(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits === '' ? null : (int) $digits;
    }

    /** @return array{0: int|null, 1: int|null} */
    private function gradeServiceDuration(?string $value): array
    {
        if ($value === null) {
            return [null, null];
        }

        preg_match_all('/\d+/', $value, $matches);
        $numbers = array_map('intval', $matches[0] ?? []);

        if ($numbers === []) {
            return [null, null];
        }

        return [$numbers[0], $numbers[1] ?? 0];
    }

    private function digitsOrNull(?string $value): ?string
    {
        $digits = EmployeeNipMetadata::digits($value);

        return $digits === '' ? null : $digits;
    }

    private function booleanValue(?string $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return match (strtoupper(trim($value))) {
            'YA', 'YES', 'TRUE', '1' => true,
            'TIDAK', 'NO', 'FALSE', '0' => false,
            default => null,
        };
    }

    private function employmentStatus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match (strtoupper(trim($value))) {
            '1', 'PNS' => 'PNS',
            '2', 'PPPK' => 'PPPK',
            'LAINNYA', '3' => 'Lainnya',
            default => null,
        };
    }

    private function normaliseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        if (is_numeric($value) && (float) $value > 1000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    /** @return list<array<int, mixed>> */
    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsv($file);
        }

        if ($extension !== 'xlsx') {
            throw new InvalidArgumentException('Gunakan file CSV atau Excel (.xlsx).');
        }

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());

            try {
                return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('File Excel payroll tidak dapat dibaca.', 0, $exception);
        }
    }

    /** @return list<array<int, string|null>> */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('File CSV payroll tidak dapat dibaca.');
        }

        try {
            $firstLine = fgets($handle) ?: '';
            rewind($handle);
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            $rows = [];

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($rows === [] && isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
