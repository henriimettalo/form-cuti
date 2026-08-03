<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Official;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class LeaveRequestGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_annual_leave_is_selected_by_default_without_overriding_old_input(): void
    {
        $operator = User::factory()->create();
        Employee::query()->create([
            'nip' => '197207151993032009',
            'full_name' => 'Pegawai Formulir',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        $supervisor = Employee::query()->create([
            'nip' => '197207151993032010',
            'full_name' => 'Atasan Formulir',
            'position_title' => 'Kepala Seksi',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        $annualLeave = LeaveType::query()->create([
            'code' => 'annual',
            'name' => 'Cuti Tahunan',
            'requires_attachment' => false,
            'is_active' => true,
        ]);
        $sickLeave = LeaveType::query()->create([
            'code' => 'sick',
            'name' => 'Cuti Sakit',
            'requires_attachment' => true,
            'is_active' => true,
        ]);

        $this->actingAs($operator)->get(route('leave-requests.create'))
            ->assertOk()
            ->assertSee('data-employee-search', false)
            ->assertSee('data-employee-combobox', false)
            ->assertSee('data-employee-option-index', false)
            ->assertSee('suri*008', false)
            ->assertSee('data-employee-search-result', false)
            ->assertDontSee('<select class="form-select mt-2" id="employee_id"', false)
            ->assertSee('data-supervisor-select', false)
            ->assertSee('data-supervisor-combobox', false)
            ->assertSee('data-supervisor-option', false)
            ->assertSee('data-supervisor-option-index', false)
            ->assertSee('Cari nama atau NIP, mis. suri*008', false)
            ->assertSee('supervisor-search-result', false)
            ->assertSee('Langkah 2 dari 4', false)
            ->assertSee('Pilih hari satu per satu secara berurutan.', false)
            ->assertSee('data-date-range-picker', false)
            ->assertSee('data-date-range-grid', false)
            ->assertSee('data-date-range-selection-help', false)
            ->assertSee('id="start_date" type="hidden" name="start_date"', false)
            ->assertSee('id="end_date" type="hidden" name="end_date"', false)
            ->assertDontSee('<select class="form-select" id="supervisor_employee_id"', false)
            ->assertSee('<textarea class="form-textarea resize-none cursor-not-allowed bg-slate-50 text-slate-600" id="supervisor_position_title"', false)
            ->assertSee('<textarea class="form-textarea resize-none cursor-not-allowed bg-slate-50 text-slate-600" id="official_position_title"', false)
            ->assertSee("value=\"{$annualLeave->id}\" data-code=\"annual\" selected", false)
            ->assertDontSee("value=\"{$sickLeave->id}\" data-code=\"sick\" selected", false);

        $this->actingAs($operator)
            ->withSession(['_old_input' => [
                'leave_type_id' => (string) $sickLeave->id,
                'supervisor_employee_id' => (string) $supervisor->id,
            ]])
            ->get(route('leave-requests.create'))
            ->assertOk()
            ->assertSee("value=\"{$sickLeave->id}\" data-code=\"sick\" selected", false)
            ->assertSee("id=\"supervisor_employee_id\" type=\"hidden\" name=\"supervisor_employee_id\" value=\"{$supervisor->id}\"", false)
            ->assertSee('data-full-name="Atasan Formulir" data-nip="197207151993032010" data-position-title="Kepala Seksi"', false)
            ->assertDontSee("value=\"{$annualLeave->id}\" data-code=\"annual\" selected", false);
    }

    public function test_operator_can_create_a_leave_request_and_downloadable_docx(): void
    {
        Storage::fake('local');

        $department = Department::query()->create([
            'name' => 'Kantor Camat Pontianak Selatan',
            'is_active' => true,
        ]);
        $position = Position::query()->create([
            'name' => 'Pengelola Layanan Operasional',
            'is_active' => true,
        ]);
        $employee = Employee::query()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => '197207151993032008',
            'full_name' => 'Pegawai Uji',
            'position_title' => $position->name,
            'rank_name' => 'Penata Muda Tk. I',
            'grade' => 'III/b',
            'employment_status' => 'PNS',
            'service_started_on' => '1993-03-15',
            'is_active' => true,
        ]);
        $supervisor = Employee::query()->create([
            'department_id' => $department->id,
            'nip' => '199008152014021001',
            'full_name' => 'Atasan Uji',
            'position_title' => 'Kasi Pemerintahan Umum',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        LeaveBalance::query()->create([
            'employee_id' => $employee->id,
            'leave_year' => 2026,
            'current_year_entitlement' => 12,
            'current_year_used' => 0,
            'carryover_n1' => 3,
            'carryover_n2' => 0,
        ]);
        $leaveType = LeaveType::query()->create([
            'code' => 'annual',
            'name' => 'Cuti Tahunan',
            'requires_attachment' => false,
            'is_active' => true,
        ]);
        Official::query()->create([
            'department_id' => null,
            'signature_role' => 'authorized_official',
            'full_name' => 'Pejabat Master',
            'nip' => '198601222004122001',
            'position_title' => 'Camat Pontianak Selatan',
            'is_active' => true,
        ]);

        $operator = User::factory()->create();
        $payload = [
            'idempotency_key' => '51f8e5d9-cdd5-4b11-bec6-95d4d9d1e797',
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'form_date' => '2026-07-27',
            'reason' => 'Cuti Tahunan',
            'start_date' => '2026-07-29',
            'end_date' => '2026-07-30',
            'duration_unit' => 'day',
            'address_during_leave' => 'Pontianak',
            'phone_during_leave' => '081345504637',
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_name' => 'Atasan Tidak Sah',
            'supervisor_nip' => '000000000000000000',
            'supervisor_position_title' => 'Jabatan Tidak Sah',
            'official_name' => 'Pejabat Tidak Sah',
            'official_nip' => '000000000000000000',
            'official_position_title' => 'Jabatan Tidak Sah',
        ];

        $response = $this->actingAs($operator)->post(route('leave-requests.store'), $payload);

        $leaveRequest = LeaveRequest::query()->firstOrFail();
        $document = GeneratedDocument::query()->firstOrFail();

        $this->actingAs($operator)->post(route('leave-requests.store'), $payload)
            ->assertRedirect(route('leave-requests.show', $leaveRequest));

        $response->assertRedirect(route('leave-requests.show', $leaveRequest));
        $this->assertSame('generated', $leaveRequest->status);
        $this->assertSame(auth()->id(), $leaveRequest->created_by);
        $this->assertSame(1, LeaveRequest::query()->count());
        $this->assertSame(1, GeneratedDocument::query()->count());
        $this->assertSame('Penata Muda Tk. I (III/b)', $leaveRequest->employee_snapshot['rank_grade']);
        $this->assertSame([
            'full_name' => 'Atasan Uji',
            'nip' => '199008152014021001',
            'position_title' => 'Kasi Pemerintahan Umum',
        ], $leaveRequest->officials_snapshot['supervisor']);
        $this->assertSame([
            'full_name' => 'Pejabat Master',
            'nip' => '198601222004122001',
            'position_title' => 'Camat Pontianak Selatan',
        ], $leaveRequest->officials_snapshot['authorized_official']);
        $this->assertDatabaseHas('leave_balance_snapshots', [
            'leave_request_id' => $leaveRequest->id,
            'requested_days' => 2,
        ]);
        Storage::disk('local')->assertExists($document->storage_path);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open(Storage::disk('local')->path($document->storage_path)) === true);
        $documentXml = $archive->getFromName('word/document.xml');
        $archive->close();

        $this->assertStringContainsString('FORMULIR PERMINTAAN DAN PEMBERIAN CUTI', $documentXml);
        $this->assertStringContainsString('Pegawai Uji', $documentXml);
        $this->assertStringContainsString('Penata Muda Tk. I (III/b)', $documentXml);
        $this->assertStringContainsString('Pejabat Master', $documentXml);
        $this->assertStringNotContainsString('Pejabat Tidak Sah', $documentXml);

        $this->get(route('documents.download', $document))
            ->assertOk()
            ->assertDownload($document->filename);
    }

    public function test_employee_cannot_select_themselves_as_direct_supervisor(): void
    {
        $employee = Employee::query()->create([
            'nip' => '197207151993032010',
            'full_name' => 'Pegawai Tanpa Atasan',
            'position_title' => 'Pengelola Layanan Operasional',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        $leaveType = LeaveType::query()->create([
            'code' => 'annual',
            'name' => 'Cuti Tahunan',
            'requires_attachment' => false,
            'is_active' => true,
        ]);
        Official::query()->create([
            'department_id' => null,
            'signature_role' => 'authorized_official',
            'full_name' => 'Pejabat Master',
            'position_title' => 'Camat Pontianak Selatan',
            'is_active' => true,
        ]);

        $this->from(route('leave-requests.create'))
            ->actingAs(User::factory()->create())
            ->post(route('leave-requests.store'), [
                'idempotency_key' => '4142fc19-dbf2-4eb7-9fb9-1ccde78696c8',
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'form_date' => '2026-07-27',
                'reason' => 'Cuti Tahunan',
                'start_date' => '2026-07-29',
                'end_date' => '2026-07-30',
                'duration_unit' => 'day',
                'supervisor_employee_id' => $employee->id,
                'phone_during_leave' => '081345504637',
            ])
            ->assertRedirect(route('leave-requests.create'))
            ->assertSessionHasErrors('supervisor_employee_id');

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_direct_supervisor_cannot_also_be_the_plh(): void
    {
        $employee = Employee::query()->create([
            'nip' => '198601222004122001',
            'full_name' => 'Pegawai Pemohon',
            'position_title' => 'Kepala Seksi Pemerintahan Umum Kecamatan Pontianak Selatan',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        $supervisor = Employee::query()->create([
            'nip' => '197207151993032010',
            'full_name' => 'Pegawai Atasan',
            'position_title' => 'Sekretaris Kecamatan Pontianak Selatan',
            'employment_status' => 'PNS',
            'is_active' => true,
        ]);
        $leaveType = LeaveType::query()->create([
            'code' => 'annual',
            'name' => 'Cuti Tahunan',
            'requires_attachment' => false,
            'is_active' => true,
        ]);
        Official::query()->create([
            'department_id' => null,
            'signature_role' => 'authorized_official',
            'full_name' => 'Pejabat Master',
            'position_title' => 'Camat Pontianak Selatan',
            'is_active' => true,
        ]);

        $this->from(route('leave-requests.create'))
            ->actingAs(User::factory()->create())
            ->post(route('leave-requests.store'), [
                'idempotency_key' => '8d1c2f3e-4a5b-6c7d-8e9f-0a1b2c3d4e5f',
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'form_date' => '2026-08-02',
                'reason' => 'Cuti Tahunan',
                'start_date' => '2026-08-04',
                'end_date' => '2026-08-05',
                'duration_unit' => 'day',
                'supervisor_employee_id' => $supervisor->id,
                'plh_employee_id' => $supervisor->id,
                'phone_during_leave' => '081345504637',
            ])
            ->assertRedirect(route('leave-requests.create'))
            ->assertSessionHasErrors('plh_employee_id');

        $this->assertDatabaseCount('leave_requests', 0);
    }
}
