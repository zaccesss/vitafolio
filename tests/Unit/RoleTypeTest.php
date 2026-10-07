<?php

namespace Tests\Unit;

use App\Support\Jobs\RoleType;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleTypeTest extends TestCase
{
    public static function titles(): array
    {
        return [
            ['Software Engineering Internship', false, 'internship'],
            ['Summer Analyst 2027', false, 'internship'],
            ['International Trade Intern', false, 'internship'],
            ['Industrial Placement 2027/28', false, 'placement'],
            ['Industrial Placement 2025/26', false, null],
            ['Industrial Placement Year 2026/27', false, 'placement'],
            ['12 Month Placement - Electronic Engineering', false, 'placement'],
            ['Spring Insight Week 2027', false, 'insight'],
            ['Graduate Software Engineer', false, 'graduate'],
            ['Technology Graduate Programme 2027', false, 'graduate'],
            ['Entry-Level Data Analyst', false, 'graduate'],
            ['Internal Audit Manager', false, null],
            ['Internal Communications Executive', false, null],
            ['Senior Graduate Recruiter', false, null],
            ['Placement Coordinator', false, null],
            ['Replacement Window Fitter', false, null],
            ['Postgraduate Research Fellow', false, null],
            ['Degree Apprenticeship in Software', false, 'apprenticeship'],
            ['Level 3 Accounting Apprentice', false, 'apprenticeship'],
            ['Apprenticeship Programme Manager', false, null],
            ['Senior Software Engineer', false, null],
            ['12 month contract - Project Manager', false, null],
            ['2026 Machine Learning Center of Excellence (NLP)-Internship', false, 'internship'],
            ['12 months Placement- Back-End Developer (Internship) starting July 2027', false, 'placement'],
            ['Network Engineer Intern (12 Months)', false, 'placement'],
            ['Controls Systems Industrial Placements', false, 'placement'],
            ['Tech Insight Experience for Women - Engineering', false, 'insight'],
            ['Discovery Week', false, 'insight'],
            ['2027 Full-Time Analyst Programme - Client and Product Functions', false, 'graduate'],
            ['Investment Banking 2027 Off-cycle Analyst - London', false, 'internship'],
            ['Quant Research Associate Programme', false, 'graduate'],
            ['Post-graduate Teaching Assistant', false, null],
            ['Junior Software Engineer', false, null],
            ['Student Ambassador', true, 'part-time'],
            ['Weekend Sales Assistant', true, 'part-time'],
            ['Part Time Assistant Accountant', true, null],
            ['Part Time Store Manager', true, null],
        ];
    }

    #[DataProvider('titles')]
    public function test_titles_are_classified_by_whole_words(string $title, bool $partTime, ?string $kind): void
    {
        Carbon::setTestNow('2026-10-08');
        $this->assertSame($kind, RoleType::inCycle($title) ? RoleType::classify($title, $partTime) : null, $title);
    }

    public function test_the_cycle_rejects_past_years_and_finished_seasons(): void
    {
        $now = Carbon::parse('2026-10-08');
        $this->assertTrue(RoleType::inCycle('Graduate Engineer', $now));
        $this->assertTrue(RoleType::inCycle('Summer Internship 2027', $now));
        $this->assertTrue(RoleType::inCycle('Placement 2026-27', $now));
        $this->assertFalse(RoleType::inCycle('Summer Internship 2026', $now));
        $this->assertFalse(RoleType::inCycle('Graduate Scheme 2025', $now));
        $this->assertFalse(RoleType::inCycle('Graduate Programme 2028', $now));
        $this->assertFalse(RoleType::inCycle('Placement 2025/26', $now));
        $this->assertTrue(RoleType::inCycle('Undergraduate Placement Year 2027-28', $now));
        $this->assertTrue(RoleType::inCycle('Technology Analyst Graduate Programme 2027 - 2028', $now));
        $this->assertFalse(RoleType::inCycle('Graduate Programme 2028 - 2029', $now));
    }
}
