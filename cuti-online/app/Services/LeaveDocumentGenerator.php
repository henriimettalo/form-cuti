<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\GeneratedDocument;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class LeaveDocumentGenerator
{
    public function __construct(private readonly LeaveDocumentTemplateRenderer $templateRenderer) {}

    /**
     * Generate a printable DOCX from an immutable request snapshot.
     */
    public function generate(LeaveRequest $leaveRequest): GeneratedDocument
    {
        $leaveRequest->loadMissing([
            'employee.department',
            'employee.position',
            'leaveType',
            'leaveBalanceSnapshot',
            'documentTemplate',
        ]);

        $template = $leaveRequest->documentTemplate;

        if ($template !== null && Storage::disk('local')->exists($template->storage_path)) {
            return $this->generateFromTemplate($leaveRequest, $template);
        }

        return $this->generateDefault($leaveRequest);
    }

    private function generateFromTemplate(LeaveRequest $leaveRequest, DocumentTemplate $template): GeneratedDocument
    {
        $target = $this->outputTarget($leaveRequest);
        $this->templateRenderer->render($template, $leaveRequest, $target['absolute_path']);

        return $this->recordGeneratedDocument(
            $leaveRequest,
            $target,
            $template->version,
            [
                'generator' => 'document-template',
                'template_id' => $template->id,
                'leave_type' => $leaveRequest->leaveType->code,
            ],
        );
    }

    private function generateDefault(LeaveRequest $leaveRequest): GeneratedDocument
    {
        $leaveRequest->loadMissing([
            'employee.department',
            'employee.position',
            'leaveType',
            'leaveBalanceSnapshot',
        ]);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => Converter::cmToTwip(1.3),
            'marginBottom' => Converter::cmToTwip(1.3),
            'marginLeft' => Converter::cmToTwip(1.5),
            'marginRight' => Converter::cmToTwip(1.5),
        ]);

        $employee = $leaveRequest->employee_snapshot;
        $officials = $leaveRequest->officials_snapshot ?? [];
        $balance = $leaveRequest->leaveBalanceSnapshot;

        $this->addHeading($section, $leaveRequest);
        $this->addEmployeeSection($section, $employee);
        $this->addLeaveTypeSection($section, $leaveRequest);
        $this->addLeaveDetailsSection($section, $leaveRequest);
        $this->addBalanceSection($section, $balance);
        $this->addContactAndSignatureSection($section, $leaveRequest, $employee, $officials);
        $this->addDecisionSections($section, $officials);
        $this->addNotes($section);

        $target = $this->outputTarget($leaveRequest);
        IOFactory::createWriter($phpWord, 'Word2007')->save($target['absolute_path']);

        return $this->recordGeneratedDocument(
            $leaveRequest,
            $target,
            'generator-v1',
            [
                'generator' => 'phpword',
                'leave_type' => $leaveRequest->leaveType->code,
            ],
        );
    }

    /**
     * @return array{path: string, filename: string, absolute_path: string}
     */
    private function outputTarget(LeaveRequest $leaveRequest): array
    {
        $employee = $leaveRequest->employee_snapshot ?? [];
        $directory = 'leave-documents/'.CarbonImmutable::parse($leaveRequest->form_date)->year;
        $filename = sprintf(
            '%s-%s.docx',
            $leaveRequest->request_number ?? 'CUTI-'.$leaveRequest->id,
            Str::slug((string) ($employee['full_name'] ?? 'pegawai')),
        );
        $path = $directory.'/'.$filename;
        $disk = Storage::disk('local');
        $disk->makeDirectory($directory);

        return [
            'path' => $path,
            'filename' => $filename,
            'absolute_path' => $disk->path($path),
        ];
    }

    /**
     * @param  array{path: string, filename: string, absolute_path: string}  $target
     * @param  array<string, mixed>  $metadata
     */
    private function recordGeneratedDocument(
        LeaveRequest $leaveRequest,
        array $target,
        string $templateVersion,
        array $metadata,
    ): GeneratedDocument {
        return GeneratedDocument::query()->create([
            'leave_request_id' => $leaveRequest->id,
            'generated_by' => $leaveRequest->created_by,
            'format' => 'docx',
            'storage_path' => $target['path'],
            'filename' => $target['filename'],
            'file_size' => filesize($target['absolute_path']) ?: null,
            'checksum' => hash_file('sha256', $target['absolute_path']) ?: null,
            'template_version' => $templateVersion,
            'metadata' => $metadata,
            'generated_at' => now(),
        ]);
    }

    private function addHeading(Section $section, LeaveRequest $leaveRequest): void
    {
        $section->addText(
            'Pontianak, '.$this->formatIndonesianDate(CarbonImmutable::parse($leaveRequest->form_date)),
            ['size' => 10],
            ['alignment' => Jc::END, 'spaceAfter' => 180],
        );
        $section->addText('Kepada', ['size' => 10]);
        $section->addText('Yth. Wali Kota Pontianak', ['size' => 10]);
        $section->addText('u.p. Kepala BKPSDM Kota Pontianak', ['size' => 10]);
        $section->addText('di -', ['size' => 10]);
        $section->addText('PONTIANAK', ['size' => 10, 'bold' => true], ['indentation' => ['left' => 720], 'spaceAfter' => 220]);
        $section->addText(
            'FORMULIR PERMINTAAN DAN PEMBERIAN CUTI',
            ['size' => 11, 'bold' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 150],
        );
    }

    /**
     * @param  array<string, mixed>  $employee
     */
    private function addEmployeeSection(Section $section, array $employee): void
    {
        $this->addSectionLabel($section, 'DATA PEGAWAI');
        $this->addKeyValueTable($section, [
            'Nama' => $employee['full_name'] ?? '-',
            'NIP' => $employee['nip'] ?? '-',
            'Jabatan' => $employee['position'] ?? '-',
            'Pangkat/Golongan' => $employee['rank_grade'] ?? '-',
            'Unit Kerja' => $employee['department'] ?? '-',
            'Masa Kerja' => $employee['service_period'] ?? '-',
        ]);
    }

    private function addLeaveTypeSection(Section $section, LeaveRequest $leaveRequest): void
    {
        $this->addSectionLabel($section, 'JENIS CUTI YANG DIAMBIL');

        $types = [
            'annual' => 'Cuti Tahunan',
            'long' => 'Cuti Besar',
            'sick' => 'Cuti Sakit',
            'maternity' => 'Cuti Melahirkan',
            'important-reason' => 'Cuti Karena Alasan Penting',
            'unpaid' => 'Cuti di Luar Tanggungan Negara',
        ];

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '9CA3AF',
            'cellMargin' => 80,
            'alignment' => JcTable::CENTER,
        ]);

        foreach (array_chunk($types, 2, true) as $row) {
            $table->addRow();

            foreach ($row as $code => $name) {
                $marker = $leaveRequest->leaveType->code === $code ? '√' : '☐';
                $table->addCell(4500)->addText($marker.'  '.$name, ['size' => 9]);
            }

            if (count($row) === 1) {
                $table->addCell(4500);
            }
        }
    }

    private function addLeaveDetailsSection(Section $section, LeaveRequest $leaveRequest): void
    {
        $this->addSectionLabel($section, 'ALASAN CUTI');
        $section->addText($leaveRequest->reason ?: '-', ['size' => 10], ['spaceAfter' => 100]);

        $this->addSectionLabel($section, 'LAMANYA CUTI');
        $this->addKeyValueTable($section, [
            'Selama' => sprintf(
                '%d %s',
                $leaveRequest->duration_value,
                $this->durationUnitLabel($leaveRequest->duration_unit),
            ),
            'Mulai tanggal' => $this->formatIndonesianDate(CarbonImmutable::parse($leaveRequest->start_date)),
            'Sampai dengan' => $this->formatIndonesianDate(CarbonImmutable::parse($leaveRequest->end_date)),
        ]);
    }

    private function addBalanceSection(Section $section, mixed $balance): void
    {
        $this->addSectionLabel($section, 'CATATAN CUTI');

        if ($balance === null) {
            $section->addText('Tidak ada catatan saldo cuti tahunan untuk formulir ini.', ['size' => 9]);

            return;
        }

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '9CA3AF',
            'cellMargin' => 80,
            'alignment' => JcTable::CENTER,
        ]);
        $table->addRow();
        $table->addCell(2200)->addText('Periode', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(2000)->addText('Sisa', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(4800)->addText('Keterangan', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);

        $rows = [
            ['N-2', (string) $balance->n2_remaining, '-'],
            ['N-1', (string) $balance->n1_remaining, '-'],
            [
                'N',
                (string) $balance->current_year_remaining_before,
                sprintf(
                    'Permohonan %d hari; sisa setelah formulir %d hari.',
                    $balance->requested_days,
                    $balance->current_year_remaining_after,
                ),
            ],
        ];

        foreach ($rows as [$period, $remaining, $note]) {
            $table->addRow();
            $table->addCell(2200)->addText($period, ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(2000)->addText($remaining, ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(4800)->addText($note, ['size' => 9]);
        }
    }

    /**
     * @param  array<string, mixed>  $employee
     * @param  array<string, mixed>  $officials
     */
    private function addContactAndSignatureSection(
        Section $section,
        LeaveRequest $leaveRequest,
        array $employee,
        array $officials,
    ): void {
        $this->addSectionLabel($section, 'ALAMAT SELAMA MENJALANKAN CUTI');
        $this->addKeyValueTable($section, [
            'Alamat' => $leaveRequest->address_during_leave ?: '-',
            'Telepon' => $leaveRequest->phone_during_leave ?: '-',
        ]);

        $table = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 70,
            'alignment' => JcTable::CENTER,
        ]);
        $table->addRow();
        $table->addCell(4500);
        $table->addCell(4500)->addText('Hormat saya,', ['size' => 10], ['alignment' => Jc::CENTER]);
        $table->addRow(850);
        $table->addCell(4500);
        $table->addCell(4500);
        $table->addRow();
        $table->addCell(4500);
        $table->addCell(4500)->addText($employee['full_name'] ?? '-', ['size' => 10, 'bold' => true], ['alignment' => Jc::CENTER]);
        $table->addRow();
        $table->addCell(4500);
        $table->addCell(4500)->addText('NIP. '.($employee['nip'] ?? '-'), ['size' => 9], ['alignment' => Jc::CENTER]);
    }

    /**
     * @param  array<string, mixed>  $officials
     */
    private function addDecisionSections(Section $section, array $officials): void
    {
        $this->addSectionLabel($section, 'PERTIMBANGAN ATASAN LANGSUNG');
        $this->addDecisionBlock(
            $section,
            $officials['supervisor'] ?? [],
            'Atasan Langsung',
        );

        $this->addSectionLabel($section, 'KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI');
        $this->addDecisionBlock(
            $section,
            $officials['authorized_official'] ?? [],
            'Pejabat Berwenang',
        );
    }

    /**
     * @param  array<string, mixed>  $official
     */
    private function addDecisionBlock(Section $section, array $official, string $fallbackTitle): void
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '9CA3AF',
            'cellMargin' => 80,
            'alignment' => JcTable::CENTER,
        ]);
        $table->addRow();
        $table->addCell(4500)->addText('☐ DISETUJUI', ['size' => 9]);
        $table->addCell(4500)->addText('☐ PERUBAHAN / DITANGGUHKAN / TIDAK DISETUJUI', ['size' => 9]);
        $table->addRow(1100);
        $table->addCell(4500)->addText(
            $official['position_title'] ?? $fallbackTitle,
            ['size' => 9],
            ['alignment' => Jc::CENTER],
        );
        $table->addCell(4500);
        $table->addRow();
        $table->addCell(4500)->addText(
            $official['full_name'] ?? '-',
            ['size' => 9, 'bold' => true],
            ['alignment' => Jc::CENTER],
        );
        $table->addCell(4500);
        $table->addRow();
        $table->addCell(4500)->addText(
            'NIP. '.($official['nip'] ?? '-'),
            ['size' => 9],
            ['alignment' => Jc::CENTER],
        );
        $table->addCell(4500);
    }

    private function addNotes(Section $section): void
    {
        $section->addText('Catatan:', ['size' => 8, 'bold' => true], ['spaceBefore' => 140]);
        $section->addText('1. Coret yang tidak perlu.', ['size' => 8]);
        $section->addText('2. Pilih satu jenis cuti dengan memberi tanda centang.', ['size' => 8]);
        $section->addText('3. Kolom keputusan diisi dan ditandatangani secara manual.', ['size' => 8]);
    }

    /**
     * @param  array<string, string>  $rows
     */
    private function addKeyValueTable(Section $section, array $rows): void
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '9CA3AF',
            'cellMargin' => 80,
            'alignment' => JcTable::CENTER,
        ]);

        foreach ($rows as $label => $value) {
            $table->addRow();
            $table->addCell(2500)->addText($label, ['size' => 9]);
            $table->addCell(6500)->addText($value, ['size' => 9]);
        }
    }

    private function addSectionLabel(Section $section, string $label): void
    {
        $section->addText(
            $label,
            ['size' => 9, 'bold' => true],
            ['spaceBefore' => 130, 'spaceAfter' => 60],
        );
    }

    private function durationUnitLabel(string $unit): string
    {
        return match ($unit) {
            'month' => 'bulan',
            'year' => 'tahun',
            default => 'hari',
        };
    }

    private function formatIndonesianDate(CarbonImmutable $date): string
    {
        $months = [
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
        ];

        return sprintf('%d %s %d', $date->day, $months[$date->month], $date->year);
    }
}
