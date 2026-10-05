<?php

namespace Tests\Unit;

use App\Support\Links;
use PHPUnit\Framework\TestCase;

class LinksTest extends TestCase
{
    public function test_known_sites_are_named_including_subdomains(): void
    {
        $links = Links::parse("github.com/someone\nhttps://uk.linkedin.com/in/someone\nscholar.google.com/citations?user=x\nmastodon.example.org/@me");

        $this->assertSame(['GitHub', 'LinkedIn', 'Google Scholar', 'Mastodon'], array_column($links, 'label'));
        $this->assertSame('https://github.com/someone', $links[0]['url']);
    }

    public function test_lookalikes_and_unsafe_schemes_are_not_trusted(): void
    {
        $links = Links::parse("github.com.example.net\njavascript:alert(1)\ndata:text/html,hi\nftp://example.com\nnot a link");

        $this->assertSame(['github.com.example.net'], array_column($links, 'label'));
        $this->assertSame('website', $links[0]['icon']);
    }

    public function test_at_most_ten_links_are_kept(): void
    {
        $this->assertCount(10, Links::parse(implode("\n", array_map(fn ($n) => "site$n.example.com", range(1, 15)))));
    }
}
