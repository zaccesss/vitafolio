<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    use HasFactory;

    /** the kinds of role students and graduates look for, in the order the filter shows them */
    public const KINDS = [
        'internship' => 'Internships',
        'placement' => 'Placement years',
        'insight' => 'Spring weeks and insight days',
        'graduate' => 'Graduate roles',
        'part-time' => 'Part-time and student jobs',
    ];

    /** @return array<string, string> the kinds in the reader's language, written out so the translation scanner sees them */
    public static function kindLabels(): array
    {
        return [
            'internship' => __('Internships'),
            'placement' => __('Placement years'),
            'insight' => __('Spring weeks and insight days'),
            'graduate' => __('Graduate roles'),
            'part-time' => __('Part-time and student jobs'),
        ];
    }

    /** adzuna and reed are fetched from their own APIs; employer listings arrive through the feed */
    public const SOURCES = ['adzuna' => 'Adzuna', 'reed' => 'Reed', 'employer' => 'Employer'];

    protected $fillable = ['source', 'board', 'external_id', 'kind', 'sector', 'title', 'company', 'location', 'salary_min', 'salary_max', 'description', 'url', 'posted_at', 'closes_at', 'last_seen_at'];

    protected function casts(): array
    {
        return ['posted_at' => 'datetime', 'closes_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }

    /** @param  Builder<JobListing>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>=', now()));
    }

    public function salaryText(): ?string
    {
        if (! $this->salary_min && ! $this->salary_max) {
            return null;
        }
        $format = fn (?int $n) => $n ? '£'.number_format($n) : null;

        return $this->salary_min && $this->salary_max && $this->salary_min !== $this->salary_max
            ? $format($this->salary_min).' to '.$format($this->salary_max)
            : $format($this->salary_max ?? $this->salary_min);
    }
}
