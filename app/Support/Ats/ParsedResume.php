<?php

namespace App\Support\Ats;

final readonly class ParsedResume
{
    /**
     * @param  list<string>  $links
     * @param  list<string>  $skills
     * @param  array<string, list<string>>  $sections  lines under each standard heading
     * @param  list<string>  $headings  every heading found, standard or not
     * @param  list<string>  $unclearHeadings  heading-like lines a system would not recognise
     */
    public function __construct(
        public ?string $name,
        public ?string $email,
        public ?string $phone,
        public array $links,
        public array $skills,
        public array $sections,
        public array $headings,
        public array $unclearHeadings,
    ) {}

    /** @return list<string> */
    public function section(string $key): array
    {
        return $this->sections[$key] ?? [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'links' => $this->links,
            'summary' => $this->section('summary'),
            'education' => $this->section('education'),
            'skills' => $this->skills,
            'experience' => $this->section('experience'),
            'projects' => $this->section('projects'),
            'headings' => $this->headings,
        ];
    }
}
