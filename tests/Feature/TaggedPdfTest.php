<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Support\PdfCv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaggedPdfTest extends TestCase
{
    use RefreshDatabase;

    private function requireTypst(): void
    {
        // ci installs typst, so a missing binary fails there; elsewhere the tagged checks are skipped
        if (PdfCv::binary() === null && ! getenv('CI')) {
            $this->markTestSkipped('typst is not installed, so tagged pdfs cannot be checked here.');
        }
    }

    public function test_without_typst_the_pdf_still_downloads(): void
    {
        config(['vitafolio.typst_binary' => '/nonexistent/typst']);
        $cv = Cv::factory()->create(['profile' => 'Builds things']);

        $pdf = (string) $this->get(route('cv.pdf', $cv))->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    private function catalog(string $pdf): string
    {
        $this->assertSame(1, preg_match('#<</Type/Catalog(.*?)>>\s*endobj#s', $pdf, $match), 'the pdf has a readable catalog');

        return $match[1];
    }

    public function test_the_cv_pdf_is_tagged_with_its_language_and_title(): void
    {
        $this->requireTypst();
        $cv = Cv::factory()->create([
            'profile' => "Builds accessible tools.\n\n- Leads a team of four\n- Ships every week",
            'experience' => 'Engineer at Acme',
        ]);

        $response = $this->get(route('cv.pdf', $cv))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $pdf = (string) $response->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);

        $catalog = $this->catalog($pdf);
        $this->assertStringContainsString('/MarkInfo<</Marked true', $catalog);
        $this->assertMatchesRegularExpression('#/StructTreeRoot \d+ 0 R#', $catalog);
        $this->assertStringContainsString('/Lang(en-GB)', $catalog);
        $this->assertStringContainsString('/DisplayDocTitle true', $catalog);
        // headings become bookmarks
        $this->assertMatchesRegularExpression('#/Outlines \d+ 0 R#', $catalog);
        // the title sits in the xmp metadata, which pdf/ua reads, as well as the info dictionary
        $this->assertStringContainsString('<rdf:li xml:lang="x-default">'.$cv->user->name.' CV</rdf:li>', $pdf);
        $this->assertStringContainsString('pdfuaid:part>1<', $pdf);
        // real structure for the name, sections, paragraphs and the bulleted list
        foreach (['/S/H1', '/S/H2', '/S/P', '/S/L', '/S/LI'] as $tag) {
            $this->assertStringContainsString($tag, $pdf);
        }
    }

    public function test_the_cover_letter_pdf_is_tagged_with_its_own_title(): void
    {
        $this->requireTypst();
        $cv = Cv::factory()->create(['cover_letter' => "Dear hiring team,\n\nThank you for reading.", 'letter_to' => 'Acme']);

        $pdf = (string) $this->get(route('cv.letter.pdf', $cv))->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();

        $catalog = $this->catalog($pdf);
        $this->assertStringContainsString('/MarkInfo<</Marked true', $catalog);
        $this->assertMatchesRegularExpression('#/StructTreeRoot \d+ 0 R#', $catalog);
        $this->assertStringContainsString('/Lang(en-GB)', $catalog);
        $this->assertStringContainsString('<rdf:li xml:lang="x-default">'.$cv->user->name.' cover letter</rdf:li>', $pdf);
    }

    public function test_links_are_tagged_and_cv_text_is_never_read_as_markup(): void
    {
        $this->requireTypst();
        $cv = Cv::factory()->create(['profile' => '#read("/etc/passwd") and $x$ stay as text']);
        $cv->projects()->create(['title' => 'Engine', 'url' => 'https://example.com/engine', 'description' => 'A small engine']);
        $cv->user->update(['links' => 'https://github.com/example']);

        $pdf = (string) $this->get(route('cv.pdf', $cv))->assertOk()->getContent();

        foreach (['/S/Link', '/S/Table', '/S/TH', '/S/TD', '/S/H3'] as $tag) {
            $this->assertStringContainsString($tag, $pdf);
        }
        $this->assertStringContainsString('/URI(https://example.com/engine)', $pdf);
        $this->assertStringNotContainsString('root:', $pdf);
    }

    public function test_cv_text_splits_into_paragraphs_and_lists(): void
    {
        $this->assertSame([
            ['type' => 'p', 'lines' => ['One', 'Two']],
            ['type' => 'list', 'items' => ['First', 'Second']],
            ['type' => 'p', 'lines' => ['Three']],
        ], PdfCv::blocks("One\nTwo\n- First\n- Second\n\nThree"));
        $this->assertSame([], PdfCv::blocks(null));
    }
}
