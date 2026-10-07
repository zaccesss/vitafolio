<?php

namespace Tests\Feature;

use App\Models\Cv;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\SampleResumes;
use Tests\TestCase;

class CvCheckTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/cvcheck-'.uniqid();
        mkdir($this->dir);
    }

    public function test_the_page_needs_a_signed_in_account(): void
    {
        $this->get(route('check'))->assertRedirect(route('login'));
    }

    public function test_an_uploaded_pdf_gets_a_score_and_report(): void
    {
        $file = new UploadedFile(SampleResumes::pdf($this->dir), 'cv.pdf', 'application/pdf', null, true);

        $this->actingAs(User::factory()->create())
            ->post(route('check.run'), ['source' => 'file', 'resume' => $file, 'job_advert' => 'Python, SQL and Kubernetes'])
            ->assertOk()
            ->assertSee('Score:')
            ->assertSee('sam.taylor@example.com')
            ->assertSee('kubernetes')
            ->assertSee('Download the report (Markdown)');
    }

    public function test_an_uploaded_word_file_with_layout_problems_is_flagged(): void
    {
        $file = new UploadedFile(SampleResumes::docx($this->dir, layoutProblems: true), 'cv.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $this->actingAs(User::factory()->create())
            ->post(route('check.run'), ['source' => 'file', 'resume' => $file])
            ->assertOk()
            ->assertSee('table(s)')
            ->assertSee('page header');
    }

    public function test_other_file_types_are_refused(): void
    {
        $file = UploadedFile::fake()->create('cv.txt', 10, 'text/plain');

        $this->actingAs(User::factory()->create())
            ->post(route('check.run'), ['source' => 'file', 'resume' => $file])
            ->assertSessionHasErrors('resume');
    }

    public function test_an_owner_can_check_their_own_vitafolio_cv(): void
    {
        $cv = Cv::factory()->create(['experience' => "Software Intern, Acme, 2025\nBuilt a dashboard used by 40 staff", 'education' => 'BSc Computer Science, 2024 to 2028']);

        $this->actingAs($cv->user)
            ->post(route('check.run'), ['source' => 'cv', 'cv' => $cv->id])
            ->assertOk()
            ->assertSee($cv->user->email)
            ->assertSee('Experience: 2 lines.');
    }

    public function test_an_uploaded_file_is_checked_even_with_the_vitafolio_option_still_selected(): void
    {
        $cv = Cv::factory()->create(['title' => 'Saved CV']);
        $file = new UploadedFile(SampleResumes::pdf($this->dir), 'uploaded.pdf', 'application/pdf', null, true);

        $this->actingAs($cv->user)
            ->post(route('check.run'), ['source' => 'cv', 'cv' => $cv->id, 'resume' => $file])
            ->assertOk()
            ->assertSee('Checked: uploaded.pdf')
            ->assertSee('sam.taylor@example.com')
            ->assertDontSee('Checked: Saved CV');
    }

    public function test_nobody_can_check_someone_elses_cv(): void
    {
        $cv = Cv::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('check.run'), ['source' => 'cv', 'cv' => $cv->id])
            ->assertNotFound();
    }

    public function test_the_last_report_downloads_as_json_and_markdown(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('check.download', 'md'))->assertNotFound();

        $file = new UploadedFile(SampleResumes::pdf($this->dir), 'cv.pdf', 'application/pdf', null, true);
        $this->actingAs($user)->post(route('check.run'), ['source' => 'file', 'resume' => $file])->assertOk();

        $this->get(route('check.download', 'md'))->assertOk()->assertSee('# CV check report')
            ->assertHeader('Content-Disposition', 'attachment; filename="report.md"');
        $json = $this->get(route('check.download', 'json'))->assertOk()->json();
        $this->assertSame('Sam Taylor', $json['parsed']['name']);
        $this->assertArrayHasKey('total', $json['score']);
    }

    public function test_the_command_writes_parsed_json_and_a_report(): void
    {
        $pdf = SampleResumes::pdf($this->dir);
        $out = $this->dir.'/output';

        $this->artisan('cv:analyse', ['file' => $pdf, '--out' => $out])
            ->expectsOutputToContain('Score:')
            ->assertSuccessful();

        $this->assertSame('sam.taylor@example.com', json_decode((string) file_get_contents($out.'/parsed.json'), true)['parsed']['email']);
        $this->assertStringStartsWith('# CV check report', (string) file_get_contents($out.'/report.md'));
        $this->artisan('cv:analyse', ['file' => $this->dir.'/missing.pdf'])->assertFailed();
    }
}
