<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Rules\SafeUpload;
use App\Services\LeaveDocxTemplateInspector;
use App\Services\LeaveTemplatePlaceholderResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    public function index(LeaveTemplatePlaceholderResolver $placeholderResolver): View
    {
        return view('document-templates.index', [
            'templates' => DocumentTemplate::query()
                ->orderByDesc('is_active')
                ->latest('created_at')
                ->get(),
            'placeholderGroups' => $placeholderResolver->placeholderGroups(),
        ]);
    }

    public function store(Request $request, LeaveDocxTemplateInspector $inspector): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'file' => ['bail', 'required', 'file', 'mimes:docx', 'extensions:docx', 'max:10240', new SafeUpload],
        ]);

        /** @var UploadedFile $file */
        $file = $data['file'];
        $path = $file->getRealPath();
        if ($path === false || ! $inspector->accepts($path)) {
            throw ValidationException::withMessages(['file' => LeaveDocxTemplateInspector::REJECTION_MESSAGE]);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $name = $name !== '' ? $name : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($name) ?: 'formulir-cuti';
        $version = now()->format('YmdHisv').'-'.Str::lower(Str::random(4));
        $storagePath = $file->storeAs('leave-templates', "{$slug}-{$version}.docx", 'local');

        try {
            DB::transaction(function () use ($slug, $name, $version, $storagePath): void {
                DocumentTemplate::query()->where('is_active', true)->update(['is_active' => false]);

                DocumentTemplate::query()->create([
                    'slug' => $slug,
                    'name' => $name,
                    'version' => $version,
                    'storage_path' => $storagePath,
                    'is_active' => true,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storagePath);

            throw $exception;
        }

        return to_route('document-templates.index')
            ->with('status', 'Template berhasil diunggah dan langsung dijadikan template aktif.');
    }

    public function activate(DocumentTemplate $documentTemplate): RedirectResponse
    {
        DB::transaction(function () use ($documentTemplate): void {
            DocumentTemplate::query()->where('is_active', true)->update(['is_active' => false]);
            $documentTemplate->update(['is_active' => true]);
        });

        return to_route('document-templates.index')
            ->with('status', 'Template aktif untuk formulir cuti baru berhasil diubah.');
    }
}
