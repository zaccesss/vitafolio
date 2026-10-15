<?php

namespace App\Support;

/**
 * Starting points for a CV, one per sector students and graduates apply to. Every value is a
 * structure with [square brackets] to replace, never a finished CV, so nobody publishes template
 * text by accident. Skills are the ones UK employers in the sector commonly ask for; the editor
 * tells people to keep only those they can show. They stay in English, like the letter templates,
 * since the advice is written for UK applications.
 */
class CvTemplates
{
    public const KINDS = [
        'finance' => 'Finance and accounting',
        'technology' => 'Technology and software',
        'engineering' => 'Engineering',
        'healthcare' => 'Healthcare',
        'education' => 'Education',
        'hospitality' => 'Hospitality',
        'retail' => 'Retail and customer service',
        'creative' => 'Creative and media',
        'law' => 'Law',
        'public-sector' => 'Public sector',
    ];

    /**
     * the labels in the reader's language. Written out with __() so the translation scanner sees
     * each one; KINDS stays the list of valid template names
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'finance' => __('Finance and accounting'),
            'technology' => __('Technology and software'),
            'engineering' => __('Engineering'),
            'healthcare' => __('Healthcare'),
            'education' => __('Education'),
            'hospitality' => __('Hospitality'),
            'retail' => __('Retail and customer service'),
            'creative' => __('Creative and media'),
            'law' => __('Law'),
            'public-sector' => __('Public sector'),
        ];
    }

    public static function exists(?string $kind): bool
    {
        return $kind !== null && array_key_exists($kind, self::KINDS);
    }

    /**
     * the editor fields a template fills: headline, key_language, skills, profile, experience and education
     *
     * @return array<string, string>
     */
    public static function fields(string $kind): array
    {
        $t = self::TEMPLATES[$kind];

        return [
            'headline' => $t['headline'],
            'key_language' => $t['tool'],
            'skills' => implode(', ', $t['skills']),
            'profile' => $t['profile'],
            'experience' => $t['experience'],
            'education' => self::education($t['education']),
        ];
    }

    /** the same education block for every sector, with one line for what that sector looks for */
    private static function education(string $focus): string
    {
        return "[Degree, for example BSc (Hons) Subject], [university], [start year] to [expected graduation year]\n"
            ."- [Expected or achieved classification or current average]\n"
            ."- {$focus}\n"
            ."- Dissertation or final project: [title and one line on what you found or built]\n\n"
            ."[A levels, BTEC or equivalent], [school or college], [years]\n"
            .'- [Subjects and grades]';
    }

    private const TEMPLATES = [
        'finance' => [
            'headline' => '[Course] student seeking a [role, for example audit or analyst] placement in finance',
            'tool' => '[Main tool, for example Excel]',
            'skills' => ['Excel', 'Financial analysis', 'Attention to detail', 'Data analysis', 'Commercial awareness', 'Communication', 'Teamwork'],
            'profile' => '[Year] [course] student at [university] with a strong interest in [area, for example audit, corporate finance or financial planning]. Comfortable with numbers and spreadsheets, I have [one example of analysis or handling money]. Looking for a [placement, internship or graduate role] where I can build towards [qualification, for example ACA, ACCA or CFA].',
            'experience' => "[Job title], [organisation], [month year] to [month year or Present]\n"
                ."- [Task involving numbers, records or money], which [result, ideally with a number]\n"
                ."- [Example of accuracy, for example reconciling, checking or reporting]\n"
                ."- [Example of working with customers, clients or a team]\n\n"
                ."[Society role, for example Treasurer], [society], [years]\n"
                .'- [Budget you managed or report you prepared, with an amount or outcome]',
            'education' => 'Relevant modules: [for example financial accounting, economics, statistics]',
        ],
        'technology' => [
            'headline' => '[Course] student seeking a [role, for example software engineering] placement',
            'tool' => '[Main language, for example Python]',
            'skills' => ['Python', 'Java', 'SQL', 'Git', 'Problem solving', 'Testing', 'Teamwork'],
            'profile' => '[Year] [course] student at [university] who enjoys building [kind of software, for example web apps, tools or data pipelines]. I have built [project] using [languages and tools], which [what it does or who uses it]. Looking for a [placement, internship or graduate role] in [area, for example backend, data or cloud].',
            'experience' => "[Project or job title], [organisation or personal project], [month year] to [month year or Present]\n"
                ."- Built [feature or product] with [languages and tools], used by [who or how many]\n"
                ."- Improved [something measurable, for example speed or test coverage] by [amount]\n"
                ."- Worked in [team size or method, for example a team of four using Git and pull requests]\n\n"
                ."[Hackathon, open source or society role], [where], [year]\n"
                .'- [What you contributed and the outcome, with a link if public]',
            'education' => 'Relevant modules: [for example data structures, databases, software engineering]',
        ],
        'engineering' => [
            'headline' => '[Course] student seeking a [discipline, for example mechanical or electrical] engineering placement',
            'tool' => '[Main tool, for example MATLAB or CAD software]',
            'skills' => ['Technical drawing', 'CAD', 'MATLAB', 'Problem solving', 'Health and safety', 'Report writing', 'Teamwork'],
            'profile' => '[Year] [course] student at [university] interested in [area, for example power systems, manufacturing or structures]. Through [project or lab work] I have designed, built and tested [what], using [tools or methods]. Looking for a [placement, internship or graduate role] with a route to [Chartered or Incorporated Engineer status, if relevant].',
            'experience' => "[Project title or job title], [team, company or university], [month year] to [month year or Present]\n"
                ."- Designed [part, circuit or system] to meet [requirement or constraint]\n"
                ."- Tested [what] and improved [measure, for example weight, cost or efficiency] by [amount]\n"
                ."- Wrote [report or documentation] and presented to [audience]\n\n"
                ."[Part-time job or society role], [organisation], [years]\n"
                .'- [Responsibility that shows safety, reliability or teamwork]',
            'education' => 'Relevant modules: [for example mechanics, electronics, control, materials]',
        ],
        'healthcare' => [
            'headline' => '[Course] student seeking a [role, for example healthcare assistant] position',
            'tool' => '[Main skill or system, for example patient records]',
            'skills' => ['Patient care', 'Communication', 'Empathy', 'Infection control', 'Record keeping', 'Confidentiality', 'Teamwork'],
            'profile' => '[Year] [course] student at [university] committed to [area, for example patient care, community health or research]. In [placement, volunteering or job] I supported [who] by [what you did]. Looking for a [role] where I can [what you want to contribute], working within [relevant standard or code, if any].',
            'experience' => "[Role, for example Healthcare assistant or Volunteer], [setting or organisation], [month year] to [month year or Present]\n"
                ."- Supported [number or type of patients or service users] with [care tasks]\n"
                ."- Followed [infection control, safeguarding or record keeping procedure]\n"
                ."- Worked with [nurses, doctors or a multidisciplinary team] to [outcome]\n\n"
                ."[Customer-facing job or caring responsibility], [organisation], [years]\n"
                .'- [Example of calm communication or reliability]',
            'education' => 'Clinical placements or training: [settings, hours and certificates, for example first aid]',
        ],
        'education' => [
            'headline' => '[Course] student seeking a [role, for example teaching assistant or tutor] position',
            'tool' => '[Main subject, for example Mathematics]',
            'skills' => ['Lesson support', 'Communication', 'Safeguarding awareness', 'Behaviour management', 'Patience', 'Organisation', 'Teamwork'],
            'profile' => '[Year] [course] student at [university] who enjoys helping others learn [subject]. Through [tutoring, mentoring or volunteering] I have supported [age group or number of learners] to [outcome]. Looking for a [role] and working towards [teacher training route, if relevant].',
            'experience' => "[Role, for example Tutor or Classroom volunteer], [school or organisation], [month year] to [month year or Present]\n"
                ."- Supported [number] learners aged [ages] with [subject or skill]\n"
                ."- Planned [activity or resource] that helped learners [outcome]\n"
                ."- Followed [safeguarding or behaviour] policies and kept [records or feedback]\n\n"
                ."[Youth work, coaching or society role], [organisation], [years]\n"
                .'- [Example of leading or encouraging a group]',
            'education' => 'Checks and training: [for example enhanced DBS, safeguarding course]',
        ],
        'hospitality' => [
            'headline' => '[Role, for example front of house or barista] with experience in busy service',
            'tool' => '[Main system, for example a till or booking system]',
            'skills' => ['Customer service', 'Food hygiene', 'Cash handling', 'Working under pressure', 'Communication', 'Reliability', 'Teamwork'],
            'profile' => 'Friendly and reliable [student or worker] with [length of time] in [setting, for example cafes, restaurants or hotels]. I stay calm during busy shifts and [one strength, for example remembering regulars or upselling]. Available [days and hours, for example evenings and weekends].',
            'experience' => "[Job title], [venue], [month year] to [month year or Present]\n"
                ."- Served [number] customers a shift while keeping [standard, for example order accuracy]\n"
                ."- Handled [cash, card payments or bookings] and closed the till accurately\n"
                ."- Followed [food hygiene or allergen] procedures\n\n"
                ."[Other job or volunteering], [organisation], [years]\n"
                .'- [Example of teamwork, punctuality or handling a complaint]',
            'education' => 'Certificates: [for example Level 2 Food Hygiene, personal licence]',
        ],
        'retail' => [
            'headline' => '[Role, for example sales assistant] with strong customer service skills',
            'tool' => '[Main system, for example tills or stock systems]',
            'skills' => ['Customer service', 'Cash handling', 'Stock control', 'Visual merchandising', 'Communication', 'Reliability', 'Teamwork'],
            'profile' => 'Reliable [student or worker] with [length of time] in [retail or customer service setting]. I enjoy helping customers find what they need and [one strength, for example hitting targets or handling queries]. Available [days and hours, for example weekends and holidays].',
            'experience' => "[Job title], [store or company], [month year] to [month year or Present]\n"
                ."- Helped [number] customers a day and [result, for example met sales or survey targets]\n"
                ."- Processed [payments, refunds or click and collect orders] accurately\n"
                ."- Restocked and arranged [area] to [standard or plan]\n\n"
                ."[Other job or volunteering], [organisation], [years]\n"
                .'- [Example of solving a customer problem or training a colleague]',
            'education' => 'Relevant achievements: [for example employee of the month, training completed]',
        ],
        'creative' => [
            'headline' => '[Course] student and [discipline, for example designer or writer] with a portfolio',
            'tool' => '[Main tool, for example Figma or Adobe Creative Cloud]',
            'skills' => ['Design', 'Storytelling', 'Adobe Creative Cloud', 'Figma', 'Social media', 'Feedback and iteration', 'Teamwork'],
            'profile' => '[Year] [course] student at [university] who creates [kind of work, for example brand identities, video or copy]. My portfolio at [link] includes [project] for [client or audience]. Looking for a [placement, internship or junior role] in [area, for example design, marketing or content].',
            'experience' => "[Project or job title], [client, company or personal], [month year] to [month year or Present]\n"
                ."- Created [work] for [audience] that [result, for example reach, sales or feedback]\n"
                ."- Took a brief from [who] and delivered [what] by [deadline]\n"
                ."- Used [tools] and revised the work after [feedback or testing]\n\n"
                ."[Society, freelance or volunteering role], [organisation], [years]\n"
                .'- [Example with a link to the work]',
            'education' => 'Portfolio: [link] and exhibitions or publications: [if any]',
        ],
        'law' => [
            'headline' => '[Course] student seeking a [vacation scheme, training contract or paralegal] role',
            'tool' => '[Main research tool, for example Westlaw or LexisNexis]',
            'skills' => ['Legal research', 'Written communication', 'Attention to detail', 'Commercial awareness', 'Analysis', 'Client care', 'Teamwork'],
            'profile' => '[Year] [course] student at [university] interested in [area of law, for example commercial, family or public law]. Through [mooting, pro bono or work experience] I have [what you did, for example researched case law or drafted documents]. Looking for a [role] on the route to qualifying as a [solicitor or barrister].',
            'experience' => "[Role, for example Pro bono volunteer or Legal assistant], [firm or organisation], [month year] to [month year or Present]\n"
                ."- Researched [area] and summarised [number] [cases or documents] for [who]\n"
                ."- Drafted [letters, notes or forms] checked by [supervising solicitor or tutor]\n"
                ."- Handled [client or customer] queries with confidentiality\n\n"
                ."[Mooting, debating or part-time job], [organisation], [years]\n"
                .'- [Result or skill, for example reached the semi-final]',
            'education' => 'Relevant modules: [for example contract, tort, public law] and SQE progress: [if any]',
        ],
        'public-sector' => [
            'headline' => '[Course] student seeking a [scheme or role, for example civil service] position',
            'tool' => '[Main skill, for example data analysis or policy research]',
            'skills' => ['Communication', 'Working together', 'Making effective decisions', 'Delivering at pace', 'Data analysis', 'Report writing', 'Public service'],
            'profile' => '[Year] [course] student at [university] motivated by [public service area, for example health, environment or justice]. In [role or project] I [what you did that helped people or improved a service]. Looking for a [scheme or role] where I can [what you want to contribute].',
            'experience' => "[Role], [organisation], [month year] to [month year or Present]\n"
                ."- Situation: [the challenge]. Action: [what you did]. Result: [the outcome, with a number]\n"
                ."- Worked with [colleagues, partners or the public] to [outcome]\n"
                ."- Made a decision about [what] using [evidence or data]\n\n"
                ."[Volunteering, society or part-time role], [organisation], [years]\n"
                .'- [Example of serving a community or delivering on time]',
            'education' => 'Relevant modules: [for example policy, economics, statistics]',
        ],
    ];
}
