<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\CvDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    private function pdf(string $body = '%PDF-1.4 a test cv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('My CV.pdf', $body);
    }

    public function test_a_pdf_is_stored_and_served_in_a_sandbox(): void
    {
        $cv = Cv::factory()->create();
        $this->actingAs($cv->user)->post(route('cvs.document', $cv), ['document' => $this->pdf()])->assertSessionHasNoErrors();

        $document = CvDocument::where('cv_id', $cv->id)->firstOrFail();
        $this->assertSame('database', $document->storage);
        $this->assertSame('My CV.pdf', $document->filename);

        $response = $this->get(route('cv.file', $cv))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
        $this->assertSame('%PDF-1.4 a test cv', $response->getContent());
    }

    public function test_a_file_pretending_to_be_a_pdf_is_refused(): void
    {
        $cv = Cv::factory()->create();
        $this->actingAs($cv->user)->post(route('cvs.document', $cv), ['document' => $this->pdf('<script>alert(1)</script>')])
            ->assertSessionHasErrors('document');
        $this->assertDatabaseCount('cv_documents', 0);
    }

    public function test_the_storage_allowance_is_enforced(): void
    {
        config(['vitafolio.limits.storage_mb' => 0]);
        $cv = Cv::factory()->create();
        $this->actingAs($cv->user)->post(route('cvs.document', $cv), ['document' => $this->pdf()])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('cv_documents', 0);
    }

    public function test_a_private_cvs_file_stays_private(): void
    {
        $cv = Cv::factory()->visibility('private')->create();
        $this->actingAs($cv->user)->post(route('cvs.document', $cv), ['document' => $this->pdf()]);
        auth()->logout();

        $this->get(route('cv.file', $cv))->assertNotFound();
    }

    public function test_with_cloudinary_files_leave_the_database_and_are_cleaned_up(): void
    {
        config(['vitafolio.cloudinary' => ['cloud_name' => 'demo', 'api_key' => 'key', 'api_secret' => 'secret']]);
        Http::fake([
            'api.cloudinary.com/v1_1/demo/raw/upload' => Http::response(['public_id' => 'vitafolio/cv-files/abc.pdf']),
            'api.cloudinary.com/v1_1/demo/raw/download*' => Http::response('%PDF-1.4 from cloudinary'),
            'api.cloudinary.com/v1_1/demo/raw/destroy' => Http::response(['result' => 'ok']),
        ]);
        $cv = Cv::factory()->create();

        $this->actingAs($cv->user)->post(route('cvs.document', $cv), ['document' => $this->pdf()])->assertSessionHasNoErrors();
        $document = CvDocument::where('cv_id', $cv->id)->firstOrFail();
        $this->assertSame('cloudinary', $document->storage);
        $this->assertNull(\DB::table('cv_documents')->where('id', $document->id)->value('data'));

        $this->get(route('cv.file', $cv))->assertOk()->assertSee('from cloudinary');
        // uploads are multipart, so each field arrives as its own part
        Http::assertSent(fn ($request) => str_contains($request->url(), '/raw/upload')
            && collect($request->data())->firstWhere('name', 'type')['contents'] === 'authenticated');

        $this->delete(route('cvs.destroy', $cv), ['confirm_title' => $cv->title])->assertRedirect();
        Http::assertSent(fn ($request) => str_contains($request->url(), '/raw/destroy') && $request['public_id'] === 'vitafolio/cv-files/abc.pdf');
    }
}
