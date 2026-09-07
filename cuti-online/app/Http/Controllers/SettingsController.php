<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Official;
use App\Models\OrganizationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $organizationProfile = OrganizationProfile::current();
        $department = Department::query()
            ->where('name', $organizationProfile->name)
            ->first();

        return view('settings.index', [
            'canViewSensitive' => $request->user()?->canViewSensitiveSimpegData() ?? false,
            'currentYear' => now()->year,
            'employees' => Employee::query()
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get(),
            'organizationProfile' => $organizationProfile,
            'balances' => LeaveBalance::query()
                ->with('employee')
                ->where('leave_year', now()->year)
                ->orderByDesc('updated_at')
                ->get(),
            'supervisor' => $department === null ? null : Official::query()
                ->where('department_id', $department->id)
                ->where('is_active', true)
                ->where('signature_role', 'direct_supervisor')
                ->latest('updated_at')
                ->first(),
            'backups' => $this->backupList(),
        ]);
    }

    /**
     * @return list<array{file: string, name: string, size: int, size_human: string, modified_at: string}>
     */
    private function backupList(): array
    {
        $dir = storage_path('app/backups');

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.'/*.sqlite') ?: [];
        $items = [];

        foreach ($files as $file) {
            $size = (int) filesize($file);
            $items[] = [
                'file' => $file,
                'name' => basename($file),
                'size' => $size,
                'size_human' => $size >= 1048576
                    ? number_format($size / 1048576, 1).' MB'
                    : number_format($size / 1024, 0).' KB',
                'modified_at' => \Illuminate\Support\Carbon::createFromTimestamp(filemtime($file))->format('d M Y H:i'),
            ];
        }

        usort($items, fn ($a, $b) => filemtime($b['file']) <=> filemtime($a['file']));

        return $items;
    }

    public function storeBalance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')],
            'leave_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'current_year_entitlement' => ['required', 'integer', 'min:0', 'max:366'],
            'current_year_used' => ['required', 'integer', 'min:0', 'max:366'],
            'carryover_n1' => ['required', 'integer', 'min:0', 'max:366'],
            'carryover_n2' => ['required', 'integer', 'min:0', 'max:366'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        LeaveBalance::query()->updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'leave_year' => $data['leave_year'],
            ],
            [
                'current_year_entitlement' => $data['current_year_entitlement'],
                'current_year_used' => $data['current_year_used'],
                'carryover_n1' => $data['carryover_n1'],
                'carryover_n2' => $data['carryover_n2'],
                'notes' => $data['notes'] ?? null,
            ],
        );

        return to_route('settings.index')->with('status', 'Saldo cuti berhasil disimpan.');
    }

    public function storeSupervisor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:32'],
            'position_title' => ['required', 'string', 'max:255'],
        ]);
        $department = OrganizationProfile::current()->resolveDepartment();

        Official::query()->updateOrCreate(
            [
                'department_id' => $department->id,
                'signature_role' => 'direct_supervisor',
                'is_active' => true,
            ],
            [
                'full_name' => $data['full_name'],
                'nip' => $data['nip'] ?? null,
                'position_title' => $data['position_title'],
            ],
        );

        return to_route('settings.index')->with('status', 'Data atasan langsung berhasil disimpan.');
    }

    public function resetData(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canViewSensitiveSimpegData() ?? false, 403);

        $request->validate([
            'confirm_text' => ['required', 'string', 'in:RESET'],
        ], [
            'confirm_text.in' => 'Ketik RESET untuk mengonfirmasi.',
        ]);

        // Backup database sebelum reset.
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $backupName = 'backup-'.now()->format('Y-m-d-His').'.sqlite';
        $backupPath = $backupDir.'/'.$backupName;

        if ($this->databaseDriver() === 'sqlite') {
            copy(config('database.connections.sqlite.database'), $backupPath);
        } else {
            $connection = config('database.connections.pgsql');
            $cmd = sprintf(
                'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -d %s -F c -f %s 2>/dev/null',
                escapeshellarg((string) ($connection['password'] ?? '')),
                escapeshellarg((string) $connection['host']),
                escapeshellarg((string) $connection['port']),
                escapeshellarg((string) $connection['username']),
                escapeshellarg((string) $connection['database']),
                escapeshellarg($backupPath),
            );

            if (shell_exec('command -v pg_dump') === null) {
                return back()->withErrors(['reset_data' => 'pg_dump tidak tersedia di server. Instal postgresql-client untuk mengaktifkan backup sebelum reset.']);
            }

            $exitCode = 0;
            $output = shell_exec($cmd.'; echo __RC__$?');
            $rcMatch = preg_match('/__RC__(\d+)$/', (string) $output, $m);

            if ($rcMatch !== 1 || (int) $m[1] !== 0 || ! is_file($backupPath)) {
                return back()->withErrors(['reset_data' => 'Backup database gagal. Reset dibatalkan.']);
            }
        }

        DB::transaction(function (): void {
            // Urutan dari tabel anak ke induk agar FK terpenuhi.
            foreach ([
                'generated_documents',
                'leave_balance_snapshots',
                'leave_balances',
                'leave_requests',
                'employee_payrolls',
                'payroll_imports',
                'payroll_periods',
                'employee_salary_histories',
                'employee_position_histories',
                'employee_rank_histories',
                'employee_bank_accounts',
                'employee_imports',
                'employees',
                'audit_logs',
                'personal_access_tokens',
            ] as $table) {
                DB::table($table)->delete();
            }
        });

        return to_route('settings.index')
            ->with('status', "Semua data transaksional telah dihapus. Salinan database tersimpan di ".basename($backupPath).'.');
    }

    private function databaseDriver(): string
    {
        return config('database.default');
    }

    public function downloadBackup(Request $request, string $file): StreamedResponse
    {
        abort_unless($request->user()?->canViewSensitiveSimpegData() ?? false, 403);

        $base = realpath(storage_path('app/backups'));
        $candidate = realpath(storage_path('app/backups').'/'.basename($file));

        if ($base === false || $candidate === false || ! str_starts_with($candidate, $base.'/')) {
            abort(404, 'Backup tidak ditemukan.');
        }

        if (! is_file($candidate)) {
            abort(404, 'Backup tidak ditemukan.');
        }

        return response()->streamDownload(function () use ($candidate): void {
            readfile($candidate);
        }, basename($candidate), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
