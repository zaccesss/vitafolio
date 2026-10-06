<?php

namespace App\Support;

use App\Models\Cv;

/** builds starter latex documents from a cv, with every user value escaped for tex */
class Latex
{
    public const TEMPLATES = [
        'classic' => 'Classic: clear headings, one column',
        'compact' => 'Compact: fits a lot on one page',
        'modern' => 'Modern: accent rule and sidebar details',
        'academic' => 'Academic: education first, room for publications',
        'sidebar' => 'Sidebar: two columns, with contact and skills beside the main story',
        'elegant' => 'Elegant: a centred serif page with small capitals',
        'minimal' => 'Minimal: clean sans-serif with plenty of white space',
    ];

    /** the characters tex treats as commands are replaced, so a cv can never inject latex code */
    public static function escape(?string $text): string
    {
        $map = [
            '\\' => '\\textbackslash{}', '{' => '\\{', '}' => '\\}', '$' => '\\$', '&' => '\\&',
            '#' => '\\#', '^' => '\\textasciicircum{}', '_' => '\\_', '~' => '\\textasciitilde{}', '%' => '\\%',
        ];

        return strtr((string) $text, $map);
    }

    /** turns plain cv text into latex paragraphs, with lines starting "- " as bullet lists */
    public static function blocks(?string $text): string
    {
        $out = [];
        $inList = false;
        foreach (preg_split('/\R/', trim((string) $text)) ?: [] as $line) {
            $line = trim($line);
            if (str_starts_with($line, '- ')) {
                if (! $inList) {
                    $out[] = '\\begin{itemize}';
                    $inList = true;
                }
                $out[] = '  \\item '.self::escape(substr($line, 2));

                continue;
            }
            if ($inList) {
                $out[] = '\\end{itemize}';
                $inList = false;
            }
            $out[] = $line === '' ? '' : self::escape($line).'\\\\';
        }
        if ($inList) {
            $out[] = '\\end{itemize}';
        }

        return implode("\n", $out);
    }

    public static function starter(Cv $cv, string $template): string
    {
        $user = $cv->user;
        $vars = [
            '{{NAME}}' => self::escape($user->name),
            '{{HEADLINE}}' => self::escape($cv->displayHeadline()),
            '{{CONTACT}}' => self::escape(implode(' | ', array_filter([$user->location, $cv->show_email ? $user->email : null, ...array_column(Links::parse($user->links), 'url')]))),
            '{{SUMMARY}}' => self::blocks($cv->profile),
            '{{EXPERIENCE}}' => self::blocks($cv->experience),
            '{{EDUCATION}}' => self::blocks($cv->education),
            '{{SKILLS}}' => self::escape($cv->tags->pluck('name')->implode(', ')),
            '{{PROJECTS}}' => $cv->projects->map(fn ($p) => '\\textbf{'.self::escape($p->title).'}'.($p->description ? ' -- '.self::escape($p->description) : ''))->implode("\\\\\n"),
        ];
        $source = (string) file_get_contents(resource_path('latex/'.(array_key_exists($template, self::TEMPLATES) ? $template : 'classic').'.tex'));

        return strtr($source, $vars);
    }
}
