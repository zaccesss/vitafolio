<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class WordExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_public_cv_downloads_as_a_valid_word_document(): void
    {
        $cv = Cv::factory()->create(['profile' => "First line & more\n- A bullet with <angle> brackets", 'experience' => 'Built things']);

        $response = $this->get(route('cv.word', $cv))->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringContainsString('.docx', (string) $response->headers->get('Content-Disposition'));

        $path = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $document = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($path);

        // the document is well-formed xml, with the owner's text escaped and the bullet as a real list item
        $this->assertNotFalse(simplexml_load_string($document));
        $this->assertStringContainsString('First line &amp; more', $document);
        $this->assertStringContainsString('&lt;angle&gt;', $document);
        $this->assertStringContainsString('w:val="ListBullet"', $document);
        $this->assertStringContainsString($cv->user->name, $document);
    }

    public function test_a_private_cv_cannot_be_downloaded_by_someone_else(): void
    {
        $cv = Cv::factory()->visibility('private')->create();

        $this->get(route('cv.word', $cv))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('cv.word', $cv))->assertNotFound();
        $this->actingAs($cv->user)->get(route('cv.word', $cv))->assertOk();
    }
}
