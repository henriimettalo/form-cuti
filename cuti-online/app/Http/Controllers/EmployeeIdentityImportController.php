<?php

namespace App\Http\Controllers;

use App\Services\EmployeeIdentityImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeIdentityImportController extends Controller
{
    private const SESSION_KEY = 'identity-import-preview';

    private const PATH_KEY = 'identity-import-path';

    public function __construct(private readonly EmployeeIdentityImportService $imports) {}

    public function create(): View
    {
        return view('employees.identity-import-create', [
            'preview' => session(self::SESSION_KEY),
        ]);
    }

    public function preview(Request $request): RedirectResponse
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
        } catch (\Throwable $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        $path = $file->storeAs('identity-imports', $file->getClientOriginalName(), 'local');

        if ($path === false) {
            return back()->withErrors(['file' => 'File tidak dapat disimpan untuk diproses.']);
        }

        session([
            self::SESSION_KEY => $preview,
            self::PATH_KEY => $path,
        ]);

        return to_route('employees.identity-import.create');
    }

    public function store(Request $request): RedirectResponse|View
    {
        $path = $request->session()->get(self::PATH_KEY);
        $preview = $request->session()->get(self::SESSION_KEY);

        if ($path === null || $preview === null) {
            return to_route('employees.identity-import.create')
                ->withErrors(['file' => 'Pratinjau kedaluwarsa. Unggah file ulang.']);
        }

        $disk = Storage::disk('local');
        $fullPath = $disk->path($path);

        if (! file_exists($fullPath)) {
            return to_route('employees.identity-import.create')
                ->withErrors(['file' => 'File tidak tersedia. Unggah ulang.']);
        }

        $count = $this->imports->storeByPath($fullPath);

        $request->session()->forget([self::SESSION_KEY, self::PATH_KEY]);
        $disk->delete($path);

        if ($request->boolean('modal')) {
            return view('employees.identity-import-complete', ['count' => $count]);
        }

        return to_route('employees.index')
            ->with('status', "{$count} data identitas pegawai berhasil diimpor.");
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = $this->imports->template();

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-impor-identitas-pegawai.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
