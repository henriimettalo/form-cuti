<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeChangeLogController extends Controller
{
    private const EVENT = 'employee.master_updated_from_payroll_import';

    /** @var array<string, string> */
    private const FIELD_LABELS = [
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
    ];

    private const SENSITIVE_FIELDS = ['nik', 'npwp', 'spouse_nip'];

    public function index(Request $request): View
    {
        $input = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $search = trim((string) ($input['search'] ?? ''));
        $canViewSensitive = $request->user()?->canViewSensitiveSimpegData() ?? false;

        $logs = AuditLog::query()
            ->where('event', self::EVENT)
            ->where('auditable_type', Employee::class)
            ->with(['user', 'auditable'])
            ->when($search !== '', function ($query) use ($search): void {
                $term = "%{$search}%";
                $query->whereHasMorph('auditable', [Employee::class], function ($employeeQuery) use ($term): void {
                    $employeeQuery->withTrashed()
                        ->where(function ($nested) use ($term): void {
                            $nested->where('nip', 'like', $term)
                                ->orWhere('full_name', 'like', $term);
                        });
                });
            })
            ->when($input['from'] ?? null, fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($input['to'] ?? null, fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        $logs->getCollection()->transform(function (AuditLog $log) use ($canViewSensitive): AuditLog {
            $oldValues = $log->old_values ?? [];
            $newValues = $log->new_values ?? [];
            $fields = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
            $changes = [];

            foreach ($fields as $field) {
                $changes[] = [
                    'label' => self::FIELD_LABELS[$field] ?? $field,
                    'old' => $this->displayValue($field, $oldValues[$field] ?? null, $canViewSensitive),
                    'new' => $this->displayValue($field, $newValues[$field] ?? null, $canViewSensitive),
                ];
            }

            $log->setAttribute('change_items', $changes);

            return $log;
        });

        return view('employees.change-logs.index', [
            'logs' => $logs,
            'search' => $search,
            'from' => $input['from'] ?? null,
            'to' => $input['to'] ?? null,
        ]);
    }

    private function displayValue(string $field, mixed $value, bool $canViewSensitive): string
    {
        if (in_array($field, self::SENSITIVE_FIELDS, true) && ! $canViewSensitive) {
            return '[disembunyikan]';
        }

        if ($value === null || $value === '') {
            return '-';
        }

        return match ($field) {
            'position_type' => Employee::POSITION_TYPES[$value] ?? (string) $value,
            'marital_status' => [1 => 'Menikah', 2 => 'Belum menikah'][$value] ?? (string) $value,
            'spouse_is_pns' => (bool) $value ? 'Ya' : 'Tidak',
            default => (string) $value,
        };
    }
}
