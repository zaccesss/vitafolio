<?php

namespace App\Support;

use App\Models\Cv;
use App\Models\Tag;
use Illuminate\Support\Str;

class Tags
{
    // common spellings share one slug, so JS, Javascript and JavaScript group together
    private const ALIASES = [
        'js' => 'javascript', 'java-script' => 'javascript', 'ts' => 'typescript',
        'c-sharp' => 'csharp', 'golang' => 'go', 'py' => 'python', 'postgres' => 'postgresql',
        'node' => 'nodejs', 'node-js' => 'nodejs', 'reactjs' => 'react', 'react-js' => 'react',
        'vuejs' => 'vue', 'vue-js' => 'vue', 'k8s' => 'kubernetes',
    ];

    // symbols that str::slug would strip, which would merge c, c++ and c# into one tag
    private const SYMBOLS = ['c++' => 'cpp', 'c#' => 'csharp', 'f#' => 'fsharp', '.net' => 'dotnet'];

    /** splits on commas, semicolons and new lines; returns slug => display name */
    public static function parse(?string $text): array
    {
        $tags = [];
        foreach (preg_split('/[,;\n]+/', (string) $text) ?: [] as $raw) {
            $name = Str::limit(trim($raw), 40, '');
            if ($name !== '') {
                $tags[self::slug($name)] ??= $name;
            }
        }

        return array_slice($tags, 0, 30, true);
    }

    public static function slug(string $name): string
    {
        $lower = Str::lower(trim($name));
        if (isset(self::SYMBOLS[$lower])) {
            return self::SYMBOLS[$lower];
        }
        $slug = Str::slug($lower);

        return self::ALIASES[$slug] ?? ($slug !== '' ? $slug : 'tag');
    }

    public static function sync(Cv $cv, array $tags): void
    {
        $ids = [];
        foreach ($tags as $slug => $name) {
            $ids[] = Tag::firstOrCreate(['slug' => $slug], ['name' => $name])->id;
        }
        $cv->tags()->sync($ids);
    }

    public static function toText(Cv $cv): string
    {
        return $cv->tags->pluck('name')->implode(', ');
    }
}
