<?php

namespace App\Support\Jobs;

use Illuminate\Support\Carbon;

/**
 * Decides from a job title alone whether a role is for students and graduates and which kind it is.
 * Every term matches whole words only: plain substring checks let "internal" count as an internship,
 * "replacement" as a placement and "Senior Graduate Recruiter" as a graduate role.
 */
class RoleType
{
    private const PLACEMENT = ['placement', 'placement year', 'year in industry', 'industrial placement', 'industrial year',
        'sandwich year', 'sandwich placement', '12 month placement', '12-month placement', 'year-long placement', 'year long placement', 'work placement'];

    private const INSIGHT = ['spring week', 'spring insight', 'insight week', 'insight day', 'insight days', 'insight programme',
        'insight program', 'spring programme', 'spring program', 'discovery programme', 'discovery day', 'spring intern', 'spring internship'];

    private const GRADUATE = ['graduate', 'graduates', 'grad', 'graduate scheme', 'graduate programme', 'graduate program', 'grad scheme',
        'new grad', 'entry level', 'entry-level', 'early careers', 'early career', 'early talent'];

    private const INTERNSHIP = ['intern', 'interns', 'internship', 'internships', 'summer intern', 'off-cycle intern', 'co-op',
        'student researcher', 'undergraduate researcher', 'vacation scheme', 'summer analyst'];

    /** staff who run these schemes and senior roles that mention them are never listed */
    private const STAFF = '/\b(senior|sr\.?|staff|lead|principal|head of|director|manager|vp|vice president|architect|recruiter|recruitment|'
        .'talent acquisition|coordinator|co-ordinator|officer|adviser|advisor|lecturer|supervisor|mentor|ii|iii|iv)\b/i';

    private const INTERNAL = '/^internal\b|\binternal\s+(engineering|engineer|audit|auditor|ai|ops|operations|tools|platform|systems|it|hr|'
        .'recruiter|recruiting|transfer|mobility|communications|comms)\b/i';

    /** part-time roles a student can take alongside study; anything else part-time is left out */
    private const STUDENT_PART_TIME = '/\b(student|students|weekend|weekends|evening|evenings|seasonal|christmas|casual|temporary|temp|'
        .'ambassador|tutor|sales assistant|retail assistant|customer assistant|store assistant|shop assistant|teaching assistant|'
        .'team member|crew|barista|cashier|waiter|waitress|host|steward|stewardess|customer service|store colleague|'
        .'warehouse operative|picker|packer|lifeguard|usher|promoter)\b/i';

    /** the kind of role the title names; null when it is not a student or graduate role */
    public static function classify(string $title, bool $partTime = false): ?string
    {
        $t = mb_strtolower(trim(preg_replace('/\s+/', ' ', $title)));
        if ($t === '' || preg_match('/\b(apprentice|apprenticeship|apprenticeships)\b/', $t)) {
            // apprenticeships are a different route, often for school leavers, so they are not mixed in
            return null;
        }
        $staff = (bool) preg_match(self::STAFF, $t);
        $intern = self::has(self::INTERNSHIP, $t) && ! preg_match(self::INTERNAL, $t);

        if ($partTime) {
            return ! $staff && preg_match(self::STUDENT_PART_TIME, $t) ? 'part-time' : null;
        }
        if ($staff && ! $intern) {
            return null;
        }

        return match (true) {
            self::has(self::PLACEMENT, $t) => 'placement',
            self::has(self::INSIGHT, $t) => 'insight',
            self::has(self::GRADUATE, $t) => 'graduate',
            $intern => 'internship',
            default => null,
        };
    }

    /**
     * True when nothing in the title places the role outside the recruitment cycle being listed:
     * every year it names must fall within the cycle and a season it names must not be over.
     */
    public static function inCycle(string $title, ?Carbon $now = null): bool
    {
        $now ??= now();
        [$first, $last] = config('vitafolio.jobs_cycle');
        $t = mb_strtolower($title);

        // "2026/27" and "2026-27" name the second year in short
        preg_match_all('/\b(20\d\d)\s*[\/-]\s*(\d\d)\b/', $t, $short, PREG_SET_ORDER);
        $years = array_map(fn ($m) => (int) (substr($m[1], 0, 2).$m[2]), $short);
        preg_match_all('/\b(20\d\d)\b/', $t, $full);
        $years = array_merge($years, array_map('intval', $full[1]));
        foreach ($years as $year) {
            if ($year < $first || $year > $last) {
                return false;
            }
        }

        // a spring or summer role is over once that season ends in the latest year the title names
        if ($years !== [] && preg_match('/\b(spring|summer)\b/', $t, $season)) {
            $ends = Carbon::create(max($years), $season[1] === 'spring' ? 6 : 9, 30)->endOfDay();
            if ($now->greaterThan($ends)) {
                return false;
            }
        }

        return true;
    }

    /** @param  list<string>  $terms */
    private static function has(array $terms, string $text): bool
    {
        foreach ($terms as $term) {
            if (preg_match('/(?<![\w-])'.preg_quote($term, '/').'(?![\w-])/u', $text)) {
                return true;
            }
        }

        return false;
    }
}
