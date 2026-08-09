<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeImport;
use App\Support\EmployeeNipMetadata;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Throwable;

class EmployeeIdentityImportService
{
    private const MAX_ROWS = 1000;

    private const HEADER_ALIASES = [
        'nip' => ['nip', 'nip_pegawai', 'nip pegawai'],
        'full_name' => ['nama_lengkap', 'nama', 'full_name', 'nama pegawai', 'nama_pegawai'],
        'position_title' => ['jabatan', 'nama_jabatan', 'position_title'],
        'phone' => ['nomor_telepon', 'no_hp', 'nomor_hp', 'telepon', 'phone', 'no handphone', 'no_handphone'],
        'email' => ['email', 'surel'],
    ];

    private const REQUIRED = ['nip', 'full_name'];

    /**
     * @return array{
     *     file_name: string,
     *     total_rows: int,
     *     valid_rows: list<array<string, string|null>>,
     *     errors: list<array{row_number: int, messages: list<string>}>,
     *     skipped_existing: list<array<string, string|null>>,
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

        foreach (self::REQUIRED as $field) {
            if ($headerMap[$field] === null) {
                return $this->failedPreview($fileName, 'Kolom wajib hilang: '.ucfirst(str_replace('_', ' ', $field)).'. Sertakan kolom NIP dan Nama Lengkap.');
            }
        }

        $existingNips = Employee::query()
            ->withTrashed()
            ->pluck('nip')
            ->map(fn ($nip) => EmployeeNipMetadata::digits($nip))
            ->flip();

        $seenNips = [];
        $validRows = [];
        $skipped = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->extract($row, $headerMap);

            if ($this->isBlankRow($data)) {
                continue;
            }

            $messages = $this->rowMessages($data, $seenNips, $existingNips);

            if (empty($messages)) {
                $nipLookup = EmployeeNipMetadata::digits($data['nip']);
                $seenNips[$nipLookup] = $rowNumber;

                if ($existingNips->has($nipLookup)) {
                    $skipped[] = $data + ['row_number' => (string) $rowNumber];
                    continue;
                }

                $validRows[] = $data;
                continue;
            }

            $errors[] = ['row_number' => $rowNumber, 'messages' => $messages];
        }

        return [
            'file_name' => $fileName,
            'total_rows' => count($validRows) + count($skipped) + count($errors),
            'valid_rows' => $validRows,
            'errors' => $errors,
            'skipped_existing' => $skipped,
        ];
    }

    public function store(UploadedFile $file): int
    {
        $preview = $this->preview($file);

        return $this->storePreview($preview);
    }

    public function storeByPath(string $path): int
    {
        $file = new UploadedFile($path, basename($path), null, null, true);

        return $this->storePreview($this->preview($file));
    }

    private function storePreview(array $preview): int
    {
        $count = 0;

        foreach ($preview['valid_rows'] as $row) {
            $position = isset($row['position_title']) && $row['position_title'] !== null && trim($row['position_title']) !== ''
                ? \App\Models\Position::query()->firstOrCreate(['name' => trim($row['position_title'])], ['is_active' => true])
                : null;

            Employee::query()->create([
                'nip' => $row['nip'],
                'full_name' => $row['full_name'],
                'position_id' => $position?->id,
                'position_title' => $position?->name,
                'phone' => $row['phone'] ?? null,
                'email' => $row['email'] ?? null,
                'employment_status' => null,
                'is_active' => true,
                'service_started_on' => EmployeeNipMetadata::derive($row['nip'])['service_started_on'] ?? null,
                'nip_tmt_valid' => EmployeeNipMetadata::derive($row['nip'])['nip_tmt_valid'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Identitas Pegawai');

        $headers = ['NIP', 'Nama Lengkap', 'Jabatan', 'Nomor Telepon', 'Email'];
        $sheet->fromArray($headers, null, 'A1');

        $samples = [
            ['197207151993032008', 'Suriadarna', 'Pengelola Layanan Operasional', '081234567890', 'suriadarna@pontianakselatan.go.id'],
            ['198408282002122001', 'Rima Nurfitria', 'Sekretaris Camat Pontianak Selatan', '081234567891', 'rima@pontianakselatan.go.id'],
        ];
        $sheet->fromArray($samples, null, 'A2');

        $style = $sheet->getStyle('A1:E1');
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0EA5E9');
        $style->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A1:E3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Panduan');
        $guide->fromArray([
            ['Kolom', 'Wajib', 'Keterangan'],
            ['NIP', 'Ya', 'NIP pegawai, unik. NIP yang sudah ada di sistem akan dilewati.'],
            ['Nama Lengkap', 'Ya', 'Nama lengkap pegawai.'],
            ['Jabatan', 'Tidak', 'Nama jabatan spesifik. Kosongkan jika belum diketahui.'],
            ['Nomor Telepon', 'Tidak', '8-15 digit.'],
            ['Email', 'Tidak', 'Alamat email.'],
        ], null, 'A1');
        $guide->getStyle('A1:C1')->getFont()->setBold(true);
        foreach (range('A', 'C') as $col) {
            $guide->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * @param list<string|null> $row
     * @return array<string, string|null>
     */
    private function extract(array $row, array $headerMap): array
    {
        $cell = fn ($field) => isset($row[$headerMap[$field]]) ? $this->normaliseCell((string) $row[$headerMap[$field]]) : null;

        return [
            'nip' => $cell('nip'),
            'full_name' => $cell('full_name'),
            'position_title' => $cell('position_title'),
            'phone' => $cell('phone'),
            'email' => $cell('email'),
        ];
    }

    private function headerMap(array $headers): array
    {
        $map = [];
        $normalised = array_map(fn ($h) => $this->normaliseHeader($h), $headers);

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            $map[$field] = null;
            foreach ($aliases as $alias) {
                $key = $this->normaliseHeader($alias);
                $index = array_search($key, $normalised, true);
                if ($index !== false) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param array<string, string|null> $data
     * @param array<string, int> $seenNips
     * @param \Illuminate\Support\Collection<int, int> $existingNips
     * @return list<string>
     */
    private function rowMessages(array $data, array $seenNips, $existingNips): array
    {
        $messages = [];

        if ($data['nip'] === null || trim($data['nip']) === '') {
            $messages[] = 'NIP wajib diisi.';
        } elseif (! preg_match('/^\d{8,32}$/', EmployeeNipMetadata::digits($data['nip']))) {
            $messages[] = 'NIP harus terdiri dari 8-32 digit angka.';
        }

        if ($data['full_name'] === null || trim($data['full_name']) === '') {
            $messages[] = 'Nama lengkap wajib diisi.';
        }

        if ($data['phone'] !== null && trim($data['phone']) !== '' && ! preg_match('/^\d{8,15}$/', $data['phone'])) {
            $messages[] = 'Nomor telepon harus 8-15 digit.';
        }

        if ($data['email'] !== null && trim($data['email']) !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $messages[] = 'Format email tidak valid.';
        }

        if (empty($messages)) {
            $nipLookup = EmployeeNipMetadata::digits($data['nip']);

            if (isset($seenNips[$nipLookup])) {
                $messages[] = "NIP duplikat dengan baris {$seenNips[$nipLookup]} pada file.";
            }
        }

        return $messages;
    }

    private function normaliseCell(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normaliseHeader(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function isBlankRow(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null && trim($value) !== '') {
                return false;
            }
        }

        return true;
    }

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

    private function failedPreview(string $fileName, string $message): array
    {
        return [
            'file_name' => $fileName,
            'message' => $message,
            'total_rows' => 0,
            'valid_rows' => [],
            'errors' => [],
            'skipped_existing' => [],
        ];
    }
}