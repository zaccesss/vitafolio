<?php

namespace Tests\Feature;

use App\Support\Ats\ResumeText;
use App\Support\Ats\UnreadableResume;
use App\Support\Images;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use ZipArchive;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    private function recordedAddress(array $headers): string
    {
        Route::get('/address-probe', fn (Request $request) => $request->ip());

        return $this->withHeaders($headers)->get('/address-probe')->assertOk()->getContent();
    }

    public function test_a_made_up_forwarded_address_is_not_believed(): void
    {
        $this->assertSame('203.0.113.9', $this->recordedAddress(['X-Forwarded-For' => '192.0.2.1, 203.0.113.9']));
    }

    public function test_the_address_cloudflare_reports_wins(): void
    {
        $this->assertSame('203.0.113.7', $this->recordedAddress([
            'CF-Connecting-IP' => '203.0.113.7',
            'X-Forwarded-For' => '192.0.2.1, 198.51.100.1',
        ]));
    }

    public function test_a_broken_forwarded_header_falls_back_to_the_connection(): void
    {
        $this->assertSame('127.0.0.1', $this->recordedAddress(['X-Forwarded-For' => 'not-an-address']));
    }

    public function test_changing_the_forwarded_header_does_not_dodge_a_rate_limit(): void
    {
        $status = [];
        foreach (range(1, 8) as $i) {
            $status[] = $this->withHeaders(['X-Forwarded-For' => "192.0.2.$i, 203.0.113.9"])->post('/cron')->getStatusCode();
        }

        $this->assertSame(429, end($status));
    }

    public function test_the_database_health_check_only_asks_the_database_once_in_a_while(): void
    {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get('/up/db')->assertOk()->assertJson(['status' => 'up']);
        $this->get('/up/db')->assertOk()->assertJson(['status' => 'up']);

        $this->assertSame(1, $queries);
    }

    public function test_a_word_file_that_expands_too_far_is_refused(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        // written in pieces, so the test itself never holds the whole part in memory
        $part = tempnam(sys_get_temp_dir(), 'part');
        $handle = fopen($part, 'w');
        foreach (range(1, 21) as $megabyte) {
            fwrite($handle, str_repeat('a', 1024 * 1024));
        }
        fclose($handle);
        $zip->addFile($part, 'word/document.xml');
        $zip->close();
        unlink($part);

        try {
            $this->expectException(UnreadableResume::class);
            ResumeText::fromDocx($path);
        } finally {
            unlink($path);
        }
    }

    public function test_an_image_with_too_many_pixels_is_refused(): void
    {
        // a png header claiming 20000 by 20000 pixels, the shape of a decompression bomb
        $png = "\x89PNG\r\n\x1a\n\0\0\0\rIHDR".pack('NN', 20000, 20000)."\x08\x02\0\0\0";
        $body = 'IHDR'.pack('NN', 20000, 20000)."\x08\x02\0\0\0";
        $png .= pack('N', crc32($body));
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, $png);

        try {
            $this->assertNull(Images::squareJpeg($path, 100));
        } finally {
            unlink($path);
        }
    }

    public function test_translations_cannot_add_html_the_english_does_not_have(): void
    {
        $tags = function (string $text): array {
            preg_match_all('/<[^>]+>/', $text, $found);
            sort($found[0]);

            return $found[0];
        };

        foreach (glob(lang_path('*.json')) as $file) {
            foreach (json_decode((string) file_get_contents($file), true) as $english => $translated) {
                $this->assertSame($tags((string) $english), $tags((string) $translated), basename($file).': '.$english);
            }
        }
    }

    public function test_php_settings_in_the_image_switch_off_what_the_site_does_not_use(): void
    {
        $settings = (string) file_get_contents(base_path('docker/php.ini'));

        foreach (['exec', 'shell_exec', 'passthru', 'system', 'popen'] as $function) {
            $this->assertMatchesRegularExpression('/^disable_functions\s*=.*\b'.$function.'\b/m', $settings);
        }
        // the pdf renderer runs through proc_open, so it has to stay available
        $this->assertDoesNotMatchRegularExpression('/^disable_functions\s*=.*\bproc_open\b/m', $settings);
        $this->assertMatchesRegularExpression('/^allow_url_fopen\s*=\s*Off/m', $settings);
    }
}
