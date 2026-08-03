<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LeaveDocumentTemplateRenderer
{
    public function __construct(private readonly LeaveTemplatePlaceholderResolver $placeholderResolver) {}

    public function render(DocumentTemplate $template, LeaveRequest $leaveRequest, string $outputPath): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($template->storage_path)) {
            throw new RuntimeException('Berkas template dokumen tidak ditemukan.');
        }

        LeaveDocumentTemplateProcessor::useCurlyBraceMacros();

        try {
            $processor = new LeaveDocumentTemplateProcessor($disk->path($template->storage_path));
            $processor->setValues($this->placeholderResolver->values($leaveRequest));
            $processor->saveAs($outputPath);
        } finally {
            LeaveDocumentTemplateProcessor::resetMacroChars();
        }
    }
}
