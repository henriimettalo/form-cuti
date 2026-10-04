<?php

namespace App\Exceptions;

use InvalidArgumentException;

class PayrollImportValidationException extends InvalidArgumentException
{
    /** @param list<string> $issues */
    public function __construct(public readonly array $issues)
    {
        parent::__construct("Data payroll tidak lolos validasi:\n- ".implode("\n- ", $issues));
    }
}
