<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Settings;
use RuntimeException;

class LeaveDocumentTemplateRenderer
{
    public function __construct(
        private readonly LeaveTemplatePlaceholderResolver $placeholderResolver,
        private readonly LeaveDocxTemplateInspector $inspector,
    ) {}

    public function render(DocumentTemplate $template, LeaveRequest $leaveRequest, string $outputPath): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($template->storage_path)) {
            throw new RuntimeException('Berkas template dokumen tidak ditemukan.');
        }

        $templatePath = $disk->path($template->storage_path);
        if (! $this->inspector->accepts($templatePath)) {
            throw new RuntimeException(LeaveDocxTemplateInspector::REJECTION_MESSAGE);
        }

        LeaveDocumentTemplateProcessor::useCurlyBraceMacros();
        $wasEscaping = Settings::isOutputEscapingEnabled();
        Settings::setOutputEscapingEnabled(true);

        try {
            $processor = new LeaveDocumentTemplateProcessor($templatePath);
            $processor->setValues($this->placeholderResolver->values($leaveRequest));
            $processor->saveAs($outputPath);
        } finally {
            Settings::setOutputEscapingEnabled($wasEscaping);
            LeaveDocumentTemplateProcessor::resetMacroChars();
        }
    }
}
