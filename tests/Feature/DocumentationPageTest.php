<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentationPageTest extends TestCase
{
    public function test_every_contents_link_on_the_documentation_page_has_a_section(): void
    {
        $html = $this->get(route('docs'))->assertOk()->getContent();

        preg_match_all('/href="#([^"]+)"/', $html, $links);
        preg_match_all('/\sid="([^"]+)"/', $html, $ids);
        $this->assertNotEmpty($links[1]);
        $this->assertSame([], array_values(array_diff(array_unique($links[1]), $ids[1])));
    }

    public function test_changelog_headings_can_be_linked_to(): void
    {
        $this->get(route('changelog'))->assertOk()->assertSee('id="unreleased"', false);
    }
}
