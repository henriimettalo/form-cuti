<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $timestamp = now();

        LeaveType::query()->upsert([
            [
                'code' => 'annual',
                'name' => 'Cuti Tahunan',
                'requires_attachment' => false,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'code' => 'long',
                'name' => 'Cuti Besar',
                'requires_attachment' => false,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'code' => 'sick',
                'name' => 'Cuti Sakit',
                'requires_attachment' => true,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'code' => 'maternity',
                'name' => 'Cuti Melahirkan',
                'requires_attachment' => false,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'code' => 'important-reason',
                'name' => 'Cuti Karena Alasan Penting',
                'requires_attachment' => true,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'code' => 'unpaid',
                'name' => 'Cuti di Luar Tanggungan Negara',
                'requires_attachment' => false,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['code'], ['name', 'requires_attachment', 'is_active', 'updated_at']);
    }
}
