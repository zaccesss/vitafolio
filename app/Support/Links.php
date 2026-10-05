<?php

namespace App\Support;

class Links
{
    /**
     * known sites by domain: [label, icon]. a link matches its domain or any subdomain of it, so
     * uk.linkedin.com and gist.github.com are recognised too. the icon names a kind of site rather
     * than a brand logo, see the link-icon component
     */
    private const KNOWN = [
        'github.com' => ['GitHub', 'code'],
        'gitlab.com' => ['GitLab', 'code'],
        'codeberg.org' => ['Codeberg', 'code'],
        'bitbucket.org' => ['Bitbucket', 'code'],
        'codepen.io' => ['CodePen', 'code'],
        'linkedin.com' => ['LinkedIn', 'work'],
        'orcid.org' => ['ORCID', 'research'],
        'scholar.google.com' => ['Google Scholar', 'research'],
        'researchgate.net' => ['ResearchGate', 'research'],
        'stackoverflow.com' => ['Stack Overflow', 'chat'],
        'leetcode.com' => ['LeetCode', 'trophy'],
        'codeforces.com' => ['Codeforces', 'trophy'],
        'hackerrank.com' => ['HackerRank', 'trophy'],
        'kaggle.com' => ['Kaggle', 'trophy'],
        'huggingface.co' => ['Hugging Face', 'package'],
        'npmjs.com' => ['npm', 'package'],
        'pypi.org' => ['PyPI', 'package'],
        'behance.net' => ['Behance', 'image'],
        'dribbble.com' => ['Dribbble', 'image'],
        'instagram.com' => ['Instagram', 'image'],
        'figma.com' => ['Figma', 'image'],
        'medium.com' => ['Medium', 'writing'],
        'substack.com' => ['Substack', 'writing'],
        'dev.to' => ['DEV', 'writing'],
        'hashnode.dev' => ['Hashnode', 'writing'],
        'youtube.com' => ['YouTube', 'video'],
        'youtu.be' => ['YouTube', 'video'],
        'vimeo.com' => ['Vimeo', 'video'],
        'x.com' => ['X', 'social'],
        'twitter.com' => ['X', 'social'],
        'bsky.app' => ['Bluesky', 'social'],
        'threads.net' => ['Threads', 'social'],
        'mastodon.social' => ['Mastodon', 'social'],
        'mastodon.online' => ['Mastodon', 'social'],
        'fosstodon.org' => ['Mastodon', 'social'],
        'hachyderm.io' => ['Mastodon', 'social'],
    ];

    /** only http and https links survive, labelled from the real host so a url cannot pose as another site */
    public static function parse(?string $text): array
    {
        $links = [];
        foreach (preg_split('/[\s|,]+/', (string) $text) ?: [] as $raw) {
            if ($raw === '') {
                continue;
            }
            $url = preg_match('#^https?://#i', $raw) ? $raw : 'https://'.$raw;
            $parts = parse_url($url);
            $scheme = strtolower($parts['scheme'] ?? '');
            $host = strtolower($parts['host'] ?? '');
            if (! in_array($scheme, ['http', 'https'], true) || ! str_contains($host, '.') || filter_var($url, FILTER_VALIDATE_URL) === false) {
                continue;
            }
            $bare = preg_replace('/^www\./', '', $host);
            [$label, $icon] = self::identify($bare);
            $links[] = ['url' => $url, 'label' => $label, 'icon' => $icon, 'host' => $bare];
        }

        return array_slice($links, 0, 10);
    }

    /** matches the host or a parent domain, so a lookalike such as github.com.example.net never passes */
    private static function identify(string $host): array
    {
        for ($candidate = $host; str_contains($candidate, '.'); $candidate = substr($candidate, strpos($candidate, '.') + 1)) {
            if (isset(self::KNOWN[$candidate])) {
                return self::KNOWN[$candidate];
            }
        }
        // any other mastodon server still gets its name when the server says what it is
        if (preg_match('/(^|\.)mastodon\./', $host)) {
            return ['Mastodon', 'social'];
        }

        return [$host, 'website'];
    }
}
