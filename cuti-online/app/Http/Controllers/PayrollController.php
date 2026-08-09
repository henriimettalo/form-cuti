<?php

namespace App\Http\Controllers;

use App\Models\EmployeePayroll;
use App\Models\PayrollImport;
use App\Models\PayrollPeriod;
use App\Services\PayrollExportService;
use App\Services\PayrollImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollImportService $imports,
        private readonly PayrollExportService $exports,
    ) {}

    public function index(): View
    {
        return view('payroll.index', [
            'periods' => PayrollPeriod::query()
                ->withCount('records')
                ->withCount([
                    'records as mismatch_count' => fn ($query) => $query->where(
                        'reconciliation_status',
                        EmployeePayroll::RECONCILIATION_MISMATCH,
                    ),
                ])
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('payroll.import-create', [
            'months' => [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ],
            'pendingImports' => PayrollImport::query()
                ->where('status', PayrollImport::STATUS_PREVIEWED)
                ->latest('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'source_type' => ['nullable', 'in:primary,tpp'],
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx'],
        ], [
            'file.required' => 'Pilih file payroll yang akan diimpor.',
            'file.mimes' => 'Gunakan file CSV atau Excel (.xlsx).',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            return back()->withErrors(['file' => 'File payroll tidak valid.']);
        }

        try {
            $payrollImport = $this->imports->preview(
                $file,
                (int) $data['year'],
                (int) $data['month'],
                $request->user()?->id,
                $data['source_type'] ?? PayrollImport::SOURCE_PRIMARY,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['file' => $exception->getMessage()]);
        }

        return to_route('payroll.import.preview', $payrollImport);
    }

    public function preview(Request $request, PayrollImport $payrollImport): View
    {
        $rows = array_values($payrollImport->payload ?? []);
        $totalRows = count($rows);
        $rowsPerPage = 10;
        $lastPage = max(1, (int) ceil($totalRows / $rowsPerPage));
        $currentPage = min(max(1, (int) $request->query('page', 1)), $lastPage);
        $previewRows = new LengthAwarePaginator(
            array_slice($rows, ($currentPage - 1) * $rowsPerPage, $rowsPerPage),
            $totalRows,
            $rowsPerPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ],
        );
        $previewRows->withQueryString();

        return view('payroll.import-preview', [
            'payrollImport' => $payrollImport,
            'previewRows' => $previewRows,
            'changeReport' => $payrollImport->master_changes ?? ['total' => 0, 'items' => []],
        ]);
    }

    public function confirm(Request $request, PayrollImport $payrollImport): RedirectResponse
    {
        try {
            $period = $this->imports->confirm(
                $payrollImport,
                $request->user()?->id,
                $request->ip(),
                (string) $request->userAgent(),
            );
        } catch (InvalidArgumentException|LogicException $exception) {
            return to_route('payroll.import.preview', $payrollImport)
                ->withErrors(['import' => $exception->getMessage()]);
        }

        $masterChanges = $this->imports->lastMasterChanges();
        $status = "Payroll {$period->label()} berhasil diimpor sebagai draf.";

        if ($masterChanges['total'] > 0) {
            $status .= " Terdeteksi {$masterChanges['total']} perubahan data master; rincian ditampilkan di halaman periode.";
        }

        return to_route('payroll.show', $period)
            ->with('status', $status)
            ->with('payroll_changes', $masterChanges);
    }

    public function cancel(PayrollImport $payrollImport): RedirectResponse
    {
        if ($payrollImport->status === PayrollImport::STATUS_PREVIEWED) {
            $payrollImport->update(['status' => PayrollImport::STATUS_CANCELLED]);
        }

        return to_route('payroll.import.create')->with('status', 'Pratinjau import payroll dibatalkan. Tidak ada data yang diubah.');
    }

    public function show(Request $request, PayrollPeriod $payrollPeriod): View
    {
        $input = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:matched,mismatch'],
        ]);
        $search = trim((string) ($input['search'] ?? ''));

        $records = $payrollPeriod->records()
            ->with(['employee', 'bankAccount'])
            ->when($search !== '', function ($query) use ($search): void {
                $term = "%{$search}%";
                $query->where(function ($nested) use ($term): void {
                    $nested->where('employee_name', 'like', $term)
                        ->orWhere('employee_nip', 'like', $term)
                        ->orWhere('skpd_snapshot', 'like', $term);
                });
            })
            ->when($input['status'] ?? null, fn ($query, string $status) => $query->where('reconciliation_status', $status))
            ->orderBy('employee_name')
            ->paginate(20)
            ->withQueryString();

        return view('payroll.show', [
            'period' => $payrollPeriod,
            'records' => $records,
            'search' => $search,
            'statusFilter' => $input['status'] ?? null,
            'mismatchCount' => $payrollPeriod->records()
                ->where('reconciliation_status', EmployeePayroll::RECONCILIATION_MISMATCH)
                ->count(),
            'tppMismatchCount' => $payrollPeriod->records()
                ->where('tpp_reconciliation_status', EmployeePayroll::TPP_RECONCILIATION_MISMATCH)
                ->count(),
        ]);
    }

    public function export(PayrollPeriod $payrollPeriod): StreamedResponse
    {
        return $this->exports->download($payrollPeriod);
    }

    public function exportTpp(PayrollPeriod $payrollPeriod): StreamedResponse
    {
        abort_unless($payrollPeriod->hasTpp(), 404);

        return $this->exports->downloadTpp($payrollPeriod);
    }

    public function slip(Request $request, PayrollPeriod $payrollPeriod, EmployeePayroll $employeePayroll): View
    {
        abort_unless($employeePayroll->payroll_period_id === $payrollPeriod->id, 404);

        return view('payroll.slip', [
            'period' => $payrollPeriod,
            'record' => $employeePayroll->load(['employee', 'bankAccount']),
            'canViewSensitive' => $request->user()?->canViewSensitiveSimpegData() ?? false,
        ]);
    }

    public function lock(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        if ($payrollPeriod->status === PayrollPeriod::STATUS_LOCKED) {
            return back()->with('status', 'Periode payroll tersebut sudah terkunci.');
        }

        if (! $payrollPeriod->records()->exists()) {
            return back()->withErrors(['payroll' => 'Periode payroll belum memiliki data pegawai.']);
        }

        DB::transaction(function () use ($payrollPeriod): void {
            $payrollPeriod->update([
                'status' => PayrollPeriod::STATUS_LOCKED,
                'locked_at' => now(),
            ]);
        });

        return to_route('payroll.show', $payrollPeriod)->with('status', 'Periode payroll berhasil dikunci.');
    }

    public function reject(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        if ($payrollPeriod->status === PayrollPeriod::STATUS_LOCKED) {
            return back()->withErrors(['payroll' => 'Periode payroll yang sudah terkunci tidak dapat ditolak.']);
        }

        if (! $payrollPeriod->records()->exists()) {
            return back()->withErrors(['payroll' => 'Periode payroll belum memiliki data pegawai.']);
        }

        $payrollPeriod->update([
            'status' => PayrollPeriod::STATUS_REJECTED,
            'locked_at' => null,
        ]);

        return to_route('payroll.show', $payrollPeriod)
            ->with('status', "Payroll {$payrollPeriod->label()} ditolak. Periode tersebut sekarang dapat diimpor ulang.");
    }
}
