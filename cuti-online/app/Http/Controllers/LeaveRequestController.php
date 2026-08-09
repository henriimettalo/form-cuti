<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceSnapshot;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Official;
use App\Services\LeaveDocumentGenerator;
use App\Services\LeaveDurationCalculator;
use App\Support\EmployeeRankOptions;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveRequestController extends Controller
{
    public function index(): View
    {
        return view('leave-requests.index', [
            'leaveRequests' => LeaveRequest::query()
                ->with(['employee', 'leaveType', 'generatedDocuments'])
                ->latest('created_at')
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        $employees = Employee::query()
            ->with(['department', 'position'])
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();
        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('leave-requests.create', [
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
            'defaultLeaveTypeId' => $leaveTypes->firstWhere('code', 'annual')?->id,
            'authorizedOfficial' => $this->officialData($this->currentOfficial('authorized_official')),
            'sekdaOfficial' => $this->officialData($this->currentOfficial('sekda')),
            'walikotaOfficial' => $this->officialData($this->currentOfficial('walikota')),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(
        Request $request,
        LeaveDurationCalculator $durationCalculator,
        LeaveDocumentGenerator $documentGenerator,
    ): RedirectResponse {
        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'form_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'duration_unit' => ['required', Rule::in(['day', 'month', 'year'])],
            'address_during_leave' => ['nullable', 'string', 'max:2000'],
            'phone_during_leave' => ['required', 'digits_between:8,15'],
            'is_camat' => ['nullable', 'boolean'],
            'plh_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'supervisor_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('is_active', true),
            ],
        ]);

        $existingLeaveRequest = $this->findByIdempotencyKey($data['idempotency_key']);

        if ($existingLeaveRequest !== null) {
            return $this->existingRequestResponse($existingLeaveRequest);
        }

        $isCamat = (bool) ($data['is_camat'] ?? false);
        $authorizedOfficial = $this->currentOfficial('authorized_official');
        $walikotaOfficial = $this->currentOfficial('walikota');
        $sekdaOfficial = $this->currentOfficial('sekda');

        if ($isCamat && $walikotaOfficial === null) {
            return back()
                ->withInput()
                ->withErrors(['authorized_official' => 'Isi data Wali Kota terlebih dahulu.']);
        }

        if (! $isCamat && $authorizedOfficial === null) {
            return back()
                ->withInput()
                ->withErrors(['authorized_official' => 'Isi data pejabat berwenang terlebih dahulu.']);
        }

        $documentTemplate = DocumentTemplate::query()
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();

        $employee = Employee::query()
            ->with([
                'department',
                'position',
                'positionHistories' => fn ($query) => $query->latest('effective_on')->latest('id'),
            ])
            ->findOrFail($data['employee_id']);
        $plhEmployee = null;

        if (! empty($data['plh_employee_id'])) {
            $plhEmployee = Employee::query()
                ->with('position')
                ->where('is_active', true)
                ->find($data['plh_employee_id']);

            if ($plhEmployee === null) {
                return back()
                    ->withInput()
                    ->withErrors(['plh_employee_id' => 'Pilih pegawai aktif sebagai PLH.']);
            }
        }

        // Pemohon Camat: atasan langsung otomatis Sekda.
        if ($isCamat) {
            if ($sekdaOfficial === null) {
                return back()
                    ->withInput()
                    ->withErrors(['supervisor_employee_id' => 'Isi data Sekda terlebih dahulu.']);
            }

            $supervisor = $sekdaOfficial;
        } else {
            $supervisor = Employee::query()
                ->with('position')
                ->where('is_active', true)
                ->find($data['supervisor_employee_id']);

            if ($supervisor === null || $supervisor->id === $employee->id) {
                return back()
                    ->withInput()
                    ->withErrors(['supervisor_employee_id' => 'Pilih pegawai aktif lain sebagai atasan langsung.']);
            }
        }

        if ($plhEmployee !== null && $plhEmployee->id === $supervisor->id) {
            return back()
                ->withInput()
                ->withErrors(['plh_employee_id' => 'Atasan langsung tidak boleh menjadi pejabat berwenang (PLH). Pilih pegawai lain.']);
        }

        $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);
        $isAnnualLeave = $leaveType->code === 'annual';

        if ($isAnnualLeave && $data['duration_unit'] !== 'day') {
            return back()
                ->withInput()
                ->withErrors(['duration_unit' => 'Cuti tahunan harus dihitung dalam satuan hari.']);
        }

        $duration = $durationCalculator->calculate(
            $data['start_date'],
            $data['end_date'],
            $data['duration_unit'],
            $isAnnualLeave,
        );
        $formDate = CarbonImmutable::parse($data['form_date']);
        $balance = LeaveBalance::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_year' => $formDate->year,
            ],
            [
                'current_year_entitlement' => 12,
                'current_year_used' => 0,
                'carryover_n1' => 0,
                'carryover_n2' => 0,
            ],
        );
        $currentRemaining = max(0, $balance->current_year_entitlement - $balance->current_year_used);
        $availableAnnualLeave = $balance->carryover_n2 + $balance->carryover_n1 + $currentRemaining;

        if ($isAnnualLeave && $duration > $availableAnnualLeave) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_date' => sprintf(
                        'Lama cuti (%d hari kerja) melebihi saldo tersedia (%d hari).',
                        $duration,
                        $availableAnnualLeave,
                    ),
                ]);
        }

        try {
            $leaveRequest = DB::transaction(function () use (
                $request,
                $data,
                $employee,
                $supervisor,
                $leaveType,
                $formDate,
                $duration,
                $authorizedOfficial,
                $documentTemplate,
                $balance,
                $currentRemaining,
                $isAnnualLeave,
                $isCamat,
                $plhEmployee,
                $walikotaOfficial,
            ): LeaveRequest {
                $leaveRequest = LeaveRequest::query()->create([
                    'idempotency_key' => $data['idempotency_key'],
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'created_by' => $request->user()?->id,
                    'document_template_id' => $documentTemplate?->id,
                    'form_date' => $formDate,
                    'reason' => $data['reason'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'duration_value' => $duration,
                    'duration_unit' => $data['duration_unit'],
                    'address_during_leave' => $data['address_during_leave'] ?? null,
                    'phone_during_leave' => $data['phone_during_leave'] ?? null,
                    'employee_snapshot' => $this->employeeSnapshot($employee, $formDate),
                    'officials_snapshot' => [
                        'supervisor' => [
                            'full_name' => $supervisor->full_name,
                            'nip' => $supervisor->nip ?? null,
                            'position_title' => $supervisor->position?->name ?? $supervisor->position_title ?? '-',
                        ],
                        'authorized_official' => $this->resolveAuthorizedOfficial(
                            $isCamat,
                            $plhEmployee,
                            $walikotaOfficial,
                            $authorizedOfficial,
                        ),
                    ],
                    'status' => 'draft',
                ]);
                $leaveRequest->update([
                    'request_number' => sprintf('CUTI-%d-%06d', $formDate->year, $leaveRequest->id),
                ]);

                LeaveBalanceSnapshot::query()->create([
                    'leave_request_id' => $leaveRequest->id,
                    'leave_year' => $formDate->year,
                    'n2_remaining' => $balance->carryover_n2,
                    'n1_remaining' => $balance->carryover_n1,
                    'current_year_entitlement' => $balance->current_year_entitlement,
                    'current_year_used_before' => $balance->current_year_used,
                    'current_year_remaining_before' => $currentRemaining,
                    'requested_days' => $isAnnualLeave ? $duration : 0,
                    'current_year_remaining_after' => $isAnnualLeave
                        ? max(0, $currentRemaining - $duration)
                        : $currentRemaining,
                ]);

                return $leaveRequest;
            });
        } catch (QueryException $exception) {
            $existingLeaveRequest = $this->findByIdempotencyKey($data['idempotency_key']);

            if ($existingLeaveRequest !== null) {
                return $this->existingRequestResponse($existingLeaveRequest);
            }

            throw $exception;
        }

        $leaveRequest->load([
            'employee.department',
            'employee.position',
            'leaveType',
            'leaveBalanceSnapshot',
            'documentTemplate',
        ]);
        $documentGenerator->generate($leaveRequest);
        $leaveRequest->update([
            'status' => 'generated',
            'generated_at' => now(),
        ]);

        return to_route('leave-requests.show', $leaveRequest)
            ->with('status', 'Formulir berhasil dibuat dan dokumen Word siap diunduh.');
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $leaveRequest->load([
            'employee.department',
            'employee.position',
            'leaveType',
            'leaveBalanceSnapshot',
            'generatedDocuments',
        ]);

        return view('leave-requests.show', compact('leaveRequest'));
    }

    public function download(GeneratedDocument $generatedDocument): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($generatedDocument->storage_path), 404);

        return Storage::disk('local')->download(
            $generatedDocument->storage_path,
            $generatedDocument->filename,
        );
    }

    /**
     * @return array<string, string>
     */
    private function employeeSnapshot(Employee $employee, CarbonImmutable $formDate): array
    {
        $servicePeriod = '-';

        if ($employee->service_started_on !== null) {
            $interval = $employee->service_started_on->diff($formDate);
            $servicePeriod = sprintf('%d Tahun %02d bulan', $interval->y, $interval->m);
        }

        return [
            'full_name' => $employee->full_name,
            'nip' => $employee->nip,
            'position' => $employee->position?->name ?? $employee->position_title ?? '-',
            'rank_grade' => EmployeeRankOptions::format(
                $employee->employment_status,
                $employee->rank_name,
                $employee->grade,
            ),
            'department' => $employee->positionHistories->firstWhere('department_name')?->department_name
                ?? $employee->department?->name
                ?? '-',
            'service_period' => $servicePeriod,
            'phone' => $employee->phone ?? '-',
        ];
    }

    /**
     * @return array{full_name: string, nip: string|null, position_title: string}|null
     */
    private function officialData(?Official $official): ?array
    {
        if ($official === null) {
            return null;
        }

        return [
            'full_name' => $official->full_name,
            'nip' => $official->nip,
            'position_title' => $official->position_title,
        ];
    }

    private function currentOfficial(string $role): ?Official
    {
        return Official::query()
            ->whereNull('department_id')
            ->where('signature_role', $role)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
    }

    /**
     * Resolve pejabat berwenang untuk pengajuan.
     *
     * PLH dipilih dari pegawai -> pakai data pegawai tersebut.
     * Pemohon Camat tanpa PLH -> Wali Kota.
     * Selain itu -> pejabat berwenang standar.
     *
     * @return array{full_name: string, nip: string|null, position_title: string}
     */
    private function resolveAuthorizedOfficial(
        bool $isCamat,
        ?Employee $plhEmployee,
        ?Official $walikota,
        ?Official $authorizedOfficial,
    ): array {
        if ($plhEmployee !== null) {
            return [
                'full_name' => $plhEmployee->full_name,
                'nip' => $plhEmployee->nip,
                'position_title' => $plhEmployee->position?->name ?? $plhEmployee->position_title ?? '-',
            ];
        }

        if ($isCamat && $walikota !== null) {
            return $this->officialData($walikota);
        }

        return $this->officialData($authorizedOfficial);
    }

    private function findByIdempotencyKey(string $idempotencyKey): ?LeaveRequest
    {
        return LeaveRequest::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    private function existingRequestResponse(LeaveRequest $leaveRequest): RedirectResponse
    {
        return to_route('leave-requests.show', $leaveRequest)
            ->with('status', 'Formulir ini sudah pernah dibuat. Menampilkan formulir yang sama.');
    }
}
