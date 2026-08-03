<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
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

    private function wordTemplateUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'template-cuti-');
        $phpWord = new PhpWord;
        $phpWord->addSection()->addText('{{employee_name}}');
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
