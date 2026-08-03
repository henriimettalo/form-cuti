<?php

namespace Tests\Unit;

use App\Support\EmployeeRankOptions;
use Tests\TestCase;

class EmployeeRankOptionsTest extends TestCase
{
    public function test_it_formats_pns_rank_and_grade_with_parentheses(): void
    {
        $this->assertSame(
            'Pembina Tingkat I (IV/b)',
            EmployeeRankOptions::format('PNS', 'Pembina Tingkat I', 'IV/b'),
        );
    }

    public function test_it_keeps_pppk_grade_without_parentheses(): void
    {
        $this->assertSame(
            'Golongan IX',
            EmployeeRankOptions::format('PPPK', 'Golongan', 'IX'),
        );
    }
}
