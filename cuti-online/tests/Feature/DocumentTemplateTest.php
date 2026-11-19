<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveDocumentTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Tests\TestCase;

class DocumentTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_upload_a_valid_docx_and_make_it_active(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post(route('document-templates.store'), [
                'name' => 'Formulir Cuti Tahunan 2026',
                'file' => $this->wordTemplateUpload(),
            ])
            ->assertRedirect(route('document-templates.index'));

        $template = DocumentTemplate::query()->sole();

        $this->assertSame('Formulir Cuti Tahunan 2026', $template->name);
        $this->assertTrue($template->is_active);
        $this->assertStringStartsWith('leave-templates/formulir-cuti-tahunan-2026-', $template->storage_path);
        Storage::disk('local')->assertExists($template->storage_path);

        $this->get(route('document-templates.index'))
            ->assertOk()
            ->assertSee('Formulir Cuti Tahunan 2026')
            ->assertSee('{{employee_name}}', false)
            ->assertSee('data-file-picker', false)
            ->assertSee('Pilih template')
            ->assertSee('Template aktif')
            ->assertSeeInOrder(['Template aktif', 'Unggah template baru']);
    }

    public function test_operator_can_switch_the_active_template_without_deleting_the_old_one(): void
    {
        $operator = User::factory()->create();
        $oldTemplate = DocumentTemplate::query()->create([
            'slug' => 'formulir-lama',
            'name' => 'Formulir Lama',
            'version' => '202607310001',
            'storage_path' => 'leave-templates/formulir-lama.docx',
            'is_active' => true,
        ]);
        $newTemplate = DocumentTemplate::query()->create([
            'slug' => 'formulir-baru',
            'name' => 'Formulir Baru',
            'version' => '202607310002',
            'storage_path' => 'leave-templates/formulir-baru.docx',
            'is_active' => false,
        ]);

        $this->actingAs($operator)
            ->post(route('document-templates.activate', $newTemplate))
            ->assertRedirect(route('document-templates.index'));

        $this->assertFalse($oldTemplate->fresh()->is_active);
        $this->assertTrue($newTemplate->fresh()->is_active);
        $this->assertDatabaseCount('document_templates', 2);
    }

    public function test_invalid_docx_upload_is_rejected(): void
    {
        Storage::fake('local');

        $this->from(route('document-templates.index'))
            ->actingAs(User::factory()->create())
            ->post(route('document-templates.store'), [
                'file' => UploadedFile::fake()->createWithContent('bukan-template.docx', 'bukan dokumen Word'),
            ])
            ->assertRedirect(route('document-templates.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('document_templates', 0);
        Storage::disk('local')->assertDirectoryEmpty('leave-templates');
    }

    public function test_external_relationship_is_rejected_for_new_and_stored_templates(): void
    {
        Storage::fake('local');
        $file = $this->wordTemplateUpload(withExternalLink: true);

        $this->actingAs(User::factory()->create())
            ->post(route('document-templates.store'), ['file' => $file])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('document_templates', 0);

        $path = $file->storeAs('leave-templates', 'template-lama.docx', 'local');
        $template = new DocumentTemplate(['storage_path' => $path]);
        $output = Storage::disk('local')->path('leave-templates/keluaran.docx');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak memuat referensi eksternal');
        app(LeaveDocumentTemplateRenderer::class)->render($template, new LeaveRequest, $output);
    }

    private function wordTemplateUpload(bool $withExternalLink = false): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'template-cuti-');
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText('{{employee_name}}');
        if ($withExternalLink) {
            $section->addLink('https://example.invalid/panduan', 'Panduan');
        }
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return new UploadedFile(
            $path,
            'formulir-cuti.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true,
        );
    }
}
