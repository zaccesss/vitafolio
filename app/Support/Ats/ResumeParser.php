<?php

namespace App\Support\Ats;

/**
 * Reads a CV's plain text the way a typical applicant tracking system does: contact details by
 * pattern, then sections split on the standard headings those systems look for. A heading the
 * list does not know is still noted, because an unusual heading is itself worth flagging.
 */
final class ResumeParser
{
    /** standard headings, each with the wording systems commonly recognise */
    public const HEADINGS = [
        'summary' => ['profile', 'personal profile', 'personal statement', 'summary', 'professional summary', 'career summary', 'about me', 'objective', 'career objective'],
        'education' => ['education', 'education and qualifications', 'education & qualifications', 'qualifications', 'academic background', 'academic qualifications'],
        'experience' => ['experience', 'work experience', 'professional experience', 'relevant experience', 'employment', 'employment history', 'work history', 'career history', 'internships'],
        'skills' => ['skills', 'key skills', 'technical skills', 'core skills', 'skills and competencies', 'competencies', 'skills and interests', 'technical and soft skills'],
        'projects' => ['projects', 'personal projects', 'academic projects', 'key projects', 'selected projects', 'portfolio'],
        'other' => ['certifications', 'certificates', 'awards', 'achievements', 'awards and achievements', 'awards & achievements', 'honours and awards', 'volunteering', 'volunteer experience', 'volunteer work', 'interests', 'hobbies', 'hobbies and interests', 'languages', 'spoken languages', 'language skills', 'references', 'publications', 'research', 'research and publications', 'research & publications', 'leadership', 'leadership experience', 'positions of responsibility', 'activities', 'extracurricular activities', 'contact', 'contact details', 'additional information', 'courses', 'training'],
    ];

    public function parse(string $text): ParsedResume
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($line) => $line !== ''));

        $sections = [];
        $headings = [];
        $unclear = [];
        $current = null;
        foreach ($lines as $i => $line) {
            $key = $this->headingKey($line);
            if ($key !== null) {
                $headings[] = $line;
                $current = $key;
                $sections[$key] ??= [];

                continue;
            }
            if ($i > 2 && $this->looksLikeHeading($line)) {
                $headings[] = $line;
                $unclear[] = $line;
                $current = 'other';

                continue;
            }
            if ($current !== null) {
                $sections[$current][] = $line;
            }
        }
        unset($sections['other']);

        return new ParsedResume(
            name: $this->name($lines),
            email: $this->match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text),
            phone: $this->phone($text),
            links: $this->links($text),
            skills: $this->skills($sections['skills'] ?? []),
            sections: $sections,
            headings: $headings,
            unclearHeadings: $unclear,
        );
    }

    public function headingKey(string $line): ?string
    {
        $normal = $this->normalise($line);
        if ($normal === '' || mb_strlen($line) > 45) {
            return null;
        }
        foreach (self::HEADINGS as $key => $variants) {
            if (in_array($normal, $variants, true)) {
                return $key;
            }
        }

        return null;
    }

    private function normalise(string $line): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}&\s]/u', '', mb_strtolower($line))));
    }

    /** a short line in capitals or ending in a colon reads as a heading */
    private function looksLikeHeading(string $line): bool
    {
        $words = str_word_count($line);
        if ($words === 0 || $words > 4 || mb_strlen($line) > 40 || preg_match('/\d|@/', $line)) {
            return false;
        }

        return str_ends_with($line, ':') || (mb_strtoupper($line) === $line && preg_match('/\p{Lu}{3,}/u', $line));
    }

    /**
     * the first short line of two to four words near the top. PDFs sometimes put the contact line
     * before the name when their text is read out, so a few lines are searched. A nickname in
     * brackets such as "Isaac (Zac) Adjei" still counts
     *
     * @param  list<string>  $lines
     */
    private function name(array $lines): ?string
    {
        foreach (array_slice($lines, 0, 8) as $line) {
            $clean = trim(preg_replace('/\s+/', ' ', $line));
            if ($this->headingKey($clean) !== null || preg_match('/[@\d|:\/]|www\.|\.com/i', $clean)) {
                continue;
            }
            $word = '\(?\p{L}[\p{L}\'\x{2019}.-]*\)?';
            if (preg_match('/^'.$word.'(\s+'.$word.'){1,3}$/u', $clean)) {
                return mb_strtoupper($clean) === $clean ? mb_convert_case(mb_strtolower($clean), MB_CASE_TITLE) : $clean;
            }
        }

        return null;
    }

    private function phone(string $text): ?string
    {
        preg_match_all('/(?:\+\d{1,3}[\s.-]?)?(?:\(?\d{2,5}\)?[\s.-]?){2,4}\d{2,4}/', $text, $found);
        foreach ($found[0] as $candidate) {
            $digits = preg_replace('/\D/', '', $candidate);
            // a date range or a year is not a phone number
            if (strlen($digits) >= 10 && strlen($digits) <= 15 && ! preg_match('/^(19|20)\d{2}\s*[-\x{2013}]\s*(19|20)\d{2}$/u', trim($candidate))) {
                return trim($candidate);
            }
        }

        return null;
    }

    /** @return list<string> */
    private function links(string $text): array
    {
        preg_match_all('#(?:https?://)?(?:www\.)?(?:linkedin\.com/in|github\.com|gitlab\.com)/[A-Za-z0-9_./-]+|https?://[^\s,)]+#i', $text, $found);

        return array_values(array_unique(array_map(fn ($link) => rtrim($link, '.'), $found[0])));
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function skills(array $lines): array
    {
        $items = [];
        foreach ($lines as $line) {
            // "Languages: Python, Java" keeps the list after the label
            $line = preg_replace('/^[^:]{1,30}:\s*/', '', $line);
            foreach (preg_split('/\s*[,;|•·▪●]\s*|\s+-\s+|\s{2,}/u', $line) as $item) {
                $item = preg_replace('/^[\s*\x{2013}-]+|[\s*\x{2013}-]+$/u', '', $item);
                if ($item !== '' && mb_strlen($item) <= 40 && str_word_count($item) <= 5) {
                    $items[mb_strtolower($item)] = $item;
                }
            }
        }

        return array_values($items);
    }

    private function match(string $pattern, string $text): ?string
    {
        return preg_match($pattern, $text, $m) ? $m[0] : null;
    }
}
