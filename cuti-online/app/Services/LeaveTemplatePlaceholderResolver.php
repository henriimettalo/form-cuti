<?php

namespace App\Services;

use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;

class LeaveTemplatePlaceholderResolver
{
    /**
     * @return array<string, array<string, string>>
     */
    public function placeholderGroups(): array
    {
        return [
            'Identitas formulir' => [
                'request_number' => 'Nomor formulir cuti.',
                'form_date' => 'Tanggal formulir, misalnya 27 Juli 2026.',
            ],
            'Data pegawai' => [
                'employee_name' => 'Nama lengkap pegawai.',
                'employee_nip' => 'NIP pegawai.',
                'employee_position' => 'Jabatan pegawai.',
                'employee_rank_grade' => 'Pangkat/golongan pegawai.',
                'employee_department' => 'Unit kerja/instansi pegawai.',
                'employee_service_period' => 'Masa kerja pada tanggal formulir.',
                'employee_phone' => 'Nomor HP dari data pegawai.',
            ],
            'Permohonan cuti' => [
                'leave_type' => 'Nama jenis cuti yang dipilih.',
                'leave_reason' => 'Alasan cuti.',
                'leave_duration' => 'Lama cuti, misalnya 3 hari.',
                'leave_start_date' => 'Tanggal mulai cuti.',
                'leave_end_date' => 'Tanggal selesai cuti.',
                'leave_address' => 'Alamat selama menjalankan cuti.',
                'leave_phone' => 'Nomor HP selama menjalankan cuti.',
            ],
            'Catatan saldo' => [
                'balance_n2' => 'Sisa cuti tahun N-2.',
                'balance_n1' => 'Sisa cuti tahun N-1.',
                'balance_n' => 'Sisa cuti tahun berjalan sebelum permohonan.',
                'balance_requested_days' => 'Jumlah hari cuti tahunan yang dimohonkan.',
                'balance_remaining_after' => 'Sisa cuti tahun berjalan setelah permohonan.',
            ],
            'Pejabat dan tanda jenis cuti' => [
                'supervisor_name' => 'Nama atasan langsung.',
                'supervisor_nip' => 'NIP atasan langsung.',
                'supervisor_position' => 'Jabatan atasan langsung.',
                'official_name' => 'Nama pejabat berwenang.',
                'official_nip' => 'NIP pejabat berwenang.',
                'official_position' => 'Jabatan pejabat berwenang.',
                'leave_annual_mark' => 'Tanda √ untuk Cuti Tahunan.',
                'leave_large_mark' => 'Tanda √ untuk Cuti Besar.',
                'leave_sick_mark' => 'Tanda √ untuk Cuti Sakit.',
                'leave_maternity_mark' => 'Tanda √ untuk Cuti Melahirkan.',
                'leave_important_reason_mark' => 'Tanda √ untuk Cuti Karena Alasan Penting.',
                'leave_unpaid_mark' => 'Tanda √ untuk Cuti di Luar Tanggungan Negara.',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function values(LeaveRequest $leaveRequest): array
    {
        $leaveRequest->loadMissing(['employee', 'leaveType', 'leaveBalanceSnapshot']);

        $employee = $leaveRequest->employee_snapshot ?? [];
        $officials = $leaveRequest->officials_snapshot ?? [];
        $supervisor = $officials['supervisor'] ?? [];
        $official = $officials['authorized_official'] ?? [];
        $balance = $leaveRequest->leaveBalanceSnapshot;
        $leaveTypeCode = (string) ($leaveRequest->leaveType?->code ?? '');

        return [
            'request_number' => $this->text($leaveRequest->request_number),
            'form_date' => $this->formatIndonesianDate($leaveRequest->form_date),
            'employee_name' => $this->text($employee['full_name'] ?? null),
            'employee_nip' => $this->text($employee['nip'] ?? null),
            'employee_position' => $this->text($employee['position'] ?? null),
            'employee_rank_grade' => $this->text($employee['rank_grade'] ?? null),
            'employee_department' => $this->text($employee['department'] ?? null),
            'employee_service_period' => $this->text($employee['service_period'] ?? null),
            'employee_phone' => $this->text($employee['phone'] ?? $leaveRequest->employee?->phone),
            'leave_type' => $this->text($leaveRequest->leaveType?->name),
            'leave_reason' => $this->text($leaveRequest->reason),
            'leave_duration' => sprintf(
                '%d %s',
                $leaveRequest->duration_value,
                $this->durationUnitLabel($leaveRequest->duration_unit),
            ),
            'leave_start_date' => $this->formatIndonesianDate($leaveRequest->start_date),
            'leave_end_date' => $this->formatIndonesianDate($leaveRequest->end_date),
            'leave_address' => $this->text($leaveRequest->address_during_leave),
            'leave_phone' => $this->text($leaveRequest->phone_during_leave),
            'balance_n2' => (string) ($balance?->n2_remaining ?? 0),
            'balance_n1' => (string) ($balance?->n1_remaining ?? 0),
            'balance_n' => (string) ($balance?->current_year_remaining_before ?? 0),
            'balance_requested_days' => (string) ($balance?->requested_days ?? 0),
            'balance_remaining_after' => (string) ($balance?->current_year_remaining_after ?? 0),
            'supervisor_name' => $this->text($supervisor['full_name'] ?? null),
            'supervisor_nip' => $this->text($supervisor['nip'] ?? null),
            'supervisor_position' => $this->text($supervisor['position_title'] ?? null),
            'official_name' => $this->text($official['full_name'] ?? null),
            'official_nip' => $this->text($official['nip'] ?? null),
            'official_position' => $this->text($official['position_title'] ?? null),
            'leave_annual_mark' => $this->leaveTypeMark($leaveTypeCode, 'annual'),
            'leave_large_mark' => $this->leaveTypeMark($leaveTypeCode, 'long'),
            'leave_sick_mark' => $this->leaveTypeMark($leaveTypeCode, 'sick'),
            'leave_maternity_mark' => $this->leaveTypeMark($leaveTypeCode, 'maternity'),
            'leave_important_reason_mark' => $this->leaveTypeMark($leaveTypeCode, 'important-reason'),
            'leave_unpaid_mark' => $this->leaveTypeMark($leaveTypeCode, 'unpaid'),
        ];
    }

    private function text(mixed $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '-' : $value;
    }

    private function leaveTypeMark(string $selectedCode, string $expectedCode): string
    {
        return $selectedCode === $expectedCode ? '√' : '';
    }

    private function durationUnitLabel(string $unit): string
    {
        return match ($unit) {
            'month' => 'bulan',
            'year' => 'tahun',
            default => 'hari',
        };
    }

    private function formatIndonesianDate(mixed $date): string
    {
        $date = CarbonImmutable::parse($date);
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
