<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;

class LeaveDocumentTemplateProcessor extends TemplateProcessor
{
    public static function useCurlyBraceMacros(): void
    {
        self::$macroOpeningChars = '{{';
        self::$macroClosingChars = '}}';
    }

    public static function resetMacroChars(): void
    {
        self::$macroOpeningChars = '${';
        self::$macroClosingChars = '}';
    }
}
