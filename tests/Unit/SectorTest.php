<?php

namespace Tests\Unit;

use App\Support\Jobs\Sector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SectorTest extends TestCase
{
    public static function titles(): array
    {
        return [
            ['Embedded Software Engineer Intern', 'hardware'],
            ['Graduate Software Engineer', 'software'],
            ['Machine Learning Intern', 'data'],
            ['Trainee Solicitor 2027', 'law'],
            ['Graduate Nurse', 'health'],
            ['Investment Banking Summer Analyst', 'finance'],
            ['Graduate Civil Engineer', 'engineering'],
            ['Marketing Internship', 'creative'],
            ['Civil Service Fast Stream', 'public'],
            ['Retail Store Assistant', 'retail'],
            ['Graduate Teacher', 'education'],
            ['Management Consulting Analyst', 'business'],
            ['Research Scientist Placement', 'science'],
            ['Summer Internship', 'other'],
            ['Digital Project Management Industrial Placement', 'business'],
            ['Year in Industry - Applied and Theoretical Scientist', 'science'],
        ];
    }

    #[DataProvider('titles')]
    public function test_a_title_is_given_its_field(string $title, string $sector): void
    {
        $this->assertSame($sector, Sector::guess($title), $title);
    }

    public function test_an_adzuna_category_only_fills_a_gap(): void
    {
        $this->assertSame('law', Sector::fromAdzuna('legal-jobs', 'Graduate Trainee'));
        // a title that names its field beats the board's own category
        $this->assertSame('science', Sector::fromAdzuna('it-jobs', 'Year in Industry - Applied and Theoretical Scientist'));
        $this->assertSame('finance', Sector::fromAdzuna('graduate-jobs', 'Graduate Audit Associate'));
    }
}
