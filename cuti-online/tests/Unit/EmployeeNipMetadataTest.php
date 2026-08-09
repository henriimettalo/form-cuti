<?php

namespace Tests\Unit;

use App\Support\EmployeeNipMetadata;
use PHPUnit\Framework\TestCase;

class EmployeeNipMetadataTest extends TestCase
{
    public function test_nip_metadata_is_derived_from_standard_segments(): void
    {
        $metadata = EmployeeNipMetadata::derive('199001012020011101');

        $this->assertSame('1990-01-01', $metadata['birth_date']);
        $this->assertSame('2020-01-01', $metadata['service_started_on']);
        $this->assertSame('L', $metadata['gender']);
        $this->assertTrue($metadata['tmt_valid']);
    }

    public function test_invalid_tmt_month_is_flagged_without_forcing_a_date(): void
    {
        $metadata = EmployeeNipMetadata::derive('199001012025211101');

        $this->assertNull($metadata['service_started_on']);
        $this->assertFalse($metadata['tmt_valid']);
    }
}
