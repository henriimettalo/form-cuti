<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndonesianValidationTest extends TestCase
{
    public function test_leave_date_range_validation_messages_are_in_indonesian(): void
    {
        config([
            'app.locale' => 'id',
            'app.fallback_locale' => 'id',
        ]);
        app()->setLocale('id');

        $validator = Validator::make(
            [
                'start_date' => '2026-08-02',
                'end_date' => '2026-08-01',
            ],
            [
                'start_date' => ['required', 'date', 'before_or_equal:end_date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Tanggal mulai cuti harus sebelum atau sama dengan tanggal selesai cuti.',
            $validator->errors()->first('start_date'),
        );
        $this->assertSame(
            'Tanggal selesai cuti harus setelah atau sama dengan tanggal mulai cuti.',
            $validator->errors()->first('end_date'),
        );
    }
}
