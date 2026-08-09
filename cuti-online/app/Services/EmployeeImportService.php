<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OrganizationProfile;
use App\Support\EmployeeRankOptions;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Throwable;

class EmployeeImportService
{
    public const MAX_ROWS = 1000;

    /** @var list<string> */
    public const TEMPLATE_HEADERS = [
        'NIP',
        'Nama Lengkap',
        'Status Kepegawaian',
        'Pangkat/Golongan',
        'Unit Kerja',
        'Jabatan',
        'TMT Mulai Kerja',
        'No. HP',
        'Email',
        'Alamat',
    ];

    /** @var array<string, list<string>> */
    private const HEADER_ALIASES = [
        'nip' => ['nip'],
        'full_name' => ['nama_lengkap', 'nama', 'full_name'],
        'employment_status' => ['status_kepegawaian', 'status', 'employment_status'],
        'rank_grade' => ['pangkat_golongan', 'pangkat', 'golongan', 'rank_grade'],
        'department_name' => ['unit_kerja', 'unit', 'department_name'],
        'position_title' => ['jabatan', 'position_title'],
        'service_started_on' => ['tmt_mulai_kerja', 'mulai_masa_kerja', 'service_started_on'],
        'phone' => ['no_hp', 'nomor_hp', 'telepon', 'phone'],
        'email' => ['email'],
        'address' => ['alamat', 'address'],
    ];

    /** @var list<string> */
    private const REQUIRED_COLUMNS = [
        'nip',
        'full_name',
        'employment_status',
        'position_title',
    ];

    /**
     * @return array{
     *     file_name: string,
     *     total_rows: int,
     *     valid_rows: list<array<string, string|null>>,
     *     errors: list<array{row_number: int, messages: list<string>}>
     * }
     */
    public function preview(UploadedFile $file): array
    {
        $rows = $this->readRows($file);
        $fileName = $file->getClientOriginalName();

        if ($rows === []) {
            return $this->failedPreview($fileName, 'File tidak memiliki data yang dapat dibaca.');
        }

        $headers = array_shift($rows);
        $headerMap = $this->headerMap($headers);
        $missingColumns = array_filter(
            self::REQUIRED_COLUMNS,
            static fn (string $column): bool => $headerMap[$column] === null,
        );

        if ($missingColumns !== []) {
            $labels = array_map(fn (string $column): string => $this->headerLabel($column), $missingColumns);

            return $this->failedPreview(
                $fileName,
                'Kolom wajib tidak ditemukan: '.implode(', ', $labels).'. Unduh template untuk format yang benar.',
            );
        }

        $rows = array_values(array_filter($rows, fn (array $row): bool => ! $this->isBlankRow($row)));

        if ($rows === []) {
            return $this->failedPreview($fileName, 'Belum ada baris pegawai setelah baris judul.');
        }

        if (count($rows) > self::MAX_ROWS) {
            return $this->failedPreview(
                $fileName,
                'Maksimal '.number_format(self::MAX_ROWS, 0, ',', '.').' pegawai per file.',
            );
        }

        $existingNips = Employee::withTrashed()
            ->whereIn('nip', $this->nipCandidates($rows, $headerMap))
            ->pluck('nip')
            ->all();
        $existingNipLookup = array_fill_keys($existingNips, true);
        $seenNips = [];
        $validRows = [];
        $errors = [];
        $organizationName = OrganizationProfile::current()->name;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $rankValue = $this->cellValue($row, $headerMap['rank_grade']);
            $employmentStatus = $this->employmentStatus($this->cellValue($row, $headerMap['employment_status']));
            $rank = EmployeeRankOptions::find($employmentStatus, $rankValue);
            $data = [
                'nip' => $this->cellValue($row, $headerMap['nip']),
                'full_name' => $this->cellValue($row, $headerMap['full_name']),
                'employment_status' => $employmentStatus,
                'rank_name' => $rank['name'] ?? null,
                'grade' => $rank['grade'] ?? null,
                'department_name' => $this->cellValue($row, $headerMap['department_name']) ?? $organizationName,
                'position_title' => $this->cellValue($row, $headerMap['position_title']),
                'service_started_on' => $this->normaliseDate($this->cellValue($row, $headerMap['service_started_on'])),
                'phone' => $this->cellValue($row, $headerMap['phone']),
                'email' => $this->cellValue($row, $headerMap['email']),
                'address' => $this->cellValue($row, $headerMap['address']),
            ];
            $messages = $this->rowMessages($data, $rankValue);

            if ($data['nip'] !== null && isset($existingNipLookup[$data['nip']])) {
                $messages[] = 'NIP sudah digunakan oleh pegawai yang ada.';
            }

            if ($data['nip'] !== null && isset($seenNips[$data['nip']])) {
                $messages[] = 'NIP duplikat di file ini (baris '.$seenNips[$data['nip']].').';
            }

            if ($data['nip'] !== null) {
                $seenNips[$data['nip']] = $rowNumber;
            }

            if ($messages !== []) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'messages' => array_values(array_unique($messages)),
                ];

                continue;
            }

            $validRows[] = $data;
        }

        return [
            'file_name' => $fileName,
            'total_rows' => count($rows),
            'valid_rows' => $validRows,
            'errors' => $errors,
        ];
    }

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pegawai');
        $sheet->fromArray([self::TEMPLATE_HEADERS], null, 'A1');
        $sheet->fromArray([[
            '199001012020011001',
            'Contoh Nama Pegawai',
            'PNS',
            'III/b',
            'Kecamatan Pontianak Selatan',
            'Pengelola Layanan Operasional',
            '2020-01-01',
            '081234567890',
            'nama@example.go.id',
            'Pontianak',
        ]], null, 'A2');
        $sheet->setCellValueExplicit('A2', '199001012020011001', DataType::TYPE_STRING);
        $sheet->setShowGridLines(false);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:J2');
        $sheet->getStyle('A1:J1')->applyFromArray([
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
            ],
        ]);
        $sheet->getStyle('A1:J2')->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new Color('FFD1D5DB'));
        $sheet->getStyle('A2:A'.(self::MAX_ROWS + 1))->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('G2:G'.(self::MAX_ROWS + 1))->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $sheet->getStyle('A2:J2')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach ([
            'A' => 24,
            'B' => 28,
            'C' => 22,
            'D' => 22,
            'E' => 30,
            'F' => 34,
            'G' => 19,
            'H' => 18,
            'I' => 28,
            'J' => 30,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->fromArray([
            ['Impor Pegawai – Petunjuk'],
            ['1. Isi data pada lembar "Data Pegawai" dan hapus baris contoh sebelum diunggah.'],
            ['2. Kolom wajib: NIP, Nama Lengkap, Status Kepegawaian, dan Jabatan.'],
            ['3. Kolom Unit Kerja bersifat opsional. Jika diisi, unit tersebut dipakai; jika kosong, aplikasi memakai Profil Instansi.'],
            ['4. Pangkat/Golongan wajib untuk PNS (contoh: III/b). Untuk PPPK gunakan Golongan I sampai Golongan XX, misalnya Golongan IX.'],
            ['5. Gunakan tanggal TMT dengan format YYYY-MM-DD, misalnya 2020-01-01.'],
            ['6. NIP disimpan sebagai teks agar seluruh digit tetap utuh. Maksimal 1.000 pegawai per file.'],
        ], null, 'A1');
        $guide->setShowGridLines(false);
        $guide->mergeCells('A1:D1');
        $guide->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0F766E'],
            ],
        ]);
        $guide->getStyle('A2:A7')->getAlignment()->setWrapText(true);
        $guide->getColumnDimension('A')->setWidth(120);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @param  array<string, int|null>  $headerMap
     * @return list<string>
     */
    private function nipCandidates(array $rows, array $headerMap): array
    {
        return array_values(array_filter(array_map(
            fn (array $row): ?string => $this->cellValue($row, $headerMap['nip']),
            $rows,
        )));
    }

    /**
     * @param  list<mixed>  $headers
     * @return array<string, int|null>
     */
    private function headerMap(array $headers): array
    {
        $normalised = array_map(fn (mixed $header): string => $this->normaliseHeader($header), $headers);
        $map = [];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            $map[$field] = null;

            foreach ($aliases as $alias) {
                $index = array_search($alias, $normalised, true);

                if ($index !== false) {
                    $map[$field] = $index;

                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function cellValue(array $row, ?int $index): ?string
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return null;
        }

        $value = trim((string) $row[$index]);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, string|null>  $data
     * @return list<string>
     */
    private function rowMessages(array $data, ?string $rankValue): array
    {
        $validator = Validator::make($data, [
            'nip' => ['required', 'string', 'max:32'],
            'full_name' => ['required', 'string', 'max:255'],
            'employment_status' => ['required', 'string', 'max:32'],
            'department_name' => ['required', 'string', 'max:255'],
            'position_title' => ['required', 'string', 'max:255'],
            'service_started_on' => ['nullable', 'date_format:Y-m-d'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ], [
            'nip.required' => 'NIP wajib diisi.',
            'nip.max' => 'NIP maksimal 32 karakter.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.max' => 'Nama lengkap maksimal 255 karakter.',
            'employment_status.required' => 'Status kepegawaian wajib diisi.',
            'employment_status.max' => 'Status kepegawaian maksimal 32 karakter.',
            'department_name.required' => 'Unit kerja wajib diisi.',
            'department_name.max' => 'Unit kerja maksimal 255 karakter.',
            'position_title.required' => 'Jabatan wajib diisi.',
            'position_title.max' => 'Jabatan maksimal 255 karakter.',
            'service_started_on.date_format' => 'TMT mulai kerja harus berformat YYYY-MM-DD.',
            'phone.max' => 'Nomor HP maksimal 32 karakter.',
            'email.email' => 'Email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
        ]);
        $messages = $validator->errors()->all();

        if ($data['employment_status'] === 'PNS' && $data['grade'] === null) {
            $messages[] = $rankValue === null
                ? 'Pangkat/golongan wajib untuk PNS dan harus sesuai daftar yang tersedia.'
                : 'Pangkat/golongan PNS tidak dikenali. Gunakan contoh seperti III/b.';
        } elseif ($data['employment_status'] === 'PPPK' && $rankValue !== null && $data['grade'] === null) {
            $messages[] = 'Pangkat/golongan PPPK tidak dikenali. Gunakan contoh seperti Golongan IX.';
        } elseif ($rankValue !== null && $data['grade'] === null) {
            $messages[] = 'Pangkat/golongan hanya dapat diisi untuk PNS atau PPPK.';
        }

        return $messages;
    }

    private function employmentStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return match (strtoupper($status)) {
            'PNS' => 'PNS',
            'PPPK' => 'PPPK',
            'LAINNYA' => 'Lainnya',
            default => $status,
        };
    }

    private function normaliseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }

    private function normaliseHeader(mixed $value): string
    {
        return Str::of((string) $value)
            ->replace("\u{FEFF}", '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array<int, mixed>>
     */
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
                return $spreadsheet->getActiveSheet()->toArray(null, false, true, false);
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('File Excel tidak dapat dibaca. Pastikan file tidak rusak dan gunakan format .xlsx.', 0, $exception);
        }
    }

    /**
     * @return list<array<int, string|null>>
     */
    private function readCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('File CSV tidak dapat dibaca.');
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

    /**
     * @return array{
     *     file_name: string,
     *     total_rows: int,
     *     valid_rows: list<array<string, string|null>>,
     *     errors: list<array{row_number: int, messages: list<string>}>
     * }
     */
    private function failedPreview(string $fileName, string $message): array
    {
        return [
            'file_name' => $fileName,
            'total_rows' => 0,
            'valid_rows' => [],
            'errors' => [[
                'row_number' => 1,
                'messages' => [$message],
            ]],
        ];
    }

    private function headerLabel(string $column): string
    {
        return match ($column) {
            'full_name' => 'Nama Lengkap',
            'employment_status' => 'Status Kepegawaian',
            'department_name' => 'Unit Kerja',
            'position_title' => 'Jabatan',
            default => strtoupper($column),
        };
    }
}
