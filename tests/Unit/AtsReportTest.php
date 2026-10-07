<?php

namespace Tests\Unit;

use App\Support\Ats\AtsReport;
use App\Support\Ats\ResumeParser;
use App\Support\Ats\ResumeText;
use PHPUnit\Framework\TestCase;
use Tests\Support\SampleResumes;

class AtsReportTest extends TestCase
{
    public function test_the_parser_finds_contact_details_and_every_standard_section(): void
    {
        $parsed = (new ResumeParser)->parse(SampleResumes::TEXT);

        $this->assertSame('Sam Taylor', $parsed->name);
        $this->assertSame('sam.taylor@example.com', $parsed->email);
        $this->assertSame('+44 7700 900123', $parsed->phone);
        $this->assertContains('linkedin.com/in/samtaylor', $parsed->links);
        $this->assertContains('Docker', $parsed->skills);
        $this->assertCount(9, $parsed->skills);
        $this->assertStringContainsString('BSc Computer Science', $parsed->section('education')[0]);
        $this->assertCount(5, $parsed->section('experience'));
        $this->assertSame(['Built a CV website with Laravel and Vue'], $parsed->section('projects'));
        $this->assertSame([], $parsed->unclearHeadings);
    }

    public function test_a_year_range_is_never_mistaken_for_a_phone_number(): void
    {
        $parsed = (new ResumeParser)->parse("Jo Bloggs\njo@example.com\nEducation\nBSc Physics 2021 - 2024");

        $this->assertNull($parsed->phone);
    }

    public function test_a_complete_cv_scores_highly_and_each_area_stays_within_its_maximum(): void
    {
        $report = AtsReport::analyse(ResumeText::fromString(SampleResumes::TEXT));

        $this->assertGreaterThanOrEqual(80, $report->total());
        foreach (AtsReport::AREAS as $key => $area) {
            $this->assertLessThanOrEqual($area['max'], $report->scores[$key]);
        }
        $this->assertSame(100, array_sum(array_column(AtsReport::AREAS, 'max')));
    }

    public function test_missing_sections_and_contact_details_are_reported_with_fixes(): void
    {
        $report = AtsReport::analyse(ResumeText::fromString("my journey\nI love building things"));

        $this->assertLessThan(20, $report->total());
        $this->assertContains('Email address', $report->missing);
        $this->assertContains('Phone number', $report->missing);
        $this->assertContains('A clear Experience heading', $report->missing);
        $this->assertNotEmpty($report->suggestions);
    }

    public function test_a_job_advert_shows_which_keywords_are_present_and_which_are_missing(): void
    {
        $report = AtsReport::analyse(ResumeText::fromString(SampleResumes::TEXT), 'We need Python, SQL, Kubernetes and AWS for this placement.');

        $this->assertContains('python', $report->keywordMatch['matched']);
        $this->assertContains('kubernetes', $report->keywordMatch['missing']);
        $this->assertNotContains('need', $report->keywordMatch['missing']);
    }

    public function test_weak_phrases_and_unusual_headings_are_flagged(): void
    {
        $report = AtsReport::analyse(ResumeText::fromString(SampleResumes::TEXT."\nMY JOURNEY\nStarted coding at 14"));

        $this->assertStringContainsString('MY JOURNEY', implode(' ', $report->formatting));
        $this->assertStringContainsString('responsible for', implode(' ', $report->suggestions));
    }

    public function test_the_markdown_report_has_every_section(): void
    {
        $markdown = AtsReport::analyse(ResumeText::fromString(SampleResumes::TEXT), 'Python and AWS')->toMarkdown();

        foreach (['# CV check report', '## What was found', '## Missing', '## Formatting issues', '## Suggestions', '## Job advert keywords', '| Heading clarity |'] as $part) {
            $this->assertStringContainsString($part, $markdown);
        }
    }

    public function test_real_pdf_and_word_files_are_read(): void
    {
        $dir = sys_get_temp_dir().'/ats-'.uniqid();
        mkdir($dir);

        $pdf = ResumeText::fromFile(SampleResumes::pdf($dir));
        $this->assertSame('pdf', $pdf->format);
        $this->assertStringContainsString('sam.taylor@example.com', $pdf->text);
        $this->assertFalse($pdf->signals['scanned']);

        $docx = ResumeText::fromFile(SampleResumes::docx($dir, layoutProblems: true));
        $this->assertStringContainsString('Retail Assistant, Tesco', $docx->text);
        $this->assertSame(1, $docx->signals['tables']);
        $this->assertTrue($docx->signals['header_contact']);

        $report = AtsReport::analyse($docx);
        $this->assertStringContainsString('table', implode(' ', $report->formatting));
        $this->assertStringContainsString('page header', implode(' ', $report->formatting));
    }

    public function test_a_name_after_the_contact_line_and_with_a_nickname_is_found(): void
    {
        $parsed = (new ResumeParser)->parse("contact@example.com | www.example.com\nISAAC (ZAC) ADJEI\nProfile\nStudent.");

        $this->assertSame('Isaac (Zac) Adjei', $parsed->name);
    }

    public function test_common_extra_headings_count_as_standard(): void
    {
        $parsed = (new ResumeParser)->parse(SampleResumes::TEXT."\nRESEARCH & PUBLICATIONS\nA paper\nSPOKEN LANGUAGES\nEnglish\nAwards and Achievements\nA prize");

        $this->assertSame([], $parsed->unclearHeadings);
    }

    public function test_advert_filler_is_ignored_and_plurals_match(): void
    {
        $report = AtsReport::analyse(ResumeText::fromString(SampleResumes::TEXT."\nWorked with other engineers"), 'Engineer role at scale. Apply now, related qualifications, networks and network automation around the UK.');

        $all = array_merge($report->keywordMatch['matched'], $report->keywordMatch['missing']);
        foreach (['now', 'around', 'related', 'qualifications', 'scale'] as $filler) {
            $this->assertNotContains($filler, $all);
        }
        $this->assertContains('engineer', $report->keywordMatch['matched']);
        $this->assertSame(1, count(array_intersect(['network', 'networks'], $all)));
    }

    public function test_bullets_opening_with_any_tense_of_a_strong_verb_count(): void
    {
        $text = "Jo Bloggs\njo@example.com\nExperience\nArchitected a platform used by forty students each week\nEngineered a secure login with two factor checks\nFacilitate weekly sessions for over thirty students here";
        $report = AtsReport::analyse(ResumeText::fromString($text));

        $this->assertSame(10, $report->scores['keywords']);
    }
}
