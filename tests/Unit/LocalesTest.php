<?php

namespace Tests\Unit;

use App\Support\Locales;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LocalesTest extends TestCase
{
    public static function headers(): array
    {
        return [
            'spanish' => ['es-ES,es;q=0.9,en;q=0.8', 'es'],
            'french from canada' => ['fr-CA', 'fr'],
            'arabic' => ['ar-SA,ar;q=0.9', 'ar'],
            'urdu' => ['ur-PK', 'ur'],
            'plain chinese' => ['zh', 'zh_CN'],
            'mainland chinese' => ['zh-CN,zh;q=0.9', 'zh_CN'],
            'simplified script' => ['zh-Hans', 'zh_CN'],
            'simplified script with a region' => ['zh-Hans-CN', 'zh_CN'],
            'traditional chinese is not simplified' => ['zh-TW,zh-Hant;q=0.9', 'en'],
            'portuguese' => ['pt', 'pt_BR'],
            'brazilian portuguese' => ['pt-BR', 'pt_BR'],
            'european portuguese' => ['pt-PT', 'pt_BR'],
            'british english' => ['en-GB,en;q=0.9', 'en'],
            'quality decides, not order' => ['de;q=0.9, fr;q=0.4, ar;q=0.8', 'ar'],
            'equal quality keeps the browser order' => ['ur, ar', 'ur'],
            'an unsupported language falls through' => ['de-DE,de;q=0.9,es;q=0.5', 'es'],
            'a refused language is skipped' => ['fr;q=0, es', 'es'],
            'nothing supported' => ['de, ja', 'en'],
            'empty' => ['', 'en'],
            'wildcard' => ['*', 'en'],
        ];
    }

    #[DataProvider('headers')]
    public function test_the_browser_language_is_matched(string $header, string $expected): void
    {
        $this->assertSame($expected, Locales::fromHeader($header));
    }

    public function test_only_arabic_and_urdu_read_right_to_left(): void
    {
        $rtl = array_keys(array_filter(Locales::ALL, fn ($language) => $language['dir'] === 'rtl'));
        $this->assertSame(['ar', 'ur'], $rtl);
        $this->assertSame('pt-BR', Locales::ALL['pt_BR']['html']);
        $this->assertSame('zh-CN', Locales::ALL['zh_CN']['html']);
    }
}
