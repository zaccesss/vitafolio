<?php

namespace App\Support;

use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;

/**
 * renders the repository's own markdown documents (documentation and changelog) for the site
 */
class Markdown
{
    /**
     * headings get the same ids github gives them, so a contents list written as [Title](#title)
     * jumps to its section on the site too
     */
    public static function document(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => ['apply_id_to_heading' => true, 'insert' => 'none', 'id_prefix' => '', 'fragment_prefix' => ''],
        ], [new HeadingPermalinkExtension]);
    }
}
