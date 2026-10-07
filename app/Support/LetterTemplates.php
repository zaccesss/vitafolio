<?php

namespace App\Support;

/**
 * Starting points for a cover letter, one per kind of role students and graduates apply for. Each
 * is a structure with [square brackets] to replace, never a finished letter, so nobody sends
 * template text by accident. They stay in English, like the legal pages, since the advice is
 * written for UK applications.
 */
class LetterTemplates
{
    public const KINDS = [
        'internship' => 'Internship',
        'placement' => 'Placement year',
        'graduate' => 'Graduate role',
        'part-time' => 'Part-time or student job',
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
            'internship' => __('Internship'),
            'placement' => __('Placement year'),
            'graduate' => __('Graduate role'),
            'part-time' => __('Part-time or student job'),
        ];
    }

    public static function exists(?string $kind): bool
    {
        return $kind !== null && array_key_exists($kind, self::KINDS);
    }

    public static function body(string $kind, string $name): string
    {
        $opening = [
            'internship' => 'I am writing to apply for the [role] internship at [company], advertised on [where you saw it]. I am a [year] student studying [course] at [university]. This internship is a chance to put what I have learnt into practice on [something specific the team works on].',
            'placement' => 'I am writing to apply for the [role] placement at [company] for [placement dates, for example June 2027 to June 2028]. I am a [year] student studying [course] at [university]. My course includes a year in industry, which I want to spend building [skill or area] on real projects.',
            'graduate' => 'I am writing to apply for the [role] graduate position at [company]. I will graduate from [university] in [month and year] with a [degree and expected classification] in [course]. I want to start my career at [company] because [one specific reason, such as a product, project or value of theirs].',
            'part-time' => 'I am writing to apply for the part-time [role] position at [company]. I am a student at [university] and am available [days and hours you can work], which fits around my studies.',
        ][$kind];

        $middle = [
            'internship' => "During [module, project or society], I [what you did], which [result, ideally with a number]. This taught me [skill the advert asks for], which I would bring to [the team or task].\n\nI am particularly interested in [company] because [something you found on their website, news or social media]. I would welcome the chance to learn from your team while contributing to [a goal from the advert].",
            'placement' => "In my studies I have [project or coursework], where I [what you did and the result]. Outside my course, I [part-time job, society role or personal project], which showed me [skill the advert asks for].\n\nA year at [company] would let me [what you want to learn]. I would bring [two strengths matched to the advert] to the team from my first week.",
            'graduate' => "During my degree I [main achievement, with a number where you can]. In [internship, placement or job] I [what you did and its impact]. These experiences built the [two or three skills from the advert] the role asks for.\n\n[Company]'s [graduate scheme, culture or recent work] appeals to me because [reason]. I am keen to contribute to [a goal from the advert] and to grow through [training or rotations the scheme offers].",
            'part-time' => "In [previous job, volunteering or society role] I [what you did, such as serving customers, handling cash or working in a team], which taught me [reliability, communication or another skill from the advert].\n\nI am a reliable and friendly person who would enjoy being part of the team at [company].",
        ][$kind];

        return "Dear [name of the hiring manager, otherwise Hiring team],\n\n{$opening}\n\n{$middle}\n\nThank you for considering my application. I would welcome the chance to discuss how I could contribute. I am available for an interview at your convenience.\n\nYours sincerely,\n{$name}";
    }
}
