<?php

namespace Tests\Unit;

use App\Support\Latex;
use PHPUnit\Framework\TestCase;

class LatexTemplatesTest extends TestCase
{
    /** the core download holds these; anything else would make a starter template need the larger collections */
    private const CORE_PACKAGES = ['geometry', 'fontenc', 'lmodern', 'hyperref', 'color'];

    public function test_every_template_has_a_file_using_only_core_packages(): void
    {
        foreach (array_keys(Latex::TEMPLATES) as $template) {
            $path = dirname(__DIR__, 2).'/resources/latex/'.$template.'.tex';
            $this->assertFileExists($path);
            $source = (string) file_get_contents($path);

            preg_match_all('/\\\\usepackage(?:\[[^\]]*\])?\{([^}]+)\}/', $source, $matches);
            foreach ($matches[1] as $package) {
                $this->assertContains($package, self::CORE_PACKAGES, "$template uses $package, which is outside the core packages");
            }
            $this->assertStringContainsString('{lmodern}', $source, "$template needs lmodern so list bullets render");
            foreach (['{{NAME}}', '{{EXPERIENCE}}', '{{EDUCATION}}'] as $placeholder) {
                $this->assertStringContainsString($placeholder, $source, "$template is missing $placeholder");
            }
        }
    }
}
