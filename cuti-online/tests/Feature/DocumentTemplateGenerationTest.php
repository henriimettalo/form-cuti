<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentTemplate;
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
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;
use ZipArchive;

class DocumentTemplateGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_template_fills_placeholders_including_table_leave_marks(): void
    {
        Storage::fake('local');

        $template = $this->createTemplate();
        [$operator, $employee, $supervisor, $leaveType] = $this->seedLeaveData();

        $this->actingAs($operator)
            ->post(route('leave-requests.store'), [
                'idempotency_key' => 'a752a116-2b5b-4de7-ae5c-f2b0bb8c4aa3',
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'form_date' => '2026-07-27',
                'reason' => 'Keperluan keluarga',
                'start_date' => '2026-07-29',
                'end_date' => '2026-07-30',
                'duration_unit' => 'day',
                'address_during_leave' => 'Pontianak',
                'phone_during_leave' => '081345504637',
                'supervisor_employee_id' => $supervisor->id,
            ])
            ->assertRedirect();

        $leaveRequest = LeaveRequest::query()->sole();
        $document = GeneratedDocument::query()->sole();

        $this->assertSame($template->id, $leaveRequest->document_template_id);
        $this->assertSame($template->version, $document->template_version);
        $this->assertSame('document-template', $document->metadata['generator']);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open(Storage::disk('local')->path($document->storage_path)) === true);
        $documentXml = $archive->getFromName('word/document.xml');
        $archive->close();

        $this->assertStringContainsString('Pegawai Uji', $documentXml);
        $this->assertStringContainsString('197207151993032008', $documentXml);
        $this->assertStringContainsString('Keperluan keluarga', $documentXml);
        $this->assertStringContainsString('Camat Pontianak Selatan', $documentXml);
        $this->assertStringContainsString('√', $documentXml);
        $this->assertStringNotContainsString('{{employee_name}}', $documentXml);
        $this->assertStringNotContainsString('{{leave_sick_mark}}', $documentXml);
    }

    /**
     * @return array{0: User, 1: Employee, 2: Employee, 3: LeaveType}
     */
    private function seedLeaveData(): array
    {
        $department = Department::query()->create([
            'name' => 'Kecamatan Pontianak Selatan',
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
            'phone' => '081234567890',
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

        return [User::factory()->create(), $employee, $supervisor, $leaveType];
    }

    private function createTemplate(): DocumentTemplate
    {
        $path = 'leave-templates/formulir-cuti.docx';
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('FORMULIR PERMINTAAN CUTI');
        $employeeRun = $section->addTextRun();
        $employeeRun->addText('Nama: ');
        $employeeRun->addText('{{employee_');
        $employeeRun->addText('name}}');
        $section->addText('NIP: {{employee_nip}}');
        $section->addText('Jabatan: {{employee_position}}');
        $section->addText('Alasan: {{leave_reason}}');
        $section->addText('Pejabat: {{official_position}}');

        $table = $section->addTable();
        $row = $table->addRow();
        $row->addCell()->addText('1. Cuti Tahunan {{leave_annual_mark}}');
        $row->addCell()->addText('2. Cuti Besar {{leave_large_mark}}');
        $row = $table->addRow();
        $row->addCell()->addText('3. Cuti Sakit {{leave_sick_mark}}');
        $row->addCell()->addText('4. Cuti Melahirkan {{leave_maternity_mark}}');

        Storage::disk('local')->makeDirectory('leave-templates');
        IOFactory::createWriter($phpWord, 'Word2007')->save(Storage::disk('local')->path($path));

        return DocumentTemplate::query()->create([
            'slug' => 'formulir-cuti',
            'name' => 'Formulir Cuti',
            'version' => '202607310001',
            'storage_path' => $path,
            'is_active' => true,
        ]);
    }
}
