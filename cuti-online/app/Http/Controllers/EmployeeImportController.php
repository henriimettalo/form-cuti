<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeImport;
use App\Services\EmployeeImportService;
use App\Services\EmployeeOnboardingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportController extends Controller
{
    private const PREVIEW_ROWS_PER_PAGE = 10;

    public function __construct(
        private readonly EmployeeImportService $imports,
        private readonly EmployeeOnboardingService $onboarding,
    ) {}

    public function create(): View
    {
        return view('employees.import-create');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = $this->imports->template();

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-impor-pegawai.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xlsx'],
        ], [
            'file.required' => 'Pilih file yang akan diimpor.',
            'file.mimes' => 'Gunakan file CSV atau Excel (.xlsx).',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            return back()->withErrors(['file' => 'File yang diunggah tidak valid.']);
        }

        try {
            $preview = $this->imports->preview($file);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        if ($preview['errors'] !== []) {
            return back()
                ->with('importErrors', $preview['errors'])
                ->with('importFileName', $preview['file_name'])
                ->with('importTotalRows', $preview['total_rows']);
        }

        $employeeImport = EmployeeImport::query()->create([
            'token' => (string) Str::uuid(),
            'original_filename' => $preview['file_name'],
            'total_rows' => $preview['total_rows'],
            'payload' => $preview['valid_rows'],
            'created_by' => $request->user()?->id,
        ]);

        return to_route('employees.import.preview', $employeeImport);
    }

    public function preview(Request $request, EmployeeImport $employeeImport): View
    {
        $rows = array_values($employeeImport->payload ?? []);
        $totalRows = count($rows);
        $lastPage = max(1, (int) ceil($totalRows / self::PREVIEW_ROWS_PER_PAGE));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $lastPage);
        $previewRows = new LengthAwarePaginator(
            array_slice($rows, ($currentPage - 1) * self::PREVIEW_ROWS_PER_PAGE, self::PREVIEW_ROWS_PER_PAGE),
            $totalRows,
            self::PREVIEW_ROWS_PER_PAGE,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ],
        );
        $previewRows->withQueryString();

        return view('employees.import-preview', [
            'employeeImport' => $employeeImport,
            'previewRows' => $previewRows,
        ]);
    }

    public function store(Request $request, EmployeeImport $employeeImport): RedirectResponse
    {
        try {
            $result = DB::transaction(function () use ($employeeImport, $request): array {
                $lockedImport = EmployeeImport::query()
                    ->lockForUpdate()
                    ->findOrFail($employeeImport->getKey());

                if ($lockedImport->status === 'completed') {
                    return [
                        'employeeImport' => $lockedImport,
                        'alreadyCompleted' => true,
                    ];
                }

                if ($lockedImport->status !== 'previewed') {
                    throw new LogicException('Pratinjau impor ini tidak dapat diproses lagi. Unggah file baru untuk melanjutkan.');
                }

                $rows = $lockedImport->payload ?? [];
                $nips = collect($rows)->pluck('nip')->filter()->all();

                if (Employee::withTrashed()->whereIn('nip', $nips)->exists()) {
                    throw new LogicException('Salah satu NIP sudah dipakai setelah pratinjau dibuat. Unggah ulang file untuk memeriksa data terbaru.');
                }

                foreach ($rows as $row) {
                    $this->onboarding->create($row, $request->user()?->id, 'Data awal impor pegawai.');
                }

                $lockedImport->update([
                    'status' => 'completed',
                    'imported_rows' => count($rows),
                    'processed_at' => now(),
                ]);

                return [
                    'employeeImport' => $lockedImport,
                    'alreadyCompleted' => false,
                ];
            });
        } catch (LogicException $exception) {
            return to_route('employees.import.preview', $employeeImport)
                ->withErrors(['import' => $exception->getMessage()]);
        } catch (QueryException) {
            return to_route('employees.import.preview', $employeeImport)
                ->withErrors(['import' => 'Data berubah saat diproses. Tidak ada pegawai yang diimpor; unggah ulang file untuk melanjutkan.']);
        }

        /** @var EmployeeImport $completedImport */
        $completedImport = $result['employeeImport'];

        if ($result['alreadyCompleted']) {
            return to_route('employees.index')
                ->with('status', 'Impor sebelumnya sudah selesai. Tidak ada pegawai yang ditambahkan lagi.');
        }

        return to_route('employees.index')
            ->with('status', number_format($completedImport->imported_rows, 0, ',', '.').' pegawai berhasil diimpor.');
    }
}
