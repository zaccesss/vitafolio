<?php

namespace App\Support\Ats;

/**
 * Scores a CV out of 100 on what applicant tracking systems need and explains every point. The
 * checks are fixed rules rather than a model, so the same CV always gets the same score and every
 * deduction can be explained.
 */
final class AtsReport
{
    public const AREAS = [
        'headings' => ['label' => 'Heading clarity', 'max' => 20],
        'contact' => ['label' => 'Contact information', 'max' => 20],
        'skills' => ['label' => 'Skills', 'max' => 15],
        'education' => ['label' => 'Education', 'max' => 15],
        'experience' => ['label' => 'Experience', 'max' => 20],
        'keywords' => ['label' => 'Keywords', 'max' => 10],
    ];

    // base forms; a line counts when its first word is one of these with or without -s, -d, -ed or -ing
    private const STRONG_VERBS = ['achieve', 'analyse', 'analyze', 'architect', 'assess', 'automate', 'build', 'built', 'coach', 'collaborate', 'collect', 'complete', 'coordinate', 'create', 'cut', 'debug', 'define', 'deliver', 'deploy', 'design', 'develop', 'diagnose', 'document', 'drive', 'elect', 'engineer', 'establish', 'evaluate', 'examine', 'expand', 'facilitate', 'generate', 'grow', 'guide', 'identify', 'implement', 'improve', 'increase', 'install', 'integrate', 'introduce', 'investigate', 'judge', 'launch', 'lead', 'led', 'maintain', 'manage', 'measure', 'mentor', 'migrate', 'model', 'monitor', 'negotiate', 'operate', 'optimise', 'optimize', 'organise', 'organize', 'oversee', 'plan', 'present', 'process', 'produce', 'program', 'prototype', 'publish', 'raise', 'redesign', 'reduce', 'refactor', 'research', 'resolve', 'review', 'run', 'save', 'scale', 'secure', 'serve', 'service', 'ship', 'simplify', 'solve', 'spearhead', 'streamline', 'support', 'teach', 'test', 'train', 'transform', 'troubleshoot', 'upgrade', 'validate', 'win', 'won', 'write', 'wrote'];

    private const WEAK_PHRASES = ['responsible for', 'duties included', 'helped with', 'worked on', 'tasks included', 'involved in'];

    private const STOP_WORDS = ['the', 'and', 'for', 'with', 'you', 'your', 'our', 'are', 'will', 'this', 'that', 'have', 'from', 'who', 'all', 'can', 'able', 'work', 'role', 'team', 'skills', 'experience', 'including', 'within', 'their', 'they', 'what', 'about', 'more', 'also', 'such', 'join', 'looking', 'would', 'should', 'must', 'well', 'good', 'strong', 'into', 'across', 'other', 'per', 'any', 'not', 'but', 'has', 'how', 'its', 'job', 'new', 'one', 'out', 'use', 'year', 'years', 'day', 'days', 'part', 'time', 'apply', 'applicants', 'candidate', 'candidates', 'opportunity', 'company', 'please', 'help', 'make', 'working', 'both', 'each', 'where', 'which', 'while', 'when', 'than', 'them', 'then', 'there', 'these', 'those', 'being', 'been', 'over', 'very', 'just', 'like', 'some', 'only', 'plus', 'etc', 'need', 'needs', 'seeking', 'seek', 'want', 'ideally', 'preferred', 'essential', 'desirable', 'required', 'requirements', 'responsibilities', 'benefits', 'salary', 'apply', 'applications', 'we', 'us', 'an', 'in', 'of', 'to', 'or', 'on', 'at', 'as', 'be', 'is', 'it', 'around', 'now', 'related', 'relevant', 'qualification', 'qualifications', 'scale', 'challenges', 'challenge', 'alongside', 'gbr', 'gb', 'ltd', 'plc', 'inc', 'location', 'hybrid', 'remote', 'office', 'based', 'full', 'permanent', 'contract', 'start', 'date', 'closing', 'deadline', 'apply', 'today', 'world', 'leading', 'exciting', 'passionate', 'great', 'excellent', 'range', 'variety', 'within', 'across', 'every', 'many', 'most', 'may', 'might', 'could', 'via', 'including', 'include', 'includes', 'etc', 'get', 'gain', 'offer', 'offers', 'provide', 'support', 'ensure', 'ability', 'knowledge', 'understanding', 'business', 'people', 'customers', 'clients', 'opportunities', 'key', 'main', 'high', 'quality', 'level', 'levels', 'whilst', 'ideal', 'successful', 'diverse', 'inclusive', 'equal', 'employer'];

    /** @var array<string, int> */
    public array $scores = [];

    /** @var list<string> */
    public array $found = [];

    /** @var list<string> */
    public array $missing = [];

    /** @var list<string> */
    public array $formatting = [];

    /** @var list<string> */
    public array $suggestions = [];

    /** @var array{matched: list<string>, missing: list<string>}|null */
    public ?array $keywordMatch = null;

    public function __construct(public readonly ParsedResume $parsed, public readonly ResumeText $source, public readonly ?string $jobAdvert = null)
    {
        $this->scoreHeadings();
        $this->scoreContact();
        $this->scoreSkills();
        $this->scoreEducation();
        $this->scoreExperience();
        $this->scoreKeywords();
        $this->checkFormatting();
    }

    public static function analyse(ResumeText $source, ?string $jobAdvert = null): self
    {
        return new self((new ResumeParser)->parse($source->text), $source, filled($jobAdvert) ? $jobAdvert : null);
    }

    public function total(): int
    {
        return array_sum($this->scores);
    }

    public function grade(): string
    {
        return match (true) {
            $this->total() >= 80 => 'Strong',
            $this->total() >= 60 => 'Good, with gaps',
            $this->total() >= 40 => 'Needs work',
            default => 'Likely to be filtered out',
        };
    }

    private function scoreHeadings(): void
    {
        $core = ['education' => 'Education', 'experience' => 'Experience', 'skills' => 'Skills'];
        $score = 0;
        foreach ($core as $key => $label) {
            if (array_key_exists($key, $this->parsed->sections)) {
                $score += 5;
            } else {
                $this->missing[] = "A clear {$label} heading";
                $this->suggestions[] = "Add a heading that says exactly \"{$label}\". Systems look for the standard words, not creative ones.";
            }
        }
        if ($this->parsed->unclearHeadings === []) {
            $score += 5;
        } else {
            $list = implode(', ', array_map(fn ($h) => "\"{$h}\"", array_slice($this->parsed->unclearHeadings, 0, 4)));
            $this->formatting[] = "Headings a system may not recognise: {$list}.";
            $this->suggestions[] = 'Rename unusual headings to standard ones such as Profile, Education, Experience, Skills or Projects.';
        }
        if (count($this->parsed->headings) > 0) {
            $this->found[] = count($this->parsed->headings).' headings, '.(count($this->parsed->headings) - count($this->parsed->unclearHeadings)).' of them standard.';
        }
        $this->scores['headings'] = $score;
    }

    private function scoreContact(): void
    {
        $score = 0;
        foreach (['name' => [5, 'Name'], 'email' => [8, 'Email address'], 'phone' => [7, 'Phone number']] as $field => [$points, $label]) {
            $value = $this->parsed->{$field};
            if ($value !== null) {
                $score += $points;
                $this->found[] = "{$label}: {$value}";
            } else {
                $this->missing[] = $label;
                $this->suggestions[] = match ($field) {
                    'name' => 'Put your full name on its own line at the very top.',
                    'email' => 'Add a professional email address near the top, as plain text.',
                    default => 'Most UK employers expect a phone number. Add one with its country code, for example +44 7700 900123.',
                };
            }
        }
        if ($this->parsed->links !== []) {
            $this->found[] = 'Links: '.implode(', ', $this->parsed->links);
        }
        $this->scores['contact'] = $score;
    }

    private function scoreSkills(): void
    {
        $count = count($this->parsed->skills);
        $this->scores['skills'] = match (true) {
            $count >= 8 => 15,
            $count >= 4 => 10,
            $count >= 1 => 5,
            default => 0,
        };
        if ($count > 0) {
            $this->found[] = "{$count} skills: ".implode(', ', array_slice($this->parsed->skills, 0, 15)).($count > 15 ? ', ...' : '');
        }
        if ($count < 8) {
            $this->suggestions[] = $count === 0
                ? 'Add a Skills section listing tools, languages and abilities, separated by commas.'
                : 'List more skills, ideally 8 or more, using the exact names from job adverts.';
        }
    }

    private function scoreEducation(): void
    {
        $lines = $this->parsed->section('education');
        if ($lines === []) {
            $this->scores['education'] = 0;

            return;
        }
        $text = implode(' ', $lines);
        $score = 8;
        if (preg_match('/\b(BSc|BA|BEng|MEng|MSc|MA|MPhil|PhD|LLB|BTEC|HND|degree|bachelor|master|diploma|A[- ]?levels?|GCSEs?|IB|foundation)\b/i', $text)) {
            $score += 4;
        } else {
            $this->suggestions[] = 'Name your qualification in full, for example BSc Computer Science.';
        }
        if (preg_match('/\b(19|20)\d{2}\b/', $text)) {
            $score += 3;
        } else {
            $this->suggestions[] = 'Add dates to your education, such as 2024 to 2027 (expected).';
        }
        $this->found[] = 'Education: '.self::lines(count($lines)).'.';
        $this->scores['education'] = $score;
    }

    private function scoreExperience(): void
    {
        $lines = $this->parsed->section('experience');
        if ($lines === []) {
            $this->scores['experience'] = 0;

            return;
        }
        $text = implode("\n", $lines);
        $score = 8;
        if (preg_match('/\b(19|20)\d{2}\b|present|current/i', $text)) {
            $score += 4;
        } else {
            $this->suggestions[] = 'Give every role a start and end date (month and year).';
        }
        if (count($lines) >= 4) {
            $score += 4;
        } else {
            $this->suggestions[] = 'Describe each role in two to four bullet points.';
        }
        if (preg_match('/\d+\s*%|£\s?\d|\$\s?\d|\b\d{2,}\b/', $text)) {
            $score += 4;
        } else {
            $this->suggestions[] = 'Show results with numbers, such as "cut processing time by 20%" or "served 150 customers a day".';
        }
        $weak = array_values(array_filter(self::WEAK_PHRASES, fn ($phrase) => stripos($text, $phrase) !== false));
        if ($weak !== []) {
            $this->suggestions[] = 'Replace "'.implode('", "', $weak).'" with what you achieved, starting with a strong verb such as built, led or improved.';
        }
        $this->found[] = 'Experience: '.self::lines(count($lines)).'.';
        if ($this->parsed->section('projects') !== []) {
            $this->found[] = 'Projects: '.self::lines(count($this->parsed->section('projects'))).'.';
        }
        $this->scores['experience'] = $score;
    }

    private function scoreKeywords(): void
    {
        $cv = mb_strtolower($this->source->text);
        if ($this->jobAdvert !== null) {
            $keywords = $this->advertKeywords($this->jobAdvert);
            // "engineer" in the advert also counts when the CV says "engineers"
            $matched = array_values(array_filter($keywords, fn ($word) => preg_match('/\b'.preg_quote($word, '/').'(s|es)?\b/u', $cv)));
            $absent = array_values(array_diff($keywords, $matched));
            $this->keywordMatch = ['matched' => $matched, 'missing' => $absent];
            $this->scores['keywords'] = $keywords === [] ? 0 : (int) round(10 * count($matched) / count($keywords));
            if ($absent !== []) {
                $this->suggestions[] = 'The job advert mentions these, but your CV does not: '.implode(', ', array_slice($absent, 0, 10)).'. Add the ones you genuinely have.';
            }

            return;
        }
        // without an advert, judge the language itself: how many description lines open with a
        // strong verb. Short lines are titles, dates or tool lists, so only lines of six or more
        // words count
        $lines = array_values(array_filter(
            array_merge($this->parsed->section('experience'), $this->parsed->section('projects')),
            fn ($line) => str_word_count($line) >= 6,
        ));
        $strong = count(array_filter($lines, fn ($line) => self::startsWithStrongVerb($line)));
        $this->scores['keywords'] = $lines === [] ? 0 : min(10, (int) round(10 * $strong / max(3, (int) ceil(count($lines) * 0.6))));
        if ($this->scores['keywords'] < 7) {
            $this->suggestions[] = 'Start more bullet points with a strong verb such as built, led, designed or improved. Paste a job advert to check its keywords too.';
        }
    }

    private static function startsWithStrongVerb(string $line): bool
    {
        $first = strtok(mb_strtolower(preg_replace('/^[\s\x{2022}\x{2013}*-]+/u', '', $line)), ' ,');
        if ($first === false) {
            return false;
        }
        foreach ([$first, preg_replace('/(ing|ed|d|s)$/', '', $first), preg_replace('/(ied)$/', 'y', $first), preg_replace('/(ed)$/', 'e', $first)] as $form) {
            if (in_array($form, self::STRONG_VERBS, true)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function advertKeywords(string $advert): array
    {
        preg_match_all('/[\p{L}][\p{L}\d+#.\/-]{2,}/u', mb_strtolower($advert), $words);
        $counts = [];
        foreach ($words[0] as $word) {
            $word = rtrim($word, '.');
            if (in_array($word, self::STOP_WORDS, true) || mb_strlen($word) < 3) {
                continue;
            }
            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }
        // a plural folds into its singular only when both appear, so "network" and "networks"
        // become one keyword while a name such as "kubernetes" is left whole
        foreach (array_keys($counts) as $word) {
            $single = preg_replace('/s$/', '', $word);
            if ($single !== $word && isset($counts[$single])) {
                $counts[$single] += $counts[$word];
                unset($counts[$word]);
            }
        }
        arsort($counts);

        return array_slice(array_keys($counts), 0, 20);
    }

    private function checkFormatting(): void
    {
        $s = $this->source->signals;
        if (($s['scanned'] ?? false) === true) {
            $this->formatting[] = 'Almost no text could be read. The file is probably a scan or an image, which these systems cannot read at all.';
        }
        if (($s['tables'] ?? 0) > 0) {
            $this->formatting[] = 'Uses '.$s['tables'].' table(s). Many systems read tables out of order or skip them.';
        }
        if (($s['columns'] ?? 1) > 1) {
            $this->formatting[] = 'Uses '.$s['columns'].' columns. Systems often read across columns and mix sections together.';
        }
        if (($s['text_boxes'] ?? 0) > 0) {
            $this->formatting[] = 'Uses text boxes, whose contents are often skipped.';
        }
        if (($s['images'] ?? 0) > 0) {
            $this->formatting[] = 'Contains '.$s['images'].' image(s) or icon(s). Any text inside them cannot be read.';
        }
        if (($s['header_contact'] ?? false) === true) {
            $this->formatting[] = 'Contact details are in the page header, which many systems ignore.';
        }
        if (($s['pages'] ?? 1) > 2) {
            $this->formatting[] = 'Runs to '.$s['pages'].' pages. Two pages is the usual UK limit for students and graduates; a page that holds only a line or two is the easiest to remove.';
        }
        if (preg_match('/(^|\n)\s*I\s/', $this->source->text)) {
            $this->formatting[] = 'Uses "I" to start sentences. CVs read better without it: "Built a web app", not "I built a web app".';
        }
        $layout = ($s['tables'] ?? 0) + ($s['text_boxes'] ?? 0) + ($s['images'] ?? 0) + (($s['columns'] ?? 1) > 1 ? 1 : 0);
        if ($layout > 0) {
            $this->suggestions[] = 'Use a simple single-column layout with plain text headings, no tables, text boxes or images.';
        }
    }

    private static function lines(int $count): string
    {
        return $count === 1 ? '1 line' : "{$count} lines";
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'parsed' => $this->parsed->toArray(),
            'score' => [
                'total' => $this->total(),
                'grade' => $this->grade(),
                'areas' => collect(self::AREAS)->mapWithKeys(fn ($area, $key) => [$key => ['label' => $area['label'], 'score' => $this->scores[$key], 'max' => $area['max']]])->all(),
            ],
            'found' => $this->found,
            'missing' => $this->missing,
            'formatting' => $this->formatting,
            'suggestions' => array_values(array_unique($this->suggestions)),
            'keywords' => $this->keywordMatch,
            'source' => ['format' => $this->source->format, 'signals' => $this->source->signals],
        ];
    }

    public function toMarkdown(): string
    {
        $data = $this->toArray();
        $out = ['# CV check report', '', "**Score: {$data['score']['total']} / 100** ({$data['score']['grade']})", '', '| Area | Score |', '| --- | --- |'];
        foreach ($data['score']['areas'] as $area) {
            $out[] = "| {$area['label']} | {$area['score']} / {$area['max']} |";
        }
        foreach (['found' => 'What was found', 'missing' => 'Missing', 'formatting' => 'Formatting issues', 'suggestions' => 'Suggestions'] as $key => $title) {
            $out[] = '';
            $out[] = "## {$title}";
            $out[] = '';
            $out = array_merge($out, $data[$key] === [] ? ['- Nothing to report.'] : array_map(fn ($line) => "- {$line}", $data[$key]));
        }
        if ($data['keywords'] !== null) {
            $out[] = '';
            $out[] = '## Job advert keywords';
            $out[] = '';
            $out[] = '- In your CV: '.($data['keywords']['matched'] === [] ? 'none' : implode(', ', $data['keywords']['matched']));
            $out[] = '- Not in your CV: '.($data['keywords']['missing'] === [] ? 'none' : implode(', ', $data['keywords']['missing']));
        }

        return implode("\n", $out)."\n";
    }
}
