<?php

namespace App\Support;

use App\Models\Cv;
use Illuminate\Support\Str;

/** converts between a cv and the open json resume format (jsonresume.org) */
class JsonResume
{
    public static function export(Cv $cv): array
    {
        return [
            '$schema' => 'https://raw.githubusercontent.com/jsonresume/resume-schema/v1.0.0/schema.json',
            'basics' => [
                'name' => $cv->user->name,
                'label' => (string) $cv->displayHeadline(),
                'email' => $cv->user->email,
                'url' => route('cv.show', $cv),
                'summary' => (string) $cv->profile,
                'location' => ['city' => (string) $cv->user->location],
                'profiles' => array_map(fn ($l) => ['network' => $l['label'], 'url' => $l['url']], Links::parse($cv->user->links)),
            ],
            'skills' => $cv->tags->map(fn ($t) => ['name' => $t->name])->values()->all(),
            'projects' => $cv->projects->map(fn ($p) => array_filter([
                'name' => $p->title, 'description' => $p->description, 'url' => $p->url,
            ]))->values()->all(),
            'meta' => [
                'canonical' => route('cv.show', $cv),
                'lastModified' => $cv->updated_at?->toIso8601String(),
                'vitafolio' => [
                    'keyLanguage' => (string) $cv->key_language,
                    'education' => (string) $cv->education,
                    'experience' => (string) $cv->experience,
                ],
            ],
        ];
    }

    /** maps a json resume onto cv fields; anything missing or malformed is skipped */
    public static function import(array $data): array
    {
        $str = fn ($v) => is_string($v) ? trim($v) : '';
        $list = fn ($v) => is_array($v) ? array_filter($v, 'is_array') : [];
        $basics = is_array($data['basics'] ?? null) ? $data['basics'] : [];

        $fields = [
            'headline' => Str::limit($str($basics['label'] ?? null), 120, ''),
            'profile' => Str::limit($str($basics['summary'] ?? null), 3000, ''),
            'location' => Str::limit(implode(', ', array_filter([
                $str($basics['location']['city'] ?? null), $str($basics['location']['countryCode'] ?? null),
            ])), 100, ''),
        ];

        $urls = array_merge([$str($basics['url'] ?? null)], array_map(fn ($p) => $str($p['url'] ?? null), $list($basics['profiles'] ?? null)));
        $fields['links'] = implode("\n", array_slice(array_unique(array_filter($urls)), 0, 10));

        $skills = [];
        foreach ($list($data['skills'] ?? null) as $skill) {
            $skills[] = $str($skill['name'] ?? null);
            foreach (is_array($skill['keywords'] ?? null) ? $skill['keywords'] : [] as $keyword) {
                $skills[] = $str($keyword);
            }
        }
        $fields['skills'] = implode(', ', array_slice(array_filter($skills), 0, 30));

        $dates = fn ($i) => trim($str($i['startDate'] ?? null).' to '.($str($i['endDate'] ?? null) ?: 'present'));

        $fields['experience'] = Str::limit(implode("\n\n", array_map(function ($i) use ($str, $dates) {
            $head = trim($str($i['position'] ?? null).', '.$str($i['name'] ?? ($i['company'] ?? null)), ', ');
            $bullets = array_map(fn ($h) => '- '.$str($h), is_array($i['highlights'] ?? null) ? $i['highlights'] : []);

            return trim($head.' ('.$dates($i).")\n".$str($i['summary'] ?? null)."\n".implode("\n", $bullets));
        }, $list($data['work'] ?? null))), 5000, '');

        $fields['education'] = Str::limit(implode("\n\n", array_map(function ($i) use ($str, $dates) {
            $course = trim($str($i['studyType'] ?? null).' '.$str($i['area'] ?? null));

            return trim($course.', '.$str($i['institution'] ?? null).' ('.$dates($i).')', ', ');
        }, $list($data['education'] ?? null))), 5000, '');

        return array_filter($fields, fn ($v) => $v !== '');
    }
}
