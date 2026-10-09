<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Support\EmployeeImportColumns;
use App\Support\EmployeeNipMetadata;
use App\Support\ImportDepartmentResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        'department_name' => ['unit_kerja', 'nama_unit_kerja', 'department_name'],
    ];

    private const REQUIRED = ['nip'];

    private const UPDATE_FIELDS = ['full_name', 'position_title', 'department_name', 'phone', 'email'];

    /**
     * @return array{
     *     file_name: string,
     *     total_rows: int,
     *     valid_rows: list<array<string, mixed>>,
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
                return $this->failedPreview($fileName, 'Kolom NIP wajib tersedia. Nama Lengkap wajib diisi untuk pegawai baru.');
            }
        }

        $existingNips = Employee::query()->with('department')->withTrashed()
            ->get()->keyBy(fn ($employee) => EmployeeNipMetadata::digits($employee->nip));

        $departments = Department::query()->where('is_active', true)->get();
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
            $data['_import_api_fields'] = EmployeeImportColumns::fromHeaderMap($headerMap);

            $messages = $this->rowMessages($data, $seenNips, $existingNips);
            if ($data['department_name'] !== null) {
                $department = ImportDepartmentResolver::resolve($data['department_name'], $departments);
                if ($department === null) {
                    $messages[] = "Unit kerja \"{$data['department_name']}\" tidak dikenali atau ambigu. Gunakan unit kerja aktif yang terdaftar di Akun & Unit.";
                } else {
                    $data['department_name'] = $department->name;
                }
            }

            if (empty($messages)) {
                $nipLookup = EmployeeNipMetadata::digits($data['nip']);
                $seenNips[$nipLookup] = $rowNumber;

                if ($existingNips->has($nipLookup)) {
                    $employee = $existingNips->get($nipLookup);
                    $changes = [];
                    foreach (self::UPDATE_FIELDS as $field) {
                        $old = $field === 'department_name' ? $employee->department?->name : $employee->{$field};
                        if ($data[$field] !== null && $data[$field] !== $old) {
                            $changes[$field] = ['old' => $old, 'new' => $data[$field]];
                        }
                    }
                    $columnsChanged = $employee->imported_api_fields !== $data['_import_api_fields'];
                    if (! $employee->trashed() && ($changes !== [] || $columnsChanged)) {
                        $validRows[] = $data + ['action' => 'update', 'changes' => $changes, 'employee_id' => $employee->id, 'record_columns_only' => $changes === []];
                    } else {
                        $skipped[] = $data + ['row_number' => (string) $rowNumber, 'reason' => $employee->trashed() ? 'Pegawai diarsipkan' : 'Tidak ada perubahan'];
                    }

                    continue;
                }

                $validRows[] = $data + ['action' => 'create'];

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
        return DB::transaction(function () use ($preview): int {
            $count = 0;
            foreach ($preview['valid_rows'] as $row) {
                $department = $row['department_name'] !== null
                    ? ImportDepartmentResolver::resolve($row['department_name']) : null;
                if ($row['department_name'] !== null && $department === null) {
                    throw new InvalidArgumentException('Unit kerja sudah tidak tersedia. Unggah ulang file untuk memperbarui pratinjau.');
                }
                $position = $row['position_title'] !== null
                    ? Position::query()->firstOrCreate(['name' => $row['position_title']], ['is_active' => true])
                    : null;
                if (($row['action'] ?? 'create') === 'update') {
                    $employee = Employee::query()->findOrFail($row['employee_id']);
                    $updates = array_filter([
                        'full_name' => $row['full_name'],
                        'position_title' => $position?->name,
                        'position_id' => $position?->id,
                        'department_id' => $department?->id,
                        'phone' => $row['phone'],
                        'email' => $row['email'],
                    ], fn ($value) => $value !== null);
                    $employee->update($updates);
                    $employee->update(['imported_api_fields' => $row['_import_api_fields']]);
                    $count++;

                    continue;
                }
                Employee::query()->create([
                    'nip' => $row['nip'],
                    'imported_api_fields' => $row['_import_api_fields'],
                    'full_name' => $row['full_name'],
                    'position_id' => $position?->id,
                    'position_title' => $position?->name,
                    'department_id' => $department?->id,
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
        });
    }

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Identitas Pegawai');

        $headers = ['NIP', 'Nama Lengkap', 'Jabatan', 'Nomor Telepon', 'Email', 'Unit Kerja'];
        $sheet->fromArray($headers, null, 'A1');

        $samples = [
            ['197207151993032008', 'Suriadarna', 'Pengelola Layanan Operasional', '081234567890', 'suriadarna@pontianakselatan.go.id'],
            ['198408282002122001', 'Rima Nurfitria', 'Sekretaris Camat Pontianak Selatan', '081234567891', 'rima@pontianakselatan.go.id'],
        ];
        $sheet->fromArray($samples, null, 'A2');

        $style = $sheet->getStyle('A1:F1');
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0EA5E9');
        $style->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A1:F3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Panduan');
        $guide->fromArray([
            ['Kolom', 'Wajib', 'Keterangan'],
            ['NIP', 'Ya', 'NIP menjadi kunci pencocokan. NIP baru ditambahkan, NIP terdaftar diperbarui dari kolom yang diisi. Kolom kosong mempertahankan data lama.'],
            ['Nama Lengkap', 'Untuk pegawai baru', 'Nama lengkap pegawai. Untuk pembaruan, boleh dikosongkan jika nama tidak berubah.'],
            ['Jabatan', 'Tidak', 'Nama jabatan spesifik. Kosongkan jika belum diketahui.'],
            ['Nomor Telepon', 'Tidak', '8-15 digit.'],
            ['Email', 'Tidak', 'Alamat email.'],
            ['Unit Kerja', 'Tidak', 'Gunakan unit kerja aktif yang sudah terdaftar di Akun & Unit. Nama singkat kelurahan dipadankan dengan nama resminya.'],
        ], null, 'A1');
        $guide->getStyle('A1:C1')->getFont()->setBold(true);
        foreach (range('A', 'C') as $col) {
            $guide->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  list<string|null>  $row
     * @return array<string, string|null>
     */
    private function extract(array $row, array $headerMap): array
    {
        $cell = fn ($field) => isset($row[$headerMap[$field]]) ? $this->normaliseCell((string) $row[$headerMap[$field]]) : null;

        return [
            'nip' => EmployeeNipMetadata::digits($cell('nip')) ?: null,
            'full_name' => $cell('full_name'),
            'position_title' => $cell('position_title'),
            'phone' => $cell('phone'),
            'email' => $cell('email'),
            'department_name' => $cell('department_name'),
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
     * @param  array<string, string|null>  $data
     * @param  array<string, int>  $seenNips
     * @param  Collection<string, Employee>  $existingNips
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

        if ($data['full_name'] === null && ! $existingNips->has(EmployeeNipMetadata::digits($data['nip']))) {
            $messages[] = 'Nama lengkap wajib diisi untuk pegawai baru.';
        }

        if ($data['phone'] !== null && trim($data['phone']) !== '' && ! preg_match('/^\d{8,15}$/', $data['phone'])) {
            $messages[] = 'Nomor telepon harus 8-15 digit.';
        }

        if ($data['email'] !== null && trim($data['email']) !== '' && ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $messages[] = 'Format email tidak valid.';
        }

        if ($data['department_name'] !== null && mb_strlen($data['department_name']) > 255) {
            $messages[] = 'Nama unit kerja maksimal 255 karakter.';
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
        return preg_replace('/\s+/', '_', strtolower(trim((string) $value)));
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

            while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
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
